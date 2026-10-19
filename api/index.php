<?php

require_once __DIR__ . '/common/response.php';
require_once __DIR__ . '/common/request.php';
require_once __DIR__ . '/common/db.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
header('Cache-Control: no-store');

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];

try {
    // --- MODULE XAC THUC & TAI KHOAN (AUTH) ---
    if ($path === '/api/v1/auth/register') {
        if ($method !== 'POST') {
            header('Allow: POST');
            sendError(405, 'METHOD_NOT_ALLOWED', 'Chức năng đăng ký sử dụng phương thức POST.');
        }
        require __DIR__ . '/modules/auth/register.php';
    }

    if ($path === '/api/v1/auth/login') {
        if ($method !== 'POST') {
            header('Allow: POST');
            sendError(405, 'METHOD_NOT_ALLOWED', 'Chức năng đăng nhập sử dụng phương thức POST.');
        }
        require_once __DIR__ . '/common/auth.php';
        require __DIR__ . '/modules/auth/login.php';
    }

    if ($path === '/api/v1/auth/me' || $path === '/api/v1/me') {
        if ($method !== 'GET') {
            header('Allow: GET');
            sendError(405, 'METHOD_NOT_ALLOWED', 'Chức năng xem tài khoản sử dụng phương thức GET.');
        }
        require_once __DIR__ . '/common/auth.php';
        require __DIR__ . '/modules/auth/me.php';
    }

    // --- MODULE QUAN LY DOC GIA (USERS) ---
    if ($path === '/api/v1/users') {
        if ($method !== 'GET') {
            header('Allow: GET');
            sendError(405, 'METHOD_NOT_ALLOWED', 'Danh sách độc giả chỉ hỗ trợ phương thức GET.');
        }
        require_once __DIR__ . '/common/auth.php';
        require __DIR__ . '/modules/users/list.php';
    }

    if (preg_match('#^/api/v1/users/([^/]+)$#', $path, $matches)) {
        $idParam = $matches[1];
        if (!ctype_digit($idParam) || (int) $idParam < 1 || (int) $idParam > 4294967295) {
            sendError(404, 'NOT_FOUND', 'Không tìm thấy độc giả.');
        }
        $targetUserId = (int) $idParam;

        if ($method === 'GET') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/users/detail.php';
        }

        if ($method === 'PATCH') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/users/update-status.php';
        }

        if ($method === 'DELETE') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/users/delete.php';
        }

        header('Allow: GET, PATCH, DELETE');
        sendError(405, 'METHOD_NOT_ALLOWED', 'Tài nguyên độc giả chỉ hỗ trợ phương thức GET, PATCH và DELETE.');
    }

    // --- MODULE MUON - TRA SACH (LOANS) ---
    if ($path === '/api/v1/loans') {
        if ($method === 'GET') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/loans/list.php';
        }

        if ($method === 'POST') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/loans/create.php';
        }

        header('Allow: GET, POST');
        sendError(405, 'METHOD_NOT_ALLOWED', 'Đường dẫn phiếu mượn chỉ hỗ trợ phương thức GET và POST.');
    }

    if (preg_match('#^/api/v1/loans/([^/]+)$#', $path, $loanMatches)) {
        $loanIdParam = $loanMatches[1];
        if (!ctype_digit($loanIdParam) || (int) $loanIdParam < 1 || (int) $loanIdParam > 4294967295) {
            sendError(404, 'NOT_FOUND', 'Không tìm thấy phiếu mượn.');
        }
        $targetLoanId = (int) $loanIdParam;

        if ($method === 'GET') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/loans/detail.php';
        }

        if ($method === 'PATCH') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/loans/update.php';
        }

        header('Allow: GET, PATCH');
        sendError(405, 'METHOD_NOT_ALLOWED', 'Tài nguyên phiếu mượn chỉ hỗ trợ phương thức GET và PATCH.');
    }

    // --- MODULE SACH (BOOKS) ---
    if ($path === '/api/v1/books') {
        if ($method === 'GET') {
            require __DIR__ . '/modules/books/public-list.php';
        }
        if ($method === 'POST') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/books/manage.php';
        }
        header('Allow: GET, POST');
        sendError(405, 'METHOD_NOT_ALLOWED', 'Đường dẫn sách hỗ trợ phương thức GET và POST.');
    }

    if (preg_match('#^/api/v1/books/([^/]+)$#', $path, $bookMatches)) {
        $bookIdParam = $bookMatches[1];
        if (!ctype_digit($bookIdParam) || (int) $bookIdParam < 1 || (int) $bookIdParam > 4294967295) {
            sendError(404, 'NOT_FOUND', 'Không tìm thấy đầu sách.');
        }
        $targetBookId = (int) $bookIdParam;

        if ($method === 'GET') {
            require __DIR__ . '/modules/books/manage.php';
        }
        if ($method === 'PUT' || $method === 'DELETE') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/books/manage.php';
        }

        header('Allow: GET, PUT, DELETE');
        sendError(405, 'METHOD_NOT_ALLOWED', 'Tài nguyên đầu sách hỗ trợ phương thức GET, PUT và DELETE.');
    }

    // --- MODULE DANH MUC (CATEGORIES) ---
    if ($path === '/api/v1/categories') {
        if ($method === 'GET') {
            require __DIR__ . '/modules/categories/manage.php';
        }
        if ($method === 'POST') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/categories/manage.php';
        }
        header('Allow: GET, POST');
        sendError(405, 'METHOD_NOT_ALLOWED', 'Đường dẫn danh mục hỗ trợ phương thức GET và POST.');
    }

    if (preg_match('#^/api/v1/categories/([^/]+)$#', $path, $catMatches)) {
        $catIdParam = $catMatches[1];
        if (!ctype_digit($catIdParam) || (int) $catIdParam < 1 || (int) $catIdParam > 4294967295) {
            sendError(404, 'NOT_FOUND', 'Không tìm thấy danh mục sách.');
        }
        $targetCategoryId = (int) $catIdParam;

        if ($method === 'GET') {
            require __DIR__ . '/modules/categories/manage.php';
        }
        if ($method === 'PUT' || $method === 'DELETE') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/categories/manage.php';
        }

        header('Allow: GET, PUT, DELETE');
        sendError(405, 'METHOD_NOT_ALLOWED', 'Tài nguyên danh mục hỗ trợ phương thức GET, PUT và DELETE.');
    }

    // --- MODULE THONG KE (STATISTICS) ---
    if ($path === '/api/v1/statistics') {
        if ($method === 'GET') {
            require_once __DIR__ . '/common/auth.php';
            require __DIR__ . '/modules/statistics/summary.php';
        }
        header('Allow: GET');
        sendError(405, 'METHOD_NOT_ALLOWED', 'Đường dẫn thống kê chỉ hỗ trợ phương thức GET.');
    }

    sendError(404, 'NOT_FOUND', 'Không tìm thấy API.');
} catch (Throwable $e) {
    error_log($e->getMessage());
    if ($e instanceof PDOException && strpos($e->getMessage(), '2002') !== false) {
        sendError(
            500,
            'DATABASE_OFFLINE',
            'Chưa bật MySQL! Vui lòng mở XAMPP Control Panel và nhấn nút Start ở dòng MySQL.'
        );
    }
    sendError(500, 'SERVER_ERROR', 'Máy chủ gặp lỗi. Vui lòng thử lại sau.');
}
