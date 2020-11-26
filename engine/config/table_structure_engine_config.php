<?php
/**
 * This file shouldn't be changed. There are listed core database tables. If you want to redefine one of this tables, use
 * file "local_db.php", where you can add your own. But notice, that some fields of this bases have commented
 * line "required". You should always add them to your redefined table without changes.
 */

$table_users = new TableAdapter("users", array( //CAN BE CHANGED BY REMOVING NON-REQUIRED RECORDS OR ADDING SOME OTHERS
        new TableField("id", "int"), //required
        new TableField("login", "text"), //required
        new TableField("hashed_password", "text"), //required
        new TableField("username", "text"), //custom field, that can be used by other parts of site as $currentUser["username"]
        new TableField("privileges", "text"), //required
        new TableField("email", "text"), //required
        new TableField("sessions", "text"), //required
    )
);

$table_siteConfig = new TableAdapter("config", [
    new TableField("id", "int"), //required
    new TableField("key", "text"), //required
    new TableField("value", "text") //required
], //SECTION UNDER SHOULD BE COPIED INTO REDEFINED TABLE WITHOUT CHANGES! (in case of redefinition)
    [
        [
            "key" => "visitors_24h",
            "value" => 0
        ], [
        "key" => "visitors_known24h",
        "value" => 0
    ], [
        "key" => "visitors_24h_last_reset_time",
        "value" => time()
    ]
    ]
);

$table_daysCounterInfo = new TableAdapter("stat_counter", [
        new TableField("id", "int"), //required
        new TableField("time", "int"), //required
        new TableField("count", "int"), //required
        new TableField("known_count", "int"), //required
        new TableField("bots", "int") //required
    ]
);

$table_visitors = new TableAdapter("visitors",[
    new TableField('id', 'int'),
    new TableField('time', 'text'),
    new TableField('ip', 'text'),
    new TableField('host', 'text'),
    new TableField('user_agent', 'text'),
    new TableField('user_id', 'text'),
    new TableField('referer', 'text'),
    new TableField('request', 'text'),
    new TableField('is_robot', 'text'),
    new TableField('visit_count', 'text'),
    new TableField('mirror_visitor_id', 'text'),
    new TableField('visitor_cookie', 'text'),
]);

//$table_redirect_rules = new TableAdapter('redirect_rules', [
//    new TableField('id', 'int'),
//    new TableField('pattern', 'text'),
//    new TableField('redirect_to', 'text')
//]);


/* =====================SERVICE; DO NOT CHANGE OR DELETE===================================*/
$engine_table_structure_config_loaded = 1; //DO NOT CHANGE
