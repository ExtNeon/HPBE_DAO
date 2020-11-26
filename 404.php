<?php
/**
 * Created by PhpStorm.
 * User: Кирилл
 * Date: 08.04.2019
 * Time: 16:53
 */
require_once "engine/core.php";

setCurrentPageCaption("404");
http_response_code(404);
?>
<?php include "header.php" ?>
<main class="main">

    <section class="about">
        <div class="container">
            <h2 class="section-title">Ошибка 404</h2>
            <div class="about__text">
                Данной страницы не существует.
            </div>
        </div>
    </section>

</main>
</body>
</html>