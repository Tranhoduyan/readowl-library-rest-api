<?php

function readJsonBody()
{
    $contentType = '';
    if (isset($_SERVER['CONTENT_TYPE'])) {
        $parts = explode(';', $_SERVER['CONTENT_TYPE']);
        $contentType = strtolower(trim($parts[0]));
    }

    if ($contentType !== 'application/json') {
        sendError(415, 'UNSUPPORTED_MEDIA_TYPE', 'Yêu cầu phải gửi dữ liệu JSON.');
    }

    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError(400, 'INVALID_JSON', 'Dữ liệu JSON không đúng định dạng.');
    }

    if (!is_object($data)) {
        sendError(422, 'INVALID_BODY', 'Dữ liệu gửi lên phải là một đối tượng JSON.');
    }

    return (array) $data;
}
