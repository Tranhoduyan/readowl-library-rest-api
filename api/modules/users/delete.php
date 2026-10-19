<?php

// Chuc nang Xoa tai khoan doc gia (Admin).
// Chi xoa Member chua co bat ky phieu muon nao. Giu nguyen toan bo lich su.
$currentAdmin = requireAdmin();

$userId = isset($targetUserId) ? (int) $targetUserId : 0;
if ($userId < 1) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy độc giả.');
}

$pdo = getConnection();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, role, is_active
         FROM users
         WHERE id = :id
         FOR UPDATE'
    );
    $stmt->execute(['id' => $userId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        $pdo->rollBack();
        sendError(404, 'NOT_FOUND', 'Không tìm thấy độc giả.');
    }

    if ($targetUser['role'] !== 'member' || (int) $targetUser['id'] === (int) $currentAdmin['id']) {
        $pdo->rollBack();
        sendError(403, 'FORBIDDEN', 'Không được phép xóa tài khoản quản trị viên.');
    }

    // Kiem tra doc gia co dang muon sach chua tra hay khong
    $borrowedStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM loans WHERE user_id = :user_id AND status = 'borrowed'"
    );
    $borrowedStmt->execute(['user_id' => $userId]);
    $borrowedCount = (int) $borrowedStmt->fetchColumn();

    if ($borrowedCount > 0) {
        $pdo->rollBack();
        sendError(
            409,
            'USER_HAS_ACTIVE_LOANS',
            'Độc giả đang giữ ' . $borrowedCount . ' cuốn sách chưa trả. Vui lòng nhận trả sách trước khi xóa hoặc dùng chức năng Khóa tài khoản.'
        );
    }

    // Khong xoa phieu pending/returned/rejected/cancelled de xoa duoc tai khoan.
    // Bao ve ca truong hop tai khoan tung la nguoi duyet hoac nhan tra sach.
    $historyStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM loans
         WHERE user_id = :user_id
            OR approved_by = :approved_by
            OR returned_by = :returned_by'
    );
    $historyStmt->execute([
        'user_id' => $userId,
        'approved_by' => $userId,
        'returned_by' => $userId
    ]);

    if ((int) $historyStmt->fetchColumn() > 0) {
        $pdo->rollBack();
        sendError(
            409,
            'USER_HAS_LOAN_HISTORY',
            'Không thể xóa tài khoản đã có phiếu mượn hoặc lịch sử xử lý sách. Vui lòng dùng chức năng Khóa tài khoản để giữ nguyên lịch sử.'
        );
    }

    // Xoa tai khoan doc gia
    $delUserStmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'member'");
    $delUserStmt->execute(['id' => $userId]);

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
        'deleted_id' => $userId
    ],
    'message' => 'Đã xóa tài khoản độc giả "' . $targetUser['full_name'] . '" thành công.'
]);
