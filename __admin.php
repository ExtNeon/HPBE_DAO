<?php /** @noinspection ALL */
/*header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0,pre-check=0", false);
header("Cache-Control: max-age=0", false);
header("Pragma: no-cache");*/

/*todo
    Реализовать регистрацию адекватно по мылу

Реализовать динамическое изменение шаблонов в па (панели администратора)

Реализовать менеджер учётных записей в па

Оптимизировать менеджер базы данных

Нормальный генератор форм

Реализовать журнал входов, в котором записавать каждую попытку входа. Будет содержать ip, наличие куки посетителя, время,
юзер агент, если успешно, то id пользователя

Улучшить статистику, собирать сведения, возможно даже делать фингерпринт, читать юзер агент и реферер.

сделать журнал посещений для каждого пользователя и детектор отключенных кук роботов:
будет журнал посещений, в который записывается ip, урл, наличие куки посетителя, время, юзер-агент, и если залогинен, то id пользователя
*/

require_once "engine/core.php";
require_once "engine/lib/tar_tools.php";
if (!empty($_GET["action"]) && $_GET["action"] === "reset") {
    reset_current_user_session();
    reloadPage();
}

if (($engine_debug_mode === 1 && !empty($_GET["action"]) && ($_GET["action"] === "register"))) {
    include "registration.php";
}


const PAGE_ID_MAIN = "index";
const PAGE_ID_404 = "404";
const PAGE_ID_ACCOUNT_MANAGER = 9001;
const PAGE_ID_DB_MANAGER = 9002;
const PAGE_ID_SITE_SETTINGS = 9003;
const PAGE_ID_VISITORS_JOURNAL = 9004;
const PAGE_ID_BACKUP_MANAGER = 9005;

setCurrentPage("__admin");
setCurrentPageCaption($sitename . " | " . $current_locale["admin_panel"]);
$adminPanelTopCaption = $current_locale["authorization"];

//loadCatalog();
global $currentUser;
global $ADMIN_PANEL_PAGES;

$currentPage = PAGE_ID_404;

if (isCurrentUserModerator()) {
    if (!empty($_GET["page"]) && (!empty($ADMIN_PANEL_PAGES[$_GET["page"]]) || $_GET["page"] > 9000)) {
        if ($_GET["page"] > 9000) {
            switch ($_GET["page"]) {
                case PAGE_ID_ACCOUNT_MANAGER:
                    $adminPanelTopCaption = $current_locale["account_manager"];
                    $currentPage = PAGE_ID_ACCOUNT_MANAGER;
                    break;
                case PAGE_ID_DB_MANAGER:
                    $adminPanelTopCaption = $current_locale["db_manager"];
                    $currentPage = PAGE_ID_DB_MANAGER;
                    break;
                case PAGE_ID_SITE_SETTINGS:
                    $adminPanelTopCaption = $current_locale["site_settings"];
                    $currentPage = PAGE_ID_SITE_SETTINGS;
                    break;
                case PAGE_ID_VISITORS_JOURNAL:
                    $adminPanelTopCaption = $current_locale["visitors_journal"];
                    $currentPage = PAGE_ID_VISITORS_JOURNAL;
                    break;
                case PAGE_ID_BACKUP_MANAGER:
                    $adminPanelTopCaption = $current_locale["backup_manager"];
                    $currentPage = PAGE_ID_BACKUP_MANAGER;
                    break;
                default:
                    $adminPanelTopCaption = $current_locale["error_404"];
                    $currentPage = PAGE_ID_404;
                    break;
            }
            //$adminPanelTopCaption = "ITS OVER NINE THOUSAND!";
        } else {
            $adminPanelTopCaption = $ADMIN_PANEL_PAGES[$_GET["page"]]->pageName;
            $currentPage = $_GET["page"];
        }
    } else {
        if (empty($_GET["page"])) {
            $adminPanelTopCaption = $current_locale["main_page"];
            $currentPage = PAGE_ID_MAIN;
        } else {
            switch ($_GET["page"]) {
                case "":
                case PAGE_ID_MAIN:
                    $adminPanelTopCaption = $current_locale["main_page"];
                    $currentPage = PAGE_ID_MAIN;
                    break;
                case PAGE_ID_404:
                default:
                    $adminPanelTopCaption = $current_locale["error_404"];
                    $currentPage = PAGE_ID_404;
            }
        }
    }
}

if (isset($_GET["advanced_maintenance"])) {
    switch ($_GET["advanced_maintenance"]) {
        case "1":
            siteconfig_add("site_maintenance", "1");
            redirectTo("__admin?page=" . $currentPage);
            break;
        default:
            siteconfig_remove("site_maintenance");
            redirectTo("__admin?page=" . $currentPage);
            break;
    }
}

if (isset($_GET["disable_cache"])) {
    switch ($_GET["disable_cache"]) {
        case "1":
            siteconfig_add("disable_cache", "1");
            redirectTo("__admin?page=" . $currentPage);
            break;
        default:
            siteconfig_remove("disable_cache");
            redirectTo("__admin?page=" . $currentPage);
            break;
    }
}

if (isset($_GET["counter_dont_include_robots"])) {
    switch ($_GET["counter_dont_include_robots"]) {
        case "1":
            siteconfig_add("counter_dont_include_robots", "1");
            redirectTo("__admin?page=" . $currentPage);
            break;
        default:
            siteconfig_remove("counter_dont_include_robots");
            redirectTo("__admin?page=" . $currentPage);
            break;
    }
}
?>


<!DOCTYPE html>
<html class="html__responsive">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">

    <title><?php echo $currentPageCaption ?></title>
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0">


    <link href="engine/res/__admin/_n/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css"
          href="engine/res/__admin/_n/css/toggle.css">
    <link rel="stylesheet" type="text/css"
          href="engine/res/__admin/_n/css/stacks.css">
    <link rel="stylesheet" type="text/css"
          href="engine/res/__admin/_n/css/primary.css">

    <link rel="stylesheet" type="text/css"
          href="engine/res/__admin/_n/css/index.css">
    <link rel="stylesheet" type="text/css"
          href="engine/res/__admin/_n/css/custom.css">
    <link rel="stylesheet" href="engine/lib/wymeditor/skins/default/skin.css">

    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <style type="text/css">
        #container {
            min-width: 310px;
            max-width: 800px;
            height: 400px;
            margin: 0 auto
        }

    </style>


</head>
<body class="question-page unified-theme">


<div id="notify-container"></div>
<header class="top-bar js-top-bar top-bar__network _fixed">
    <div class="wmx12 mx-auto grid ai-center h100" role="menubar">
        <div class="-main grid--cell">
            <a href="__admin">
                <div aria-hidden="true" class="svg-icon native mtn1 iconLogoSEAlternativeSm" width="107" height="15"
                     style="font-size: 16pt;color:white">
                    <?= "$sitename | " . $current_locale["admin_panel"] ?>
                </div>
            </a>
        </div>
        <ol class="overflow-x-auto ml-auto -secondary grid ai-center list-reset h100 user-logged-out"
            role="presentation">


            <li class="-ctas">
                <?php if (isCurrentUserAccounted()) echo "<a style='font-size: 16pt; color: white; margin-right: 10px'>[" . $currentUser["login"] . "]</a> <a href=\"__admin?action=reset\"
                   class=\"login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["logout"] . "</a>"; ?>
            </li>

            <li class="-ctas">
                <a href="index"
                   class="login-link s-btn s-btn__filled py8 js-gps-track"><?= $current_locale["exit_from_panel"] ?></a>
            </li>
        </ol>

    </div>
</header>


<div class="container">


    <div id="left-sidebar" data-is-here-when="md lg" class="left-sidebar js-pinned-left-sidebar ps-relative">
        <div class="left-sidebar--sticky-container js-sticky-leftnav">
            <nav role="navigation">
                <?php adminPanelLeftMenu(); ?>
            </nav>
        </div>
    </div>


    <div id="content" class="">


        <div itemprop="mainEntity" itemscope="" itemtype="http://schema.org/Question">


            <div class="inner-content clearfix">


                <div id="question-header" class="grid sm:fd-column">
                    <h1 itemprop="name"
                        class="grid--cell fs-headline1 fl1 ow-break-word mb8"><?php echo $adminPanelTopCaption ?></h1>
                </div>
                <div class="grid fw-wrap pb8 mb16 bb bc-black-2">
                    <span class="fc-light mr2"></span>
                </div>
            </div>
            <div id="mainbar" role="main" aria-label="question and answers">


                <div class="question" data-questionid="811116" id="question">


                    <div class="post-layout">


                        <div class="postcell post-layout--right">

                            <div class="post-text" itemprop="text">

                                <?php adminPanelBody() ?>

                            </div>


                        </div>


                    </div>
                </div>


            </div>
            <?php
            if ((getErrorList() != "")) {
                echo "<br><div style='color: #6a0000'><p>" . $current_locale["errors"] . ":</p><br><p>=======START LOG=======</p><br>";
                showErrors();
                showWarnings();
                echo("=======END LOG=======</div>");
            }
            ?>
        </div>

    </div>


</div>

</div>
<script src="engine/lib/js/jquery-3.4.1.min.js"></script>
<script src="engine/lib/js/jquery.maskedinput.min.js"></script>
<!--for IE8, ES5 shims are required-->
<!--[if IE 8]>
<script src="engine/lib/vendor/es5-shim.js"></script>
<script src="engine/lib/vendor/es5-sham.js"></script>
<![endif]-->

<script src="engine/lib/vendor/jquery/jquery.js"></script>
<script src="engine/lib/wymeditor/jquery.wymeditor.min.js"></script>
<script type="text/javascript">
    $(function () {
        $('.wymeditor').wymeditor();
    });
</script>


</body>
</html>

<?php

function adminPanelBody()
{
    global $currentUser;
    global $current_locale;
    if ($currentUser === ID_SESSION_EXPIRED) {
        echo $current_locale["session_expired"] . "<br>";
    }
    if (isset($_SESSION['login_result']) || isset($_SESSION['register_result'])) echo '<br>' . getLoginResultString() . getRegisterResultString() . '<br>';
    adminPanelBody_loginForm();
}

//__admin?____SPC_REQ::FUNC=f.aRESPASSW____
function adminPanelBody_loginForm()
{
    global $currentUser;
    global $current_locale;
    global $ADMIN_PANEL_PAGES;
    global $engine_debug_mode;
    if (($engine_debug_mode === 1 && !empty($_GET["action"]) && ($_GET["action"] === "reg" || $_GET["action"] === "register"))) {
        if ($_GET["action"] !== "register") {
            generate_form([
                new form_field_text('login',
                    $current_locale['login'],
                    $current_locale['login'],
                    '',
                    true,
                    true),
                new form_field_password('password',
                    $current_locale['password'],
                    $current_locale['password'],
                    '',
                    true,
                    true),
                new form_field_submit_button($current_locale['register'], FORM_BUTTON_COLOR_BLACK)
            ], '/__admin?action=register', FORM_METHOD_POST);

            echo '</div></form>';
            //wwsx  ssecho '<a class = "buttoned_link" href = registration.php>Регистрация</a>';
        }
    } else if (!isCurrentUserAccounted()) {
        generate_form([
            new form_field_text('login',
                $current_locale['login'],
                $current_locale['login'],
                '',
                true,
                true),
            new form_field_password('password',
                $current_locale['password'],
                $current_locale['password'],
                '',
                true,
                true),
            new form_field_submit_button($current_locale['log_in'], FORM_BUTTON_COLOR_BLACK)
        ], '/login?action=login', FORM_METHOD_POST);
    } else if (isCurrentUserModerator() || isCurrentUserAdministrator()) {
        global $currentPage;
        global $SPECIAL_ADMIN_PANEL_PAGES;
        switch ($currentPage) {
            case PAGE_ID_MAIN:
                adminPanelBody_DrawMainPage();
                break;
            case PAGE_ID_404:
                break;
            case PAGE_ID_ACCOUNT_MANAGER:
                adminPanelBody_DrawAccountManager();
                // $SPECIAL_ADMIN_PANEL_PAGES["users"]->processAllPage($currentPage);
                break;
            case PAGE_ID_DB_MANAGER:
                adminPanelBody_DrawDBManager();
                break;
            case PAGE_ID_SITE_SETTINGS:
                adminPanelBody_DrawSiteSettings();
                break;
            case PAGE_ID_VISITORS_JOURNAL:
                adminPanelBody_DrawVisitorJournal();
                break;
            case PAGE_ID_BACKUP_MANAGER:
                adminPanelBody_DrawBackupManager();
                break;
            default:
                $ADMIN_PANEL_PAGES[$currentPage]->processAllPage($currentPage);
                break;
        }
    } else {
        echo '<h3>У вас недостаточно прав для работы с панелью администратора</h3>';
    }
}

function adminPanelBody_DrawDBManager()
{
    global $db_host;
    global $db_login;
    global $db_password;
    global $db_name;
    echo "<form action='engine/tools/adminer' method='post'>
            <input type='hidden' name='auth[driver]' value='server'>
            <input type='hidden' name='auth[server]' value='" . $db_host . "'>
            <input type='hidden' name='auth[username]' value='" . $db_login . "'>
            <input type='hidden' name='auth[password]' value='" . $db_password . "'>
            <input type='hidden' name='auth[db]' value='" . $db_name . "'>
            <button type='submit' class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">Перейти</button>
            </form>";
}

function adminPanelBody_DrawVisitorJournal()
{
    global $table_visitors;
    global $current_locale;
    global $currentUser;
    global $table_users;
    global $currentPage;
    $filter_url_addition = (isset($_GET['filter_time_start']) ? '&filter_time_start=' . $_GET['filter_time_start'] : '') .
        (isset($_GET['filter_time_end']) ? '&filter_time_end=' . $_GET['filter_time_end'] : '');
    $sort_mode = isset($_GET['time_sort']) ? '&time_sort=asc' : '';
    $table_data[] = ["<a href='__admin?page=" . $currentPage . (isset($_GET['time_sort']) ? '' : '&time_sort=asc') . $filter_url_addition . "'>Время " . (isset($_GET['time_sort']) ? '▲' : '▼') . "</a>", 'User-agent', "Пользователь", "Запрос", "Количество посещений"];
    $table_visitors->loadFromDatabaseAll('ORDER BY `time`' . (isset($_GET['time_sort']) ? '' : ' DESC'));
    if (!empty($table_visitors->tableRecords)) {
        $fisrt_element_time = $table_visitors->tableRecords[isset($_GET['time_sort']) ? 0 : sizeof($table_visitors->tableRecords) - 1]['time'];
        $last_element_time = $table_visitors->tableRecords[isset($_GET['time_sort']) ? sizeof($table_visitors->tableRecords) - 1 : 0]['time'];
        $days = ceil(($last_element_time - $fisrt_element_time) / 86400);
        $days_pages = [];
        $last_time = $fisrt_element_time;
        echo "<a style='padding:3px; margin:4px; border:dashed " . (isset($_GET['filter_time_start']) ? '1px' : '2px #DC461D') . ";' href='__admin?page=" . $currentPage . $sort_mode . "'>" . $current_locale['all'] . "</a>";
        for ($i = 0; $i < $days; $i++) {
            echo "<a style='padding:3px; margin:4px; border:dashed " . ((isset($_GET['filter_time_start']) && $_GET['filter_time_start'] == $last_time) ? '2px #DC461D' : '1px') . ";' href='__admin?page=" . $currentPage . $sort_mode . "&filter_time_start=" . $last_time . '&filter_time_end=' . ($last_time + 86400) . "'>" . date("d.m.Y", $last_time) . "</a>";
            $days_pages[] = ['start' => $last_time, 'end' => $last_time + 86400];
            $last_time += 86400;
        }
        foreach ($table_visitors->tableRecords as $currentRecord) {
            if (isset($_GET['filter_time_start']) &&
                $currentRecord['time'] <= $_GET['filter_time_start']
                || isset($_GET['filter_time_end']) &&
                $currentRecord['time'] > $_GET['filter_time_end']) continue;
            if ($currentRecord["mirror_visitor_id"] == '-1') {
                if ($currentRecord['time'] + 2592000 < time()) {  //Записи дольше месяца удаляем. Ни к чему они нам.
                    $table_visitors->deleteRecord('id', $currentRecord['id']);
                }
                $user_login_id = $currentRecord['user_id'];
                $location = $currentRecord['request'];
                if ($currentRecord['visit_count'] > 1) {
                    foreach ($table_visitors->tableRecords as $search_item) {
                        if ($search_item['mirror_visitor_id'] == $currentRecord['id']) {
                            $location .= '<p style="max-width: 540px; border: dashed 1px; padding: 4px; margin: 1px; margin-top: 4px;"><b>' . str_ireplace(' ', '&nbsp;', '#' . $search_item['visit_count'] . ($search_item['ip'] == $currentRecord['ip'] ? ' <i>(ip совпадают)</i>' : ''). ' | ' . date("H:i:s", $search_item['time']) .
                                    ':</b> ' . $search_item['referer'] . ' -> ' . $search_item['request']) . '</p>';
                            if ($user_login_id < 0 && $search_item['user_id'] >= 0) {
                                $user_login_id = $search_item['user_id'];
                            }
                        }
                    }
                }
                if ($user_login_id >= 0) {
                    $table_users->loadFromDatabaseByKey('id', $user_login_id);
                    $username = empty($table_users->tableRecords) ? $current_locale["error_404"] : $table_users->tableRecords[0]['login'];
                } else if ($currentRecord['is_robot'] == '1') {
                    $username = '<b>' . $current_locale["robot"] . '</b>';
                } else {
                    $username = $current_locale["guest"];
                }
                if ($currentRecord['visitor_cookie'] > -1) {
                    $username .= ', <b>уже посещал сайт '. date("d.m.Y", $currentRecord['visitor_cookie']) . '</b>';
                }
                $table_data[] = [
                    '<div style="font-size:smaller">' . date("H:i:s d.m.Y", $currentRecord['time']) . '</div>',
                    '<div style="font-size:smaller">' . $currentRecord['ip'] . "<br>" .
                    $currentRecord['user_agent'] . '</div>',
                    '<div style="font-size:smaller">' . $username . '</div>',
                    '<div style="font-size:smaller">' . $currentRecord['referer'] . ' -> ' . $location . '</div>',
                    '<div style="font-size:smaller">' . $currentRecord['visit_count'] . '</div>'
                ];
            }
        }
        generate_table($table_data);
    } else {
        echo '<h3>' . $current_locale["no_data"] . '</h3>';
    }
}

function adminPanelBody_DrawBackupManager()
{
    global $currentPage;
    global $current_locale;
    if (!isset($_GET["action"])) {
        echo "<h3>Выберите действие:</h3><br><a class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\" href='__admin?page=" . $currentPage . "&action=export'>Создать и скачать резервную копию</a>
            <br><a class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\" href='__admin?page=" . $currentPage . "&action=import'>Восстановить резервную копию</a>";
    } else {
        switch ($_GET["action"]) {
            case "export":
                echo "<h1 itemprop=\"name\" class=\"grid--cell fs-headline1 fl1 ow-break-word mb8\">Создание резервной копии</h1>";
                if (!isset($_GET["submit"])) {
                    echo "<h3>Добавить следующие компоненты в резервную копию:</h3>";
                    generate_form([
                        new form_field_checkbox_simple('conf',
                            'Статичная конфигурация',
                            true),
                        new form_field_checkbox_simple('db',
                            'База данных',
                            true),
                        new form_field_checkbox_simple('engine',
                            'Файлы движка'),
                        new form_field_checkbox_simple('userfiles',
                            'Пользовательские файлы',
                            true,
                            false,
                            "Пользовательские файлы всегда включены в резервную копию. Это файлы из корневой дирректории, пользовательские папки"),
                        new form_field_submit_button($current_locale["create"], FORM_BUTTON_COLOR_ORANGE),
                        new form_field_href_button($current_locale['cancel'], "__admin?page=" . $currentPage, FORM_BUTTON_COLOR_RED)
                    ], "/__admin?page=" . $currentPage . "&action=export&submit=true", FORM_METHOD_POST);

                } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    echo "<h3>Резервная копия сформирована для скачивания</h3><br>";
                    echo "<a class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\" href='/engine/backup?";
                    if (isset($_POST["conf"]) && $_POST["conf"] === "on") echo "conf=1"; else echo "conf=0";
                    if (isset($_POST["db"]) && $_POST["db"] === "on") echo "&db=1"; else echo "&db=0";
                    if (isset($_POST["engine"]) && $_POST["engine"] === "on") echo "&engine=1"; else echo "&engine=0";
                    echo "'>Скачать</a><a href=\"__admin?page=" . $currentPage . "\" 
                   class=\"deleteButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                }
                break;
            case "import":
                echo "<h1 itemprop=\"name\" class=\"grid--cell fs-headline1 fl1 ow-break-word mb8\">Восстановление из резервной копии</h1>";
                if (!isset($_GET["submit"])) {
                    echo "<form style='word-wrap: normal;' action=\"/__admin?page=" . $currentPage . "&action=import&submit=true\" class=\"form-horizontal\" method=\"post\" enctype=\"multipart/form-data\">";
                    echo "<div class=\"form-group\">
                         <label for=\"backup_file\" class=\"col-sm-2 control-label\">Файл резервной копии (*.tar)</label>
                         <div class=\"col-sm-8\">
                         <input type=\"file\" name=\"backup_file\" placeholder=''></div></div>
              
                         ";

                    echo "<br><div class=\"form-group\">
                         <div class=\"col-sm-offset-5 col-sm-8\">
                            <button type=\"submit\" id=\"submit\" class=\"editButton login-link s-btn s-btn__filled py8 js-gps-track wymupdate\">" . $current_locale["continue"] . "</button>
                            <a href=\"__admin?page=" . $currentPage . "\" 
                   class=\"deleteButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["cancel"] . "</a>
                            <div></div>
                         </div>
                     </div>
                     </form>";
                } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    //  chmod("./engine/forbidden/", 0777);
                    if (!isset($_POST["update"]) && !isset($_GET["apply"])) {
                        $fileResult = applyUploadedFile("backup_file", "./engine/forbidden/", disk_free_space(PATH) - 128 * 1024 * 1024);
                        echo "<h3>Информация о резервной копии</h3>";
                        switch ($fileResult) {
                            case FILE_UPLOAD_RESULT_NO_FILES_IN_POST:
                                echo "<p>Файл отсутствует. Попробуйте загрузить снова.</p><br>";
                                echo "<a href=\"__admin?page=" . $currentPage . "&action=import\"                                    
                                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                                // $fileResult = NO_IMAGE_FILE_PATH;
                                break;
                            case FILE_UPLOAD_RESULT_OVERSIZE:
                                echo "<p>" . $current_locale["file_is_too_large"] . "</p><br>";
                                echo "<a href=\"__admin?page=" . $currentPage . "&action=import\"                                    
                                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                                //$fileResult = NO_IMAGE_FILE_PATH;
                                break;
                            case FILE_UPLOAD_RESULT_ERROR:
                                echo "<p>" . $current_locale["file_upload_unknown_error"] . "</p><br>";
                                echo "<a href=\"__admin?page=" . $currentPage . "&action=import\"                                    
                                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                                // $fileResult = NO_IMAGE_FILE_PATH;
                                break;
                            default:
                                rename($fileResult, dirname($fileResult) . "/backup.tar");
                                $tarfile = new TarFile("./engine/forbidden/backup.tar");
                                if ($tarfile) {
                                    if ($tarfile->get_file_details("backup-info.json")) {
                                        $json_info = json_decode($tarfile->extract_file_into_string($tarfile->get_file_details("backup-info.json")), true);
                                        echo "<p>Название резервной копии: " . $json_info["site_name"] . "</p>";
                                        echo "<p>Время создания: " . date("H:m d.m.Y", $json_info["date"]) . "</p>";
                                        echo "<p>Версия движка: " . $json_info["version"] . "</p>";
                                        echo "<p>Содержит: пользовательские данные";
                                        if ($json_info["db"]) echo ", дамп базы данных";
                                        if ($json_info["conf"]) echo ", конфигурационные файлы";
                                        if ($json_info["engine"]) echo ", файлы движка";
                                        echo '.</p>';
                                        echo "<form style='word-wrap: normal;' action=\"/__admin?page=" . $currentPage . "&action=import&submit=true&apply=true\" class=\"form-horizontal\" method=\"post\">";
                                        echo "<br><div class=\"form-group\">
                                         <div class=\" col-sm-8\">
                                         <block class='element_with_textbox'>
                                            <button type=\"submit\" id=\"submit\" class=\"deleteButton login-link s-btn s-btn__filled py8 js-gps-track wymupdate\">Восстановить сайт из резервной копии</button>
                                            <div class='hidden_textbox'><b>" . $current_locale["!attention"] . "<br>" . $current_locale["!this_action_is_permanent"] . "</b><br>
                                            После восстановления резервной копии, существующие на сервере файлы будут заменены на аналогичные из резервной копии.
                                            Также, если резервная копия содержит дамп базы данных, ВСЕ существующие поля в таблицах базы данных будут заменены на
                                            аналогичные из резервной копии. Сайт, или некоторые его части вернутся в вид, существующий в момент создания резервной копии.
                                            " . $current_locale["are_you_sure"] . ":
                                            <input type=\"checkbox\" name=\"update\"></div>
                                           
                                            </block>
                                            
                                            <a href=\"__admin?page=" . $currentPage . "\" 
                                            
                                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["cancel"] . "</a>
                                            <div></div>
                                         </div>
                                     </div>
                                     </form>";
                                    } else {
                                        echo "<p>Данный файл не является резервной копией HPBE DAO</p><a href=\"__admin?page=" . $currentPage . "&action=import\" 
                                            
                                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                                        unlink("./engine/forbidden/backup.tar");
                                    }
                                } else {
                                    echo "<p>Произошла ошибка открытия файла</p><a href=\"__admin?page=" . $currentPage . "&action=import\" 
                                            
                                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                                    unlink("./engine/forbidden/backup.tar");
                                }
                        }
                    } else if (isset($_GET["apply"])) {
                        if ($_POST["update"] === "on") {
                            siteconfig_add("site_maintenance", "1");
                            $restore_result = '';
                            if (($restore_result = do_backup_unpack())[0]) {
                                siteconfig_remove("site_maintenance");
                                echo '<p>Восстановление успешно завершено, ошибок нет.</p>';
                            } else {
                                echo '<p style="color: #6a0000">Произошла ошибка во время восстановления: </p>' . $restore_result[1];
                            }
                        } else {
                            echo "<p>Операция не была подтверждена, изменения не были произведены</p><a href=\"__admin?page=" . $currentPage . "&action=import\" 
                                            
                                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                            unlink("./engine/forbidden/backup.tar");
                        }
                    } else {
                        echo "<p>Ошибка запроса</p><a href=\"__admin?page=" . $currentPage . "&action=import\" 
                                            
                                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                        unlink("./engine/forbidden/backup.tar");
                    }
                }
                break;
        }
    }
}

function do_backup_unpack()
{
    global $db_host;
    global $db_login;
    global $db_name;
    global $db_password;
    $db_operations = 'none';
    $result[0] = true;
    if ($tarfile = new TarFile("./engine/forbidden/backup.tar")) {
        $json_info = json_decode($tarfile->extract_file_into_string($tarfile->get_file_details("backup-info.json")), true);
        if ($json_info["db"]) {
            $query = $tarfile->extract_file_into_string($tarfile->get_file_details("db_backup/db_dump.sql"));
            $db_operations = IMPORT_TABLES($db_host, $db_login, $db_password, $db_name, $query);
            $tarfile->exclude('db_backup');
        }
        $tarfile->exclude("backup-info.json");
        if (!($result[0] = $tarfile->extract_dir('', './'))) {
            $result[1] = 'Ошибка во время извлечения файлов из архива';
        }
    }
    unlink("./engine/forbidden/backup.tar");
    if ($db_operations !== 'none') {
        $result[0] &= $db_operations[0];
        if (!$db_operations[0]) {
            $result[1] = $db_operations[1] . "<br>" . $result[1];
        }
    }
    return $result;
}


function get_format_with_baloon_text($original, $baloon_text)
{
    return "<block class='element_with_textbox'>" . $original . '<div class=\'hidden_textbox\'>' . $baloon_text . '</div></block>';
}

function adminPanelBody_DrawMainPage()
{
    global $statisticsVisitors;
    $statisticsVisitors = new StatisticsVisitors();
    global $current_locale;
    $seconds = time() - $statisticsVisitors->visitors_24h_last_reset_time;
    $minutes = (int)($seconds / 60);
    $seconds -= $minutes * 60;
    $hours = (int)($minutes / 60);
    $minutes -= $hours * 60;
    foreach ($statisticsVisitors->dayRecord as $currentDay) {
        if (empty($dayMaxVisitors) || $currentDay->counter > $dayMaxVisitors->counter) {
            $dayMaxVisitors = $currentDay;
            continue;
        }
    }
    echo "<h2>" . $current_locale["statistics"] . ":</h2><p><b>[" . $current_locale["daily_counter"] . "] " . $current_locale["over_the_past"] . " " . ($hours > 0 ? $hours . " " . $current_locale["_hours"] . " " : "") . "$minutes " . $current_locale["_minutes"] . " " . $current_locale["_and"] . " $seconds " . $current_locale["_seconds"] . ":</b> " . ($statisticsVisitors->visitors_24h + (siteconfig_get('counter_dont_include_robots') == null ? $statisticsVisitors->visitors_24h_bots : 0)) . " " . $current_locale["_users"] . (siteconfig_get('counter_dont_include_robots') != null ? " " . $current_locale['_and'] : ',') . " " . $statisticsVisitors->visitors_24h_bots . " " . (siteconfig_get('counter_dont_include_robots') != null ? $current_locale["_search_bots"] : $current_locale["_of_them_are_search_bots"]) . ' ' . $current_locale['_have_visited_the_site'] . ", 
    " . $statisticsVisitors->visitors_known24h . " " . $current_locale["_users_have_already_visited_the_site_before"] . ".</p>
            <p><b>" . $current_locale["for_all_the_time"] . ": </b>" . $statisticsVisitors->allTimeVisitorsCount . ' ' . $current_locale["_users"] . (siteconfig_get('counter_dont_include_robots') != null ? " " . $current_locale['_and'] : ',') . " " . $statisticsVisitors->allTimeVisitorsBotsCount . " " . (siteconfig_get('counter_dont_include_robots') != null ? $current_locale["_search_bots"] : $current_locale["_of_them_are_search_bots"]) . ' ' . $current_locale['_have_visited_the_site'] . ". " . $statisticsVisitors->allTimeVisitorsKnownCount . " " . $current_locale["_users_have_already_visited_the_site_before"] . " (" . $current_locale["_the_counter_is_updated_every_24_hours"] . ")</p><br>";
    if (isset($dayMaxVisitors)) {
        echo "<p><b>" . date("d.m.Y", $dayMaxVisitors->time) . ":</b> " . $current_locale["_the_largest_number_of_visitors_was_recorded"] . ": " . $dayMaxVisitors->counter . ' ' . $current_locale['_users'] . (siteconfig_get('counter_dont_include_robots') != null ? " " . $current_locale['_and'] : ',') . ' ' . $dayMaxVisitors->bots_counter . " " . (siteconfig_get('counter_dont_include_robots') != null ? $current_locale["_search_bots"] : $current_locale["_of_them_are_search_bots"]) . ". " . $dayMaxVisitors->known_counter . " " . $current_locale["_users_have_already_visited_the_site_before"] . "</p>";
    }
    echo "Свободного места на диске: " . formatSize(disk_free_space(PATH)) . "<br>";
    echo "<div id=\"chart_div\" style=\"width: 100%; height: 600px;\"></div>

            ";
    include "engine/lib/chart.php";
}

function generate_table($data)
{
    if (is_array($data)) {
        if (is_array($data[0])) {
            echo "<table>";
            foreach ($data as $current_row_index => $current_row) {
                if ($current_row_index == 0)
                    echo "<thead><tr>";
                else
                    echo "<tr>";
                foreach ($current_row as $current_element) {
                    echo "<td>", $current_element, "</td>";
                }
                if ($current_row_index == 0)
                    echo "</tr></thead><tbody>";
                else
                    echo "</tr>";
            }
            echo "</tbody></table>";
        }
    }
}

function adminPanelBody_DrawAccountManager()
{
    global $table_users;
    global $current_locale;
    global $currentPage;
    global $currentUser;
    if (!isset($_GET["action"])) {
        $table_users->loadFromDatabaseAll();
        generate_form([new form_field_href_button(
            $current_locale["add"],
            "__admin?page=" . $currentPage . "&action=add",
            FORM_BUTTON_COLOR_BLACK)]);
        echo "<div><br></div>";
        $table_data[] = ['ID', $current_locale["login"], $current_locale["privileges"], $current_locale["active_sessions_count"], ''];

        foreach ($table_users->tableRecords as $selected_user) {
            $current_privileges = '';
            $user_privileges = json_decode(htmlspecialchars_decode($selected_user["privileges"]), true);
            $user_sessions = json_decode(htmlspecialchars_decode($selected_user["sessions"]), true);
            foreach ($user_privileges as $privilege) {
                switch ($privilege) {
                    case PRIVILEGE_ACCOUNTED:
                        $current_privileges = $current_locale["account_activated"];
                        break;
                    case PRIVILEGE_MODERATOR:
                        $current_privileges .= "<br>" . $current_locale["moderator"];
                        break;
                    case PRIVILEGE_ADMINISTRATOR:
                        $current_privileges .= "<br>" . $current_locale["administrator"];
                        break;
                }
            }
            $table_data[] = [$selected_user["id"],
                $selected_user["login"],
                $current_privileges,
                count($user_sessions) . generate_form([
                    new form_field_href_button(
                        $current_locale["details"],
                        "__admin?page=" . $currentPage . "&action=session_details&id=" . $selected_user["id"],
                        FORM_BUTTON_COLOR_BLACK,
                        count($user_sessions) > 0,
                        count($user_sessions) > 0 ? null : "Нет активных сессий")], '', 0, true),
                generate_form([new form_field_href_button(
                    $current_locale["edit"],
                    "__admin?page=" . $currentPage . "&action=edit&id=" . $selected_user["id"],
                    FORM_BUTTON_COLOR_ORANGE,
                    true),
                    new form_field_href_button(
                        $current_locale["reset_all_sessions"],
                        "__admin?page=" . $currentPage . "&action=reset_sessions&id=" . $selected_user["id"],
                        FORM_BUTTON_COLOR_BLACK,
                        count($user_sessions) > 0,
                        count($user_sessions) > 0 ? null : "Нет активных сессий"),
                    new form_field_href_button(
                        $current_locale['delete'],
                        "__admin?page=" . $currentPage . "&action=delete&id=" . $selected_user["id"],
                        FORM_BUTTON_COLOR_RED,
                        $selected_user["id"] !== $currentUser["id"],
                        $selected_user["id"] !== $currentUser["id"] ? null : $current_locale["you_cannot_delete_your_own_account"])], '', 0, true)
            ];

        }
        generate_table($table_data);
    } else if ($_GET["action"] == "add") {

    } else if ($_GET["action"] == "session_details") {
        if (!empty($_GET["id"])) {
            $table_users->loadFromDatabaseByKey("id", trim(htmlspecialchars(stripslashes($_GET["id"]))));
            if (!empty($table_users->tableRecords)) {
                if (!empty($_GET["reset_id"])) {
                    if (reset_session_by_session_id($_GET["reset_id"])) {
                        echo "<p>Сессия \"" . $_GET["reset_id"] . "\" была успешно сброшена</p>";
                        $table_users->loadFromDatabaseByKey("id", trim(htmlspecialchars(stripslashes($_GET["id"]))));
                    } else addErrorToLog("Account manager::session_details:reset_action_generator", "Session does not found");
                }
                $current_record = $table_users->tableRecords[0];
                $back_position_id = $current_record["id"] > 0 ? $current_record["id"] - 1 : 0;
                echo "<h2>Подробности сессий пользователя \"", $current_record["login"], "\":</h2>";
                $table_data[] = ['Время', 'Session ID', 'User-agent', 'Время окончания действия', ''];
                $user_sessions = json_decode(htmlspecialchars_decode($current_record["sessions"]), true);
                $side_session_id = trim(htmlspecialchars(stripslashes($_COOKIE['session'])));
                foreach ($user_sessions as $current_session) {
                    $table_data[] = [
                        date("H:i:s d.m.Y", $current_session['sign_up_time']),
                        get_format_with_baloon_text(substr($current_session["session_id"], 0, 13) . '...', $current_session["session_id"]),
                        $current_session["ip"] . "<br>" . $current_session["user_agent"],
                        date("H:i:s d.m.Y", $current_session['expire_time']),
                        ($side_session_id == $current_session["session_id"] ? '<b>Текущая</b><br>' : '') . generate_form([
                            new form_field_href_button(
                                $current_locale["reset"],
                                "__admin?page=" . $currentPage . "&reset_id=" . $current_session["session_id"] . "&action=session_details&id=" . $current_record["id"],
                                FORM_BUTTON_COLOR_BLACK,
                                count($user_sessions) > 0,
                                count($user_sessions) > 0 ? null : "Нет активных сессий")], '', 0, true)
                    ];
                }
                generate_table($table_data);
                generate_form([new form_field_href_button(
                    $current_locale["return_back"],
                    "__admin?page=" . $currentPage . "#" . $back_position_id,
                    FORM_BUTTON_COLOR_BLACK)]);
                //()->render();

            } else addErrorToLog("Account manager::session_details_generator", "Record does not found");

        } else addErrorToLog("Account manager::session_details_generator", "Empty GET[id] field");

    } else if ($_GET["action"] == "reset_sessions") {
        if (!empty($_GET["id"])) {
            $table_users->loadFromDatabaseByKey("id", trim(htmlspecialchars(stripslashes($_GET["id"]))));
            if (!empty($table_users->tableRecords)) {
                $current_record = $table_users->tableRecords[0];
                $back_position_id = $current_record["id"] > 0 ? $current_record["id"] - 1 : 0;
                if (reset_all_sessions($current_record["id"])) {
                    echo "<h3>Сессии учётной записи " . $current_record["login"] . " обнулены.</h3>";
                } else {
                    echo "Произошла ошибка при сбросе сессий данной учётной записи: нет активных сессий";
                }
                generate_form([new form_field_href_button(
                    $current_locale["return_back"],
                    "__admin?page=" . $currentPage . "#" . $back_position_id,
                    FORM_BUTTON_COLOR_BLACK)]);
                //()->render();

            } else addErrorToLog("Account manager::reset_sessions_generator", "Record does not found");

        } else addErrorToLog("Account manager::reset_sessions_generator", "Empty GET[id] field");
    } else if ($_GET["action"] == "edit") {

        if (!empty($_GET["id"])) {
            $table_users->loadFromDatabaseByKey("id", trim(htmlspecialchars(stripslashes($_GET["id"]))));
            if (!empty($table_users->tableRecords)) {
                $current_record = $table_users->tableRecords[0];
                if (empty($_GET["submit"])) {
                    $selected_user_fields = [];
                    foreach ($current_record as $record_key => $record_val) {
                        if (!in_array($record_key, ['id', 'login', 'hashed_password', 'privileges', 'sessions']))
                            $selected_user_fields[] = new form_field_text($record_key, $record_key, '', $record_val);
                    }
                    generate_form($selected_user_fields);
                } else {
                    $table_users->deleteRecord("id", $current_record["id"]);
                    $back_position_id = $current_record["id"] > 0 ? $current_record["id"] - 1 : 0;
                    if ($table_users->applyChanges()) {
                        echo $current_locale["deletion_complete"] . "<br>";
                    } else {
                        echo "<div class=\"error\">" . $current_locale["record_delete_error"] . "</div><br>" . $current_locale["error_details"] . ":<br>";
                    }
                    generate_form([new form_field_href_button(
                        $current_locale["return_back"],
                        "__admin?page=" . $currentPage . "#" . $back_position_id,
                        FORM_BUTTON_COLOR_BLACK)]);
                }
            } else addErrorToLog("Account manager::delete_generator", "Record does not found");

        } else addErrorToLog("Account manager::delete_generator", "Empty GET[id] field");
    } else if ($_GET["action"] == "delete") {
        if (!empty($_GET["id"])) {
            $table_users->loadFromDatabaseByKey("id", trim(htmlspecialchars(stripslashes($_GET["id"]))));
            if (!empty($table_users->tableRecords)) {

                $current_record = $table_users->tableRecords[0];
                $back_position_id = $current_record["id"] > 0 ? $current_record["id"] - 1 : 0;
                if ($current_record["id"] !== $currentUser["id"]) {
                    if (empty($_GET["submit"])) {
                        echo "<div style='align-self: center'>" . $current_locale["you_are_about_to_delete_an_entry_with_the_following_parameters"] . ":<br>";

                        echo "<p>" . $current_locale["login"] . ": \"" . htmlspecialchars_decode($current_record["login"]) . "\"</p>";
                        echo "<br></div><br>
                    <div style='align-self: center; font-size: 120%;'><b>" . $current_locale["!attention"] . ": " . $current_locale["!this_action_is_permanent"] . "!</b></div><br>
                    <div style='align-self: center;'><b>" . $current_locale["are_you_sure"] . "?</b></div><br>";
                        generate_form([new form_field_href_button(
                            $current_locale["yes_delete"],
                            "__admin?page=" . $currentPage . "&action=delete&id=" . $current_record["id"] . "&submit=true",
                            FORM_BUTTON_COLOR_RED,
                            true),
                            new form_field_href_button(
                                $current_locale["no_cancel"],
                                "__admin?page=" . $currentPage . "#" . $current_record["id"],
                                FORM_BUTTON_COLOR_BLACK,
                                true)]);
                    } else {
                        $table_users->deleteRecord("id", $current_record["id"]);

                        if ($table_users->applyChanges()) {
                            echo $current_locale["deletion_complete"] . "<br>";
                        } else {
                            echo "<div class=\"error\">" . $current_locale["record_delete_error"] . "</div><br>" . $current_locale["error_details"] . ":<br>";
                        }
                        generate_form([new form_field_href_button(
                            $current_locale["return_back"],
                            "__admin?page=" . $currentPage . "#" . $back_position_id,
                            FORM_BUTTON_COLOR_BLACK)]);
                    }
                } else {
                    echo "<div class=\"error\">" . $current_locale["record_delete_error"] . ": " . $current_locale["you_cannot_delete_your_own_account"] . "</div><br>";
                    generate_form([new form_field_href_button(
                        $current_locale["return_back"],
                        "__admin?page=" . $currentPage . "#" . $back_position_id,
                        FORM_BUTTON_COLOR_BLACK)]);
                }
            } else addErrorToLog("Account manager::delete_generator", "Record does not found");

        } else addErrorToLog("Account manager::delete_generator", "Empty GET[id] field");
    }
}

function adminPanelBody_DrawSiteSettings()
{
    global $currentPage;
    global $debug_password;
    global $engine_debug_mode;
    global $current_locale;

    generate_form([
        new form_field_checkbox_slider("maintenance_mode",
            $current_locale["maintenance_mode"],
            siteconfig_get("site_maintenance") !== null,
            true,
            $current_locale["deny_access_to_site_other_users_and_moderators"],
            "__admin?page=" . $currentPage . "&advanced_maintenance=" .
            (siteconfig_get("site_maintenance") !== null ? "0" : "1")),
        new form_field_checkbox_slider("debug_mode",
            $current_locale["debug_mode"],
            $engine_debug_mode,
            true,
            $current_locale["only_for_current_session"] . "<br>" . $current_locale["show_all_engine_errors"] . " (" . $current_locale["_php_errors_and_warnings"] . ")",
            "__admin?page=" . $currentPage .
            "&debug_key=" . ($engine_debug_mode ? "reset" : $debug_password)),
        new form_field_checkbox_slider("disable_cache",
            $current_locale["disable_cache"],
            siteconfig_get("disable_cache") !== null,
            true,
            $current_locale["disable_browser_caching_for_all_content"] . '<br>' .
            $current_locale['this_will_decrease_performance_but_content_will_always_be_downloaded_from_server'],
            "__admin?page=" . $currentPage . "&disable_cache=" .
            (siteconfig_get("disable_cache") !== null ? "0" : "1")),
        new form_field_checkbox_slider("counter_dont_include_robots",
            $current_locale["dont_count_robots"],
            siteconfig_get("counter_dont_include_robots") !== null,
            true,
            $current_locale['dont_include_robots_into_visitors_counter_but_show_on_graph'],
            "__admin?page=" . $currentPage . "&counter_dont_include_robots=" .
            (siteconfig_get("counter_dont_include_robots") !== null ? "0" : "1")),
    ]);
}

function adminPanelLeftMenu()
{
    echo "<ol class=\"nav-links\">";
    global $currentPage;
    global $current_locale;
    global $ADMIN_PANEL_PAGES;
    if (!isCurrentUserAccounted()) {
        adminPanelLeftMenu_addItem($current_locale["authorization"], "__admin", true);
    } else if (!isCurrentUserModerator()) {
        adminPanelLeftMenu_addItem($current_locale["authorization"], "__admin", true);
    } else {
        $i = 0;
        adminPanelLeftMenu_addItem($current_locale["main_page"], "__admin?page=" . PAGE_ID_MAIN, $currentPage === PAGE_ID_MAIN);
        foreach ($ADMIN_PANEL_PAGES as $currentPageKey => $currentLinkedPage) {
            adminPanelLeftMenu_addItem($currentLinkedPage->pageName, "__admin?page=" . $currentPageKey, $currentPage === $currentPageKey);
        }
        if (isCurrentUserAdministrator()) {
            echo "<div class=\"pl8 js-gps-track nav-links--link\" style='margin-top: 30px; color: #DC461D; border-left: solid 3px; border-radius: 9px 0px 0px 0px; border-bottom: dashed 1px; border-top: solid 3px; font-weight: bold; '>" . $current_locale["administration_tools"] . "</div>
<div style='border-left: solid 3px #DC461D; border-bottom: solid 3px #DC461D; padding-top: 6px; border-right: none; box-shadow: 5px 5px 7px rgba(0,0,0,0.5);border-radius: 0px 9px;'>";
            adminPanelLeftMenu_addItem($current_locale["account_manager"], "__admin?page=" . PAGE_ID_ACCOUNT_MANAGER, $currentPage === PAGE_ID_ACCOUNT_MANAGER);
            adminPanelLeftMenu_addItem($current_locale["db_manager"], "__admin?page=" . PAGE_ID_DB_MANAGER, $currentPage === PAGE_ID_DB_MANAGER);
            adminPanelLeftMenu_addItem($current_locale["site_settings"], "__admin?page=" . PAGE_ID_SITE_SETTINGS, $currentPage === PAGE_ID_SITE_SETTINGS);
            adminPanelLeftMenu_addItem($current_locale["visitors_journal"], "__admin?page=" . PAGE_ID_VISITORS_JOURNAL, $currentPage === PAGE_ID_VISITORS_JOURNAL);
            adminPanelLeftMenu_addItem($current_locale["backup_manager"], "__admin?page=" . PAGE_ID_BACKUP_MANAGER, $currentPage === PAGE_ID_BACKUP_MANAGER);
            echo "</div>";
        }
    }
    echo "</ol>";
}

function adminPanelLeftMenu_addItem($caption, $href, $isActive = false)
{
    if ($isActive) echo "<li class=\" youarehere\">"; else echo "<li>";
    echo "<a href=\"";
    echo $href;
    echo "\" class=\"pl8 js-gps-track nav-links--link\">";
    echo $caption;
    echo "</a></li>";
}


?>
