<?php

// Chay bang Terminal: php tests/test-db.php
require_once __DIR__ . '/../api/common/db.php';

try {
    $pdo = getConnection();

    $statement = $pdo->query('SELECT DATABASE() AS database_name');
    $result = $statement->fetch();

    $statement = $pdo->query('SHOW TABLES');
    $tables = $statement->fetchAll(PDO::FETCH_COLUMN);

    echo 'Ket noi thanh cong!' . PHP_EOL;
    echo 'Database: ' . $result['database_name'] . PHP_EOL;
    echo 'Cac bang:' . PHP_EOL;

    foreach ($tables as $table) {
        echo '- ' . $table . PHP_EOL;
    }
} catch (PDOException $e) {
    // Chi hien chi tiet loi trong file kiem tra chay o Terminal.
    echo 'Ket noi hoac truy van that bai: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
