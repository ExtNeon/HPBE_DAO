<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 25.08.20
 * Time: 1:57
 */
$no_debug_messages = 1;

require_once "config/site_config.php";
require_once "lib/debug_mode_checker.php";
require_once "lib/auto_config_manager.php";
require_once "lib/other_tools.php";
require_once "lib/db_low_level_tools.php";
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0,pre-check=0", false);
header("Cache-Control: max-age=0", false);
header("Pragma: no-cache");


global $engine_debug_mode;
if ($engine_debug_mode && autoconfig_get("core_configured") === null) {
    if (isset($_GET["atoken"]) && $_GET["atoken"] == 'yes') {

        autoconfig_add("core_configured", "1");
        autoconfig_write();
        redirectTo("engine/initial_configuration?success=1");
        die();
    }
    //  echo "<br><a href='initial_configuration?atoken=yes'>Установить токен конфигурации</a>";
} else {
    if ($engine_debug_mode) {
        http_response_code(403);
        redirectTo("");
    } else if (autoconfig_get("core_configured") === null) {
        redirectTo("sample_page");
    } else {
        redirectTo("");
    }
    die();
}

const SETUP_STAGE_WELCOME = 1;
const SETUP_STAGE_DB_PARAMS = 2;
const SETUP_STAGE_SITE_PARAMS = 3;
const SETUP_ADMIN_ACCOUNT = 4;
const SETUP_STAGE_FINAL = 5;

$language = autoconfig_get("language");
if ($language === null) $language = "en";
require_once "locales.php";

if (($setup_stage = autoconfig_get("setup_stage")) === null) $setup_stage = 0;


if (autoconfig_get("language") === null) $setup_stage = 0;

const SETUP_CAPTION =
[SETUP_STAGE_WELCOME => "Начало",
    SETUP_STAGE_DB_PARAMS => "Подключение к базе данных",
    SETUP_STAGE_SITE_PARAMS => "Настройка сайта",
    SETUP_ADMIN_ACCOUNT => "Создание аккаунта администратора",
    SETUP_STAGE_FINAL => "Завершение"
];

setCurrentPage("engine/initial_configuration");


if ($setup_stage === 0 && isset($_GET["language"]) && isset($locales[$_GET["language"]])) {
    autoconfig_add('language', $_GET['language']);
    autoconfig_add('setup_stage', SETUP_STAGE_WELCOME);
    autoconfig_write();
    reloadPage();
}

if (isset($_GET["next"]) && $_GET["next"] == 2) {
    autoconfig_add('setup_stage', SETUP_STAGE_DB_PARAMS);
    autoconfig_write();
    reloadPage();
}

if (isset($_GET["reset_setup"])) {
    autoconfig_reset();
    reloadPage();
}

$_setup_db_host = 'localhost';
$_setup_db_login = '';
$_setup_db_password = '';
$_setup_db_name = '';
$_setup_registration_result = false;
$password_mismatch = false;

if ($setup_stage == SETUP_STAGE_DB_PARAMS) {
    if (isset($_GET["db_host"]) &&
        isset($_GET["db_login"]) &&
        isset($_GET["db_password"]) &&
        isset($_GET["db_name"])) {
        //trim(htmlspecialchars(stripslashes(
        autoconfig_add('db_host', trim(htmlspecialchars(stripslashes($_GET['db_host']))));
        autoconfig_add('db_login', trim(htmlspecialchars(stripslashes($_GET['db_login']))));
        autoconfig_add('db_password', trim(htmlspecialchars(stripslashes($_GET['db_password']))));
        autoconfig_add('db_name', trim(htmlspecialchars(stripslashes($_GET['db_name']))));
        autoconfig_write();
        reloadPage();
    } else if (autoconfig_get('db_host') !== null &&
        autoconfig_get('db_login') !== null &&
        autoconfig_get('db_password') !== null &&
        autoconfig_get('db_name') !== null) {
        $_setup_db_host = autoconfig_get('db_host');
        $_setup_db_login = autoconfig_get('db_login');
        $_setup_db_password = autoconfig_get('db_password');
        $_setup_db_name = autoconfig_get('db_name');
        if (isset($_GET["next"]) && $_GET["next"] == 3) {
            $connection = mysqli_connect($_setup_db_host, $_setup_db_login, $_setup_db_password);
            if ($connection) {
                $query = mysqli_query($connection, 'CREATE DATABASE IF NOT EXISTS `' . $_setup_db_name . '`;');
                if ($query) {
                    resolve_site_config();
                    require_once "lib/db_driver.php";
                    global $core_criticalError;
                    require_once 'config/table_structure_engine_config.php';
                    require_once 'config/table_structure_user_config.php';
                    require_once 'core_methods.php';

                    if (!$core_criticalError) {
                        updateCurrentUser();
                        reset_current_user_session();
                        autoconfig_add('setup_stage', SETUP_STAGE_SITE_PARAMS);
                        autoconfig_write();
                        reloadPage();
                    }
                } else {
                    echo mysqli_error($connection);
                }
            }

        }
    }
} else if ($setup_stage >= SETUP_STAGE_SITE_PARAMS) {
    resolve_site_config();
    require_once "lib/db_driver.php";
    global $core_criticalError;
    require_once 'config/table_structure_engine_config.php';
    require_once 'config/table_structure_user_config.php';
    require_once 'core_methods.php';
    updateCurrentUser();
    if (isset($_GET["sitename"]) && isset($_GET["admin_email"])) {
        autoconfig_add('site_name', trim(htmlspecialchars(stripslashes($_GET['sitename']))));
        autoconfig_add('admin_email', trim(htmlspecialchars(stripslashes($_GET['admin_email']))));
        autoconfig_add('setup_stage', SETUP_ADMIN_ACCOUNT);
        autoconfig_write();
        reloadPage();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST'
        && isset($_POST['login']) && isset($_POST['password']) && isset($_POST["password_again"])) {
        if ($_POST['password'] == $_POST['password_again']) {
            $admin_user = ['login' => $_POST['login'], 'password' => $_POST['password'],
                'privileges' => ['P', 'M', 'A']];
            reset_current_user_session();
            $result = registerNewUser($admin_user);
            $_setup_registration_result = $result;
            if ($result) {
                $_setup_registration_result &= loginOperation($_POST['login'], $_POST['password']);
            }
        } else {
            $password_mismatch = true;
        }
    }

    if ($setup_stage == SETUP_ADMIN_ACCOUNT && isset($_GET['next']) && $_GET['next'] == SETUP_STAGE_FINAL) {
        autoconfig_add('setup_stage', SETUP_STAGE_FINAL);
        autoconfig_write();
        reloadPage();
    }
    if ($setup_stage == SETUP_STAGE_FINAL && isset($_GET['finish']) && $_GET['finish'] == 1) {
        autoconfig_remove('setup_stage');
        autoconfig_add('core_configured', 1);
        autoconfig_write();
        redirectTo("__admin");
    }
}

/*
 * ;core_configured=0
;db_login=root
;db_password=1234
;db_name=engine_test
;language=ru
 */
?>

    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>HPBE DAO initial setup</title>
        <link rel="stylesheet" href="res/__admin/_n/css/index.css">
        <link href="res/__admin/_n/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" type="text/css"
              href="res/__admin/_n/css/toggle.css">
        <link rel="stylesheet" type="text/css"
              href="res/__admin/_n/css/stacks.css">
        <link rel="stylesheet" type="text/css"
              href="res/__admin/_n/css/primary.css">

        <link rel="stylesheet" type="text/css"
              href="res/__admin/_n/css/index.css">
        <link rel="stylesheet" type="text/css"
              href="res/__admin/_n/css/custom.css">
    </head>
    <body class="question-page unified-theme">
    <?php initial_setup_body(); ?>

    </body>
    </html>

<?php

function initial_setup_body()
{
    global $setup_stage;
    global $locales;
    if ($setup_stage === 0) {
        echo " <main class=\"main \">
    <section class=\"about\">
        <div class=\"container\">
            <h2 class=\"section-title\">HPBE DAO</h2>
            <div class=\"about__text\">
                Initial setup manager<br>
                <form action=\"initial_configuration\" method=\"get\">
                <select name='language' required>";
        foreach ($locales as $locale_key => $sel_locale) {
            echo "<option " . ($locale_key == 'en' ? "selected" : '') . " value='" . $locale_key . "'>" . $sel_locale["locale_name"] . "</option>";
        }
        echo "</select>
            <input type='submit' value='>>'>
            </form>
            </div>
            
        </div>
    </section></main>";

    } else {
        configurator();
    }
}


function configurator()
{
    global $setup_stage;
    global $current_locale;
    echo "

    <div id=\"notify-container\"></div>
    <header class=\"top-bar js-top-bar top-bar__network _fixed\">
        <div class=\"wmx12 mx-auto grid ai-center h100\" role=\"menubar\">
            <div class=\"-main grid--cell\">
                <a>
                    <div aria-hidden=\"true\" class=\"svg-icon native mtn1 iconLogoSEAlternativeSm\" width=\"107\" height=\"15\"
                         style=\"font-size: 16pt;color:white\">
                        HPBE DAO initial setup
                    </div>
                </a>
            </div>
            <ol class=\"overflow-x-auto ml-auto -secondary grid ai-center list-reset h100 user-logged-out\"
                role=\"presentation\">


                <li class=\"-ctas\">
                    
                </li>

                <li class=\"-ctas\">
                    <a href=\"initial_configuration?reset_setup=1\"
                       class=\"login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["reset"] . "</a>
                </li>
            </ol>

        </div>
    </header>


    <div class=\"container\">


        <div id=\"left-sidebar\" data-is-here-when=\"md lg\" class=\"left-sidebar js-pinned-left-sidebar ps-relative\">
            <div class=\"left-sidebar--sticky-container js-sticky-leftnav\">
                <nav role=\"navigation\">";
    initConfigLeftMenu();
    echo "
                </nav>
            </div>
        </div>


        <div id=\"content\" class=\"\">


            <div itemprop=\"mainEntity\" itemscope=\"\" itemtype=\"http://schema.org/Question\">


                <div class=\"inner-content clearfix\">


                    <div id=\"question-header\" class=\"grid sm:fd-column\">
                        <h1 itemprop=\"name\"
                            class=\"grid--cell fs-headline1 fl1 ow-break-word mb8\">" . SETUP_CAPTION[$setup_stage] . "</h1>
                    </div>
                    <div class=\"grid fw-wrap pb8 mb16 bb bc-black-2\">
                        <span class=\"fc-light mr2\"></span>
                    </div>
                </div>
                <div id=\"mainbar\" role=\"main\" aria-label=\"question and answers\">


                    <div class=\"question\" data-questionid=\"811116\" id=\"question\">


                        <div class=\"post-layout\">


                            <div class=\"postcell post-layout--right\">

                                <div class=\"post-text\" itemprop=\"text\">

                                    ";
    initConfigInteractivePart();
    echo "

                                </div>


                            </div>


                        </div>
                    </div>


                </div>
                
            </div>

        </div>


    </div>

    </div>
    <script src=\"engine/lib/js/jquery-3.4.1.min.js\"></script>
    <script src=\"engine/lib/js/jquery.maskedinput.min.js\"></script>
    <!--for IE8, ES5 shims are required-->
    <!--[if IE 8]>
    <script src=\"engine/lib/vendor/es5-shim.js\"></script>
    <script src=\"engine/lib/vendor/es5-sham.js\"></script>
    <![endif]-->

    <script src=\"engine/lib/vendor/jquery/jquery.js\"></script>
    <script src=\"engine/lib/wymeditor/jquery.wymeditor.min.js\"></script>
   
";
}


function initConfigLeftMenu()
{
    echo "<ol class=\"nav-links\">";
    global $setup_stage;
    foreach (SETUP_CAPTION as $stage_key => $caption) {
        initConfigLeftMenu_addItem($caption, $setup_stage == $stage_key);
    }

    echo "</ol>";
}

function initConfigLeftMenu_addItem($caption, $isActive = false)
{
    if ($isActive) echo "<li class=\" youarehere\">"; else echo "<li>";
    echo "<div class=\"pl8 js-gps-track nav-links--link\">";
    echo $caption;
    echo "</div></li>";
}

function initConfigInteractivePart()
{
    global $current_locale;
    global $setup_stage;
    global $_setup_db_host;
    global $_setup_db_login;
    global $_setup_db_password;
    global $_setup_db_name;
    global $_setup_registration_result;
    global $core_criticalError;
    global $password_mismatch;
    $db_check_correct = false;
    switch ($setup_stage) {
        case SETUP_STAGE_WELCOME:
            echo "Добро пожаловать в менеджер по первоначальной настройке сайта. <br>
                    Настройка состоит из нескольких шагов, к которым будет приложена подробная инструкция.<br>
                     В случае, если вы ошиблись, или что - то пошло не по плану - нажатие на кнопку сбросить в заголовке сбросит весь прогресс настройки к странице выбора языка.<br>
                    <br><a href=\"initial_configuration?next=2\"
                           class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["continue"] . "</a><div><br></div>";
            break;
        case SETUP_STAGE_DB_PARAMS:

            if ($_setup_db_password != '') {
                $connection = mysqli_connect($_setup_db_host, $_setup_db_login, $_setup_db_password);
                if ($connection) {
                    //$db_check_correct = true;
                    echo "Соединение с базой данных успешно, но мы ещё не попытались создать базу данных с названием <strong>" . $_setup_db_name . "</strong>.<br>
                            Если вы уверены в том, что базы с таким названием нет, либо (если она существует) она была создана ранее HPBE DAO, нажмите создать.<br>
                            В противном случае измените параметры в форме снизу.";
                    echo "<br><br><a href=\"initial_configuration?next=3\"
                           class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["create"] . "</a><div><br></div>";

                } else {
                    echo 'Произошла ошибка во время соединения с базой данных: ' . mysqli_connect_error();
                }
            } else {
                echo '<p>Введите учётные данные для подключения к базе данных (данные аккаунта СУБД). 
                            Учтите: движок не может создать пользователя базы данных, так как запускается в режиме ограниченных прав по соображениям 
                            безопасности. Вы должны создать пользователя через специальные средства хостера самостоятельно, 
                            либо использовать уже имеющийся аккаунт.</p>';
                echo '<p>Хост базы данных обычно всегда localhost, однако если в вашем случае это не так, укажите хост базы данных.</p>';
                echo '<p>Название базы данных рекомендуется указывать осмысленно, например <strong>dao_my_new_site</strong>, где dao - префикс, позволяющий <br>
                    при наличии на сервере базы данных с таким же названием, но другим движком, не затереть информацию.</p>';
                echo '<p>В случае, если базы данных с таким названием на сервере нет, она будет создана автоматически.</p><br><br>';
            }
            if (!$db_check_correct) {

                generate_form([
                    new form_field_text('db_host',
                        'Хост базы данных',
                        'localhost',
                        $_setup_db_host,
                        true,
                        true),
                    new form_field_text('db_login',
                        'Логин для СУБД',
                        'login',
                        $_setup_db_login,
                        true,
                        true),
                    new form_field_password('db_password',
                        'Пароль для СУБД',
                        'password',
                        $_setup_db_password,
                        true,
                        false),
                    new form_field_text('db_name',
                        'Название базы данных',
                        'dao_my_new_site',
                        $_setup_db_name,
                        true,
                        true),
                    new form_field_submit_button(
                        $current_locale["save"], FORM_BUTTON_COLOR_ORANGE
                    )
                ], '/engine/initial_configuration');
            }
            break;
        case SETUP_STAGE_SITE_PARAMS:
            if ($core_criticalError) {
                echo '<p>Произошла ошибка при работе с базой данных:</p>' . getErrorList();
                echo '<br><br><p>Вам необходимо самостоятельно решить проблему при помощи редактирования конфигурационных файлов или базы данных, либо сбросить настройки.</p>';
                echo '<p><strong>Важно:</strong> при сбросе настроек, созданная вами база данных не удаляется!</p>';
            } else {
                echo '<p>Введите название сайта и e-mail администратора.</p>';
                echo '<p>Название сайта используется в нескольких целях: служебное (заголовок страниц) и заголовок панели администратора.</p>';
                echo '<p>E-mail администратора нужен для сброса пароля главного администратора в случае его утери, а ещё он выводится в случае критических ошибок, чтобы пользователи могли отправить вам информацию об ошибке.</p>';
                generate_form([
                    new form_field_text('sitename',
                        'Название сайта',
                        'My test site',
                        '',
                        true,
                        true),
                    new form_field_text('admin_email',
                        'E-mail администратора',
                        'admin@site.com',
                        '',
                        true,
                        true),
                    new form_field_submit_button(
                        $current_locale["save"], FORM_BUTTON_COLOR_ORANGE
                    )
                ], '/engine/initial_configuration');

            }
            break;
        case SETUP_ADMIN_ACCOUNT:
            if ($_SERVER['REQUEST_METHOD'] == 'POST' || isCurrentUserAdministrator() && !$password_mismatch) {
                if ($_setup_registration_result || isCurrentUserAdministrator()) {
                    echo "<br><br><p>Регистрация администратора успешно завершена, авторизация успешна.</p>";
                    echo "<br><br><a href=\"initial_configuration?next=5\"
                           class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["continue"] . "</a><div><br></div>";
                } else {
                    echo "<br><br><p>При регистрации или авторизации произошла ошибка: </p>" . $_SESSION["register_result"];
                    echo '<br><br>' . $_SESSION['login_result'];
                    echo "<br><br><a href=\"initial_configuration\"
                           class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">Попробовать снова</a><div><br></div>";
                }
            } else {
                echo '<p>HPBE DAO имеет систему аккаунтов. Каждый аккаунт имеет определённое количество прав, характеризующихся флагами: обычный пользователь, модератор, администратор.</p>';
                echo '<p>Обычный пользователь не имеет доступа к панели администратора, модератор может создавать, изменять и удалять посты в панели администратора, но не имеет доступа к специальным настройкам.</p>';
                echo '<p>Администратор - аккаунт с максимальным количеством прав. Вам необходимо создать первый аккаунт администратора. В дальнейшем, вы сможете управлять правами других пользователей через панель.</p>';
                if ($password_mismatch) {
                    echo '<br><br><p>Пароли не совпадают! Попробуйте снова.</p>';
                }
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
                    new form_field_password('password_again',
                        $current_locale['enter_password_again'],
                        $current_locale['enter_password_again'],
                        '',
                        true,
                        true),
                    new form_field_submit_button($current_locale['create'], FORM_BUTTON_COLOR_BLACK)
                ], '/engine/initial_configuration', FORM_METHOD_POST);
            }
            break;
        case SETUP_STAGE_FINAL:
            echo '<p>Настройка успешно завершена.</p>';
            echo '<p>Все ключевые параметры для работы сайта были успешно установлены, но сайт всё ещё не запущен. Для продолжения нажмите на кнопку снизу.</p>';
            echo '<p>Спасибо за использование HPBE DAO!</p>';
            echo "<br><br><a href=\"initial_configuration?finish=1\"
                           class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">Запустить сайт</a><div><br></div>";
    }
}
