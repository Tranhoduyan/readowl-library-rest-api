# Bước 4 — Đăng nhập, JWT, xem tài khoản và đăng xuất

## 1. Nội dung của bước này

- POST /api/v1/auth/login: nhận email/mật khẩu, xác minh bằng password_verify, cấp JWT.
- GET /api/v1/auth/me: chỉ trả thông tin của chủ token hợp lệ.
- Đăng xuất ở trình duyệt: xóa token trong sessionStorage và về trang đăng nhập.
- Hàm requireAdmin chuẩn bị dùng cho các module quản trị sau; chưa có API quản trị ở bước này.
- API đăng ký đã làm vẫn được giữ trong api/index.php.

PHP xử lý API; HTML chứa form; CSS trình bày; JavaScript dùng fetch để gọi API.

## 2. Các file trong gói

| File | Công việc |
| --- | --- |
| config/app.php | Issuer, audience và thời hạn JWT 900 giây |
| scripts/init-auth.php | Tạo khóa riêng trên máy, bổ sung .gitignore, kiểm tra thư viện JWT |
| api/index.php | Ghép các tuyến đăng ký, đăng nhập và tài khoản |
| api/common/auth.php | Tạo token, xác thực người dùng, kiểm tra quyền admin |
| api/modules/auth/login.php | Kiểm tra dữ liệu và mật khẩu, trả token |
| api/modules/auth/me.php | Trả thông tin tài khoản đang đăng nhập |
| public/login.html | Form đăng nhập |
| public/profile.html | Trang xem tài khoản và nút đăng xuất |
| public/assets/js/login.js | Gửi JSON, lưu token, chuyển trang |
| public/assets/js/profile.js | Gọi API có xác thực và xử lý đăng xuất |
| public/assets/css/auth-extra.css | Kiểu trình bày bổ sung |
| docs/Buoc_4_Dang_nhap_JWT.md | Tài liệu này |

Gói sử dụng lại các file đã chạy thành công ở bước 3:
router.php, api/common/db.php, request.php, response.php,
api/modules/auth/register.php, public/register.html,
public/assets/js/api.js và public/assets/css/auth.css.
Giữ nguyên cấu hình CSDL và composer.json/composer.lock hiện tại.
Trong gói không có khóa ký, vendor hay dữ liệu tài khoản.

## 3. Cài vào đồ án

1. Giải nén gói ra một thư mục tạm.
2. Chép các thư mục api, config, scripts, public, docs vào thư mục gốc đồ án.
   Chọn gộp thư mục. Thay api/index.php bằng bản mới.
   Nếu đã tự sửa api/index.php/config/app.php, lưu bản cũ trước khi chép.
   Không đặt các thư mục này vào một thư mục Buoc_4 bên trong đồ án.
3. Mở terminal tại:
   D:\HK1_2027\Lập trình WEB và Ứng Dụng\Giữa kì
4. Chạy:

```powershell
& "D:\xamphp\php\php.exe" scripts/init-auth.php
```

Kết quả mong đợi:

```text
Da tao khoa JWT rieng cho may nay.
Kiem tra thu vien JWT: THANH CONG.
Khong chia se khoa trong config/jwt-secret.php.
```

Chạy lại script giữ nguyên khóa cũ. Script tự tạo config/jwt-secret.php
và bổ sung hai dòng /vendor/ và /config/jwt-secret.php vào .gitignore,
giữ nguyên nội dung đã có.
Không gửi nội dung khóa hoặc token đăng nhập qua ảnh/chat.
Git ignore không xóa file đã được theo dõi, nhưng dự án hiện chưa push GitLab.

5. Bật MySQL trong XAMPP.
6. Ở terminal chạy web server, nhấn Ctrl+C để dừng nếu đang chạy,
   rồi khởi động lại:

```powershell
& "D:\xamphp\php\php.exe" -S localhost:8000 -t public router.php
```

7. Mở http://localhost:8000/login.html.
   Dùng email và mật khẩu bạn đã đăng ký ở bước 3.
   Không có tài khoản admin mặc định trong bước này.

Máy của thành viên thứ hai: composer install, cấu hình CSDL của máy đó,
rồi chạy scripts/init-auth.php. Mỗi máy có khóa riêng và đăng nhập riêng.
Code vẫn nằm ngoài htdocs.

## 4. Luồng xử lý

### Đăng nhập
1. JS đọc email/mật khẩu; mật khẩu không bị trim.
2. JS gửi POST JSON bằng apiFetch.
3. PHP kiểm tra dữ liệu và tìm email bằng prepared statement.
4. password_verify so sánh với password_hash trong CSDL.
5. Email không tồn tại hoặc mật khẩu sai: cùng một thông báo 401.
6. Tài khoản bị vô hiệu hóa với mật khẩu đúng: 403.
7. Tài khoản hợp lệ: ký token HS256, trả 200 cùng thông tin an toàn.
8. JS lưu access_token vào sessionStorage với khóa token, chuyển sang profile.html.

### Xem tài khoản
1. apiFetch gửi Authorization: Bearer <token>.
2. requireAuth xác minh chữ ký và thời hạn qua thư viện.
3. Kiểm tra iss/aud/sub/iat/exp của ứng dụng.
4. Tìm người dùng trong CSDL và kiểm tra is_active.
5. me.php trả thông tin hiện tại, không trả mật khẩu/password_hash.
6. JS hiển thị bằng textContent.

Quyền admin đọc từ CSDL hiện tại, không lấy từ thông tin do frontend gửi.
Ẩn nút trên frontend chưa đủ để bảo vệ API.

## 5. Nội dung JWT

| Claim | Ý nghĩa |
| --- | --- |
| iss | Ứng dụng phát hành token: library-rest-api |
| aud | Ứng dụng nhận token: library-web |
| sub | ID người dùng dạng chuỗi |
| iat | Thời điểm cấp token, Unix timestamp |
| nbf | Thời điểm token bắt đầu hợp lệ |
| exp | Thời điểm hết hạn, sau 15 phút |

JWT là token có chữ ký, không phải dữ liệu mã hóa bí mật.
Không đặt mật khẩu hoặc khóa ký vào payload.
HS256 dùng cùng một khóa bí mật cho ký và xác minh.
Server cố định thuật toán HS256 khi xác minh.

## 6. Kiểm tra lần lượt

| STT | Thao tác | Kết quả |
| --- | --- | --- |
| 1 | Chạy init-auth.php | Kiểm tra JWT thành công |
| 2 | Email đã đăng ký + mật khẩu đúng | Chuyển profile, thông tin đúng |
| 3 | Tải lại profile | Vẫn lấy được thông tin khi token còn hạn |
| 4 | Đăng xuất | Về login, xóa token |
| 5 | Mở profile.html sau đăng xuất | Tự về login |
| 6 | Đăng nhập sai mật khẩu | Hiện Email hoặc mật khẩu không đúng |
| 7 | Dùng email chưa đăng ký | Cùng thông báo như sai mật khẩu |
| 8 | Gọi me không token | HTTP 401 |
| 9 | Gọi me với token sai | HTTP 401 |
| 10 | POST me | HTTP 405 và Allow: GET |
| 11 | Token quá 15 phút | me trả 401, frontend xóa token và về login |
| 12 | Đăng ký tài khoản mới | Chức năng đăng ký vẫn hoạt động |

Ban đầu làm 1–7. Sau khi giao diện chạy đúng, dùng Postman:
- POST http://localhost:8000/api/v1/auth/login
- Body > raw > JSON, gửi email/mật khẩu của bạn:

```json
{
  "email": "email-ban-da-dang-ky@example.com",
  "password": "mat-khau-ban-da-dang-ky"
}
```

Response thành công có success=true, data.access_token, data.token_type,
data.expires_in=900, data.user.
Không chia sẻ token trong ảnh chụp.
- GET http://localhost:8000/api/v1/auth/me
- Authorization > Bearer Token: token vừa nhận.
- Xóa Authorization để thử 401. Điền abc.def.ghi để thử token sai.

Kiểm tra tài khoản bị khóa (tùy chọn, sau 1–12):
- Chọn đúng tài khoản thử nghiệm trong phpMyAdmin, sửa is_active từ 1 sang 0.
- Gọi me với token đã cấp: 401.
- Đăng nhập bằng mật khẩu đúng: 403.
- Khôi phục is_active=1 sau khi kiểm tra.

## 7. Phạm vi và những phần tiếp theo

Đăng xuất hiện tại xóa token ở trình duyệt. Token đã bị sao chép trước đó
vẫn còn dùng được đến khi hết hạn nếu tài khoản còn hoạt động.
Bước này chưa có danh sách thu hồi token, refresh token hoặc API logout.
Đây là phạm vi cần giải thích trung thực trong báo cáo.

sessionStorage dùng cho bản demo cùng origin, không chống được XSS nếu
ứng dụng có lỗ hổng XSS. Các trang hiện tại dùng textContent cho dữ liệu.
Khi triển khai thật cần HTTPS và bổ sung biện pháp chống dò mật khẩu.
Ở bước này chạy HTTP trên localhost để học và thử nghiệm.

Tiếp theo mới làm quản lý độc giả, tạo tài khoản admin và module mượn–trả.
Trang profile hiện chỉ xem thông tin, chưa chỉnh sửa hồ sơ.

## 8. Khi gặp lỗi

- 500: nhìn thông báo ở terminal web server; gửi dòng lỗi đã che dữ liệu bí mật.
- Chưa tạo khóa: chạy scripts/init-auth.php từ thư mục gốc.
- Không có vendor/autoload.php: chạy composer install.
- 404 login.html: kiểm tra file nằm trong public và server dùng -t public.
- 404 API: kiểm tra server khởi chạy có router.php.
- Lỗi CSDL: bật MySQL; giữ config/database.php đã kiểm tra thành công.
- Sau cập nhật JS mà trình duyệt còn bản cũ: nhấn Ctrl+F5.

## 9. Tài liệu chính thức

Cách gọi JWT::encode và JWT::decode với Key:
https://github.com/googleapis/php-jwt

## 10. Kiểm tra trước khi giao gói

Đã kiểm tra cú pháp JavaScript, liên kết file/ID HTML và tính toàn vẹn ZIP.
Môi trường soạn gói không có PHP/MySQL nên chưa chạy tích hợp API ở đây.
Các bước kiểm tra trên máy Windows của bạn ở mục 6 là xác nhận thực tế.

