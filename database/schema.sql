-- He thong thu vien muon-tra sach su dung REST API
-- Database chung cua nhom hai nguoi.
-- File nay tao cau truc; du lieu mau se nam trong seed.sql.

CREATE DATABASE IF NOT EXISTS library_rest
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE library_rest;
SET time_zone = '+07:00';

-- 1. Tai khoan: Admin kiem thu thu va Member (doc gia).
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_users_active CHECK (is_active IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Danh muc sach.
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Moi dong la mot dau sach, co the co nhieu ban giong nhau.
CREATE TABLE IF NOT EXISTS books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(200) NOT NULL,
    publisher VARCHAR(200) NULL,
    publication_year SMALLINT UNSIGNED NULL,
    description TEXT NULL,
    cover_path VARCHAR(255) NULL,
    total_copies INT UNSIGNED NOT NULL DEFAULT 0,
    available_copies INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_books_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,

    CONSTRAINT chk_books_copies
        CHECK (available_copies <= total_copies),
    CONSTRAINT chk_books_active
        CHECK (is_active IN (0, 1)),
    CONSTRAINT chk_books_year
        CHECK (publication_year IS NULL
            OR publication_year BETWEEN 1000 AND 2100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Moi dong la mot yeu cau muon mot ban cua mot dau sach.
-- pending: cho xu ly; borrowed: dang muon; returned: da tra;
-- rejected: bi tu choi; cancelled: da huy.
-- Qua han duoc tinh tu status va due_date, khong luu thanh status rieng.
CREATE TABLE IF NOT EXISTS loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'borrowed', 'returned', 'rejected', 'cancelled')
        NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    borrowed_at DATETIME NULL,
    due_date DATE NULL,
    returned_at DATETIME NULL,
    approved_by INT UNSIGNED NULL,
    returned_by INT UNSIGNED NULL,
    rejection_reason VARCHAR(500) NULL,

    INDEX idx_loans_user_status (user_id, status),
    INDEX idx_loans_book_status (book_id, status),
    INDEX idx_loans_status_due (status, due_date),

    CONSTRAINT fk_loans_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,

    CONSTRAINT fk_loans_book
        FOREIGN KEY (book_id) REFERENCES books(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,

    CONSTRAINT fk_loans_approved_by
        FOREIGN KEY (approved_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT,

    CONSTRAINT fk_loans_returned_by
        FOREIGN KEY (returned_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PHP API se kiem tra quyen, gioi han 3 luot dang muon, han 14 ngay,
-- yeu cau trung va cac chuyen trang thai; khong tu dong co nho schema.
-- Giao/tra sach phai cap nhat loans va books trong cung transaction.
