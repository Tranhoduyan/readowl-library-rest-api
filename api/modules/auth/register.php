<?php

$data = readJsonBody();
$fullName = '';
$email = '';
$password = '';

if (isset($data['full_name']) && is_string($data['full_name'])) {
    $fullName = trim($data['full_name']);
}

if (isset($data['email']) && is_string($data['email'])) {
    $email = strtolower(trim($data['email']));
}

if (isset($data['password']) && is_string($data['password'])) {
    // Giu nguyen mat khau; khong trim.
    $password = $data['password'];
}

$fields = [];

if (mb_strlen($fullName, 'UTF-8') < 2 || mb_strlen($fullName, 'UTF-8') > 100) {
    $fields['full_name'] = 'Họ tên phải có từ 2 đến 100 ký tự.';
}

if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fields['email'] = 'Email không hợp lệ hoặc quá dài.';
}

if (mb_strlen($password, 'UTF-8') < 8) {
    $fields['password'] = 'Mật khẩu phải có ít nhất 8 ký tự.';
} elseif (strlen($password) > 72) {
    // PHP 8.2 PASSWORD_DEFAULT dung bcrypt, gioi han 72 byte.
    $fields['password'] = 'Mật khẩu quá dài. Vui lòng dùng mật khẩu ngắn hơn.';
} elseif (strpos($password, "\0") !== false) {
    $fields['password'] = 'Mật khẩu chứa ký tự không hợp lệ.';
}

if (count($fields) > 0) {
    sendError(422, 'VALIDATION_ERROR', 'Vui lòng kiểm tra lại thông tin.', $fields);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$pdo = getConnection();

try {
    $statement = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, role, is_active)
         VALUES (:full_name, :email, :password_hash, 'member', 1)"
    );

    $statement->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => $passwordHash
    ]);
} catch (PDOException $e) {
    // UNIQUE email van bao ve du lieu khi hai request den cung luc.
    if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
        sendError(409, 'EMAIL_EXISTS', 'Email này đã được đăng ký.', [
            'email' => 'Vui lòng sử dụng email khác.'
        ]);
    }

    throw $e;
}

$id = (int) $pdo->lastInsertId();
header('Location: /api/v1/users/' . $id);

// Chi tra thong tin an toan, khong tra password/password_hash.
sendJson(201, [
    'success' => true,
    'data' => [
        'id' => $id,
        'full_name' => $fullName,
        'email' => $email,
        'role' => 'member',
        'is_active' => 1
    ],
    'message' => 'Đăng ký tài khoản thành công!'
]);
