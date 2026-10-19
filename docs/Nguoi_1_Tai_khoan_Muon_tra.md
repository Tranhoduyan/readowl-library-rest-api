# Người 1 — Tài khoản, JWT và nghiệp vụ mượn–trả

Dự án: Hệ thống thư viện mượn–trả sách sử dụng REST API

Bản phân công ngày 14/10/2026. Đọc phần công việc riêng trước, sau đó dùng quy ước chung để triển khai và ghép code. Các ô checklist dùng để theo dõi tiến độ thực tế.


## Công việc riêng của Người 1

Bạn phụ trách tài khoản, JWT, phân quyền, quản lý độc giả và toàn bộ nghiệp vụ mượn–trả. Bạn làm cả API PHP, màn hình HTML, CSS và JavaScript tương ứng. Độ khó chính nằm ở quyền truy cập, chuyển trạng thái và tính đúng của số lượng khi nhiều yêu cầu đến cùng lúc.

### A. Tài khoản và bảo mật

| Mã | Việc cần làm | Đầu vào/kết quả | Quy tắc và tiêu chí đạt |
|---|---|---|---|
| TK01 | Đăng ký | full_name, email, password; trả thông tin tài khoản an toàn | Trim tên/email; email hợp lệ, duy nhất; tên 2–100 ký tự; mật khẩu tối thiểu 8 ký tự; không trim mật khẩu; role luôn member |
| TK02 | Đăng nhập | Email/mật khẩu; trả token, expires_in, user | Dùng password_verify; tài khoản hoạt động; thông báo sai đăng nhập chung; không trả hash |
| TK03 | Đăng xuất trên client | Xóa token và thông tin phiên ở trình duyệt | Trở về trang công khai; yêu cầu mới từ giao diện không kèm token cũ |
| TK04 | Xem hồ sơ | JWT; trả tài khoản hiện tại | Không cho chọn user_id khác qua request |
| TK05 | Kiểm tra JWT | Authorization Bearer | Xác minh chữ ký, thuật toán được cho phép, exp, issuer/audience theo cấu hình |
| TK06 | Kiểm tra trạng thái/quyền | User đã xác minh | Đọc tài khoản hiện tại để áp dụng khóa tài khoản và role hiện tại; không tin role gửi từ frontend |

Cách thực hiện:
1. Cùng Người 2 tạo users và seed Admin/Member bằng password_hash.
2. Cài thư viện JWT bằng Composer, chọn phiên bản tương thích PHP đang dùng và lưu lockfile. Không tự viết thuật toán ký token.
3. Thống nhất khóa bí mật đủ mạnh, issuer, audience, thời hạn token; gợi ý access token 15 phút cho bản demo.
4. Viết requireAuth và requireAdmin theo hợp đồng chung.
5. Viết API đăng ký, đăng nhập, /me; test Postman trước rồi gắn form HTML.
6. Xây login.html, register.html, profile.html; JS hiển thị lỗi đúng từng tình huống.
7. Kiểm tra các API của Người 2 bằng token Admin/Member để xác nhận hàm dùng chung thực sự hoạt động.

Quyết định lưu token cho demo nhiều trang: có thể dùng sessionStorage để giữ qua chuyển trang trong cùng tab. Đây là nơi JavaScript đọc được, cần hạn chế XSS bằng textContent, kiểm soát dữ liệu và không ghi token vào log/URL. Không tuyên bố đây là giải pháp chống XSS tuyệt đối. Khi triển khai ngoài localhost phải dùng HTTPS.

Giới hạn đăng xuất: xóa token ở client không tự thu hồi một bản sao token đã cấp. Với bản cơ sở, token hết hiệu lực khi hết hạn; khóa tài khoản có hiệu lực nhờ kiểm tra DB. Nếu muốn thu hồi token tức thì, cần thêm cơ chế và kiểm thử riêng, không quảng cáo đã có khi chưa triển khai.

### B. Quản lý độc giả

| Mã | Chức năng | Quy tắc |
|---|---|---|
| DG01 | Danh sách độc giả có phân trang | Admin; chỉ role=member; trả trường an toàn |
| DG02 | Tìm theo tên/email, lọc hoạt động | Tham số được kiểm tra; giới hạn page/limit |
| DG03 | Chi tiết và lịch sử độc giả | Admin; dùng loans API đã có thay vì lặp lại nghiệp vụ |
| DG04 | Khóa/mở khóa | Chỉ chỉnh is_active của Member; không xóa lịch sử, không làm thay đổi số lượng sách |

Không xây API cho Member tự đổi role. Bản cơ sở không cho Admin vô tình khóa chính mình qua màn hình độc giả. Hồ sơ Member mới chỉ cần xem; sửa hồ sơ/đổi mật khẩu là mở rộng sau.

Màn hình: admin/users.html và JS/CSS riêng. Trên từng hàng có xem chi tiết, trạng thái và nút khóa/mở; cần xác nhận trước thao tác khóa.

### C. Tạo, xem và hủy yêu cầu mượn

1. POST yêu cầu chỉ nhận book_id; user_id lấy từ requireAuth.
2. Kiểm tra Member hoạt động, sách tồn tại/hoạt động, còn bản, chưa vượt giới hạn.
3. Kiểm tra chưa có pending/borrowed cùng sách của độc giả.
4. Tạo loans trạng thái pending, requested_at từ server; không giảm available_copies.
5. GET danh sách trả đúng dữ liệu theo vai trò: Member chỉ thấy của mình; Admin được lọc toàn bộ.
6. Member hủy yêu cầu của mình khi pending; đặt cancelled, không cập nhật tồn.
7. Người 1 cung cấp hợp đồng cho Người 2 gắn nút gửi yêu cầu ở trang chi tiết sách. Người 1 phụ trách my-loans.html.

Trang my-loans nên có các tab Chờ xử lý, Đang mượn, Lịch sử; hiển thị tên sách, ngày liên quan, nhãn quá hạn, lý do từ chối khi có. Chỉ tab chờ có nút hủy phù hợp.

### D. Xác nhận giao sách và từ chối

Khi Admin giao sách:
1. Xác thực Admin và đọc yêu cầu.
2. Bắt đầu transaction.
3. Khóa và kiểm tra lại dữ liệu liên quan theo quy ước xử lý đồng thời bên dưới.
4. Chỉ chấp nhận pending; độc giả/sách phải còn hoạt động.
5. Kiểm tra số lượt đang mượn dưới 3 và available_copies > 0.
6. Đổi sang borrowed; gán borrowed_at, due_date, approved_by.
7. Giảm available_copies một bản và cập nhật updated_at của books.
8. Commit; trả bản ghi cập nhật. Nếu bước nào lỗi, rollback toàn bộ.

Từ chối: chỉ pending; nhận rejection_reason dài 1–500 ký tự sau trim; chuyển rejected; không giảm tồn. Nếu thiếu lý do trả 422. Nếu yêu cầu đã đổi trạng thái trả 409.

Màn hình admin/loans.html có bộ lọc trạng thái và tìm độc giả/sách; nút giao, từ chối hoặc nhận lại tùy trạng thái. Việc nhấn nút giao biểu thị thủ thư đã giao sách thực tế.

### E. Trả sách và quá hạn

1. Admin xác nhận đã nhận lại sách.
2. Bắt đầu transaction và khóa bản ghi liên quan.
3. Chỉ status=borrowed mới được trả.
4. Đặt returned, returned_at, returned_by; tăng available_copies một.
5. Kiểm tra không vượt total_copies; cập nhật books.updated_at và commit.
6. Lặp lại thao tác trên lượt returned phải trả 409, không tăng tồn.
7. Cho phép trả sách của độc giả bị khóa hoặc sách đã ngừng cho mượn.
8. Hiển thị quá hạn theo quy tắc ngày ở phần chung; không sửa trạng thái bằng cron.

### F. Xử lý đồng thời cần làm rõ

Transaction là nhóm cập nhật cùng thành công hoặc cùng thất bại. Khóa hàng giúp hai request không đồng thời vượt giới hạn hoặc lấy cùng bản cuối.

- Thống nhất cho các luồng Người 1: khóa users của độc giả, rồi books, rồi loans khi cần cập nhật bản ghi có sẵn; dùng SELECT ... FOR UPDATE trong transaction.
- Có thể đọc ID liên quan trước để biết hàng cần khóa, nhưng phải đọc lại và xác minh trạng thái sau khi khóa. user_id/book_id của loans bất biến sau khi tạo.
- Tạo yêu cầu mới: khóa users trước để tuần tự hóa các yêu cầu của cùng độc giả; kiểm tra trùng pending/borrowed và các điều kiện trong transaction rồi mới insert.
- Giao sách: khóa độc giả để hai lượt giao khác sách không đồng thời vượt giới hạn 3; khóa sách để hai độc giả không lấy cùng bản cuối.
- Trả, hủy và từ chối cũng kiểm tra trạng thái trong transaction để xử lý tranh chấp giao/hủy hoặc trả lặp.
- Người 2 khóa books khi sửa tổng số bản; thống nhất thứ tự khóa để giảm deadlock.
- Nếu lỗi khóa/deadlock, rollback, trả lỗi có kiểm soát và cho phép thử lại; không để thay đổi dở dang.
- Nút bị vô hiệu hóa khi đang gửi chỉ giúp UI; vẫn phải bảo vệ dữ liệu tại server.
- POST tạo yêu cầu không tự có tính idempotent. Quy tắc chống trùng ở đây là luật nghiệp vụ cho cùng độc giả/sách khi còn pending/borrowed.

### G. API Người 1 sở hữu

| Method | Route sau /api/v1 | Quyền | Kết quả chính |
|---|---|---|---|
| POST | /auth/register | Guest | 201 tạo Member |
| POST | /auth/login | Guest | 200 token và user |
| GET | /me | Đã xác thực | 200 hồ sơ hiện tại |
| GET | /users | Admin | 200 danh sách Member |
| GET | /users/{id} | Admin | 200 chi tiết Member |
| PATCH | /users/{id} | Admin | 200; chỉ is_active theo phạm vi |
| POST | /loans | Member | 201 pending, Location đến /loans/{id} |
| GET | /loans | Member/Admin | 200 danh sách theo quyền |
| GET | /loans/{id} | Chủ sở hữu/Admin | 200 hoặc 404 |
| PATCH | /loans/{id} | Tùy chuyển trạng thái | 200; target status và lý do khi rejected |

PATCH loans chỉ chấp nhận cancelled, borrowed, rejected hoặc returned theo bảng chuyển trạng thái. Không cho client gửi pending để khôi phục. Bản cơ sở không có DELETE loans và không cần endpoint logout khi chỉ xóa token client.

Bộ lọc GET loans: status, page, limit; Admin có thể thêm user_id/book_id/q. Member gửi user_id của người khác phải bị từ chối hoặc bị bỏ qua theo một quy tắc đã ghi tài liệu; chốt dùng 403 khi Member cố đặt user_id khác. Với truy cập /loans/{id} không thuộc mình, dùng 404.

### H. Bộ kiểm thử bắt buộc của Người 1

Mỗi dòng phải có kết quả thực tế, không chỉ đánh dấu dự kiến.

| Test | Tình huống | Mong đợi |
|---|---|---|
| A01 | Đăng ký hợp lệ | 201; role member; password được hash |
| A02 | Email trùng, kể cả hai request gần đồng thời | 409; không có hai tài khoản trùng |
| A03 | Sai mật khẩu hoặc tài khoản bị khóa | Từ chối; không cấp token |
| A04 | JWT thiếu, bị sửa, hết hạn | 401 |
| A05 | Member gọi API Admin | 403 |
| A06 | Member xem lượt mượn người khác | 404; không lộ dữ liệu |
| A07 | Tạo yêu cầu hợp lệ | pending; tồn không đổi |
| A08 | Tạo yêu cầu trùng cho cùng sách | 409 |
| A09 | Hủy pending của mình | cancelled; tồn không đổi |
| A10 | Hủy lượt đã borrowed | 409 |
| A11 | Admin giao thành công | borrowed; hạn 14 ngày; tồn giảm 1 |
| A12 | Giao khi Member đã mượn 3 bản | 409; không đổi dữ liệu |
| A13 | Hai lượt giao khác sách cho cùng người đang mượn 2 bản | Chỉ một thành công; tổng đang mượn tối đa 3 |
| A14 | Hai người được giao cùng bản cuối gần đồng thời | Chỉ một thành công; tồn bằng 0 |
| A15 | Nhận trả thành công | returned; tồn tăng 1 |
| A16 | Nhận trả lặp hoặc đồng thời | Chỉ một lần cập nhật tồn |
| A17 | Khóa Member đang giữ sách rồi Admin nhận trả | Admin vẫn nhận trả được |
| A18 | Ngày hiện tại bằng hạn trả / lớn hơn hạn trả | Chưa quá hạn / quá hạn |
| A19 | Từ chối không có lý do | 422 |
| A20 | Lỗi phát sinh giữa cập nhật loan và tồn trong môi trường test | Rollback cả hai thay đổi |
| A21 | Khóa tài khoản sau khi đã cấp JWT | Yêu cầu bảo vệ tiếp theo bị từ chối |
| A22 | Member tự gửi role hoặc user_id để nâng quyền | Không thay đổi quyền/chủ sở hữu |

Kiểm thử đồng thời cần hai request thực sự chồng lấn hoặc script chạy song song; hai lần bấm tuần tự không đủ chứng minh. Lỗi giả lập chỉ bật ở môi trường test, không để endpoint gây lỗi trong bản nộp.

### I. Kiến thức cần học theo thứ tự

1. PHP nhận JSON: REQUEST_METHOD, php://input, json_decode/json_encode, http_response_code.
2. PDO: prepared statements, khóa ngoại, xử lý lỗi; tuyệt đối không nối dữ liệu nhập vào SQL.
3. password_hash/password_verify; xác thực khác phân quyền.
4. JWT: header/payload/signature, exp, iss, aud; decode khác verify; token ký không đồng nghĩa payload được mã hóa.
5. Stateless: mỗi request đủ thông tin để xử lý; DB vẫn lưu tài nguyên. Kiểm tra tài khoản hiện tại không có nghĩa là phải dùng PHP session cho API.
6. HTTP: 401/403/404/409/422 và mục đích từng mã.
7. JavaScript fetch, async/await, response.ok, xử lý lỗi và DOM an toàn.
8. Transaction, row lock, chuyển trạng thái, cập nhật số lượng đúng một lần.
9. OAuth là framework ủy quyền; JWT là định dạng token. Nghiên cứu cho báo cáo, không bắt buộc xây OAuth server.
10. Postman: token, biến môi trường base_url, kiểm tra response và chạy collection.

### J. Đầu ra và trách nhiệm tài liệu

- Bàn giao API/màn hình/hàm auth theo bảng sở hữu file.
- Viết OpenAPI phần auth/users/loans và bộ Postman của mình; gửi Người 2 để tổng hợp.
- Viết hướng dẫn tạo tài khoản mẫu, cấu hình JWT, thời hạn token, đăng xuất và các giới hạn bảo mật.
- Viết báo cáo phần JWT/phân quyền/stateless, nghiệp vụ mượn–trả, sequence luồng mượn, state diagram, kiểm thử và khó khăn xử lý đồng thời.
- Phản biện phần lý thuyết/so sánh của Người 2; kiểm tra file Word/PDF và thông tin bìa.
- Thuyết trình khoảng 8–9 phút trong video chung 18 phút: xác thực, quyền, luồng mượn–trả, lỗi và bằng chứng kiểm thử.
- Thực hiện thử cài đặt trên máy của mình theo README do Người 2 tổng hợp, ghi lại bước thiếu.
- Phần mở rộng tùy thời gian: hypermedia links theo trạng thái của loan và client thực sự sử dụng link; phối hợp Người 2 cập nhật tài liệu. Không chỉ thêm trường links rồi tuyên bố đã triển khai đầy đủ REST.

### K. Checklist nhận và bàn giao

Nhận từ Người 2:
- [ ] PDO, router, response helper, readJsonBody và API_BASE/apiFetch đúng hợp đồng.
- [ ] Schema đã ghép; sách/danh mục mẫu hợp lệ.
- [ ] Trang chi tiết sách sẵn chỗ gọi POST loans.
- [ ] Quy tắc sửa số lượng thống nhất với luồng mượn–trả.

Bàn giao cho Người 2:
- [ ] requireAuth/requireAdmin, tài khoản và token thử.
- [ ] Hợp đồng request/response loans, danh sách trạng thái và lỗi.
- [ ] Dữ liệu test có borrowed/returned/overdue để kiểm tra dashboard.
- [ ] OpenAPI, Postman, kết quả kiểm thử và ảnh minh chứng.
- [ ] Nội dung báo cáo/README và phần thuyết trình đã tập.

---


## Quy ước chung của nhóm

Đây là bản hướng dẫn triển khai cho nhóm hai người. Tên Người 1 và Người 2 là nhãn phân công, chưa phải tên thành viên. Mỗi người chịu trách nhiệm trọn phần của mình: PHP API, HTML, CSS, JavaScript, kiểm thử, nội dung báo cáo và thuyết trình. Phạm vi nghiệp vụ và các con số giới hạn dưới đây là đề xuất của nhóm dựa trên nội dung đã phân tích, không phải quy định bắt buộc do giảng viên đưa ra.

### 1. Mục tiêu và phạm vi

- Xây dựng thư viện sách giấy: độc giả gửi yêu cầu trên web; Admin kiêm thủ thư xác nhận giao và nhận lại sách thực tế.
- Stack: HTML, CSS, JavaScript thuần, PHP, MySQL/MariaDB; có thể chạy bằng XAMPP. Composer quản lý thư viện JWT.
- JavaScript gọi API PHP bằng fetch; API trao đổi JSON và kiểm tra quyền ở máy chủ.
- Frontend và Postman sử dụng cùng API.
- Phạm vi cốt lõi: tài khoản/JWT, danh mục, sách, tra cứu, độc giả, yêu cầu mượn, xác nhận giao/trả, lịch sử và thống kê cơ bản.
- Phần mở rộng thực hiện sau khi phần cốt lõi đạt kiểm thử: cache HTTP, hypermedia, đo lường.
- Chưa đưa vào phạm vi cơ sở: tiền phạt, thu tiền, gửi email, quét mã vạch, gia hạn, đặt giữ chỗ, quản lý từng bản sách bằng mã riêng. Những phần này chỉ thêm khi cả nhóm thống nhất đủ thời gian.

### 2. Phân công cân bằng

Điểm khối lượng là ước lượng tương đối để phân công, không phải điểm môn học hoặc số giờ cố định. Sau lần tích hợp đầu tiên, điều chỉnh theo công việc thực tế; mục tiêu 50/50.

| Người | Gói việc | Điểm khối lượng |
|---|---|---:|
| Người 1 | Tài khoản, JWT, kiểm tra quyền | 12 |
| Người 1 | Yêu cầu mượn, giao sách, trả sách, lịch sử và xử lý đồng thời | 22 |
| Người 1 | Quản lý độc giả và giao diện tương ứng | 6 |
| Người 1 | Kiểm thử, tài liệu API phần mình, báo cáo và video | 10 |
| Người 2 | Kết nối DB, router, cấu hình và các tiện ích dùng chung | 8 |
| Người 2 | Danh mục, sách, số lượng và giao diện quản trị | 16 |
| Người 2 | Tra cứu, trang công khai và bố cục chung | 8 |
| Người 2 | Dashboard và thống kê | 6 |
| Người 2 | Kiểm thử, tổng hợp tài liệu API, báo cáo và video | 12 |

Cả hai đều viết đủ HTML/CSS/JS/PHP. Không giao toàn bộ code cho một người và toàn bộ báo cáo cho người còn lại. Người 2 tổng hợp tài liệu; Người 1 vẫn phải nộp tài liệu và kết quả kiểm thử phần mình hoàn chỉnh.

### 3. Vai trò và quyền

| Khả năng | Guest | Member | Admin kiêm thủ thư |
|---|---|---|---|
| Xem sách đang cho mượn, tra cứu, xem danh mục | Có | Có | Có |
| Đăng ký | Có, luôn tạo Member | Không cần | Không cần |
| Xem thông tin cá nhân | Không | Của mình | Của mình |
| Gửi yêu cầu mượn | Không | Có | Không thuộc luồng cơ sở |
| Hủy yêu cầu đang chờ | Không | Chỉ của mình | Dùng thao tác từ chối thay vì hủy hộ |
| Xem lịch sử mượn | Không | Chỉ của mình | Tất cả |
| Giao sách, xác nhận trả, từ chối | Không | Không | Có |
| Quản lý sách, danh mục, độc giả, thống kê | Không | Không | Có |

Ẩn nút trên giao diện chỉ hỗ trợ trải nghiệm. Mọi yêu cầu đều phải được PHP xác thực và kiểm tra quyền. user_id của yêu cầu mượn lấy từ người đã xác thực, không lấy theo giá trị tùy ý do trình duyệt gửi.

### 4. Quy tắc nghiệp vụ đã chọn cho bản cơ sở

1. Một bản ghi loans tương ứng một yêu cầu mượn một bản của một đầu sách.
2. Một độc giả có tối đa 3 lượt đang mượn. Kiểm tra khi gửi yêu cầu và kiểm tra lại lúc giao sách. Yêu cầu đang chờ không giữ chỗ và không làm giảm tồn.
3. Một độc giả không được có đồng thời hai yêu cầu pending/borrowed cho cùng đầu sách.
4. Hạn trả là 14 ngày tính theo ngày xác nhận giao sách. Ngày hạn trả vẫn được xem là đúng hạn.
5. Chỉ Member đang hoạt động được gửi yêu cầu. Chỉ sách đang hoạt động và còn bản mới được yêu cầu.
6. Khi Admin giao sách, kiểm tra lại tài khoản hoạt động, sách hoạt động, số lượng còn và giới hạn mượn.
7. Nếu hết sách trước khi xử lý, không được tạo tồn âm. API trả xung đột, giữ yêu cầu pending để Admin xem xét hoặc từ chối.
8. Độc giả chỉ hủy được yêu cầu pending của mình.
9. Chỉ Admin xác nhận giao và trả sách. Member mang sách đến trả thực tế.
10. Mượn thành công giảm available_copies đúng một; trả thành công tăng đúng một.
11. Từ chối/hủy pending không thay đổi số lượng sách.
12. Lượt đã trả không được trả lần hai. Không xóa lịch sử mượn.
13. Tài khoản bị khóa không được đăng nhập/gọi API cá nhân; Admin vẫn được nhận lại sách của tài khoản đó.
14. Sách từng xuất hiện trong loans không xóa cứng; chuyển is_active=false để ngừng cho mượn mới.
15. Danh mục có sách không xóa. Đổi danh mục cho sách trước nếu muốn xóa danh mục.
16. Giảm tổng số bản không được làm tổng thấp hơn số bản đang mượn.
17. Bản cơ sở chỉ gắn nhãn quá hạn, chưa tự động cấm người quá hạn mượn tiếp và chưa tính tiền phạt.
18. Các giới hạn 3 và 14 đặt trong cấu hình chung, không rải số cố định khắp code.
19. Mọi ngày mượn, hạn trả, ngày trả và người thực hiện đều do server xác định.
20. Thống nhất múi giờ ứng dụng Asia/Ho_Chi_Minh; thời gian DB dùng cùng quy ước +07:00 trong môi trường demo. Không trộn thời gian UTC và giờ địa phương tùy chỗ.

### 5. Trạng thái và chuyển trạng thái

| Trạng thái DB | Nhãn UI | Chuyển tiếp hợp lệ | Người thực hiện |
|---|---|---|---|
| pending | Chờ xử lý | borrowed | Admin khi thực tế giao sách |
| pending | Chờ xử lý | rejected | Admin, có lý do |
| pending | Chờ xử lý | cancelled | Member sở hữu yêu cầu |
| borrowed | Đang mượn | returned | Admin khi thực tế nhận sách |
| returned | Đã trả | Không có trong bản cơ sở | — |
| rejected | Bị từ chối | Không có trong bản cơ sở | — |
| cancelled | Đã hủy | Không có trong bản cơ sở | — |

Quá hạn được tính: status=borrowed và ngày hiện tại lớn hơn due_date. Không lưu overdue thành trạng thái để tránh phải đồng bộ trạng thái mỗi đêm. Lịch sử đã trả có thể hiển thị trả trễ bằng cách so ngày returned_at với due_date.

### 6. Hợp đồng dữ liệu chung

Người 2 tổng hợp database/schema.sql; Người 1 thiết kế và bàn giao phần users/loans. Hai người duyệt sơ đồ quan hệ trước khi viết API. Dùng InnoDB, utf8mb4 và khóa ngoại.

| Bảng | Trường tối thiểu | Ràng buộc chính |
|---|---|---|
| users | id, full_name, email, password_hash, role, is_active, created_at | email duy nhất; role chỉ admin/member; không trả password_hash qua API |
| categories | id, name, description | tên duy nhất theo quy tắc so sánh đã thống nhất |
| books | id, category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active, created_at, updated_at | category_id tồn tại; 0 <= available_copies <= total_copies |
| loans | id, user_id, book_id, status, requested_at, borrowed_at, due_date, returned_at, approved_by, returned_by, rejection_reason | user/book tồn tại; người xử lý tham chiếu users; ngày chưa phát sinh để NULL |

- users 1–n loans; books 1–n loans; categories 1–n books.
- approved_by và returned_by là Admin thực hiện, không phải độc giả.
- Không cascade delete làm mất lịch sử. Khóa tài khoản thay vì xóa.
- Index nên có: users.email (unique), books.category_id, loans(user_id,status), loans(book_id,status), loans(status,due_date). Chỉ thêm index khác khi có lý do.
- Với dữ liệu hợp lệ: total_copies - available_copies bằng số loans có status=borrowed của đầu sách đó.
- Khi sửa tổng số bản: delta = tổng mới - tổng cũ; số còn mới = số còn cũ + delta. Chỉ chấp nhận khi số còn mới không âm.
- Trình duyệt không được gửi available_copies để ép server cập nhật trực tiếp.
- Dữ liệu mẫu đề xuất: 1 Admin, ít nhất 2 Member, 4 danh mục, khoảng 20 đầu sách; có sách hết bản, sách ngừng cho mượn và lịch sử ở nhiều trạng thái.
- Tài khoản demo ghi trong README; mật khẩu trong DB phải là hash. Khóa bí mật JWT thật nằm ngoài mã nguồn công khai.

### 7. Quy ước API và tiện ích để ghép code

Base URL API có hậu tố /api/v1. Tiền tố thư mục XAMPP do cấu hình chung quyết định; không hardcode đường dẫn máy cá nhân trong từng module. Frontend và API chạy cùng origin trong bản cơ sở.

| Hạng mục | Quy ước |
|---|---|
| Tên trường, đường dẫn, trạng thái | Tiếng Anh theo bảng dữ liệu; comment và tài liệu học có thể tiếng Việt |
| JSON thành công | {"success":true,"data":...,"message":"..."} |
| JSON thất bại | {"success":false,"error":{"code":"...","message":"...","fields":{}}} |
| Danh sách phân trang | data là mảng; thêm meta gồm page, limit, total, total_pages |
| DELETE thành công | 204 và không có body; JS không gọi response.json() khi 204 |
| JSON gửi lên | Content-Type: application/json |
| Xác thực | Authorization: Bearer JWT_TOKEN |
| Người dùng hiện tại | Lấy từ token đã xác minh và kiểm tra users hiện tại |
| Quyền không đủ | 403; lượt mượn thuộc người khác trả 404 để không lộ sự tồn tại |
| Lỗi xác thực | 401; kèm WWW-Authenticate phù hợp |
| Các lỗi khác | 400 JSON hỏng; 404 không tồn tại; 405 phương thức không hỗ trợ kèm Allow; 409 xung đột; 415 sai kiểu nội dung; 422 dữ liệu hợp lệ về JSON nhưng sai giá trị; 500 lỗi nội bộ không lộ SQL |
| Tạo tài nguyên | 201 và Location đến tài nguyên mới |
| PUT | Gửi đầy đủ các trường có thể sửa theo hợp đồng; không dùng để thay ID/lịch sử |
| PATCH loans | Chỉ chuyển trạng thái đã định nghĩa, không cho sửa tự do user_id/book_id/ngày |

Hợp đồng các hàm dùng chung phải được hai người thống nhất ở mốc đầu:
- Người 2: getConnection() trả PDO; sendJson(statusCode, payload); readJsonBody(); router; apiFetch(path, options) và API_BASE ở JS.
- Người 1: requireAuth() trả thông tin người dùng hiện tại hoặc dừng bằng lỗi; requireAdmin() chỉ cho Admin.
- apiFetch phải kiểm tra response.ok, xử lý 204, lỗi mạng và 401. Với 403/404/409/422, giữ thông báo đúng thay vì coi mọi lỗi là hết phiên.
- Token phải được kiểm tra ở PHP; dữ liệu decode chưa xác minh không được dùng để quyết định quyền.
- Các response riêng tư, đăng nhập và dữ liệu mượn đặt Cache-Control: no-store.
- PDO prepared statements cho dữ liệu nhập. Các tên cột sắp xếp dùng allowlist, không nối tùy ý từ người dùng.
- Dùng textContent để hiển thị chuỗi người dùng nhập. Không đưa chuỗi chưa xử lý vào innerHTML.
- PHP tách phần API trả JSON khỏi trang HTML; tránh warning hoặc HTML lẫn vào JSON.

### 8. Thư mục và quyền sửa file

Đây là cây dự kiến để triển khai; hai file hướng dẫn không kèm mã nguồn ứng dụng.

| Nhóm file | Chủ trì | Ghi chú |
|---|---|---|
| config/, database/schema.sql, database/seed.sql, router và .htaccess | Người 2 | Schema users/loans do Người 1 gửi để ghép |
| api/common/db.php, response.php, request.php | Người 2 | Thay chữ ký hàm phải báo Người 1 |
| api/common/auth.php; api/modules/auth/, users/, loans/ | Người 1 | Auth dùng chung cho cả dự án |
| api/modules/categories/, books/, statistics/ | Người 2 | Dùng auth của Người 1 cho thao tác quản trị |
| public/login.html, register.html, profile.html, my-loans.html | Người 1 | Có JS và CSS tương ứng |
| public/admin/users.html, loans.html | Người 1 | Có JS và CSS tương ứng |
| public/index.html, book-detail.html | Người 2 | Trang công khai |
| public/admin/books.html, categories.html, dashboard.html | Người 2 | Trang quản trị |
| public/assets/js/api.js, public/assets/css/common.css | Người 2 | Tiện ích gọi API và bố cục chung |
| docs/openapi-auth-loans.yaml, tests/postman/member1-* | Người 1 | Tài liệu/kiểm thử phần mình |
| docs/openapi-catalog.yaml, tests/postman/member2-* | Người 2 | Tài liệu/kiểm thử phần mình |
| docs/openapi.yaml, README.md | Người 2 tổng hợp | Người 1 gửi đủ nội dung auth/mượn–trả |
| report/, slides/, video/ | Cả hai | Mỗi người viết và trình bày phần mình |

Không sửa file của người kia âm thầm. Nếu cần thay đổi hợp đồng chung, ghi rõ thay đổi và cập nhật cả hai phần.

### 9. Cách phối hợp theo mốc

1. Mốc 0: cả hai duyệt schema, trạng thái, giới hạn 3/14, hợp đồng JSON, chữ ký hàm và route.
2. Mốc 1: Người 2 bàn giao kết nối DB/router/JSON helper/apiFetch; Người 1 bàn giao đăng nhập và requireAuth/requireAdmin. Mỗi người có thể dùng dữ liệu giả trong lúc chờ, nhưng không nộp bản mock làm sản phẩm cuối.
3. Mốc 2: Người 2 hoàn thành danh mục/sách; Người 1 hoàn thành tài khoản/độc giả. Thử đăng nhập Admin để sửa sách và Member để bị từ chối.
4. Mốc 3: Người 1 hoàn thành yêu cầu mượn/giao/trả; Người 2 gắn nút mượn ở chi tiết sách và dashboard đọc dữ liệu thật.
5. Mốc 4: hai người kiểm thử xuyên suốt, kiểm thử đồng thời và các lỗi quyền.
6. Mốc 5: hoàn thiện Postman/OpenAPI/README; chạy trên máy người còn lại bằng hướng dẫn.
7. Mốc 6: viết hoàn chỉnh báo cáo, tập thuyết trình, quay video và kiểm tra bài nộp.
8. Mở rộng chỉ bắt đầu sau mốc 4 đạt. Đây là thứ tự phụ thuộc, không giả định nhóm còn đủ 8 tuần kể từ hôm nay.

Git: mỗi người có nhánh làm việc riêng; commit theo chức năng; tích hợp phần nhỏ thường xuyên. Người 2 có thể ghép bản phát hành đầu, Người 1 ghép bản tiếp theo để chia trách nhiệm. Trước khi tích hợp, người còn lại đọc thay đổi và chạy các test liên quan. Không commit mật khẩu thật, khóa JWT hay token.

### 10. Định nghĩa hoàn thành

Một chức năng chỉ hoàn thành khi:
- Có API xử lý đúng dữ liệu và quyền, không chỉ có màn hình.
- Có giao diện HTML/CSS và JS gọi API thật.
- Có tình huống thành công, dữ liệu sai, chưa đăng nhập và sai quyền nếu áp dụng.
- Có bảng test ghi đầu vào, kết quả mong đợi, kết quả thực tế và đạt/không đạt.
- Có tài liệu request/response và ảnh minh chứng phù hợp.
- Không còn lỗi console/PHP phá vỡ luồng đang kiểm tra.
- Người phụ trách giải thích được request đi qua những file nào và thay đổi dữ liệu gì.

### 11. Đối chiếu đề và bài nộp

Nguồn đề: 503073-Midterm_Vietnamese.docx đã được đọc trong cuộc trao đổi, các mục I–V. Các gợi ý REST gồm quản lý dữ liệu bằng API, JWT, frontend hoặc Postman; nhóm chọn trình diễn cả frontend và Postman.

| Yêu cầu/tiêu chí | Trách nhiệm |
|---|---|
| Lý thuyết 2 điểm | Người 2 chủ trì lịch sử, ràng buộc REST, HTTP, biểu diễn tài nguyên; Người 1 chủ trì stateless, JWT, OAuth và phân quyền; cùng phản biện |
| So sánh 1 điểm | Người 2 chủ trì REST/GraphQL/SOAP trên cùng bài toán; Người 1 bổ sung xác thực, ủy quyền, giới hạn bảo mật và nhận xét |
| Áp dụng và triển khai 1 điểm | Mỗi người mô tả kiến trúc và phần code của mình, cùng ghép luồng tổng thể |
| Cấu trúc báo cáo 2 điểm | Người 2 ghép định dạng; Người 1 kiểm tra thông tin, trích dẫn, hình và tính nhất quán; cả hai duyệt |
| Phạm vi demo 2 điểm | Hai người chịu trách nhiệm các module và luồng tích hợp |
| Minh họa 1 điểm | Mỗi người chụp bằng chứng phần mình và giải thích request/response |
| Video/thuyết trình 1 điểm | Mỗi người trình bày khoảng nửa thời lượng, có lý thuyết lẫn demo |

- Báo cáo tiếng Anh, Word và PDF, 20–60 trang không tính bìa/phụ lục, theo mẫu Khoa.
- Cần đối chiếu Report templates and guidelines.zip trước khi chốt font, lề, trang đầu và phụ lục; tệp mẫu chưa được cung cấp trong cuộc trao đổi.
- Báo cáo có mở đầu, nghiên cứu, so sánh, yêu cầu, thiết kế, triển khai/đánh giá, kết luận, tài liệu tham khảo và phụ lục phù hợp. Giải thích hệ thống, không chép dài toàn bộ code.
- Mã nguồn đầy đủ; composer.json và composer.lock; SQL; cấu hình mẫu; README có môi trường đã kiểm thử, lệnh cài/chạy, tài khoản demo và hướng dẫn kiểm thử.
- Video tiếng Anh tối đa 20 phút, có âm thanh và cả hai thành viên. Đề quy định demo 0 điểm nếu thiếu video có âm thanh hoặc thiếu mã nguồn hoàn chỉnh có hướng dẫn thiết lập.
- Chuẩn bị slide để cả hai trình bày trên lớp tuần 9–10 theo lịch môn học.
- Đề giao thời gian 8 tuần; hạn cụ thể theo lớp. Nộp trễ trừ 1 điểm/ngày, trễ một giây cũng tính một ngày.
- Nhóm hai người đáp ứng quy mô 2–3. Phân công không đều, thiếu thành viên trong video, sai ngôn ngữ, video kém hoặc thiếu thông tin có thể bị trừ điểm theo đề.
- Hoàn thành danh sách không bảo đảm điểm tuyệt đối. Các phần mở rộng phải có bằng chứng và giải thích đúng.

### 12. Nguồn kiến thức chính thức

Đọc các phần liên quan khi thực hiện; đưa nguồn thực sự sử dụng vào tài liệu tham khảo:
- REST và các ràng buộc: https://ics.uci.edu/~fielding/pubs/dissertation/rest_arch_style.htm
- HTTP methods/status: https://www.rfc-editor.org/rfc/rfc9110.html
- JWT: https://www.rfc-editor.org/rfc/rfc7519.html
- Thư viện PHP JWT: https://github.com/firebase/php-jwt
- OAuth 2.0 (khái niệm ủy quyền): https://www.rfc-editor.org/rfc/rfc6749.html
- PDO prepared statements: https://www.php.net/manual/en/pdo.prepared-statements.php
- Mật khẩu PHP: https://www.php.net/manual/en/function.password-hash.php
- JavaScript fetch: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
- OpenAPI: https://learn.openapis.org/introduction.html
- GraphQL: https://spec.graphql.org/
- SOAP: https://www.w3.org/TR/soap12-part1/
- HTTP cache: https://www.rfc-editor.org/rfc/rfc9111.html

