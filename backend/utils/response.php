<?php
/**
 * Standardized JSON response helpers.
 */

function send_json(int $status, array $payload): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function send_ok(string $message, array $data = []): void {
    send_json(200, ['success' => true, 'message' => $message, 'data' => $data]);
}

function send_created(string $message, array $data = []): void {
    send_json(201, ['success' => true, 'message' => $message, 'data' => $data]);
}

function send_error(int $status, string $message): void {
    send_json($status, ['success' => false, 'message' => $message]);
}
