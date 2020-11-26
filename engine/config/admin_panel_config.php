<?php


const MAX_IMAGE_SIZE = 10 * 1024 * 1024; //10mb
const MAX_FILE_SIZE = 10 * 1024 * 1024; //10mb
const ALLOWED_IMAGE_TYPES = array('image/gif', 'image/png', 'image/jpeg');
const NO_IMAGE_FILE_PATH = "/images/no-image.jpg";


$ADMIN_PANEL_PAGES = [];
require_once __DIR__ . "/../lib/admin_panel_tools.php"; //DO NOT CHANGE

$ADMIN_PANEL_PAGES["test"] = new AdminEditablePage("Тест", $table_test, //Название страницы, таблица в базе данных
    [
        new AdminPageField("name", //Кодовое имя, которое будет использоваться для вывода формы
            "Наименование", //Описание, выводящееся в админке
            ADMIN_PAGE_FIELD_TYPE_TEXT, //Тип элемента
            "database", //Источник для данных
            "name", //Имя столбца в таблице, с которым будет работать данный элемент
            "input", //Поведение, пока что возможен только ввод
            "Название", //Начальное значение, которое будет в элементе
            TRUE //Показывать эту часть записи в списке записей
        ),
        new AdminPageField("area",
            "Текстареа",
            ADMIN_PAGE_FIELD_TYPE_TEXTAREA,
            "database",
            "area",
            "input",
            "",
            TRUE
        ),
        new AdminPageField("wymed",
            "Поле с форматируемым текстом",
            ADMIN_PAGE_FIELD_TYPE_WYMEDITOR,
            "database",
            "wymed",
            "input",
            "",
            FALSE
        ),
        new AdminPageField("image",
            "Картинка",
            ADMIN_PAGE_FIELD_TYPE_IMAGE,
            "database",
            "image_id",
            "input",
            "images/",
            TRUE
        ),
    ],
    ["name"] //Какой элемент записи будет выводиться при удалении записи

);


/* =====================SERVICE; DO NOT CHANGE OR DELETE===================================*/
$engine_admin_panel_config_loaded = 1; //DO NOT CHANGE