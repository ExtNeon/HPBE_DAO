<?php
/**
 * Древний файл, не меняйте и не юзайте. Скоро от него избавимся
 */
$__hpbe_internal_dont_echo_text_http_200=true;
require_once "engine/core.php";

if ($_GET["action"] === "login") {
    if (isset($_POST['login'])) {
        $login = $_POST['login'];
        if ($login === '') {
            unset($login);
        }
    } //заносим введенный пользователем логин в переменную $login, если он пустой, то уничтожаем переменную
    if (isset($_POST['password'])) {
        $password = $_POST['password'];
        if ($password === '') {
            unset($password);
        }
    }
//заносим введенный пользователем пароль в переменную $password, если он пустой, то уничтожаем переменную

    if (empty($login) or empty($password)) { //если пользователь не ввел логин или пароль, то выдаем ошибку и останавливаем скрипт
        $_SESSION['login_result'] = "Вы ввели не всю информацию, вернитесь назад и заполните все поля!";
    } else {
//если логин и пароль введены,то обрабатываем их, чтобы теги и скрипты не работали, мало ли что люди могут ввести
        loginOperation($login, $password);
    }
    /*showErrors();
     showWarnings();*/
    reloadPage();
}