<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 29.02.20
 * Time: 0:22
 */

$table_test = new TableAdapter("test", array(
        new TableField("id", "int"), //required
        new TableField("name", "text"),
        new TableField("wymed", "text"),
        new TableField("area", "text"),
        new TableField("image_id", "text"),
        new TableField("list_item_id", "text"),
    )
);


/* =====================SERVICE; DO NOT CHANGE OR DELETE===================================*/
$engine_table_structure_user_config_loaded = 1; //DO NOT CHANGE