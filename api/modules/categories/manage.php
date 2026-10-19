<?php

// Quan ly danh muc sach (GET cong khai, POST/PUT/DELETE danh cho Admin).
$pdo = getConnection();

if ($method === 'GET' && !isset($targetCategoryId)) {
    $rows = $pdo->query(
        'SELECT
            c.id,
            c.name,
            c.description,
            COUNT(b.id) AS books_count
         FROM categories c
         LEFT JOIN books b ON b.category_id = c.id
         GROUP BY c.id, c.name, c.description
         ORDER BY c.id ASC'
    )->fetchAll();

    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['books_count'] = (int) $r['books_count'];
    }
    unset($r);

    sendJson(200, [
        'success' => true,
        'data' => $rows,
        'message' => 'Lấy danh sách danh mục sách thành công.'
    ]);
}

if ($method === 'GET' && isset($targetCategoryId)) {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.name, c.description, COUNT(b.id) AS books_count
         FROM categories c
         LEFT JOIN books b ON b.category_id = c.id
         WHERE c.id = :id
         GROUP BY c.id, c.name, c.description'
    );
    $stmt->execute(['id' => (int) $targetCategoryId]);
    $cat = $stmt->fetch();
    if (!$cat) {
        sendError(404, 'NOT_FOUND', 'Không tìm thấy danh mục sách.');
    }
    $cat['id'] = (int) $cat['id'];
    $cat['books_count'] = (int) $cat['books_count'];

    sendJson(200, [
        'success' => true,
        'data' => $cat,
        'message' => 'Lấy chi tiết danh mục thành công.'
    ]);
}

// Cac thao tac POST, PUT, DELETE yeu cau quyen Admin
requireAdmin();

if ($method === 'POST') {
    $body = readJsonBody();
    $name = isset($body['name']) && is_string($body['name']) ? trim($body['name']) : '';
    $description = isset($body['description']) && is_string($body['description']) ? trim($body['description']) : null;

    $fields = [];
    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
        $fields['name'] = 'Tên danh mục phải từ 2 đến 100 ký tự.';
    }
    if ($description !== null && mb_strlen($description, 'UTF-8') > 1000) {
        $fields['description'] = 'Mô tả danh mục tối đa 1000 ký tự.';
    }
    if (!empty($fields)) {
        sendError(422, 'VALIDATION_ERROR', 'Dữ liệu danh mục không hợp lệ.', $fields);
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (:name, :desc)');
        $stmt->execute([
            'name' => $name,
            'desc' => $description !== '' ? $description : null
        ]);
    } catch (PDOException $e) {
        if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
            sendError(409, 'CATEGORY_EXISTS', 'Tên danh mục này đã tồn tại.', [
                'name' => 'Vui lòng chọn tên danh mục khác.'
            ]);
        }
        throw $e;
    }

    $id = (int) $pdo->lastInsertId();
    header('Location: /api/v1/categories/' . $id);
    sendJson(201, [
        'success' => true,
        'data' => [
            'id' => $id,
            'name' => $name,
            'description' => $description !== '' ? $description : null,
            'books_count' => 0
        ],
        'message' => 'Tạo danh mục sách thành công.'
    ]);
}

if ($method === 'PUT' && isset($targetCategoryId)) {
    $catId = (int) $targetCategoryId;
    $check = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
    $check->execute(['id' => $catId]);
    if (!$check->fetch()) {
        sendError(404, 'NOT_FOUND', 'Không tìm thấy danh mục sách.');
    }

    $body = readJsonBody();
    $name = isset($body['name']) && is_string($body['name']) ? trim($body['name']) : '';
    $description = isset($body['description']) && is_string($body['description']) ? trim($body['description']) : null;

    $fields = [];
    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
        $fields['name'] = 'Tên danh mục phải từ 2 đến 100 ký tự.';
    }
    if (!empty($fields)) {
        sendError(422, 'VALIDATION_ERROR', 'Dữ liệu danh mục không hợp lệ.', $fields);
    }

    try {
        $up = $pdo->prepare('UPDATE categories SET name = :name, description = :desc WHERE id = :id');
        $up->execute([
            'name' => $name,
            'desc' => $description !== '' ? $description : null,
            'id' => $catId
        ]);
    } catch (PDOException $e) {
        if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
            sendError(409, 'CATEGORY_EXISTS', 'Tên danh mục này đã tồn tại.');
        }
        throw $e;
    }

    sendJson(200, [
        'success' => true,
        'data' => [
            'id' => $catId,
            'name' => $name,
            'description' => $description !== '' ? $description : null
        ],
        'message' => 'Cập nhật danh mục thành công.'
    ]);
}

if ($method === 'DELETE' && isset($targetCategoryId)) {
    $catId = (int) $targetCategoryId;
    $check = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id');
    $check->execute(['id' => $catId]);
    $cat = $check->fetch();
    if (!$cat) {
        sendError(404, 'NOT_FOUND', 'Không tìm thấy danh mục sách.');
    }

    // Quy tac 15: Danh muc co sach khong xoa
    $bookCntStmt = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category_id = :id');
    $bookCntStmt->execute(['id' => $catId]);
    $bookCnt = (int) $bookCntStmt->fetchColumn();
    if ($bookCnt > 0) {
        sendError(
            409,
            'CATEGORY_HAS_BOOKS',
            'Danh mục này đang chứa ' . $bookCnt . ' đầu sách, không thể xóa. Vui lòng chuyển danh mục cho sách trước.'
        );
    }

    $del = $pdo->prepare('DELETE FROM categories WHERE id = :id');
    $del->execute(['id' => $catId]);

    http_response_code(204);
    exit;
}
