-- DU LIEU MAU VA SUA DU LIEU MAU CU - library_rest
-- Nhap schema.sql truoc. Dung web server PHP truoc khi nhap file nay.
-- Nen xuat ban sao luu CSDL hien tai trong phpMyAdmin truoc khi nhap.
-- CSDL rong: tao 6 tai khoan, 4 danh muc, 9 dau sach, 4 phieu muon.
-- CSDL da co du lieu: KHONG them tai khoan/sach/phieu muon mau.
-- Chi sua hash mau sai da biet va tinh lai so ban con theo phieu borrowed.
-- Khong xoa lich su, khong doi tong so ban, vai tro hay trang thai tai khoan.
-- Admin: admin@example.com / Admin12345
-- Member: an@example.com, chau.le@example.com, anh.hoang@example.com,
--         khoi.vo@example.com / ThuVien123
-- bao.pham@example.com co mat khau ThuVien123 nhung dang bi khoa.

USE library_rest;
SET NAMES utf8mb4;
SET time_zone = '+07:00';
START TRANSACTION;

-- Chi tao du lieu minh hoa khi CA BON bang deu rong.
SET @seed_new = (
    (SELECT COUNT(*) FROM users) = 0
    AND (SELECT COUNT(*) FROM categories) = 0
    AND (SELECT COUNT(*) FROM books) = 0
    AND (SELECT COUNT(*) FROM loans) = 0
);

-- 1. Tao tai khoan mau, de CSDL tu cap ID.

INSERT INTO users (full_name, email, password_hash, role, is_active)
SELECT 'Quản Trị Viên Thư Viện', 'admin@example.com', '$2y$10$7CCwARfXIoietbE1ivGFUuUPbvRi6dkHGUKqzSLdAmHjh.7PruzbO', 'admin', 1
WHERE @seed_new = 1;

INSERT INTO users (full_name, email, password_hash, role, is_active)
SELECT 'Trần Hồ Duy An', 'an@example.com', '$2y$10$jcPls5eHDQ7iEHai1pAp4Oj1v0ZmoJG6TagUgqw/BdIaW5WTlWn..', 'member', 1
WHERE @seed_new = 1;

INSERT INTO users (full_name, email, password_hash, role, is_active)
SELECT 'Lê Thị Minh Châu', 'chau.le@example.com', '$2y$10$jcPls5eHDQ7iEHai1pAp4Oj1v0ZmoJG6TagUgqw/BdIaW5WTlWn..', 'member', 1
WHERE @seed_new = 1;

INSERT INTO users (full_name, email, password_hash, role, is_active)
SELECT 'Phạm Quốc Bảo', 'bao.pham@example.com', '$2y$10$jcPls5eHDQ7iEHai1pAp4Oj1v0ZmoJG6TagUgqw/BdIaW5WTlWn..', 'member', 0
WHERE @seed_new = 1;

INSERT INTO users (full_name, email, password_hash, role, is_active)
SELECT 'Hoàng Ngọc Ánh', 'anh.hoang@example.com', '$2y$10$jcPls5eHDQ7iEHai1pAp4Oj1v0ZmoJG6TagUgqw/BdIaW5WTlWn..', 'member', 1
WHERE @seed_new = 1;

INSERT INTO users (full_name, email, password_hash, role, is_active)
SELECT 'Võ Minh Khôi', 'khoi.vo@example.com', '$2y$10$jcPls5eHDQ7iEHai1pAp4Oj1v0ZmoJG6TagUgqw/BdIaW5WTlWn..', 'member', 1
WHERE @seed_new = 1;

-- 2. Tao danh muc mau.

INSERT INTO categories (name, description)
SELECT 'Công nghệ & Lập trình', 'Sách kỹ thuật phần mềm, web, trí tuệ nhân tạo và kiến trúc hệ thống.'
WHERE @seed_new = 1;

INSERT INTO categories (name, description)
SELECT 'Thiết kế & UX/UI', 'Tư duy thiết kế sản phẩm, trải nghiệm người dùng và đồ họa ứng dụng.'
WHERE @seed_new = 1;

INSERT INTO categories (name, description)
SELECT 'Văn học & Tiểu thuyết', 'Tác phẩm văn học kinh điển và tiểu thuyết hiện đại.'
WHERE @seed_new = 1;

INSERT INTO categories (name, description)
SELECT 'Kỹ năng & Kinh doanh', 'Phát triển bản thân, quản trị dự án và khởi nghiệp.'
WHERE @seed_new = 1;

SET @cat_1 = (SELECT id FROM categories WHERE name = 'Công nghệ & Lập trình' LIMIT 1);
SET @cat_2 = (SELECT id FROM categories WHERE name = 'Thiết kế & UX/UI' LIMIT 1);
SET @cat_3 = (SELECT id FROM categories WHERE name = 'Văn học & Tiểu thuyết' LIMIT 1);
SET @cat_4 = (SELECT id FROM categories WHERE name = 'Kỹ năng & Kinh doanh' LIMIT 1);

-- 3. Tao sach voi so ban con bang tong. Muc 6 se tru dung so phieu borrowed.

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_1, 'RESTful Web APIs: Services for a Changing World', 'Leonard Richardson & Mike Amundsen', 'O''Reilly Media', 2013, 'Hướng dẫn thiết kế kiến trúc REST API chuẩn mực, hypermedia và HTTP.', 'linear-gradient(135deg, #0ea5e9 0%, #1e3a8a 100%)', 5, 5, 1
WHERE @seed_new = 1;

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_2, 'The Design of Everyday Things', 'Don Norman', 'Basic Books', 2013, 'Cuốn sách gối đầu giường về tư duy thiết kế lấy người dùng làm trung tâm.', 'linear-gradient(135deg, #facc15 0%, #ca8a04 100%)', 4, 4, 1
WHERE @seed_new = 1;

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_2, 'Strategic Writing for UX', 'Torrey Podmajersky', 'O''Reilly Media', 2019, 'Nghệ thuật viết nội dung giao diện giúp tăng tương tác và độ rõ ràng.', 'linear-gradient(135deg, #475569 0%, #0f172a 100%)', 3, 3, 1
WHERE @seed_new = 1;

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_1, 'Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', 'Prentice Hall', 2008, 'Nguyên tắc viết mã sạch, dễ bảo trì và mở rộng trong phát triển phần mềm.', 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)', 6, 6, 1
WHERE @seed_new = 1;

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_1, 'Designing Data-Intensive Applications', 'Martin Kleppmann', 'O''Reilly Media', 2017, 'Kiến trúc hệ thống dữ liệu lớn, xử lý đồng thời, transaction và độ tin cậy.', 'linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%)', 4, 4, 1
WHERE @seed_new = 1;

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_3, 'The Lord of the Rings', 'J.R.R. Tolkien', 'Allen & Unwin', 1954, 'Bộ sử thi kỳ ảo kinh điển của nền văn học thế giới.', 'linear-gradient(135deg, #1e293b 0%, #090d16 100%)', 3, 3, 1
WHERE @seed_new = 1;

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_3, '1984 — Nineteen Eighty-Four', 'George Orwell', 'Secker & Warburg', 1949, 'Tiểu thuyết kinh điển đầy suy ngẫm về xã hội và con người.', 'linear-gradient(135deg, #64748b 0%, #334155 100%)', 5, 5, 1
WHERE @seed_new = 1;

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_4, 'Steve Jobs: The Exclusive Biography', 'Walter Isaacson', 'Simon & Schuster', 2011, 'Tiểu sử cuộc đời và triết lý sáng tạo sản phẩm của Steve Jobs.', 'linear-gradient(135deg, #059669 0%, #064e3b 100%)', 4, 4, 1
WHERE @seed_new = 1;

INSERT INTO books (category_id, title, author, publisher, publication_year, description, cover_path, total_copies, available_copies, is_active)
SELECT @cat_2, 'Logo Design Love: A Guide to Creating Iconic Brand Identities', 'David Airey', 'New Riders', 2014, 'Cẩm nang thiết kế nhận diện thương hiệu và biểu trưng chuyên nghiệp.', 'linear-gradient(135deg, #ea580c 0%, #9a3412 100%)', 3, 3, 1
WHERE @seed_new = 1;

-- Lay ID theo du lieu, khong gia dinh ID luon la 1, 2, 3...

SET @admin_id = (SELECT id FROM users WHERE email = 'admin@example.com' LIMIT 1);
SET @an_id = (SELECT id FROM users WHERE email = 'an@example.com' LIMIT 1);
SET @chau_id = (SELECT id FROM users WHERE email = 'chau.le@example.com' LIMIT 1);
SET @anh_id = (SELECT id FROM users WHERE email = 'anh.hoang@example.com' LIMIT 1);
SET @book_1 = (SELECT id FROM books WHERE title = 'RESTful Web APIs: Services for a Changing World' AND author = 'Leonard Richardson & Mike Amundsen' ORDER BY id LIMIT 1);
SET @book_2 = (SELECT id FROM books WHERE title = 'The Design of Everyday Things' AND author = 'Don Norman' ORDER BY id LIMIT 1);
SET @book_3 = (SELECT id FROM books WHERE title = 'Strategic Writing for UX' AND author = 'Torrey Podmajersky' ORDER BY id LIMIT 1);
SET @book_4 = (SELECT id FROM books WHERE title = 'Clean Code: A Handbook of Agile Software Craftsmanship' AND author = 'Robert C. Martin' ORDER BY id LIMIT 1);

-- 4. Phieu muon minh hoa chi tao tren CSDL rong, khong gan phieu gia cho doc gia cu.
INSERT INTO loans (user_id, book_id, status, requested_at, borrowed_at, due_date, approved_by)
SELECT @an_id, @book_1, 'borrowed', DATE_SUB(NOW(), INTERVAL 4 DAY),
       DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), @admin_id
WHERE @seed_new = 1;

INSERT INTO loans (user_id, book_id, status, requested_at)
SELECT @an_id, @book_2, 'pending', DATE_SUB(NOW(), INTERVAL 1 DAY)
WHERE @seed_new = 1;

INSERT INTO loans (user_id, book_id, status, requested_at, borrowed_at, due_date, approved_by)
SELECT @chau_id, @book_3, 'borrowed', DATE_SUB(NOW(), INTERVAL 18 DAY),
       DATE_SUB(NOW(), INTERVAL 17 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), @admin_id
WHERE @seed_new = 1;

INSERT INTO loans (user_id, book_id, status, requested_at, borrowed_at, due_date, returned_at, approved_by, returned_by)
SELECT @anh_id, @book_4, 'returned', DATE_SUB(NOW(), INTERVAL 20 DAY),
       DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY),
       DATE_SUB(NOW(), INTERVAL 7 DAY), @admin_id, @admin_id
WHERE @seed_new = 1;

-- 5. Sua dung hash mau cu bi sai (hash cua "password").
-- Tai khoan da doi mat khau, khac email/role mau se duoc giu nguyen.
SET @old_sample_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

UPDATE users
SET password_hash = '$2y$10$7CCwARfXIoietbE1ivGFUuUPbvRi6dkHGUKqzSLdAmHjh.7PruzbO'
WHERE email = 'admin@example.com' AND role = 'admin'
  AND BINARY password_hash = BINARY @old_sample_hash;

UPDATE users
SET password_hash = '$2y$10$jcPls5eHDQ7iEHai1pAp4Oj1v0ZmoJG6TagUgqw/BdIaW5WTlWn..'
WHERE role = 'member'
  AND email IN ('an@example.com', 'chau.le@example.com', 'bao.pham@example.com',
                'anh.hoang@example.com', 'khoi.vo@example.com')
  AND BINARY password_hash = BINARY @old_sample_hash;

-- 6. Doi chieu ton kho theo quy tac cua du an:
-- So ban con = tong so ban - so phieu DANG MUON; pending khong giu cho.
-- Khong tu tang tong so ban neu so dang muon da vuot tong; bao o muc 7.
UPDATE books
SET available_copies = CAST(total_copies AS SIGNED) - (
    SELECT COUNT(*) FROM loans
    WHERE loans.book_id = books.id AND loans.status = 'borrowed'
)
WHERE total_copies >= (
    SELECT COUNT(*) FROM loans
    WHERE loans.book_id = books.id AND loans.status = 'borrowed'
)
AND available_copies <> CAST(total_copies AS SIGNED) - (
    SELECT COUNT(*) FROM loans
    WHERE loans.book_id = books.id AND loans.status = 'borrowed'
);

COMMIT;

-- 7. Kiem tra: ket qua rong nghia la ton kho da khop.
-- Neu con dong du lieu, can xem lai tong so ban va phieu muon cua sach do.
SELECT b.id, b.title, b.total_copies, b.available_copies,
       (SELECT COUNT(*) FROM loans l
        WHERE l.book_id = b.id AND l.status = 'borrowed') AS borrowed_count
FROM books b
WHERE b.total_copies - b.available_copies <> (
    SELECT COUNT(*) FROM loans l
    WHERE l.book_id = b.id AND l.status = 'borrowed'
);
