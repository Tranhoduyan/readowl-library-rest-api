<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

require_once __DIR__ . '/../../vendor/autoload.php';

function getAuthConfig()
{
    $config = require __DIR__ . '/../../config/app.php';
    $secretFile = __DIR__ . '/../../config/jwt-secret.php';

    if (!is_file($secretFile)) {
        throw new RuntimeException('Chua tao khoa JWT. Hay chay php scripts/init-auth.php.');
    }

    $secret = require $secretFile;
    if (!is_string($secret) || strlen($secret) < 64) {
        throw new RuntimeException('Khoa JWT khong hop le.');
    }

    $config['jwt_secret'] = $secret;
    return $config;
}

function publicUser($user)
{
    return [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'is_active' => (int) $user['is_active'],
        'created_at' => $user['created_at']
    ];
}

function createAccessToken($userId)
{
    $config = getAuthConfig();
    $now = time();

    $payload = [
        'iss' => $config['jwt_issuer'],
        'aud' => $config['jwt_audience'],
        'sub' => (string) $userId,
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + $config['jwt_ttl']
    ];

    return [
        'access_token' => JWT::encode($payload, $config['jwt_secret'], 'HS256'),
        'token_type' => 'Bearer',
        'expires_in' => $config['jwt_ttl']
    ];
}

function authError($code, $message)
{
    header('WWW-Authenticate: Bearer realm="library-api"');
    sendError(401, $code, $message);
}

function requireAuth()
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches)) {
        authError('UNAUTHENTICATED', 'Bạn cần đăng nhập để sử dụng chức năng này.');
    }

    $config = getAuthConfig();
    try {
        // Co dinh HS256; thu vien kiem tra chu ky, exp, nbf va iat.
        $payload = JWT::decode($matches[1], new Key($config['jwt_secret'], 'HS256'));
    } catch (ExpiredException $e) {
        authError('TOKEN_EXPIRED', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
    } catch (UnexpectedValueException $e) {
        authError('INVALID_TOKEN', 'Token không hợp lệ. Vui lòng đăng nhập lại.');
    } catch (DomainException $e) {
        authError('INVALID_TOKEN', 'Token không hợp lệ. Vui lòng đăng nhập lại.');
    }

    // JWT::decode khong tu kiem tra iss/aud theo cau hinh ung dung.
    if (($payload->iss ?? null) !== $config['jwt_issuer']
        || ($payload->aud ?? null) !== $config['jwt_audience']
        || !isset($payload->exp, $payload->iat, $payload->sub)
        || !is_int($payload->exp)
        || !is_int($payload->iat)
        || $payload->exp <= $payload->iat
        || !is_string($payload->sub)
        || !ctype_digit($payload->sub)
        || (int) $payload->sub < 1
        || (int) $payload->sub > 4294967295) {
        authError('INVALID_TOKEN', 'Token không hợp lệ. Vui lòng đăng nhập lại.');
    }

    // Doc lai tai khoan de dung vai tro/trang thai hien tai trong CSDL.
    $pdo = getConnection();
    $statement = $pdo->prepare(
        'SELECT id, full_name, email, role, is_active, created_at FROM users WHERE id = :id'
    );
    $statement->execute(['id' => (int) $payload->sub]);
    $user = $statement->fetch();

    if (!$user || (int) $user['is_active'] !== 1) {
        authError('ACCOUNT_UNAVAILABLE', 'Tài khoản không còn khả dụng.');
    }

    return publicUser($user);
}

function requireAdmin()
{
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        sendError(403, 'FORBIDDEN', 'Chức năng này chỉ dành cho quản trị viên.');
    }

    return $user;
}

