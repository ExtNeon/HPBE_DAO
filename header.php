<?php
/**
 * Created by PhpStorm.
 * User: Кирилл
 * Date: 08.04.2019
 * Time: 16:53
 */
require_once "engine/core.php";
?>

    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title><?php echo $currentPageCaption ?></title>
        <link rel="stylesheet" href="engine/res/__admin/_n/css/index.css">
    </head>
    <body>
<?php
if (isCurrentUserModerator()) {
    echo "<div class=\"footer\" style='padding: 8px;'> 
        
    <div class=\"container footer__container\">
            <div class=\"footer__left\">
                <a href=\"__admin\" class=\"btn\" style='color:#FFFFFF;'>
                 <div class=\"btn-shadow\" style='padding: 3px;'>
                            Перейти в панель администратора
                        </div>
                
                </a>
            </div>
            <div class=\"footer__center\">
            </div>
            <div class=\"footer__right\">
            <a href=\"__admin?action=reset\"
                   class=\"btn\">
                        <div class=\"btn-shadow\" style='padding: 3px;'>
                            Разлогинить
                        </div>
                    </a>
            </div>
        </div>
        </div>";
}
?>