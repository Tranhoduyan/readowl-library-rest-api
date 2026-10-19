<?php

$data = readJsonBody();
$email = '';
$password = '';
$fields = [];

if (isset($data['email']) && is_string($data['email'])) {
    $email = strtolower(trim($data['email']));
}
if (isset($data['password']) && is_string($data['password'])) {
    $password = $data['password'];
}

if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fields['email'] = 'Vui lòng nhập email hợp lệ.';
}
if ($password === '' || strlen($password) > 72 || strpos($password, "\0") !== false) {
    $fields['password'] = 'Vui lòng nhập mật khẩu hợp lệ, tối đa 72 byte.';
}
if ($fields) {
    sendError(422, 'VALIDATION_ERROR', 'Vui lòng kiểm tra thông tin đăng nhập.', $fields);
}

$pdo = getConnection();
$statement = $pdo->prepare(
    'SELECT id, full_name, email, password_hash, role, is_active, created_at
     FROM users WHERE email = :email'
);
$statement->execute(['email' => $email]);
$user = $statement->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    authError('INVALID_CREDENTIALS', 'Email hoặc mật khẩu không đúng.');
}
if ((int) $user['is_active'] !== 1) {
    sendError(403, 'ACCOUNT_DISABLED', 'Tài khoản đã bị vô hiệu hóa.');
}

$token = createAccessToken($user['id']);
$token['user'] = publicUser($user);

sendJson(200, [
    'success' => true,
    'data' => $token,
    'message' => 'Đăng nhập thành công.'
]);

