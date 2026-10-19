@echo off
chcp 65001 >nul
echo ========================================================
echo   KHỞI ĐỘNG HỆ THỐNG THƯ VIỆN (CHẠY NGOÀI HTDOCS)
echo ========================================================
echo.
echo Lưu ý: Hãy đảm bảo bạn đã nhấn [Start] ở dòng MySQL trong XAMPP!
echo.
echo Đang mở trình duyệt và khởi động máy chủ tại cổng 8000...
start http://127.0.0.1:8000/login.html
"D:\xamphp\php\php.exe" -S 127.0.0.1:8000 -t public router.php
pause
