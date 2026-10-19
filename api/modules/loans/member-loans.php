<?php

// Nghiep vu Muon - Xem - Huy yeu cau muon sach phuc vu giao dien Thu vien Doc gia & Admin.
$currentUser = requireAuth();
$pdo = getConnection();

if ($method === 'GET') {
    $statusFilter = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';
    $allowedStatuses = ['pending', 'borrowed', 'returned', 'rejected', 'cancelled'];
    if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) {
        sendError(422, 'VALIDATION_ERROR', 'Trạng thái lọc không hợp lệ.', [
            'status' => 'Trạng thái không hợp lệ.'
        ]);
    }

    $where = [];
    $params = [];

    if ($currentUser['role'] === 'member') {
        if (isset($_GET['user_id']) && (int) $_GET['user_id'] !== (int) $currentUser['id']) {
            sendError(403, 'FORBIDDEN', 'Độc giả chỉ được xem danh sách phiếu mượn của chính mình.');
        }
        $where[] = 'l.user_id = :user_id';
        $params['user_id'] = (int) $currentUser['id'];
    } else {
        if (isset($_GET['user_id']) && ctype_digit((string) $_GET['user_id'])) {
            $where[] = 'l.user_id = :user_id';
            $params['user_id'] = (int) $_GET['user_id'];
        }
    }

    if ($statusFilter !== '') {
        $where[] = 'l.status = :status';
        $params['status'] = $statusFilter;
    }

    $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "
        SELECT
            l.id,
            l.user_id,
            u.full_name AS user_name,
            u.email AS user_email,
            l.book_id,
            b.title AS book_title,
            b.author AS book_author,
            b.cover_path AS book_cover,
            c.name AS category_name,
            l.status,
            l.requested_at,
            l.borrowed_at,
            l.due_date,
            l.returned_at,
            l.rejection_reason,
            CASE
                WHEN l.status = 'borrowed' AND l.due_date IS NOT NULL AND l.due_date < CURDATE() THEN 1
                ELSE 0
            END AS is_overdue
        FROM loans l
        INNER JOIN users u ON u.id = l.user_id
        INNER JOIN books b ON b.id = l.book_id
        INNER JOIN categories c ON c.id = b.category_id
        $whereSql
        ORDER BY l.requested_at DESC, l.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['user_id'] = (int) $r['user_id'];
        $r['book_id'] = (int) $r['book_id'];
        $r['is_overdue'] = (int) $r['is_overdue'] === 1;
    }
    unset($r);

    sendJson(200, [
        'success' => true,
        'data' => $rows,
        'message' => 'Lấy danh sách phiếu mượn thành công.'
    ]);
}

if ($method === 'POST') {
    if ($currentUser['role'] !== 'member') {
        sendError(403, 'FORBIDDEN', 'Chỉ tài khoản độc giả mới được gửi yêu cầu mượn sách.');
    }

    $body = readJsonBody();
    $bookId = isset($body['book_id']) && is_int($body['book_id']) ? $body['book_id'] : 0;
    if ($bookId < 1 && isset($body['book_id']) && ctype_digit((string) $body['book_id'])) {
        $bookId = (int) $body['book_id'];
    }

    if ($bookId < 1) {
        sendError(422, 'VALIDATION_ERROR', 'Vui lòng chọn đầu sách hợp lệ.', [
            'book_id' => 'Mã sách (book_id) phải là số nguyên dương.'
        ]);
    }

    try {
        $pdo->beginTransaction();

        // 1. Khoa dong users cua doc gia de tuan tu hoa yeu cau
        $uStmt = $pdo->prepare('SELECT id, is_active FROM users WHERE id = :id FOR UPDATE');
        $uStmt->execute(['id' => $currentUser['id']]);
        $uRow = $uStmt->fetch();
        if (!$uRow || (int) $uRow['is_active'] !== 1) {
            $pdo->rollBack();
            sendError(403, 'ACCOUNT_DISABLED', 'Tài khoản không còn hoạt động.');
        }

        // 2. Khoa dong books
        $bStmt = $pdo->prepare(
            'SELECT id, title, available_copies, is_active FROM books WHERE id = :id FOR UPDATE'
        );
        $bStmt->execute(['id' => $bookId]);
        $book = $bStmt->fetch();

        if (!$book || (int) $book['is_active'] !== 1) {
            $pdo->rollBack();
            sendError(404, 'BOOK_NOT_FOUND', 'Đầu sách không tồn tại hoặc đã ngừng cho mượn.');
        }

        if ((int) $book['available_copies'] < 1) {
            $pdo->rollBack();
            sendError(409, 'OUT_OF_STOCK', 'Đầu sách này hiện đã hết bản có sẵn để mượn.');
        }

        // 3. Kiem tra gioi han 3 cuon dang muon
        $activeStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM loans WHERE user_id = :uid AND status = 'borrowed'"
        );
        $activeStmt->execute(['uid' => $currentUser['id']]);
        if ((int) $activeStmt->fetchColumn() >= 3) {
            $pdo->rollBack();
            sendError(409, 'BORROW_LIMIT_REACHED', 'Bạn đang mượn tối đa 3 cuốn sách. Vui lòng trả sách trước khi mượn thêm.');
        }

        // 4. Kiem tra trung yeu cau pending/borrowed cho cung dau sach
        $dupStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM loans
             WHERE user_id = :uid AND book_id = :bid AND status IN ('pending', 'borrowed')"
        );
        $dupStmt->execute([
            'uid' => $currentUser['id'],
            'bid' => $bookId
        ]);
        if ((int) $dupStmt->fetchColumn() > 0) {
            $pdo->rollBack();
            sendError(409, 'DUPLICATE_LOAN', 'Bạn đã có yêu cầu đang chờ duyệt hoặc đang mượn cuốn sách này.');
        }

        $insStmt = $pdo->prepare(
            "INSERT INTO loans (user_id, book_id, status) VALUES (:uid, :bid, 'pending')"
        );
        $insStmt->execute([
            'uid' => $currentUser['id'],
            'bid' => $bookId
        ]);
        $loanId = (int) $pdo->lastInsertId();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    header('Location: /api/v1/loans/' . $loanId);
    sendJson(201, [
        'success' => true,
        'data' => [
            'id' => $loanId,
            'user_id' => (int) $currentUser['id'],
            'book_id' => $bookId,
            'status' => 'pending'
        ],
        'message' => 'Đã gửi yêu cầu mượn sách thành công! Vui lòng chờ thủ thư xác nhận.'
    ]);
}

if ($method === 'PATCH' && isset($targetLoanId)) {
    $body = readJsonBody();
    $targetStatus = isset($body['status']) && is_string($body['status']) ? trim($body['status']) : '';

    if ($targetStatus !== 'cancelled') {
        sendError(422, 'VALIDATION_ERROR', 'Trạng thái cập nhật không hợp lệ.', [
            'status' => 'Độc giả chỉ được phép hủy yêu cầu đang chờ (cancelled).'
        ]);
    }

    try {
        $pdo->beginTransaction();

        $lStmt = $pdo->prepare('SELECT id, user_id, status FROM loans WHERE id = :id FOR UPDATE');
        $lStmt->execute(['id' => (int) $targetLoanId]);
        $loan = $lStmt->fetch();

        if (!$loan || (int) $loan['user_id'] !== (int) $currentUser['id']) {
            $pdo->rollBack();
            sendError(404, 'NOT_FOUND', 'Không tìm thấy phiếu mượn.');
        }

        if ($loan['status'] !== 'pending') {
            $pdo->rollBack();
            sendError(409, 'INVALID_STATE_TRANSITION', 'Chỉ có thể hủy yêu cầu khi đang ở trạng thái chờ xử lý (pending).');
        }

        $upStmt = $pdo->prepare("UPDATE loans SET status = 'cancelled' WHERE id = :id");
        $upStmt->execute(['id' => (int) $targetLoanId]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    sendJson(200, [
        'success' => true,
        'data' => [
            'id' => (int) $targetLoanId,
            'status' => 'cancelled'
        ],
        'message' => 'Đã hủy yêu cầu mượn sách.'
    ]);
}
