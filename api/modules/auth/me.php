<?php

$user = requireAuth();

sendJson(200, [
    'success' => true,
    'data' => $user,
    'message' => 'Lấy thông tin tài khoản thành công.'
]);

