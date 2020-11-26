<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 25.08.20
 * Time: 23:36
 */

require_once "engine/config/site_config.php";
require_once "engine/lib/debug_mode_checker.php";
require_once "engine/lib/auto_config_manager.php";
require_once "engine/lib/other_tools.php";
resolve_site_config();
require_once "engine/lib/db_driver.php";
require_once "engine/config/table_structure_engine_config.php";
siteconfig_reload();

if (siteconfig_get("site_maintenance") === null) {
    http_response_code(403);
    redirectTo("");
    echo '<h1>403 forbidden.</h1>';

    die();
}
http_response_code(503);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Maintenance</title>
    <link rel="stylesheet" href="engine/res/__admin/_n/css/index.css">
</head>
<body>
<main class="main">

    <section class="about">
        <div class="container">
            <h2 class="section-title">Сайт находится на временном обслуживании</h2>
            <div class="about__text">
                Скоро он вновь заработает
            </div>
        </div>
    </section>

</main>
</body>
</html>
