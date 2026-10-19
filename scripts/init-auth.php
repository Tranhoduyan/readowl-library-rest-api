<?php
// Chi chay bang Terminal, khong chay qua trinh duyet.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Chua cai thu vien. Hay chay composer install.\n");
    exit(1);
}
require_once $autoload;

try {
    // Bo sung .gitignore va giu nguyen noi dung da co.
    $ignoreFile = $root . '/.gitignore';
    $ignore = is_file($ignoreFile) ? file_get_contents($ignoreFile) : '';
    if ($ignore === false) {
        throw new RuntimeException('Khong doc duoc .gitignore.');
    }
    foreach (['/vendor/', '/config/jwt-secret.php'] as $rule) {
        if (!in_array($rule, preg_split('/\r?\n/', $ignore), true)) {
            $ignore .= "\n" . $rule . "\n";
        }
    }
    if (file_put_contents($ignoreFile, $ignore) === false) {
        throw new RuntimeException('Khong luu duoc .gitignore.');
    }

    $secretFile = $root . '/config/jwt-secret.php';
    if (!is_file($secretFile)) {
        $secret = bin2hex(random_bytes(32));
        $content = "<?php\n\nreturn '" . $secret . "';\n";
        $handle = fopen($secretFile, 'x');
        if ($handle === false) {
            throw new RuntimeException('Khong tao duoc file khoa JWT.');
        }
        $written = fwrite($handle, $content);
        fclose($handle);
        if ($written !== strlen($content)) {
            unlink($secretFile);
            throw new RuntimeException('Khong ghi duoc khoa JWT.');
        }
        echo "Da tao khoa JWT rieng cho may nay.\n";
    } else {
        echo "Khoa JWT da ton tai, giu nguyen khoa cu.\n";
    }

    $secret = require $secretFile;
    if (!is_string($secret) || strlen($secret) < 64) {
        throw new RuntimeException('Khoa JWT khong hop le.');
    }

    // Kiem tra thu vien thuc te vua cai co ky/xac minh duoc token.
    $token = JWT::encode(['sub' => '1', 'iat' => time(), 'exp' => time() + 60], $secret, 'HS256');
    $decoded = JWT::decode($token, new Key($secret, 'HS256'));
    if ($decoded->sub !== '1') {
        throw new RuntimeException('Kiem tra JWT that bai.');
    }
    echo "Kiem tra thu vien JWT: THANH CONG.\n";
    echo "Khong chia se khoa trong config/jwt-secret.php.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Loi: " . $e->getMessage() . "\n");
    exit(1);
}

