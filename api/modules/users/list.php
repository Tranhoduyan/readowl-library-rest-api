<?php

// DG01 & DG02: Danh sach doc gia co phan trang, tim kiem theo ten/email va loc trang thai.
// Chi Admin moi duoc phep truy cap.
$currentAdmin = requireAdmin();

$fields = [];

// 1. Kiem tra tham so phan trang: page va limit
$page = 1;
if (isset($_GET['page']) && $_GET['page'] !== '') {
    $pageRaw = trim((string) $_GET['page']);
    if (!ctype_digit($pageRaw) || (int) $pageRaw < 1) {
        $fields['page'] = 'Số trang (page) phải là số nguyên dương lớn hơn hoặc bằng 1.';
    } else {
        $page = (int) $pageRaw;
    }
}

$limit = 10;
if (isset($_GET['limit']) && $_GET['limit'] !== '') {
    $limitRaw = trim((string) $_GET['limit']);
    if (!ctype_digit($limitRaw) || (int) $limitRaw < 1 || (int) $limitRaw > 50) {
        $fields['limit'] = 'Số lượng mỗi trang (limit) phải từ 1 đến 50.';
    } else {
        $limit = (int) $limitRaw;
    }
}

// 2. Kiem tra tham so tim kiem q (ten hoac email)
$q = '';
if (isset($_GET['q'])) {
    if (!is_string($_GET['q'])) {
        $fields['q'] = 'Từ khóa tìm kiếm không hợp lệ.';
    } else {
        $q = trim($_GET['q']);
        if (mb_strlen($q, 'UTF-8') > 150) {
            $fields['q'] = 'Từ khóa tìm kiếm không được vượt quá 150 ký tự.';
        }
    }
}

// 3. Kiem tra tham so loc trang thai is_active
$isActiveFilter = null;
if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
    $activeRaw = strtolower(trim((string) $_GET['is_active']));
    if ($activeRaw === '1' || $activeRaw === 'true') {
        $isActiveFilter = 1;
    } elseif ($activeRaw === '0' || $activeRaw === 'false') {
        $isActiveFilter = 0;
    } else {
        $fields['is_active'] = 'Tham số is_active chỉ nhận giá trị 0 hoặc 1.';
    }
}

if (!empty($fields)) {
    sendError(422, 'VALIDATION_ERROR', 'Tham số truy vấn không hợp lệ.', $fields);
}

$pdo = getConnection();

// Chi lay tai khoan doc gia (role = 'member') theo quy uoc DG01
$whereClauses = ["u.role = 'member'"];
$params = [];

if ($q !== '') {
    // Escape ky tu % va _ trong LIKE de tim kiem chinh xac chuoi nguoi dung nhap
    $escapedQ = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
    $whereClauses[] = "(u.full_name LIKE :q_name ESCAPE '\\\\' OR u.email LIKE :q_email ESCAPE '\\\\')";
    $params['q_name'] = '%' . $escapedQ . '%';
    $params['q_email'] = '%' . $escapedQ . '%';
}

if ($isActiveFilter !== null) {
    $whereClauses[] = 'u.is_active = :is_active';
    $params['is_active'] = $isActiveFilter;
}

$whereSql = implode(' AND ', $whereClauses);

// Dem tong so ban ghi khop bo loc
$countSql = "SELECT COUNT(*) FROM users u WHERE $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;
$offset = ($page - 1) * $limit;

// Thong ke nhanh tong quan doc gia (khong phu thuoc bo loc hien tai) de hien thi tren Dashboard
$summaryStmt = $pdo->query(
    "SELECT
        COUNT(*) AS total_members,
         COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active_members,
         COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) AS locked_members
     FROM users
     WHERE role = 'member'"
);
$summaryRow = $summaryStmt->fetch();

// Truy van danh sach doc gia kem so luot dang muon / cho duyet / qua han
$listSql = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.role,
        u.is_active,
        u.created_at,
        COALESCE(SUM(CASE WHEN l.status = 'borrowed' THEN 1 ELSE 0 END), 0) AS borrowed_count,
        COALESCE(SUM(CASE WHEN l.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
        COALESCE(SUM(CASE WHEN l.status = 'borrowed' AND l.due_date < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue_count
    FROM users u
    LEFT JOIN loans l ON l.user_id = u.id
    WHERE $whereSql
    GROUP BY u.id, u.full_name, u.email, u.role, u.is_active, u.created_at
    ORDER BY u.created_at DESC, u.id DESC
    LIMIT :limit OFFSET :offset
";

$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();

$rows = $listStmt->fetchAll();
$users = [];

foreach ($rows as $row) {
    $item = publicUser($row);
    $item['borrowed_count'] = (int) $row['borrowed_count'];
    $item['pending_count'] = (int) $row['pending_count'];
    $item['overdue_count'] = (int) $row['overdue_count'];
    $users[] = $item;
}

sendJson(200, [
    'success' => true,
    'data' => $users,
    'meta' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $totalPages,
        'summary' => [
            'total_members' => (int) ($summaryRow['total_members'] ?? 0),
            'active_members' => (int) ($summaryRow['active_members'] ?? 0),
            'locked_members' => (int) ($summaryRow['locked_members'] ?? 0)
        ]
    ],
    'message' => 'Lấy danh sách độc giả thành công.'
]);
