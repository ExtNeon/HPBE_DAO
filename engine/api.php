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
    switch ($request_params['request_target']) {
        case 'catalog_pages_count':
            if ($request_params['elements_on_page'] > 0) {
                $count_of_elements = $table_catalog->count();
                $pages_count =floor($count_of_elements / $request_params['elements_on_page'])+1;
                $payload = [];
                $payload['pages_count'] = $pages_count;
                $status_code = 200;
                $message = 'OK';
            } else {
                $message = 'COUNT OF ELEMENTS ON PAGE IS UNKNOWN';
            }
            break;
        case 'catalog_items':
            if (isset($request_params['catalog_page'])) {
                $count_of_elements = $table_catalog->count();
                $pages_count = floor($count_of_elements / $request_params['elements_on_page']) + 1;
                $start_element = $request_params['catalog_page'] * $request_params['elements_on_page'];
                if ($request_params['catalog_page'] >= $pages_count || $request_params['catalog_page'] < 0) {
                    $message = 'INVALID REQUEST: CATALOG ISN\'T IN BOUNDS';
                } else {
                    if ($table_catalog->loadFromDatabaseAll('ORDER BY `id` LIMIT ' . $start_element . ', ' . ($request_params['elements_on_page'] / 1))) {
                        $payload = [];
                        $payload["catalog_items"] = $table_catalog->tableRecords;
                        $status_code = 200;
                        $message = 'OK';
                    } else {
                        $status_code = 500;
                        $message = 'DATABASE FETCH ERROR';
                        $payload = $core_current_error;
                    }
                }
            } else {
                $message = 'INVALID REQUEST: CATALOG PAGE NOT FOUND';
            }
    }
    http_response_code($status_code);
    echo json_encode(['status_code' => $status_code, 'message' => $message, 'request' => $request_params, 'payload' => $payload]);
} else {
    http_response_code(403);
    echo json_encode(["status_code" => "403", "message" => "ACCESS DENIED"]);
}
die();