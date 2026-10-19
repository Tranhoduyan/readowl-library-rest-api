<?php

// DG04: Khoa / mo khoa tai khoan doc gia (chi chinh is_active cua Member).
// Khong xoa lich su, khong lam thay doi so luong sach, khong cho doi role hoac khoa Admin.
$currentAdmin = requireAdmin();

$userId = isset($targetUserId) ? (int) $targetUserId : 0;
if ($userId < 1) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy độc giả.');
}

$data = readJsonBody();
$fields = [];

// Chi cho phep truong is_active trong body PATCH /users/{id}
$allowedKeys = ['is_active'];
foreach (array_keys($data) as $key) {
    if (!in_array($key, $allowedKeys, true)) {
        $fields[$key] = 'Trường này không được phép cập nhật qua API quản lý độc giả.';
    }
}

$newActive = null;
if (!array_key_exists('is_active', $data)) {
    $fields['is_active'] = 'Vui lòng cung cấp trạng thái is_active (0 hoặc 1).';
} else {
    $val = $data['is_active'];
    if ($val === 1 || $val === true) {
        $newActive = 1;
    } elseif ($val === 0 || $val === false) {
        $newActive = 0;
    } else {
        $fields['is_active'] = 'Trạng thái is_active chỉ chấp nhận 0 (khóa) hoặc 1 (hoạt động).';
    }
}

if (!empty($fields)) {
    sendError(422, 'VALIDATION_ERROR', 'Dữ liệu cập nhật không hợp lệ.', $fields);
}

$pdo = getConnection();

try {
    $pdo->beginTransaction();

    // Khoa hang user theo quy uoc xu ly dong thoi (SELECT ... FOR UPDATE)
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, role, is_active, created_at
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

    // Khong cho Admin khoa chinh minh hoac chinh sua tai khoan Admin khac
    if ($targetUser['role'] !== 'member' || (int) $targetUser['id'] === (int) $currentAdmin['id']) {
        $pdo->rollBack();
        sendError(403, 'FORBIDDEN', 'Không được phép khóa hoặc thay đổi trạng thái của tài khoản quản trị viên.');
    }

    if ((int) $targetUser['is_active'] !== $newActive) {
        $updateStmt = $pdo->prepare(
            "UPDATE users SET is_active = :is_active WHERE id = :id AND role = 'member'"
        );
        $updateStmt->execute([
            'is_active' => $newActive,
            'id' => $userId
        ]);
        $targetUser['is_active'] = $newActive;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

$actionMessage = $newActive === 1
    ? 'Đã mở khóa tài khoản độc giả thành công.'
    : 'Đã khóa tài khoản độc giả thành công.';

sendJson(200, [
    'success' => true,
    'data' => publicUser($targetUser),
    'message' => $actionMessage
]);
