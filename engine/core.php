<?php
/**
 *---------------------------------------------------------------------------------------------------
 *         **              :::    ::: :::::::::  :::::::::  ::::::::::                               |
 *        ****             :+:    :+: :+:    :+: :+:    :+: :+:                                      |
 *       ** ***            +:+    +:+ +:+    +:+ +:+    +:+ +:+                                      |
 *      *    ***           +#++:++#++ +#++:++#+  +#++:++#+  +#++:++#                                 |
 *     *  *  ****          +#+    +#+ +#+        +#+    +#+ +#+                                      |
 *    *     ******         #+#    #+# #+#        #+#    #+# #+#                                      |
 *   **   *********        ###    ### ###        #########  ##########                               |
 *  ***  ***********                                                                                 |
 *   *** ****** ***        8 888888888o.               .8.              ,o888888o.                   |
 *    ************         8 8888    `^888.           .888.          . 8888     `88.                 |
 *     **********          8 8888        `88.        :88888.        ,8 8888       `8b                |
 *      ********           8 8888         `88       . `88888.       88 8888        `8b               |
 *       ******            8 8888          88      .8. `88888.      88 8888         88               |
 *        ****             8 8888          88     .8`8. `88888.     88 8888         88               |
 *         **              8 8888         ,88    .8' `8. `88888.    88 8888        ,8P               |
 *                         8 8888        ,88'   .8'   `8. `88888.   `8 8888       ,8P                |
 *                         8 8888    ,o88P'    .888888888. `88888.   ` 8888     ,88'                 |
 *                         8 888888888P'      .8'       `8. `88888.     `8888888P'                   |
 * --------------------------------------------------------------------------------------------------
 *
 * Hypertext preprocessor based engine - database access oriented
 */

/**
 * Тут находятся основные методы движка.
 */

/** TODO
 * Защита от брутфорса
 * Комментарий к ядру и описание
 * Регистрация по мылу и прочие плюшки
 * В настройках сделать пункты про название сайта и прочее
 * В мастере первоначальных настроек сделать возможность восстановления бекапа
 */
const HPBE_DAO_VERSION = "0.1.7b"; // Hypertext preprocessor based engine - database access oriented


register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] == E_ERROR || $error['type'] == E_PARSE || $error['type'] == E_COMPILE_ERROR)) {
        if (strpos($error['message'], 'Allowed memory size') === 0) { // если кончилась память
            ini_set('memory_limit', (intval(ini_get('memory_limit')) + 64) . "M"); // выделяем немножко что бы доработать корректно
            // Log::error("PHP Fatal: not enough memory in ".$error['file'].":".$error['line']);
        } else {
            //Log::error("PHP Fatal: ".$error['message']." in ".$error['file'].":".$error['line']);
        }
        // ... завершаемая корректно ....
        global $engine_debug_mode;
        global $admin_email;
        http_response_code(500);
        if (!$engine_debug_mode) {
            global $current_locale;
            global $engine_debug_checker_loaded;

            echo "<p>500. Something terrible have happened now...</p>";

            global $engine_config_file_loaded;
            if (!isset($engine_config_file_loaded)) echo "Config file are not loaded<br>"; else
                if (!isset($engine_debug_checker_loaded)) echo "Internal debug module are not loaded<br>"; else
                    if (isset($current_locale)) {
                        echo "<div style='color: #6a0000'><br> " . $current_locale["!server_critical_error"] . " <br><br>" . $current_locale["contact_administrator_immediately"] . ". <a href='mailto:" . $admin_email . "'>" . $current_locale["contacts"] . "</a><br><p>" . $current_locale["error_details"] . ":</p><p>=======START LOG=======</p>";
                        global $engine_autoconfig_module_loaded;
                        global $engine_db_driver_loaded;
                        global $engine_table_structure_config_loaded;
                        global $engine_table_structure_user_config_loaded;
                        global $engine_admin_panel_tools_loaded;
                        global $engine_admin_panel_config_loaded;
                        global $engine_other_tools_loaded;

                        if (!isset($engine_autoconfig_module_loaded)) echo "Internal automatic engine config are not loaded<br>"; else
                            if (!isset($engine_db_driver_loaded)) echo "Database driver are not loaded<br>"; else
                                if (!isset($engine_table_structure_config_loaded)) echo "Table structure engine config file are not loaded<br>"; else
                                    if (!isset($engine_table_structure_user_config_loaded)) echo "Table structure user config file are not loaded<br>"; else
                                        if (!isset($engine_admin_panel_tools_loaded)) echo "Admin panel tools are not loaded<br>"; else
                                            if (!isset($engine_admin_panel_config_loaded)) echo "Admin panel config are not loaded<br>"; else
                                                if (!isset($engine_other_tools_loaded)) echo "Common libraries are not loaded<br>";
                    } else {
                        echo "Locales are not loaded.<br>";
                    }
        }
    }
});


require_once "config/site_config.php";
require_once "lib/debug_mode_checker.php";
require_once "lib/auto_config_manager.php";
require_once "lib/other_tools.php";
resolve_site_config();
require_once "locales.php";
require_once "core_methods.php";
if (autoconfig_get("core_configured") == null && is_dir($dir.'.git')) {
    removeDirectory($dir.'.git');
}
if (autoconfig_get("core_configured") !== null) {
    require_once "lib/db_driver.php";
    require_once "config/table_structure_engine_config.php";
    require_once "config/table_structure_user_config.php";
    require_once "config/admin_panel_config.php";
}



siteconfig_reload();
session_start();
if (autoconfig_get("core_configured") === null) {
    if ($engine_debug_mode) {
        // autoconfig_reset();
        // autoconfig_add("initial_configuration_call_time", time());
        //autoconfig_write();
        redirectTo("engine/initial_configuration");

    } else {
        redirectTo("sample_page");
    }
    die();
}

if ((getErrorList() != "" || getWarningList() != "") && $core_criticalError && !isset($__hpbe_internal_disable_error_echo)) {
    global $core_current_error;
    global $core_error_code;


    if ($core_criticalError) {
        http_response_code(500);
        echo "<div style='color: #6a0000'><br> " . $current_locale["!server_critical_error"] . "(500) <br><br>" . $current_locale["contact_administrator_immediately"] . ". <a href='mailto:" . $admin_email . "'>" . $current_locale["contacts"] . "</a><br><p>" . $current_locale["error_details"] . ":</p><p>=======START LOG=======</p>";
    }

    echo $core_current_error . "<br>";
    if ($engine_debug_mode || (isCurrentUserAccounted() && isCurrentUserAdministrator())) {
        showErrors();
        showWarnings();
    } else {
        echo "<p>" . $current_locale["for_more_details_you_need_to_enter_to_debug_mode"] . "</p>";
        show_debug_mode_form();
    }
    die("=======END LOG=======</div>");

}


// =======ПЕРЕМЕННЫЕ=======


$currentUser = null;
$catalog = [];
$currentPageCaption = "";
updateCurrentUser();
process_visitors_counter();
$statisticsVisitors = new StatisticsVisitors(true);

if (siteconfig_get("disable_cache") !== null) {
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Expires: " . date("r"));
}


if ($engine_debug_mode && !isset($__hpbe_internal_dont_echo_text_http_200)) echo "<a href=\"" . (isset($_SESSION['current_page']) ? $_SESSION['current_page'] : "") . "?debug_key=reset\">" . $current_locale["!debug_mode_is_enabled_click_here_to_reset"] . "</a>";
if (siteconfig_get("site_maintenance") !== null && ($engine_debug_mode || (isCurrentUserAccounted() && isCurrentUserAdministrator()))) {
    if (!isset($__hpbe_internal_dont_echo_text_http_200)) echo "<p>" . $current_locale["site_is_now_in_maintenance_mode"] . "</p>";
} else if (siteconfig_get("site_maintenance") !== null) {
    redirectTo("site_maintenance");
    //http_response_code(403);
    echo '<h1>403 forbidden.</h1>';
    die();
}