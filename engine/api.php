<?php
/**
 * REST API TEMPLATE
 */
$__hpbe_internal_dont_echo_text_http_200 = 1;
$__hpbe_internal_disable_error_echo = 1;
require_once 'core.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
global $table_catalog;
if (!empty($_GET['request'])) {
    $request_params = json_decode($_GET['request'], true);
    $payload = 'null';
    $status_code = 400;
    $message = 'INVALID REQUEST';

    http_response_code($status_code);
    echo json_encode(['status_code' => $status_code, 'message' => $message, 'request' => $request_params, 'payload' => $payload]);
} else {
    http_response_code(403);
    echo json_encode(["status_code" => "403", "message" => "ACCESS DENIED"]);
}
die();