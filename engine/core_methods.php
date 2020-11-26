<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 10.09.20
 * Time: 23:31
 */
const ID_SESSION_EXPIRED = -2;
/**
 * Проводит процедуру авторизации
 * @param $userlogin Логин
 * @param $userpassword Пароль
 * @return bool|int True в случае удачи, false в случае ошибки базы данных и -1 в случае, если логин или пароль не найдены или не подходят
 */
function loginOperation($userlogin, $userpassword)
{
    $userlogin = trim(htmlspecialchars(stripslashes($userlogin)));
    $userpassword = trim(htmlspecialchars(stripslashes($userpassword)));
    global $table_users;
    global $current_locale;
    if ($table_users->loadFromDatabaseByKey("login", $userlogin)) {
        if (!empty($table_users->tableRecords)) {
            $userId = $table_users->tableRecords[0]["id"];
            $hashedPassword = $table_users->tableRecords[0]["hashed_password"];
            if ($userId < 0 || $hashedPassword === -1 || password_verify($userpassword, $hashedPassword) != TRUE) {
                $_SESSION['login_result'] = $current_locale["login_or_password_not_found"];
                return -1;
            } else {
                $session_id = md5(mt_rand()) . md5(time()); //Совершенно случайный набор символов
                global $session_alive_time;
                $expires = /*$longSession ? */
                    time() + $session_alive_time/* : 0*/
                ;
                $sessions_from_database = json_decode(htmlspecialchars_decode($table_users->getOneElementWhere("id", $userId)["sessions"]), true);
                $sessions_from_database[] = [
                    "session_id" => $session_id,
                    "expire_time" => $expires,
                    "sign_up_time" => time(),
                    "user_agent" => $_SERVER['HTTP_USER_AGENT'],
                    "ip" => $_SERVER['REMOTE_ADDR']];
                $sessions_encoded = json_encode($sessions_from_database);
                if (!$sessions_encoded) addErrorToLog("login module", "json encoding error");
                $table_users->updateRecord("id", $userId, [
                    "sessions" => htmlspecialchars($sessions_encoded),
                ]);

                if ($table_users->applyChanges()) {
                    setcookie('session', $session_id, $expires, '/', '', true, true); // 86400 = 1 день в секундах. Наша кука будет держаться 30 дней

                    global $table_visitors;
                    $table_visitors->loadFromDatabaseByKey('user_agent', htmlspecialchars($_SERVER['HTTP_USER_AGENT']),
                        "AND (`visitor_cookie`=\"" . (isset($_COOKIE['DAILY_VISITOR']) ? $_COOKIE['DAILY_VISITOR'] : 0) . "\"
                        OR `visitor_cookie`=\"-1\") 
                        AND `ip`=\"" . ($_SERVER['REMOTE_ADDR']) . "\" 
                        AND `user_id`=\"-1\" 
            AND `mirror_visitor_id`=\"-1\"");
                    if (!empty($table_visitors->tableRecords)) {
                        $table_visitors->updateRecord('id', $table_visitors->tableRecords[0]['id'], [
                            'user_id' => $userId
                        ]);
                        if ($table_visitors->applyChanges()) {
                            $_SESSION['login_result'] = $current_locale["you_have_been_logged_in_successfully"];
                            return true;
                        } else {
                            $_SESSION['login_result'] = $current_locale["db_error"];
                            return false;
                        }
                    }
                    $_SESSION['login_result'] = $current_locale["you_have_been_logged_in_successfully"];
                    return true;
                } else {
                    $_SESSION['login_result'] = $current_locale["db_error"];
                    return false;
                }

            }
        } else {
            $_SESSION['login_result'] = $current_locale["login_or_password_not_found"];
            return -1;
        }
    }
    return false;
}

/**
 * @return mixed Строка с результатом операции авторизации
 */
function getLoginResultString()
{
    if (isset($_SESSION["login_result"])) {
        $loginResult = $_SESSION['login_result'];
        unset($_SESSION['login_result']);
        return $loginResult;
    } else return '';
}

/**
 * @return mixed Строка с результатом регистрации
 */
function getRegisterResultString()
{
    if (isset($_SESSION['register_result'])) {
        $regResult = $_SESSION['register_result'];
        unset($_SESSION['register_result']);
        return $regResult;
    } else return '';
}

/**
 * @return int|null Массив с полями текущего пользователя с текущей сессией из базы данных, или null в случае неудачи
 */
function getUserBySession()
{
    global $table_users;
    if (empty($_COOKIE['session'])) {
        $side_session_id = "empty";
    } else {
        $side_session_id = trim(htmlspecialchars(stripslashes($_COOKIE['session'])));
    }
    if ($side_session_id === "") {
        addWarningToLog("GET UID BY SESSION", "session is empty");
        // if ($engine_debug_mode) printf("Error session-checking: %s\n<br>", "Session is empty");
        return null;
    }
    $table_users->loadFromDatabaseAll(); //Грузим всех юзеров (да, плохо для производительности
    //todo Вот этот момент проработать
    if (!empty($table_users->tableRecords)) { //Если юзеры есть
        foreach ($table_users->tableRecords as $gettedUser) { // То перебираем всех
            $database_sessions_for_user = json_decode(htmlspecialchars_decode($gettedUser["sessions"]), true); //Получаем сессии пользователя
            if ($database_sessions_for_user !== NULL) { //И если они есть
                foreach ($database_sessions_for_user as $yetAnotherSession) { //Перебираем сессии
                    if ($yetAnotherSession["session_id"] === $side_session_id) { //Если совпадает с нашей
                        if ($yetAnotherSession["expire_time"] <= time() || $yetAnotherSession['user_agent'] != $_SERVER["HTTP_USER_AGENT"]) { //Но просрочена или юзер агент изменился
                            addWarningToLog("Session manager", "Session expired");
                            // if ($engine_debug_mode) printf("Error session-checking: %s\n<br>", "Session expired");
                            reset_current_user_session(); //Сбрасываем к херам
                            setAllSessionsExpired($gettedUser["id"]); //Да ещё и остальные тоже впридачу, чтобы неповадно было
                            return ID_SESSION_EXPIRED; //EXPIRES
                        } else {
                            $gettedUser["privileges"] = json_decode(htmlspecialchars_decode($gettedUser["privileges"]), true); //А в случае удачи, грузим привелегии
                            return $gettedUser;  //И возвращаем все поля из базы данных, плюс декодированный массив привелегий
                        }
                    }
                }
            }
        }
    }
    return null;
}

/**
 * Сбрасывает текущую активную сессию пользователя или все сессии
 * @param bool $resetAllSessionsForUser Если true - сбрасывает все сессии пользователя, если false - то только активную
 * @return bool результат записи изменений в базу данных или общего процесса сброса сессии.
 */
function reset_current_user_session($resetAllSessionsForUser = false)
{
    global $table_users;
    //  global $engine_debug_mode;
    $session_id = trim(htmlspecialchars(stripslashes($_COOKIE['session'])));
    if ($session_id != "") {
        @setcookie('session', '');
        $table_users->loadFromDatabaseAll();
        if (!empty($table_users->tableRecords)) {
            foreach ($table_users->tableRecords as $gettedUser) {
                $database_sessions_for_user = json_decode(htmlspecialchars_decode($gettedUser["sessions"]), true);
                if ($database_sessions_for_user !== NULL) {
                    foreach ($database_sessions_for_user as $session_element_key => $yetAnotherSession) {
                        if ($yetAnotherSession["session_id"] === $session_id) {
                            if ($resetAllSessionsForUser) {
                            } else {
                                unset($database_sessions_for_user[$session_element_key]);
                            }
                            $table_users->updateRecord("id", $gettedUser["id"], [
                                "sessions" => htmlspecialchars(json_encode($database_sessions_for_user)),
                            ]);
                            return $table_users->applyChanges();
                        }
                    }
                }
            }
        }
    }
    return FALSE;
}

/**
 * Делает все сессии пользователя просроченными, не разрывая их (не удаляя информацию о сессии)
 * @param $user_id Идентефикатор пользователя
 * @return bool Результат
 */
function setAllSessionsExpired($user_id)
{
    global $table_users;
    //  global $engine_debug_mode;
    $table_users->loadFromDatabaseByKey("id", $user_id);
    if (!empty($table_users->tableRecords)) {
        $sessions_from_database = json_decode(htmlspecialchars_decode($table_users->getOneElementWhere("id", $user_id)["sessions"]), true);
        if (!empty($sessions_from_database)) {
            foreach ($sessions_from_database as $currentSessionKey => $currentSession) {
                $sessions_from_database[$currentSessionKey]["expire_time"] = 0;
            }
            $table_users->updateRecord("id", $user_id, [
                "sessions" => htmlspecialchars(json_encode($sessions_from_database)),
            ]);
            return $table_users->applyChanges();
        }
    }
    return FALSE;
}

/**
 * Удаляет все данные о сессиях пользователя, разрывая их.
 * @param $user_id Идентефикатор пользователя
 * @return bool Результат
 */
function reset_all_sessions($user_id)
{
    global $table_users;
    $table_users->loadFromDatabaseByKey("id", $user_id);
    if (!empty($table_users->tableRecords)) {
        $sessions_from_database = json_decode(htmlspecialchars_decode($table_users->getOneElementWhere("id", $user_id)["sessions"]), true);
        if (!empty($sessions_from_database)) {
            $sessions_from_database = [];
            $table_users->updateRecord("id", $user_id, [
                "sessions" => htmlspecialchars(json_encode($sessions_from_database)),
            ]);
            return $table_users->applyChanges();
        }
    }
    return FALSE;
}

function reset_session_by_session_id($session_id)
{
    global $table_users;
    $table_users->loadFromDatabaseAll(); //Грузим всех юзеров (да, плохо для производительности
    //todo Вот этот момент проработать
    if (!empty($table_users->tableRecords)) { //Если юзеры есть
        foreach ($table_users->tableRecords as $gettedUser) { // То перебираем всех
            $database_sessions_for_user = json_decode(htmlspecialchars_decode($gettedUser["sessions"]), true); //Получаем сессии пользователя
            if ($database_sessions_for_user !== NULL) { //И если они есть
                foreach ($database_sessions_for_user as $session_element_key => $yetAnotherSession) { //Перебираем сессии
                    if ($yetAnotherSession["session_id"] === $session_id) { //Если совпадает с нашей
                        unset($database_sessions_for_user[$session_element_key]);
                        $table_users->updateRecord("id", $gettedUser["id"], [
                            "sessions" => htmlspecialchars(json_encode($database_sessions_for_user)),
                        ]);
                        return $table_users->applyChanges();
                    }
                }
            }
        }
    }
    return null;
}

function updateCurrentUser()
{
    global $currentUser;
    $currentUser = getUserBySession();
}

function registerNewUser(array $fields)
{
    global $current_locale;
    if ($fields["login"] === null || $fields["password"] === null) {
        $_SESSION["register_result"] = $current_locale["reg_not_enough_data"];
        return FALSE;
    }
    foreach ($fields as $key => $currentRec) {
        if (!is_array($fields[$key])) $fields[$key] = trim(htmlspecialchars(stripslashes($currentRec)));
    }
    $hashedPassword = password_hash($fields["password"], PASSWORD_DEFAULT);
    $userLogin = $fields["login"];
    unset($fields["password"]);
    $fields["hashed_password"] = $hashedPassword;
    $fields["privileges"] = htmlspecialchars(json_encode($fields["privileges"]));
    global $table_users;
    $table_users->loadFromDatabaseByKey("login", $userLogin);
    if (empty($table_users->tableRecords)) {
        if (!$table_users->addNewRecord($fields)) {
            $_SESSION["register_result"] = $current_locale["reg_too_much_data"];
        } else if ($table_users->applyChanges()) {
            $_SESSION['register_result'] = $current_locale["you_have_been_registered_successfully"];
            return TRUE;
        } else {
            $_SESSION['register_result'] = $current_locale["something_went_wrong"];
            return FALSE;
        }
    } else {
        $_SESSION['register_result'] = $current_locale["this_login_omitted"];
        return FALSE;
    }
    return FALSE;
}

function saveCurrentUserToDatabase()
{
    global $currentUser;
    if (is_array($currentUser)) {
        foreach ($currentUser as $key => $currentRec) {
            $currentUser[$key] = trim(htmlspecialchars(stripslashes($currentRec)));
        }
        if (!empty($currentUser["password"])) {
            $currentUser["hashed_password"] = password_hash($currentUser["password"], PASSWORD_DEFAULT);
            unset($currentUser["password"]);
        }

        $currentUser["privileges"] = htmlspecialchars(json_encode($currentUser["privileges"]));
        global $table_users;
        $table_users->loadFromDatabaseByKey("id", $currentUser["id"]);
        if (!empty($table_users->tableRecords)) {
            $table_users->updateRecord("id", $currentUser["id"], $currentUser);
            return $table_users->applyChanges();
        } else {
            global $current_locale;
            $_SESSION['status_message'] = $current_locale["this_account_have_not_found"];
            return FALSE;
        }
    }
    return FALSE;
}

function isCurrentUserModerator()
{
    global $currentUser;
    return isCurrentUserSessionActive() && in_array(PRIVILEGE_MODERATOR, $currentUser["privileges"]);
}

function isCurrentUserAdministrator()
{
    global $currentUser;
    return isCurrentUserSessionActive() && in_array(PRIVILEGE_ADMINISTRATOR, $currentUser["privileges"]);
}

/**
 * @return bool Залогинен ли пользователь на базовом уровне (активна ли сессия, подошёл ли пароль и логин при авторизации)
 * При этом, неважно пуст ли аккаунт или нет, есть ли какие - либо права
 */
function isCurrentUserSessionActive()
{
    global $currentUser;
    return $currentUser !== null && $currentUser !== ID_SESSION_EXPIRED && is_array($currentUser);
}

/**
 * @return bool Есть ли у пользователя право называть себя пользователем, или он просто пустой аккаунт без информации и прав
 */
function isCurrentUserAccounted()
{
    global $currentUser;
    return isCurrentUserSessionActive() && in_array(PRIVILEGE_ACCOUNTED, $currentUser["privileges"]);
}

function convertDateToString($date)
{
    $explodedDate = explode('.', $date, 3);
    $monthName = "";
    switch ($explodedDate[1]) {
        case "1":
            $monthName = "января";
            break;
        case "2":
            $monthName = "февраля";
            break;
        case "3":
            $monthName = "марта";
            break;
        case "4":
            $monthName = "апреля";
            break;
        case "5":
            $monthName = "мая";
            break;
        case "6":
            $monthName = "июня";
            break;
        case "7":
            $monthName = "июля";
            break;
        case "8":
            $monthName = "августа";
            break;
        case "9":
            $monthName = "сентября";
            break;
        case "10":
            $monthName = "октября";
            break;
        case "11":
            $monthName = "ноября";
            break;
        case "12":
            $monthName = "декабря";
            break;
    }
    return $explodedDate[0] . ' ' . $monthName . ' ' . $explodedDate[2];
}

class CustomDate
{
    var $day = 0;
    var $month = 0;
    var $year = 0;

    /**
     * customDate constructor.
     * @param $strDate
     */
    public function __construct($strDate)
    {
        $exploded = explode('.', $strDate, 3);
        if (count($exploded) === 3) {
            $this->day = $exploded[0];
            $this->month = $exploded[1];
            $this->year = $exploded[2];
        }
    }

    function isDateInFutureOrNow()
    {
        $currentDate = explode('-', date("d-m-Y"));
        if (count($currentDate) === 3) {
            if ($currentDate[2] > $this->year) {
                return true;
            } else if ($currentDate[2] === $this->year) {
                if ($currentDate[1] > $this->month) {
                    return true;
                } else if ($currentDate[1] === $this->month) {
                    return $this->day < $currentDate[0];
                } else return false;
            } else return false;
        } else return false;
    }
}

class StatisticsVisitors_dayRecord
{
    var $time = 0;
    var $counter = 0;
    var $known_counter = 0;
    var $bots_counter = 0;

    /**
     * StatisticsVisitors_dayRecord constructor.
     * @param int $time
     * @param int $counter
     */
    public function __construct($time, $counter, $known_counter, $bots)
    {
        $this->time = $time;
        $this->counter = $counter;
        $this->known_counter = $known_counter;
        $this->bots_counter = $bots;
    }


}

class StatisticsVisitors
{
    var $dayRecord = [];
    var $allTimeVisitorsCount = 0;
    var $allTimeVisitorsKnownCount = 0;
    var $allTimeVisitorsBotsCount = 0;
    var $visitors_24h = 0;
    var $visitors_known24h = 0;
    var $visitors_24h_last_reset_time = 0;
    var $visitors_24h_bots = 0;

    /**
     * StatisticsVisitors constructor.
     */
    public function __construct($do_not_do_anything = false)
    {
        if (!$do_not_do_anything) {
            global $table_daysCounterInfo;
            if (siteconfig_get("visitors_24h") === null) {
                addErrorToLog("Statistics loader", "10: Error config table import");
            } else {
                $this->visitors_24h = siteconfig_get("visitors_24h");
                $this->visitors_known24h = siteconfig_get("visitors_known24h");
                $this->visitors_24h_last_reset_time = siteconfig_get("visitors_24h_last_reset_time");
                $this->visitors_24h_bots = siteconfig_get("visitors_bots_24h");
                if ($this->visitors_24h == null) $this->visitors_24h = 0;
                if ($this->visitors_known24h == null) $this->visitors_known24h = 0;
                if ($this->visitors_24h_last_reset_time == null) $this->visitors_24h_last_reset_time = 0;
                if ($this->visitors_24h_bots == null) $this->visitors_24h_bots = 0;
            }
            if (!$table_daysCounterInfo->loadFromDatabaseAll()) {
                addErrorToLog("Statistics loader", "10: Error daysCounter table import");
            } else {
                if (!empty($table_daysCounterInfo->tableRecords)) {
                    foreach ($table_daysCounterInfo->tableRecords as $currentRecord) {
                        $this->dayRecord[] = new StatisticsVisitors_dayRecord($currentRecord["time"], $currentRecord["count"] + (siteconfig_get('counter_dont_include_robots') == null ? $currentRecord['bots'] : 0), $currentRecord["known_count"], $currentRecord["bots"]);
                        $this->allTimeVisitorsCount += $currentRecord["count"] + (siteconfig_get('counter_dont_include_robots') == null ? $currentRecord['bots'] : 0);
                        $this->allTimeVisitorsKnownCount += $currentRecord["known_count"];
                        $this->allTimeVisitorsBotsCount += $currentRecord['bots'];
                    }
                }
            }
        }
    }


}

function process_visitors_counter()
{
    if (empty($_COOKIE["DAILY_VISITOR"]) || (isset($_COOKIE["DAILY_VISITOR"]) && $_COOKIE["DAILY_VISITOR"] < time())) {
        global $table_siteConfig;
        global $table_visitors;
        $was_visitor = false;
        $was_bot = false;
        $querySuccess = siteconfig_reload();
        //$querySuccess = $table_siteConfig->loadFromDatabaseAll();
        global $currentUser;
        $visitors_24h_res_time = siteconfig_get("visitors_24h_last_reset_time");
        $table_visitors->loadFromDatabaseByKey('user_agent', htmlspecialchars($_SERVER['HTTP_USER_AGENT']),
            "AND `time`>=\"" . ($visitors_24h_res_time == null ? time() : $visitors_24h_res_time) . "\" 
            AND `mirror_visitor_id`=\"-1\"");
        $ip_s_list = [];
        foreach ($table_visitors->tableRecords as $an_record) {
            $ip_s_list[] = $an_record['ip'];
        }
        if (empty($table_visitors->tableRecords) ||
            ( //Если таких юзер агентов нет, или если они есть, но ип другие и они не боты, то добавить их в список и посчитать в счётчике
                !empty($table_visitors->tableRecords) &&
                (!isBot($_SERVER["HTTP_USER_AGENT"]) && !in_array($_SERVER['REMOTE_ADDR'], $ip_s_list)))) {
            if (isBot($_SERVER['HTTP_USER_AGENT'])) {
                $was_bot = true;
                $querySuccess &= siteconfig_reload();
                $querySuccess &= siteconfig_add("visitors_bots_24h", siteconfig_get("visitors_bots_24h") + 1);
            }
            if (isset($_COOKIE["DAILY_VISITOR"])) {
                $was_visitor = true;
                $querySuccess &= siteconfig_reload();
                if (!$was_bot) $querySuccess &= siteconfig_add("visitors_known24h", siteconfig_get("visitors_known24h") + 1);
            }

            $table_visitors->addNewRecord([
                'time' => time(),
                'ip' => htmlspecialchars($_SERVER['REMOTE_ADDR']),
                'host' => htmlspecialchars($_SERVER['REMOTE_HOST']),
                'user_agent' => htmlspecialchars($_SERVER['HTTP_USER_AGENT']),
                'user_id' => $currentUser != null ? $currentUser['id'] : '-1',
                'referer' => htmlspecialchars($_SERVER['HTTP_REFERER']),
                'request' => htmlspecialchars($_SERVER['REQUEST_URI']),
                'is_robot' => $was_bot ? '1' : '0',
                'visit_count' => '1',
                'mirror_visitor_id' => '-1',
                'visitor_cookie' => !empty($_COOKIE["DAILY_VISITOR"]) ? $_COOKIE["DAILY_VISITOR"] / 1 : '-1'
            ]);
            if (!$was_bot) $querySuccess &= siteconfig_add("visitors_24h", siteconfig_get("visitors_24h") + 1);
        } else {
            $table_visitors->updateRecord('id', $table_visitors->tableRecords[0]["id"], [
                'visit_count' => ($table_visitors->tableRecords[0]["visit_count"] + 1)
            ]);
            $table_visitors->addNewRecord([
                'time' => time(),
                'ip' => htmlspecialchars($_SERVER['REMOTE_ADDR']),
                'host' => isset($_SERVER['REMOTE_HOST']) ? htmlspecialchars($_SERVER['REMOTE_HOST']) : '',
                'user_agent' => htmlspecialchars($_SERVER['HTTP_USER_AGENT']),
                'user_id' => $currentUser != null ? $currentUser['id'] : '-1',
                'referer' => htmlspecialchars($_SERVER['HTTP_REFERER']),
                'request' => htmlspecialchars($_SERVER['REQUEST_URI']),
                'is_robot' => $was_bot ? '1' : '0',
                'visit_count' => $table_visitors->tableRecords[0]["visit_count"] + 1,
                'mirror_visitor_id' => $table_visitors->tableRecords[0]["id"],
                'visitor_cookie' => !empty($_COOKIE["DAILY_VISITOR"]) ? $_COOKIE["DAILY_VISITOR"] / 1 : '-1'
            ]);
        }
        if (!$table_visitors->applyChanges()) {
            addErrorToLog('VISITORS COUNTER::TABLE APPLY ERROR', 'Error saving data to DB');
        }
        if (!$querySuccess) {
            addErrorToLog("VISITORS COUNTER ERROR", "Error incrementing Data visitors");
        } else {
            setcookie('DAILY_VISITOR', time() + 86400, time() + (86400 * 30), '/');

            if ($visitors_24h_res_time !== null) {
                if (time() - $visitors_24h_res_time > 86400) {
                    global $table_daysCounterInfo;
                    //$table_daysCounterInfo->loadFromDatabaseAll();
                    $table_daysCounterInfo->addNewRecord([
                        "time" => $visitors_24h_res_time,
                        "count" => siteconfig_get("visitors_24h") - 1,
                        "known_count" => siteconfig_get("visitors_known24h") - ($was_visitor ? 1 : 0),
                        "bots" => siteconfig_get("visitors_bots_24h") - ($was_bot ? 1 : 0)
                    ]);
                    $querySuccess = $table_daysCounterInfo->applyChanges();
                    siteconfig_add('visitors_24h_last_reset_time', time());
                    siteconfig_add('visitors_24h', 1);
                    siteconfig_add('visitors_known24h', 1);
                    siteconfig_add('visitors_bots_24h', ($was_bot ? 1 : 0));
                    $querySuccess &= siteconfig_write();
                    if (!$querySuccess) {
                        addErrorToLog("VISITORS COUNTER ERROR", "Error resetting visitors");
                    }
                }
            } else {
                addErrorToLog("VISITORS COUNTER ERROR", "Error get last reset time");
            }
        }
    }
}

function show_debug_mode_form()
{
    echo "<form style='word-wrap: normal;' action=\"/" . $_SESSION["current_page"] . "\" class=\"form-horizontal\" method=\"get\" enctype=\"multipart/form-data\">";
    global $current_locale;
    echo "<div class=\"form-group\">
                         <label for=\"debug_key\" class=\"col-sm-2 control-label\">" . $current_locale["enter_debug_mode_password"] . "</label>
                         <div class=\"col-sm-8\">";


    echo "<input type=\"password\" name=\"debug_key\">";
    echo "</div></div>";
    echo "<div class=\"form-group\">
                         <div class=\"col-sm-offset-5 col-sm-8\">
                            <button type=\"submit\" id=\"submit\" class=\"btn btn-primary wymupdate\">" . $current_locale["log_in"] . "</button>
                            <div></div>
                         </div>
                     </div>
                     </form>";
}


/**
 * Функция определяет по поискового робота при помощи user-agent
 * @return string|bool
 */
function isBot($user_agent)
{
    if (empty($user_agent)) {
        return false;
    }

    $bots = [
        // Yandex
        'YandexBot', 'YandexAccessibilityBot', 'YandexMobileBot', 'YandexDirectDyn', 'YandexScreenshotBot',
        'YandexImages', 'YandexVideo', 'YandexVideoParser', 'YandexMedia', 'YandexBlogs', 'YandexFavicons',
        'YandexWebmaster', 'YandexPagechecker', 'YandexImageResizer', 'YandexAdNet', 'YandexDirect',
        'YaDirectFetcher', 'YandexCalendar', 'YandexSitelinks', 'YandexMetrika', 'YandexNews',
        'YandexNewslinks', 'YandexCatalog', 'YandexAntivirus', 'YandexMarket', 'YandexVertis',
        'YandexForDomain', 'YandexSpravBot', 'YandexSearchShop', 'YandexMedianaBot', 'YandexOntoDB',
        'YandexOntoDBAPI', 'YandexTurbo', 'YandexVerticals',

        // Google
        'Googlebot', 'Googlebot-Image', 'Mediapartners-Google', 'AdsBot-Google', 'APIs-Google',
        'AdsBot-Google-Mobile', 'AdsBot-Google-Mobile', 'Googlebot-News', 'Googlebot-Video',
        'AdsBot-Google-Mobile-Apps',

        // Other
        'Mail.RU_Bot', 'bingbot', 'Accoona', 'ia_archiver', 'Ask Jeeves', 'OmniExplorer_Bot', 'W3C_Validator',
        'WebAlta', 'YahooFeedSeeker', 'Yahoo!', 'Ezooms', 'Tourlentabot', 'MJ12bot', 'AhrefsBot',
        'SearchBot', 'SiteStatus', 'Nigma.ru', 'Baiduspider', 'Statsbot', 'SISTRIX', 'AcoonBot', 'findlinks',
        'proximic', 'OpenindexSpider', 'statdom.ru', 'Exabot', 'Spider', 'SeznamBot', 'oBot', 'C-T bot',
        'Updownerbot', 'Snoopy', 'heritrix', 'Yeti', 'DomainVader', 'DCPbot', 'PaperLiBot', 'StackRambler',
        'msnbot', 'msnbot-media', 'msnbot-news', 'Konturbot', 'Trident', 'python-requests', 'DuckDuckGo-Favicons-Bot', 'backupland',
        'vkShare', 'BackupLand', 'Go-http-client', 'PocketParser', 'TelegramBot', 'TwitterBot', 'baidu'
    ];

    foreach ($bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            return $bot;
        }
    }

    return false;
}