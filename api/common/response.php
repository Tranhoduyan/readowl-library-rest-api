<?php

function sendJson($statusCode, $payload)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($statusCode, $code, $message, $fields = [])
{
    sendJson($statusCode, [
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message,
            'fields' => (object) $fields
        ]
    ]);
}
