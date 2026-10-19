<?php

require_once __DIR__ . '/helpers.php';

$currentUser = requireAuth();
$pdo = getConnection();

$loanId = isset($targetLoanId) ? (int) $targetLoanId : 0;
if ($loanId < 1) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy phiếu mượn.');
}

$row = fetchLoanDetailById($pdo, $loanId);

if (!$row) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy phiếu mượn.');
}

// Test A06 & Muc G: Member truy cap phieu muon cua nguoi khac -> tra 404 de khong lo su ton tai
if ($currentUser['role'] === 'member' && (int) $row['user_id'] !== (int) $currentUser['id']) {
    sendError(404, 'NOT_FOUND', 'Không tìm thấy phiếu mượn.');
}

sendJson(200, [
    'success' => true,
    'data' => formatLoanRow($row, $currentUser['role']),
    'message' => 'Lấy chi tiết phiếu mượn thành công.'
]);
