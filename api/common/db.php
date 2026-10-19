<?php

function getConnection()
{
    // __DIR__ la thu muc chua file db.php.
    $config = require __DIR__ . '/../../config/database.php';

    $dsn = 'mysql:host=' . $config['host']
        . ';port=' . $config['port']
        . ';dbname=' . $config['name']
        . ';charset=utf8mb4';

    $pdo = new PDO($dsn, $config['user'], $config['password']);

    // Khi truy van loi, PDO se nem exception de noi goi xu ly.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lay du lieu theo ten cot, vi du $row['email'].
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Dung prepared statements cua MySQL thay vi mo phong o PHP.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Dong bo mui gio ket noi MySQL voi quy uoc cua du an.
    $pdo->exec("SET time_zone = '+07:00'");

    return $pdo;
}
