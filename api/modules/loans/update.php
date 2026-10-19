<?php

require_once __DIR__ . '/helpers.php';

// Muc C, D, E, F: Chuyen trang thai phieu muon (cancelled, borrowed, rejected, returned)
// co Transaction va Row Lock theo thu tu chuan: users -> books -> loans.
$currentUser = requireAuth();
$loanId = isset($targetLoanId) ? (int) $targetLoanId : 0;
if ($loanId < 1) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy phiếu mượn.');
}

$body = readJsonBody();
$fields = [];

// Chi cho phep status va rejection_reason trong body PATCH /loans/{id}
$allowedKeys = ['status', 'rejection_reason'];
foreach (array_keys($body) as $key) {
    if (!in_array($key, $allowedKeys, true)) {
        $fields[$key] = 'Trường này không được phép chỉnh sửa trực tiếp.';
    }
}

$targetStatus = '';
if (!isset($body['status']) || !is_string($body['status'])) {
    $fields['status'] = 'Vui lòng cung cấp trạng thái chuyển tiếp hợp lệ.';
} else {
    $targetStatus = strtolower(trim($body['status']));
    $allowedTargetStatuses = ['cancelled', 'borrowed', 'rejected', 'returned'];
    if (!in_array($targetStatus, $allowedTargetStatuses, true)) {
        $fields['status'] = 'Trạng thái chỉ chấp nhận cancelled, borrowed, rejected hoặc returned.';
    }
}

$rejectionReason = null;
if ($targetStatus === 'rejected') {
    if (!isset($body['rejection_reason']) || !is_string($body['rejection_reason'])) {
        $fields['rejection_reason'] = 'Vui lòng nhập lý do từ chối yêu cầu mượn.';
    } else {
        $rejectionReason = trim($body['rejection_reason']);
        $len = mb_strlen($rejectionReason, 'UTF-8');
        if ($len < 1 || $len > 500) {
            $fields['rejection_reason'] = 'Lý do từ chối phải từ 1 đến 500 ký tự.';
        }
    }
}

if (!empty($fields)) {
    sendError(422, 'VALIDATION_ERROR', 'Dữ liệu cập nhật phiếu mượn không hợp lệ.', $fields);
}

// Kiem tra quyen theo trang thai dich
if ($targetStatus === 'cancelled') {
    if ($currentUser['role'] !== 'member') {
        sendError(403, 'FORBIDDEN', 'Quản trị viên vui lòng dùng thao tác Từ chối (rejected) thay vì hủy hộ độc giả.');
    }
} else {
    // borrowed, rejected, returned chi danh cho Admin
    if ($currentUser['role'] !== 'admin') {
        sendError(403, 'FORBIDDEN', 'Chức năng duyệt, từ chối và nhận trả sách chỉ dành cho quản trị viên.');
    }
}

$pdo = getConnection();
$loanConfig = getLoanConfig();
$maxActiveLoans = $loanConfig['max_active_loans'];
$loanDurationDays = $loanConfig['loan_duration_days'];

// Doc truoc user_id va book_id de khoa hang theo dung thu tu: users -> books -> loans
$preStmt = $pdo->prepare('SELECT id, user_id, book_id FROM loans WHERE id = :id');
$preStmt->execute(['id' => $loanId]);
$preLoan = $preStmt->fetch();

if (!$preLoan) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy phiếu mượn.');
}

// Neu Member co tinh truy cap phieu muon cua nguoi khac -> tra 404 (Test A06)
if ($currentUser['role'] === 'member' && (int) $preLoan['user_id'] !== (int) $currentUser['id']) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy phiếu mượn.');
}

$ownerUserId = (int) $preLoan['user_id'];
$targetBookId = (int) $preLoan['book_id'];
$message = 'Cập nhật phiếu mượn thành công.';

try {
    $pdo->beginTransaction();

    // 1. Khoa dong users cua doc gia
    $uStmt = $pdo->prepare('SELECT id, full_name, email, role, is_active FROM users WHERE id = :id FOR UPDATE');
    $uStmt->execute(['id' => $ownerUserId]);
    $memberRow = $uStmt->fetch();

    // 2. Khoa dong books
    $bStmt = $pdo->prepare(
        'SELECT id, title, total_copies, available_copies, is_active FROM books WHERE id = :id FOR UPDATE'
    );
    $bStmt->execute(['id' => $targetBookId]);
    $bookRow = $bStmt->fetch();

    // 3. Khoa dong loans va xac minh trang thai hien tai sau khi khoa
    $lStmt = $pdo->prepare(
        'SELECT id, user_id, book_id, status, requested_at, borrowed_at, due_date, returned_at
         FROM loans
         WHERE id = :id
         FOR UPDATE'
    );
    $lStmt->execute(['id' => $loanId]);
    $loanRow = $lStmt->fetch();

    if (!$loanRow || !$memberRow || !$bookRow) {
        $pdo->rollBack();
        sendError(404, 'NOT_FOUND', 'Không tìm thấy dữ liệu phiếu mượn liên quan.');
    }

    $currentStatus = $loanRow['status'];

    // --- TRUONG HOP 1: MEMBER HUY YEU CAU PENDING (cancelled) ---
    if ($targetStatus === 'cancelled') {
        if ($currentStatus !== 'pending') {
            $pdo->rollBack();
            sendError(
                409,
                'INVALID_STATE_TRANSITION',
                'Chỉ có thể hủy yêu cầu khi phiếu mượn đang ở trạng thái chờ xử lý (pending).'
            );
        }

        $upStmt = $pdo->prepare("UPDATE loans SET status = 'cancelled' WHERE id = :id");
        $upStmt->execute(['id' => $loanId]);
        $message = 'Đã hủy yêu cầu mượn sách thành công.';
    }

    // --- TRUONG HOP 2: ADMIN XAC NHAN GIAO SACH (borrowed) ---
    elseif ($targetStatus === 'borrowed') {
        if ($currentStatus !== 'pending') {
            $pdo->rollBack();
            sendError(
                409,
                'INVALID_STATE_TRANSITION',
                'Chỉ phiếu mượn đang chờ xử lý (pending) mới có thể xác nhận giao sách.'
            );
        }

        if ((int) $memberRow['is_active'] !== 1) {
            $pdo->rollBack();
            sendError(409, 'MEMBER_INACTIVE', 'Tài khoản độc giả hiện đang bị khóa, không thể giao sách.');
        }

        if ((int) $bookRow['is_active'] !== 1) {
            $pdo->rollBack();
            sendError(409, 'BOOK_INACTIVE', 'Đầu sách này hiện đã ngừng cho mượn.');
        }

        // Kiem tra lai gioi han 3 luot dang muon trong Transaction
        $cntStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM loans WHERE user_id = :uid AND status = 'borrowed'"
        );
        $cntStmt->execute(['uid' => $ownerUserId]);
        $currentBorrowedCount = (int) $cntStmt->fetchColumn();

        if ($currentBorrowedCount >= $maxActiveLoans) {
            $pdo->rollBack();
            sendError(
                409,
                'BORROW_LIMIT_REACHED',
                'Độc giả đã mượn đủ tối đa ' . $maxActiveLoans . ' cuốn sách, không thể giao thêm.'
            );
        }

        // Kiem tra lai ton kho available_copies > 0 trong Transaction
        if ((int) $bookRow['available_copies'] < 1) {
            $pdo->rollBack();
            sendError(
                409,
                'OUT_OF_STOCK',
                'Đầu sách này đã hết bản có sẵn trong kho. Yêu cầu vẫn giữ chờ xử lý để xem xét hoặc từ chối.'
            );
        }

        $borrowedAt = date('Y-m-d H:i:s');
        $dueDate = date('Y-m-d', strtotime('+' . $loanDurationDays . ' days'));

        $upLoanStmt = $pdo->prepare(
            "UPDATE loans
             SET status = 'borrowed',
                 borrowed_at = :borrowed_at,
                 due_date = :due_date,
                 approved_by = :approved_by
             WHERE id = :id"
        );
        $upLoanStmt->execute([
            'borrowed_at' => $borrowedAt,
            'due_date' => $dueDate,
            'approved_by' => (int) $currentUser['id'],
            'id' => $loanId
        ]);

        $upBookStmt = $pdo->prepare(
            'UPDATE books
             SET available_copies = available_copies - 1,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :book_id'
        );
        $upBookStmt->execute(['book_id' => $targetBookId]);

        $message = 'Đã xác nhận giao sách cho độc giả thành công (Hạn trả: ' . $dueDate . ').';
    }

    // --- TRUONG HOP 3: ADMIN TU CHOI YEU CAU (rejected) ---
    elseif ($targetStatus === 'rejected') {
        if ($currentStatus !== 'pending') {
            $pdo->rollBack();
            sendError(
                409,
                'INVALID_STATE_TRANSITION',
                'Chỉ phiếu mượn đang chờ xử lý (pending) mới có thể từ chối.'
            );
        }

        $upLoanStmt = $pdo->prepare(
            "UPDATE loans
             SET status = 'rejected',
                 approved_by = :approved_by,
                 rejection_reason = :rejection_reason
             WHERE id = :id"
        );
        $upLoanStmt->execute([
            'approved_by' => (int) $currentUser['id'],
            'rejection_reason' => $rejectionReason,
            'id' => $loanId
        ]);

        $message = 'Đã từ chối yêu cầu mượn sách.';
    }

    // --- TRUONG HOP 4: ADMIN XAC NHAN NHAN TRA SACH (returned) ---
    elseif ($targetStatus === 'returned') {
        // Test A16: Tra lap hoac khong phai borrowed phai tra 409, khong tang ton
        if ($currentStatus !== 'borrowed') {
            $pdo->rollBack();
            sendError(
                409,
                'INVALID_STATE_TRANSITION',
                'Chỉ phiếu mượn đang ở trạng thái đang mượn (borrowed) mới có thể xác nhận trả sách.'
            );
        }

        // Kiem tra available_copies khong vuot total_copies
        if ((int) $bookRow['available_copies'] >= (int) $bookRow['total_copies']) {
            $pdo->rollBack();
            sendError(
                409,
                'STOCK_OVERFLOW',
                'Số bản khả dụng đã bằng tổng số bản của đầu sách, không thể cộng thêm.'
            );
        }

        $returnedAt = date('Y-m-d H:i:s');

        $upLoanStmt = $pdo->prepare(
            "UPDATE loans
             SET status = 'returned',
                 returned_at = :returned_at,
                 returned_by = :returned_by
             WHERE id = :id"
        );
        $upLoanStmt->execute([
            'returned_at' => $returnedAt,
            'returned_by' => (int) $currentUser['id'],
            'id' => $loanId
        ]);

        $upBookStmt = $pdo->prepare(
            'UPDATE books
             SET available_copies = available_copies + 1,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :book_id'
        );
        $upBookStmt->execute(['book_id' => $targetBookId]);

        $message = 'Đã xác nhận nhận lại sách thành công! Tồn kho đã tăng 1 bản.';
    }

    $updatedLoan = fetchLoanDetailById($pdo, $loanId);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

sendJson(200, [
    'success' => true,
    'data' => formatLoanRow($updatedLoan, $currentUser['role']),
    'message' => $message
]);
