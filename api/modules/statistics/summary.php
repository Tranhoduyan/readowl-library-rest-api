<?php

// Thong ke tong quan he thong cho trang Admin Dashboard.
$currentAdmin = requireAdmin();
$pdo = getConnection();

$usersStats = $pdo->query(
    "SELECT
        COUNT(*) AS total_members,
        COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active_members,
        COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) AS locked_members
     FROM users
     WHERE role = 'member'"
)->fetch();

$booksStats = $pdo->query(
    "SELECT
        COUNT(*) AS total_titles,
        COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active_titles,
        COALESCE(SUM(total_copies), 0) AS total_copies,
        COALESCE(SUM(available_copies), 0) AS available_copies
     FROM books"
)->fetch();

$categoriesCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

$loansStats = $pdo->query(
    "SELECT
        COUNT(*) AS total_loans,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_loans,
        COALESCE(SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END), 0) AS borrowed_loans,
        COALESCE(SUM(CASE WHEN status = 'borrowed' AND due_date IS NOT NULL AND due_date < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue_loans,
        COALESCE(SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END), 0) AS returned_loans,
        COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_loans,
        COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_loans
     FROM loans"
)->fetch();

$topBooks = $pdo->query(
    "SELECT
        b.id,
        b.title,
        b.author,
        c.name AS category_name,
        b.total_copies,
        b.available_copies,
        COUNT(l.id) AS borrow_count
     FROM books b
     INNER JOIN categories c ON c.id = b.category_id
     LEFT JOIN loans l ON l.book_id = b.id AND l.status IN ('borrowed', 'returned')
     GROUP BY b.id, b.title, b.author, c.name, b.total_copies, b.available_copies
     ORDER BY borrow_count DESC, b.id ASC
     LIMIT 5"
)->fetchAll();

foreach ($topBooks as &$tb) {
    $tb['id'] = (int) $tb['id'];
    $tb['total_copies'] = (int) $tb['total_copies'];
    $tb['available_copies'] = (int) $tb['available_copies'];
    $tb['borrow_count'] = (int) $tb['borrow_count'];
}
unset($tb);

sendJson(200, [
    'success' => true,
    'data' => [
        'users' => [
            'total_members' => (int) ($usersStats['total_members'] ?? 0),
            'active_members' => (int) ($usersStats['active_members'] ?? 0),
            'locked_members' => (int) ($usersStats['locked_members'] ?? 0)
        ],
        'books' => [
            'total_titles' => (int) ($booksStats['total_titles'] ?? 0),
            'active_titles' => (int) ($booksStats['active_titles'] ?? 0),
            'total_copies' => (int) ($booksStats['total_copies'] ?? 0),
            'available_copies' => (int) ($booksStats['available_copies'] ?? 0)
        ],
        'categories' => [
            'total_categories' => $categoriesCount
        ],
        'loans' => [
            'total_loans' => (int) ($loansStats['total_loans'] ?? 0),
            'pending_loans' => (int) ($loansStats['pending_loans'] ?? 0),
            'borrowed_loans' => (int) ($loansStats['borrowed_loans'] ?? 0),
            'overdue_loans' => (int) ($loansStats['overdue_loans'] ?? 0),
            'returned_loans' => (int) ($loansStats['returned_loans'] ?? 0),
            'rejected_loans' => (int) ($loansStats['rejected_loans'] ?? 0),
            'cancelled_loans' => (int) ($loansStats['cancelled_loans'] ?? 0)
        ],
        'top_books' => $topBooks
    ],
    'message' => 'Lấy thống kê tổng quan hệ thống thành công.'
]);
