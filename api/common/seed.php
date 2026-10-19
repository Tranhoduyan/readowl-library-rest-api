<?php

// Giu ten ham de tuong thich voi db.php hien tai.
// Ket noi CSDL chi nen tao ket noi, khong tu them tai khoan, sach hay phieu muon.
// Muon tao du lieu mau: nhap database/seed.sql chu dong bang phpMyAdmin.
// File SQL cung co phan sua du lieu mau cu va doi chieu lai ton kho.
function ensureSampleData(PDO $pdo)
{
    // Khong tu dong seed khi mo trang web, dang nhap hoac chay test-db.php.
    return;
}
