# Bước 3 — Đăng ký tài khoản độc giả

## Điều kiện trước khi bắt đầu

- Đã tạo library_rest bằng schema.sql và có đủ users/categories/books/loans.
- Đã chạy thành công php tests/test-db.php.
- Giữ các file config/database.php và api/common/db.php đã kiểm tra trên máy của bạn.
- PHP 8.2, PDO MySQL và mbstring. Thư viện bên ngoài chưa cần cho chức năng đăng ký; JWT được tích hợp ở bước đăng nhập.
- Gói này được kiểm tra cú pháp JavaScript, liên kết file và cấu trúc ZIP. PHP/MySQL cần được kiểm tra thực tế trên XAMPP của bạn.

## Chép file

Giải nén ra thư mục tạm, chép các file vào đúng vị trí dưới thư mục Giữa kì. Gói không chứa schema, cấu hình DB hay db.php đã có, nên các phần đó tiếp tục dùng bản bạn đã chạy thành công. Nếu đang có code do Người 2 viết trong các file cùng tên, đối chiếu trước khi ghép.

| File | Công dụng |
|---|---|
| router.php | Chuyển request /api/v1 tới PHP API; các file web được phục vụ trong public |
| api/index.php | Chọn route/method; xử lý lỗi chung; hiện chỉ có POST đăng ký |
| api/common/request.php | Nhận JSON; từ chối sai content type, JSON hỏng hoặc body không phải object |
| api/common/response.php | Trả JSON theo hợp đồng chung; sendJson kết thúc request |
| api/modules/auth/register.php | Kiểm tra thông tin, hash mật khẩu và INSERT tài khoản Member |
| public/register.html | Form họ tên, email, mật khẩu, nhập lại mật khẩu |
| public/assets/css/auth.css | Giao diện form |
| public/assets/js/api.js | Hàm fetch dùng chung, xử lý HTTP lỗi và phản hồi 204 |
| public/assets/js/register.js | Nhận form, kiểm tra xác nhận mật khẩu, gọi API và hiển thị kết quả |

Đây là khung dùng chung cần bàn giao cho Người 2. Các route đăng nhập, hồ sơ, users/books/loans và homepage được thêm ở những bước tương ứng. Location khi tạo tài khoản chỉ đến tài nguyên users/{id}; route đọc tài nguyên này chưa có ở bản bước 3.

## Cách chạy ngoài htdocs

Bật MySQL trong XAMPP. Mở Terminal trong thư mục Giữa kì:

```powershell
php -S localhost:8000 -t public router.php
```

Nếu Terminal mới chưa nhận php, dùng đường dẫn đã kiểm tra:

```powershell
& "D:\xamphp\php\php.exe" -S localhost:8000 -t public router.php
```

Mở http://localhost:8000/register.html. Giữ Terminal mở; Ctrl+C để dừng. Không chạy bằng Live Server hoặc mở file:// vì form cần cùng máy chủ với API PHP.

## Luồng xử lý

1. register.js nhận form, giữ nguyên mật khẩu và kiểm tra nhập lại.
2. apiFetch gửi POST /api/v1/auth/register với JSON và Content-Type: application/json.
3. router.php chuyển request đến api/index.php.
4. register.php gọi readJsonBody, kiểm tra giá trị và tạo hash mật khẩu.
5. getConnection trong db.php kết nối DB; PDO prepare/execute thêm tài khoản.
6. API trả 201, JSON an toàn; giao diện thông báo thành công.
7. Trong phpMyAdmin, bảng users có dòng mới với role=member, is_active=1 và password_hash đã băm.

## Quy tắc cụ thể

- full_name: chuỗi, trim hai đầu, 2–100 ký tự Unicode.
- email: chuỗi, trim và chuyển chữ thường, tối đa 150 byte, đúng định dạng; UNIQUE trong DB bảo vệ email trùng kể cả request cạnh tranh.
- password: chuỗi, giữ nguyên khoảng trắng; ít nhất 8 ký tự Unicode, tối đa 72 byte; không có byte NUL. Giới hạn byte tránh bcrypt cắt phần cuối mật khẩu trên PHP 8.2. Ký tự tiếng Việt có thể chiếm nhiều byte.
- role/is_active luôn do server đặt thành member/1. Client gửi role=admin không tạo được quyền Admin.
- password_hash dùng PASSWORD_DEFAULT; không lưu mật khẩu rõ và không trả mật khẩu/hash qua API.
- Nhập lại mật khẩu là tiện ích frontend; API chỉ cần full_name/email/password.
- 201: tạo thành công; 409: email đã tồn tại; 422: sai giá trị; 400: JSON hỏng; 415: sai content type; 405: sai method; 500: lỗi nội bộ.
- PHP ghi chi tiết lỗi nội bộ vào Terminal/log, phản hồi cho client là thông báo chung.
- Các trang dùng sessionStorage key token khi bước đăng nhập được xây dựng. 401 xóa token; 403/409/422 không được coi là hết phiên.

## Kiểm thử thực tế

Ghi kết quả thực tế của từng test; chỉ đánh dấu đạt sau khi chạy.

| Test | Cách thử | Mong đợi |
|---|---|---|
| 1 | Đăng ký Tran Ho Duy An, an@example.com, ThuVien123 và xác nhận đúng | Thông báo thành công; users có một Member mới |
| 2 | Đăng ký lại cùng email hoặc AN@example.com | 409; không tạo thêm tài khoản |
| 3 | Xác nhận mật khẩu khác | Form báo chưa khớp; không gửi request |
| 4 | Mật khẩu ngắn hơn 8 ký tự | Browser chặn; gửi trực tiếp bằng API cũng bị 422 |
| 5 | Họ tên chỉ khoảng trắng hoặc email sai qua Postman | 422 có fields phù hợp |
| 6 | Thêm role=admin trong JSON Postman với email mới | Tạo thành công nhưng role vẫn member |
| 7 | Gửi GET tới route đăng ký | 405 và Allow: POST |
| 8 | Gửi body JSON hỏng / content type text/plain | 400 / 415 |
| 9 | Gửi password hơn 72 byte hoặc chứa NUL qua API | 422; không cắt mật khẩu để lưu |
| 10 | Xem response đăng ký trong Network/Postman | Không có password/password_hash; mã 201 |

Trong Postman chọn POST, URL http://localhost:8000/api/v1/auth/register, Body → raw → JSON:

```json
{
  "full_name": "Tran Ho Duy An",
  "email": "an@example.com",
  "password": "ThuVien123"
}
```

## Khi có lỗi

- 404 trang register: kiểm tra file ở public/register.html và chạy đúng lệnh có -t public.
- 404 API: kiểm tra đã truyền router.php và chép api/index.php.
- php không được nhận diện: dùng đường dẫn đầy đủ hoặc thêm PHP vào PATH cho Terminal hiện tại.
- 500: xem Terminal PHP; gửi dòng lỗi để kiểm tra. Kiểm tra DB vẫn chạy, cấu hình kết nối đúng và mbstring có trong php -m.
- Không gửi password, token hoặc secret thật vào ảnh báo cáo. Dùng tài khoản demo.

## Kiến thức cần hiểu trước bước tiếp theo

- __DIR__ là thư mục của chính file PHP; require_once nạp file hỗ trợ.
- json_decode đọc JSON; json_encode tạo JSON phản hồi.
- sendJson và sendError là hàm tự viết để các API trả thống nhất.
- prepare/execute truyền dữ liệu riêng với câu SQL.
- password_hash băm mật khẩu; bước đăng nhập dùng password_verify để kiểm tra.
- HTTP status phản ánh kết quả API; JavaScript phải kiểm tra response.ok.
- Form đăng ký và đăng nhập là hai chức năng khác nhau; đăng ký hiện tạo Member, bước tiếp theo mới cấp JWT khi đăng nhập.

Nguồn: https://www.php.net/manual/en/function.password-hash.php ; https://www.php.net/manual/en/function.json-decode.php ; https://www.php.net/manual/en/pdo.prepare.php ; https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
