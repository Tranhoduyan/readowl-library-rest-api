<?php

// Quan ly dau sach & so luong ban (GET chi tiet cong khai, POST/PUT/DELETE danh cho Admin).
$pdo = getConnection();

if ($method === 'GET' && isset($targetBookId)) {
    $stmt = $pdo->prepare(
        'SELECT
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
         WHERE b.id = :id'
    );
    $stmt->execute(['id' => (int) $targetBookId]);
    $book = $stmt->fetch();
    if (!$book) {
        sendError(404, 'NOT_FOUND', 'Không tìm thấy đầu sách.');
    }
    $book['id'] = (int) $book['id'];
    $book['category_id'] = (int) $book['category_id'];
    $book['publication_year'] = $book['publication_year'] !== null ? (int) $book['publication_year'] : null;
    $book['total_copies'] = (int) $book['total_copies'];
    $book['available_copies'] = (int) $book['available_copies'];
    $book['is_active'] = (int) $book['is_active'];

    sendJson(200, [
        'success' => true,
        'data' => $book,
        'message' => 'Lấy thông tin đầu sách thành công.'
    ]);
}

// Cac thao tac ghi chi danh cho Admin.
requireAdmin();

// Kiem tra kieu du lieu truoc khi ep so de khong nhan true, 1.5, mang hay object.
function isBookInteger($value, $minimum, $maximum)
{
    return (is_int($value) || is_string($value))
        && ctype_digit((string) $value)
        && (int) $value >= $minimum
        && (int) $value <= $maximum;
}

if ($method === 'POST' || $method === 'PUT') {
    $body = readJsonBody();
    $fields = [];

    $categoryId = isBookInteger($body['category_id'] ?? null, 1, 4294967295)
        ? (int) $body['category_id'] : 0;
    $totalCopies = isBookInteger($body['total_copies'] ?? null, 0, 10000)
        ? (int) $body['total_copies'] : -1;
    $title = isset($body['title']) && is_string($body['title']) ? trim($body['title']) : '';
    $author = isset($body['author']) && is_string($body['author']) ? trim($body['author']) : '';

    $publisher = null;
    $description = null;
    $coverPath = null;
    foreach (['publisher', 'description', 'cover_path'] as $key) {
        if (isset($body[$key]) && !is_string($body[$key])) {
            $fields[$key] = 'Trường này phải là chuỗi hoặc null.';
        }
    }
    if (isset($body['publisher']) && is_string($body['publisher'])) {
        $publisher = trim($body['publisher']);
        if (mb_strlen($publisher, 'UTF-8') > 200) {
            $fields['publisher'] = 'Nhà xuất bản tối đa 200 ký tự.';
        }
    }
    if (isset($body['description']) && is_string($body['description'])) {
        $description = trim($body['description']);
        if (strlen($description) > 65535) {
            $fields['description'] = 'Mô tả quá dài (tối đa 65535 byte).';
        }
    }
    if (isset($body['cover_path']) && is_string($body['cover_path'])) {
        $coverPath = trim($body['cover_path']);
        if (mb_strlen($coverPath, 'UTF-8') > 255) {
            $fields['cover_path'] = 'Đường dẫn bìa tối đa 255 ký tự.';
        }
    }

    $pubYear = null;
    if (isset($body['publication_year']) && $body['publication_year'] !== '') {
        if (!isBookInteger($body['publication_year'], 1000, 2100)) {
            $fields['publication_year'] = 'Năm xuất bản phải là số nguyên từ 1000 đến 2100.';
        } else {
            $pubYear = (int) $body['publication_year'];
        }
    }

    $isActive = 1;
    if (array_key_exists('is_active', $body)) {
        if (in_array($body['is_active'], [0, 1, '0', '1', false, true], true)) {
            $isActive = (int) $body['is_active'];
        } else {
            $fields['is_active'] = 'Trạng thái chỉ nhận 0 hoặc 1.';
        }
    }

    if ($categoryId < 1) {
        $fields['category_id'] = 'Vui lòng chọn danh mục hợp lệ.';
    }
    if (mb_strlen($title, 'UTF-8') < 1 || mb_strlen($title, 'UTF-8') > 200) {
        $fields['title'] = 'Tên sách phải từ 1 đến 200 ký tự.';
    }
    if (mb_strlen($author, 'UTF-8') < 1 || mb_strlen($author, 'UTF-8') > 200) {
        $fields['author'] = 'Tên tác giả phải từ 1 đến 200 ký tự.';
    }
    if ($totalCopies < 0) {
        $fields['total_copies'] = 'Tổng số bản phải là số nguyên từ 0 đến 10000.';
    }
    if ($fields) {
        sendError(422, 'VALIDATION_ERROR', 'Dữ liệu đầu sách không hợp lệ.', $fields);
    }

    $catCheck = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
    $catCheck->execute(['id' => $categoryId]);
    if (!$catCheck->fetch()) {
        sendError(422, 'VALIDATION_ERROR', 'Danh mục sách không tồn tại.', [
            'category_id' => 'Danh mục không tồn tại.'
        ]);
    }

    $values = [
        'category_id' => $categoryId,
        'title' => $title,
        'author' => $author,
        'publisher' => $publisher !== '' ? $publisher : null,
        'publication_year' => $pubYear,
        'description' => $description !== '' ? $description : null,
        'cover_path' => $coverPath !== '' ? $coverPath : null,
        'total_copies' => $totalCopies,
        'is_active' => $isActive
    ];

    try {
        if ($method === 'POST') {
            $values['available_copies'] = $totalCopies;
            $stmt = $pdo->prepare(
                'INSERT INTO books
                    (category_id, title, author, publisher, publication_year, description,
                     cover_path, total_copies, available_copies, is_active)
                 VALUES
                    (:category_id, :title, :author, :publisher, :publication_year, :description,
                     :cover_path, :total_copies, :available_copies, :is_active)'
            );
            $stmt->execute($values);
            $newId = (int) $pdo->lastInsertId();
            header('Location: /api/v1/books/' . $newId);
            sendJson(201, [
                'success' => true,
                'data' => [
                    'id' => $newId,
                    'category_id' => $categoryId,
                    'title' => $title,
                    'author' => $author,
                    'total_copies' => $totalCopies,
                    'available_copies' => $totalCopies,
                    'is_active' => $isActive
                ],
                'message' => 'Thêm đầu sách mới thành công.'
            ]);
        }

        $bookId = (int) $targetBookId;
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'SELECT total_copies, available_copies FROM books WHERE id = :id FOR UPDATE'
        );
        $stmt->execute(['id' => $bookId]);
        $oldBook = $stmt->fetch();
        if (!$oldBook) {
            $pdo->rollBack();
            sendError(404, 'NOT_FOUND', 'Không tìm thấy đầu sách.');
        }

        $newAvailable = (int) $oldBook['available_copies']
            + $totalCopies - (int) $oldBook['total_copies'];
        if ($newAvailable < 0) {
            $pdo->rollBack();
            sendError(409, 'INVALID_TOTAL_COPIES', 'Không thể giảm tổng số bản xuống dưới số bản đang mượn.');
        }
        $values['id'] = $bookId;
        $values['available_copies'] = $newAvailable;
        $stmt = $pdo->prepare(
            'UPDATE books SET category_id = :category_id, title = :title, author = :author,
                publisher = :publisher, publication_year = :publication_year,
                description = :description, cover_path = COALESCE(:cover_path, cover_path),
                total_copies = :total_copies, available_copies = :available_copies,
                is_active = :is_active WHERE id = :id'
        );
        $stmt->execute($values);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1452) {
            sendError(409, 'CATEGORY_CHANGED', 'Danh mục đã thay đổi. Vui lòng tải lại và chọn danh mục khác.');
        }
        throw $e;
    }

    sendJson(200, [
        'success' => true,
        'data' => [
            'id' => $bookId, 'title' => $title, 'total_copies' => $totalCopies,
            'available_copies' => $newAvailable, 'is_active' => $isActive
        ],
        'message' => 'Cập nhật đầu sách thành công.'
    ]);
}

if ($method === 'DELETE' && isset($targetBookId)) {
    $bookId = (int) $targetBookId;
    try {
        $pdo->beginTransaction();
        // Khoa sach de khong tranh chap voi request tao phieu muon.
        $stmt = $pdo->prepare('SELECT id FROM books WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $bookId]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            sendError(404, 'NOT_FOUND', 'Không tìm thấy đầu sách.');
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM loans WHERE book_id = :id');
        $stmt->execute(['id' => $bookId]);
        $hasHistory = (int) $stmt->fetchColumn() > 0;
        if ($hasHistory) {
            $stmt = $pdo->prepare('UPDATE books SET is_active = 0 WHERE id = :id');
        } else {
            $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
        }
        $stmt->execute(['id' => $bookId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    if ($hasHistory) {
        sendJson(200, [
            'success' => true,
            'data' => ['id' => $bookId, 'is_active' => 0],
            'message' => 'Đầu sách có lịch sử mượn nên được chuyển sang ngừng cho mượn mới.'
        ]);
    }
    http_response_code(204);
    exit;
}
