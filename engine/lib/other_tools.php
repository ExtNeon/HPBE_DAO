<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 25.08.20
 * Time: 2:30
 */


/**
 * Преобразует русскоязычный и прочий текст с различными недопустимыми для url символами в транслитированную строку с правильным
 * форматированием
 * @param $s Исходная неотформатированная строка
 * @return mixed|null|string|string[] Отформатированная строка
 */
function translit($s)
{
    $s = (string)$s; // преобразуем в строковое значение
    $s = strip_tags($s); // убираем HTML-теги
    $s = str_replace(array("\n", "\r"), " ", $s); // убираем перевод каретки
    $s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
    $s = trim($s); // убираем пробелы в начале и конце строки
    $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
    $s = strtr($s, array('а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'j', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ъ' => '', 'ь' => ''));
    $s = preg_replace("/[^0-9a-z-_\. ]/i", "", $s); // очищаем строку от недопустимых символов
    $s = str_replace(" ", "-", $s); // заменяем пробелы знаком минус
    return $s; // возвращаем результат
}


function formatSize($size)
{
    $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return $size ?
        round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] :
        '0 ' . $filesizename[0];
}


function resolve_site_config()
{
    global $sitename;
    global $admin_email;;
    global $language;
    global $db_host;
    global $db_login;
    global $db_name;
    global $db_password;
    global $session_alive_time;

    if (($sitename = autoconfig_get("site_name")) === null) $sitename = "Sample site";
    if (($admin_email = autoconfig_get("admin_email")) === null) $admin_email = "admin@site.com";
    if (($language = autoconfig_get("language")) === null) $language = "en"; //ru, en
    if (($db_host = autoconfig_get("db_host")) === null) $db_host = "localhost"; //Хост базы данных
    if (($db_login = autoconfig_get("db_login")) === null) $db_login = "root"; //Логин для базы
    if (($db_name = autoconfig_get("db_name")) === null) $db_name = "database"; //Название базы данных
    if (($db_password = autoconfig_get("db_password")) === null) $db_password = "password"; //Пароль для базы
    if (($session_alive_time = autoconfig_get("session_alive_time")) === null) $session_alive_time = 86400 * 30; // 30 days

}

/**
 * Вызывает перезагрузку страницы на ту, что была записана в качестве текущей. Полезно при возврате со страницы авторизации на ту
 * страницу, на которой был вызов авторизации.
 */
function reloadPage()
{
    header('Location: /' . $_SESSION['current_page']);
}

/**
 * Перенаправляет на определённую страницу
 * @param $page URL страницы
 */
function redirectTo($page)
{
    header('Location: /' . $page);
}

/**
 * Устанавливает в качестве текущей страницы $page.
 */
function setCurrentPage($page)
{
    $_SESSION['current_page'] = $page;
}

/**
 * Устанавливает название текущей страницы, которое отображается в теге title
 * @param $caption Название текущей страницы
 */
function setCurrentPageCaption($caption)
{
    global $currentPageCaption;
    $currentPageCaption = $caption;
}


function addErrorToLog($moduleName, $errorStr, $critical = false)
{
    global $core_errors;
    $core_errors = $core_errors . "ERROR FROM " . $moduleName . " >> " . $errorStr . "<br>";
    if ($critical) {
        global $core_criticalError;
        $core_criticalError = true;
    }
}

function showErrors()
{
    global $core_errors;
    if ($core_errors != "") {
        //str_replace ( "\n" , "<br>" , $core_errors);
        printf("Error list: <br>%s\n<br>", $core_errors);
    }
    $core_errors = "";
}

function getErrorList()
{
    global $core_errors;
    return $core_errors;
}

function clearErrorList()
{
    global $core_errors;
    $core_errors = "";
}

function addWarningToLog($moduleName, $errorStr)
{
    global $core_warnings;
    $core_warnings = $core_warnings . "WARNING FROM " . $moduleName . " >> " . $errorStr . "<br>";
}

function showWarnings()
{
    global $core_warnings;
    if ($core_warnings != "") {
        printf("Warning list: <br>%s\n<br>", $core_warnings);
    }
    $core_warnings = "";
}

function getWarningList()
{
    global $core_warnings;
    return $core_warnings;
}

function clearWarningList()
{
    global $core_warnings;
    $core_warnings = "";
}

require_once 'form_generator.php';


/* =====================SERVICE; DO NOT CHANGE OR DELETE===================================*/
$engine_other_tools_loaded = 1; //DO NOT CHANGE