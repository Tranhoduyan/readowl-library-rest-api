<?php

// Danh sach dau sach & danh muc phuc vu trang Thu vien doc gia & Quan tri kho sach.
$pdo = getConnection();

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$categoryId = isset($_GET['category_id']) && ctype_digit((string) $_GET['category_id'])
    ? (int) $_GET['category_id']
    : 0;
$includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] === '1';

$categoriesStmt = $pdo->query('SELECT id, name, description FROM categories ORDER BY id ASC');
$categories = $categoriesStmt->fetchAll();

$where = [];
if (!$includeInactive) {
    $where[] = 'b.is_active = 1';
}
$params = [];

if ($q !== '') {
    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
    $where[] = "(b.title LIKE :q_title ESCAPE '\\\\' OR b.author LIKE :q_author ESCAPE '\\\\')";
    $params['q_title'] = '%' . $escaped . '%';
    $params['q_author'] = '%' . $escaped . '%';
}

if ($categoryId > 0) {
    $where[] = 'b.category_id = :cat_id';
    $params['cat_id'] = $categoryId;
}

$whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT
        b.id,
        b.category_id,
        c.name AS category_name,
        b.title,
        b.author,
        b.publisher,
        b.publication_year,
        b.description,
        b.cover_path,
        b.total_copies,
        b.available_copies,
        b.is_active,
        b.created_at,
        b.updated_at
    FROM books b
    INNER JOIN categories c ON c.id = b.category_id
    $whereSql
    ORDER BY b.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

foreach ($books as &$b) {
    $b['id'] = (int) $b['id'];
    $b['category_id'] = (int) $b['category_id'];
    $b['publication_year'] = $b['publication_year'] !== null ? (int) $b['publication_year'] : null;
    $b['total_copies'] = (int) $b['total_copies'];
    $b['available_copies'] = (int) $b['available_copies'];
    $b['is_active'] = (int) $b['is_active'];
}
unset($b);

sendJson(200, [
    'success' => true,
    'data' => [
        'categories' => $categories,
        'books' => $books
    ],
    'message' => 'Lấy danh sách sách thư viện thành công.'
]);
