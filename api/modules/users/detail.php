<?php

// DG03: Chi tiet doc gia va thong ke / lich su muon tra (Admin).
$currentAdmin = requireAdmin();

$userId = isset($targetUserId) ? (int) $targetUserId : 0;
if ($userId < 1) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy độc giả.');
}

$pdo = getConnection();

$stmt = $pdo->prepare(
    "SELECT id, full_name, email, role, is_active, created_at
     FROM users
     WHERE id = :id AND role = 'member'"
);
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy độc giả.');
}

// Lay thong ke luot muon cua doc gia
$statsStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_loans,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_count,
        COALESCE(SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END), 0) AS borrowed_count,
        COALESCE(SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END), 0) AS returned_count,
        COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected_count,
        COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_count,
        COALESCE(SUM(CASE WHEN status = 'borrowed' AND due_date < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue_count
     FROM loans
     WHERE user_id = :user_id"
);
$statsStmt->execute(['user_id' => $userId]);
$statsRow = $statsStmt->fetch();

// Lay danh sach lich su muon tra gan nhat cua doc gia (toi da 20 ban ghi)
$loansStmt = $pdo->prepare(
    "SELECT
        l.id,
        l.book_id,
        b.title AS book_title,
        b.author AS book_author,
        l.status,
        l.requested_at,
        l.borrowed_at,
        l.due_date,
        l.returned_at,
        l.rejection_reason,
        CASE
            WHEN l.status = 'borrowed' AND l.due_date IS NOT NULL AND l.due_date < CURDATE() THEN 1
            ELSE 0
        END AS is_overdue
     FROM loans l
     INNER JOIN books b ON b.id = l.book_id
     WHERE l.user_id = :user_id
     ORDER BY l.requested_at DESC, l.id DESC
     LIMIT 20"
);
$loansStmt->execute(['user_id' => $userId]);
$loanRows = $loansStmt->fetchAll();

$recentLoans = [];
foreach ($loanRows as $loan) {
    $recentLoans[] = [
        'id' => (int) $loan['id'],
        'book_id' => (int) $loan['book_id'],
        'book_title' => $loan['book_title'],
        'book_author' => $loan['book_author'],
        'status' => $loan['status'],
        'is_overdue' => (int) $loan['is_overdue'] === 1,
        'requested_at' => $loan['requested_at'],
        'borrowed_at' => $loan['borrowed_at'],
        'due_date' => $loan['due_date'],
        'returned_at' => $loan['returned_at'],
        'rejection_reason' => $loan['rejection_reason']
    ];
}

$data = publicUser($user);
$data['loan_stats'] = [
    'total_loans' => (int) ($statsRow['total_loans'] ?? 0),
    'pending_count' => (int) ($statsRow['pending_count'] ?? 0),
    'borrowed_count' => (int) ($statsRow['borrowed_count'] ?? 0),
    'returned_count' => (int) ($statsRow['returned_count'] ?? 0),
    'rejected_count' => (int) ($statsRow['rejected_count'] ?? 0),
    'cancelled_count' => (int) ($statsRow['cancelled_count'] ?? 0),
    'overdue_count' => (int) ($statsRow['overdue_count'] ?? 0)
];
$data['recent_loans'] = $recentLoans;

sendJson(200, [
    'success' => true,
    'data' => $data,
    'message' => 'Lấy thông tin chi tiết độc giả thành công.'
]);
