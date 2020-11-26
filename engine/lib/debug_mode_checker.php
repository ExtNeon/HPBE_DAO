<?php
const DEBUG_MODE_REPORTING = 1;

$engine_debug_mode = 0;
if (isset($_GET["debug_key"]) && $_GET["debug_key"] === $debug_password && isset($debug_password) ||
    isset($_COOKIE["debug_key"]) && $_COOKIE["debug_key"] === $debug_password && isset($debug_password) ||
    isset($_GET["debug_key"]) && $_GET["debug_key"] === "reset") {
    if (isset($_GET["debug_key"]) && $_GET["debug_key"] === "reset") {
        setcookie("debug_key", "", 0, '/');
        $engine_debug_mode = 0;
    } else {
        if (DEBUG_PHP_INFO == 1 && !isset($no_debug_messages)) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            ini_set('error_reporting', E_ALL);
        }
        setcookie("debug_key", $debug_password, 0, '/');
        $engine_debug_mode = DEBUG_MODE_REPORTING;
    }
}
if (!$engine_debug_mode) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('error_reporting', 0);
}
/* =====================SERVICE; DO NOT CHANGE OR DELETE===================================*/
$engine_debug_checker_loaded = 1; //DO NOT CHANGE