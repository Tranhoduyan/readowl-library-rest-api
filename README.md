# Hệ thống thư viện mượn-trả sách REST API

PHP 8.2, MySQL/MariaDB (InnoDB), HTML/CSS/JavaScript thuần và JWT HS256 qua Composer. Code có thể đặt ngoài `htdocs`.

## Áp dụng bản sửa tiếp theo vào dự án đang chạy

1. Dừng server PHP bằng Ctrl + C.
2. Chép các file trong gói vào đúng thư mục cùng tên, chọn thay file cũ. Gói chỉ chứa file đã sửa và README này.
3. Giữ ba file `api/modules/users/delete.php`, `api/common/seed.php`, `database/seed.sql` đã sửa ở bước trước. Gói này không chép lại chúng.
4. Nếu đã nhập thành công `seed.sql` mới ở bước trước, không cần nhập SQL lại. Không xóa database hoặc nhập lại schema để áp dụng bản này.
5. Giữ MySQL chạy trong XAMPP, mở Terminal tại thư mục gốc dự án và chạy server như bên dưới.
6. Mở website và nhấn Ctrl + F5 để trình duyệt lấy JavaScript mới.

Đổi file trong phpMyAdmin không tự thay code trong VS Code. Phải chép/dán và lưu từng file hoặc giải nén gói đúng vào thư mục dự án.

## Cài mới trên máy khác

1. Cài XAMPP có PHP 8.2 và MySQL/MariaDB, cài Composer. PHP cần `pdo_mysql`, `mbstring`, `openssl`; script test cần thêm `curl`. Composer có thể cần `zip` khi tải thư viện.
2. Bật MySQL. Nếu dùng phpMyAdmin, bật cả Apache và mở http://localhost/phpmyadmin.
3. Nhập `database/schema.sql`, sau đó nhập `database/seed.sql` đã sửa. Dữ liệu mẫu chỉ được tạo khi cả bốn bảng đều rỗng. Với CSDL đã có dữ liệu, SQL không thêm phiếu minh họa; nó sửa hash mẫu cũ đã xác định và đối chiếu tồn kho.
4. Sửa `config/database.php` theo máy: database `library_rest`, cổng mặc định `3306`, tài khoản XAMPP mặc định `root`, mật khẩu rỗng.
5. Mở Terminal tại thư mục dự án và chạy:

```powershell
composer install
& "D:\xamphp\php\php.exe" scripts/init-auth.php
```

Mỗi máy tự tạo `config/jwt-secret.php`. Không gửi file khóa thật, token hoặc database chứa dữ liệu thật lên Git. Khi đóng ZIP để chia sẻ source, loại file khóa này khỏi ZIP; `.gitignore` chỉ áp dụng cho Git, không tự loại file khỏi ZIP.

## Chạy website

Từ thư mục gốc dự án:

```powershell
& "D:\xamphp\php\php.exe" -S localhost:8000 -t public router.php
```

Nếu đường dẫn XAMPP khác thì thay đường dẫn đến `php.exe`. Giữ Terminal mở. Mở http://localhost:8000/login.html; dùng cùng hostname `localhost` trong lúc thử. `localhost` và `127.0.0.1` có vùng sessionStorage khác nhau.

Chạy website bằng lệnh trên chỉ cần MySQL từ XAMPP. Apache chỉ cần khi sử dụng phpMyAdmin. Nhấn Ctrl + C để dừng server; dữ liệu vẫn được giữ trong MySQL.

Tài khoản mẫu khi cài mới:

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | admin@example.com | Admin12345 |
| Member | an@example.com | ThuVien123 |
| Member | chau.le@example.com | ThuVien123 |

`bao.pham@example.com` là tài khoản mẫu bị khóa. Tài khoản đã đổi mật khẩu vẫn dùng mật khẩu mới của tài khoản đó.

`api/common/db.php` chỉ kết nối CSDL, không tự thêm dữ liệu. `scripts/seed-users.php` cũ đã được thay bằng thông báo hướng dẫn nhập SQL; script này không ghi vào CSDL.

## Các chức năng và quy tắc

- Đăng ký luôn tạo Member; đăng nhập trả JWT có thời hạn 15 phút.
- Token lưu trong sessionStorage. Đăng xuất xóa token trên trình duyệt; không thu hồi bản sao token đã cấp. API đọc lại quyền/trạng thái tài khoản từ CSDL trên mỗi yêu cầu cần xác thực.
- Member chỉ xem/hủy phiếu của mình. Admin quản lý độc giả, sách, danh mục, giao và nhận trả sách.
- Một phiếu tương ứng một bản sách. `pending` chưa giữ chỗ/chưa trừ tồn. Tối đa ba phiếu `borrowed`, hạn mượn 14 ngày theo `config/app.php`.
- Giao sách trừ một bản; trả sách cộng một bản. Các thao tác này dùng transaction và khóa hàng. Không được trả hoặc giao lặp một phiếu.
- Chỉ xóa độc giả chưa có phiếu/lịch sử xử lý sách. Với tài khoản có lịch sử, dùng Khóa. Không xóa lịch sử để xóa tài khoản.
- Sách có lịch sử không xóa cứng; chuyển sang ngừng cho mượn mới. Tài khoản bị khóa hoặc sách ngừng cho mượn vẫn được Admin nhận trả.
- Frontend đọc đủ các trang của API phiếu mượn, chờ 300 ms sau khi gõ tìm kiếm và bỏ kết quả của lần tìm cũ.
- Khi mở chi tiết sách, frontend lấy dữ liệu mới để tránh hiển thị tồn kho giả khi cuốn sách không nằm trong bộ lọc hiện tại.

## Kiểm tra thủ công sau khi cập nhật

1. Đăng nhập Admin, thêm sách và bỏ chọn ô cho mượn: sách phải được lưu ở trạng thái ngừng cho mượn. Mở lại Sửa để kiểm tra.
2. Sửa sách với năm/tổng số bản không hợp lệ qua Postman: API phải trả 422, không phải 500.
3. Dùng Member gửi yêu cầu; Admin giao và nhận trả. Kiểm tra số còn lần lượt giữ nguyên, giảm một, rồi tăng một.
4. Thử Xóa độc giả đã có lịch sử: phải bị chặn, phiếu vẫn còn. Độc giả mới chưa có phiếu vẫn xóa được.
5. Dùng tài khoản có trên 100 phiếu, mở Phiếu mượn của tôi: phải thấy đủ lịch sử, không bị dừng ở 20 hay 100. Có thể xem các request `page=1`, `page=2`... trong tab Network.
6. Gõ tìm kiếm nhanh, đổi danh mục: kết quả phải khớp từ khóa/bộ lọc cuối cùng. Mất kết nối hoặc hết phiên phải có thông báo/chuyển về đăng nhập.

## Kiểm thử API tự động

Mở một Terminal mới, giữ server và MySQL đang chạy:

```powershell
& "D:\xamphp\php\php.exe" tests/run-api-tests.php
```

Script dùng tài khoản Admin demo và tạo một Member TEST riêng. Nó thử đăng ký, đăng nhập, JWT sai/thiếu, phân quyền, sách ngừng cho mượn, tạo phiếu trùng, giao/trả, tồn kho và bảo toàn lịch sử. Nó kiểm tra đúng mã HTTP từng tình huống, dừng khi có lỗi và trả exit code khác 0 khi chưa đạt.

Sau test, phiếu TEST vẫn được giữ để bảo toàn lịch sử; tài khoản TEST sẽ bị khóa. Sách TEST ngừng cho mượn chưa có lịch sử sẽ được xóa. Nếu test dừng giữa chừng, script cố gắng nhận trả hoặc từ chối phiếu TEST trước khi khóa tài khoản; nếu dọn không được, nó in cảnh báo để Admin kiểm tra. Nên chạy trên CSDL demo, khi không có người khác thay đổi tồn kho cùng lúc.

Nếu Admin đã đổi email/mật khẩu, khai báo trong chính Terminal chạy test:

```powershell
$env:LIBRARY_ADMIN_EMAIL = "email-admin-cua-ban"
$env:LIBRARY_ADMIN_PASSWORD = "mat-khau-admin-cua-ban"
```

Có thể đặt `LIBRARY_API_URL` nếu dùng cổng/hostname khác. Không chụp ảnh hoặc chia sẻ Terminal có thông tin đăng nhập thật.

Các test này chạy **tuần tự**, chưa thay thế kiểm thử đồng thời hai request lấy bản cuối hoặc hai lần giao sách song song.

## Postman và tài liệu API

- Import `tests/postman/member1-collection.json` bằng Postman và chạy theo thứ tự bằng Collection Runner. Bộ request tự tạo Member TEST riêng và chọn sách còn bản; cuối luồng khóa Member TEST, giữ phiếu đã trả. Cần Admin hợp lệ và ít nhất một cuốn đang cho mượn còn bản. Nếu đổi mật khẩu Admin, sửa request đăng nhập Admin.
- `docs/openapi-auth-loans.yaml` mô tả các API auth, users và loans; không phải đặc tả đầy đủ sách/danh mục/thống kê.
- API thành công: `success`, `data`, `message`; danh sách phân trang thêm `meta` cùng cấp với `data`. Lỗi: `success=false`, `error.code`, `error.message`, `error.fields`.
- Trường lý do từ chối là `rejection_reason`. Không dùng `rejected_reason` và không gửi `due_date` khi chuyển trạng thái; hạn trả do server tính.

## Phạm vi xác nhận

Bản sửa đã được kiểm tra cú pháp JavaScript, đối chiếu ID HTML, thử logic frontend bằng API mô phỏng và đối chiếu hợp đồng tài liệu/kiểm thử. Môi trường soạn bản sửa không có PHP/MySQL, nên cần chạy script kiểm thử và thử chức năng trên XAMPP trước khi đánh dấu kiểm thử tích hợp đạt. Chưa có kết quả chạy đồng thời được xác nhận.
