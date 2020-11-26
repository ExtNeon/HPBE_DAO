<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 25.08.20
 * Time: 2:33
 */
require_once "engine/config/site_config.php";
require_once "engine/lib/debug_mode_checker.php";
require_once "engine/lib/auto_config_manager.php";
require_once "engine/lib/other_tools.php";

if (autoconfig_get("core_configured") !== null) {
    http_response_code(403);
    echo '<h1>403 forbidden.</h1>';
    die();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sample page</title>
    <link rel="stylesheet" href="engine/res/__admin/_n/css/index.css">
</head>
<body>
<main class="main">

    <section class="about">
        <div class="container">
            <h2 class="section-title">Сайт был только что создан</h2>
            <div class="about__text">
                Заглушка
            </div>
        </div>
    </section>

</main>
</body>
</html>
