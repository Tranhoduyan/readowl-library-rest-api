<?php
// Giu file cu de nguoi dung khong vo tinh chay lai bo du lieu mau da loi.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

echo "Du lieu mau da duoc thong nhat trong database/seed.sql.\n";
echo "Hay mo phpMyAdmin, chon library_rest va nhap file SQL nay.\n";
echo "Script nay khong thay doi CSDL hay tu tao them du lieu.\n";
