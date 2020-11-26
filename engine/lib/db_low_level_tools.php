<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 10.09.20
 * Time: 19:48
 */


/**
 * Выполняет запрос к базе данных.
 * @param $query
 */
function db_runQuery($query)
{
    global $db_host;
    global $db_login;
    global $db_name;
    global $db_password;
    $db = mysqli_connect($db_host, $db_login, $db_password);
    if ($db === false) {
        addErrorToLog("MYSQL RQ FUNCTION MODULE", "2: request error: cannot connect. Query = " . $query . " ErrorMessage = \"" . mysqli_connect_error() . "\"", true);
        $result = false;
    } else {
        mysqli_select_db($db, $db_name);
        global $engine_debug_mode;
        $result = mysqli_query($db, $query);
        //if ($engine_debug_mode) {
        if (!$result) {
            addErrorToLog("MYSQL RQ FUNCTION MODULE", "2: request error. Query = " . $query . " ErrorMessage = \"" . mysqli_error($db) . "\"", true);
            //      exit();
        }
        mysqli_close($db);
    }
    //}

    return $result;
}

function db_runQuery_custom($query, $db_host, $db_login, $db_password, $db_name)
{
    $db = mysqli_connect($db_host, $db_login, $db_password);
    if ($db === false) {
        addErrorToLog("MYSQL RQ FUNCTION MODULE", "2: request error: cannot connect. Query = " . $query . " ErrorMessage = \"" . mysqli_connect_error() . "\"", true);
        $result = false;
    } else {
        mysqli_select_db($db, $db_name);
        global $engine_debug_mode;
        $result = mysqli_query($db, $query);
        //if ($engine_debug_mode) {
        if (!$result) {
            addErrorToLog("MYSQL RQ FUNCTION MODULE", "2: request error. Query = " . $query . " ErrorMessage = \"" . mysqli_error($db) . "\"", true);
            //      exit();
        }
        mysqli_close($db);
    }
    //}
    return $result;
}

/*
##### EXAMPLE #####
   EXPORT_DATABASE("localhost","user","pass","db_name" );

##### Notes #####
     * (optional) 5th parameter: to backup specific tables only,like: array("mytable1","mytable2",...)
     * (optional) 6th parameter: backup filename (otherwise, it creates random name)
     * IMPORTANT NOTE ! Many people replaces strings in SQL file, which is not recommended. READ THIS:  http://puvox.software/tools/wordpress-migrator
     * If you need, you can check "import.php" too
*/
// by https://github.com/ttodua/useful-php-scripts //
function EXPORT_DATABASE($host, $user, $pass, $name, $tables = false, $backup_name = false)
{
    set_time_limit(3000);
    $mysqli = new mysqli($host, $user, $pass, $name);
    $mysqli->select_db($name);
    $mysqli->query("SET NAMES 'utf8'");
    $queryTables = $mysqli->query('SHOW TABLES');
    while ($row = $queryTables->fetch_row()) {
        $target_tables[] = $row[0];
    }
    if ($tables !== false) {
        $target_tables = array_intersect($target_tables, $tables);
    }
    $content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `" . $name . "`\r\n--\r\n\r\n\r\n";
    foreach ($target_tables as $table) {
        if (empty($table)) {
            continue;
        }
        $result = $mysqli->query('SELECT * FROM `' . $table . '`');
        $fields_amount = $result->field_count;
        $rows_num = $mysqli->affected_rows;
        $res = $mysqli->query('SHOW CREATE TABLE ' . $table);
        $TableMLine = $res->fetch_row();
        $TableMLine[1] = 'DROP TABLE IF EXISTS ' . $TableMLine[0] . ";\n\n" . $TableMLine[1];
        $TableMLine[1] = str_ireplace('COLLATE=utf8mb4_0900_ai_ci', 'COLLATE=utf8mb4_general_ci', $TableMLine[1]);
        $content .= "\n\n" . $TableMLine[1] . ";\n\n";
        // var_dump($TableMLine); echo '<br>';
        for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
            while ($row = $result->fetch_row()) { //when started (and every after 100 command cycle):
                if ($st_counter % 100 == 0 || $st_counter == 0) {
                    $content .= "\nINSERT INTO " . $table . " VALUES";
                }
                $content .= "\n(";
                for ($j = 0; $j < $fields_amount; $j++) {
                    $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                    if (isset($row[$j])) {
                        $content .= '"' . $row[$j] . '"';
                    } else {
                        $content .= '""';
                    }
                    if ($j < ($fields_amount - 1)) {
                        $content .= ',';
                    }
                }
                $content .= ")";
                //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
                    $content .= ";";
                } else {
                    $content .= ",";
                }
                $st_counter = $st_counter + 1;
            }
        }
        $content .= "\n\n\n";
    }
    $content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
    $backup_name = $backup_name ? $backup_name : $name . '___(' . date('H-i-s') . '_' . date('d-m-Y') . ').sql';
    /*ob_get_clean(); header('Content-Type: application/octet-stream');  header("Content-Transfer-Encoding: Binary");  header('Content-Length: '. (function_exists('mb_strlen') ? mb_strlen($content, '8bit'): strlen($content)) );    header("Content-disposition: attachment; filename=\"".$backup_name."\"");
    echo $content; exit;*/
    return $content;
}

// EXAMPLE:	IMPORT_TABLES("localhost","user","pass","db_name", "my_baseeee.sql"); //TABLES WILL BE OVERWRITTEN
// P.S. IMPORTANT NOTE for people who try to change/replace some strings  in SQL FILE before importing, MUST READ:  https://github.com/ttodua/useful-php-scripts/blob/master/my-sql-export%20(backup)%20database.php

// https://github.com/ttodua/useful-php-scripts
function IMPORT_TABLES($host, $user, $pass, $dbname, $sql_file_OR_content)
{
    $result__[0] = true;
    $result__[1] = '';
    set_time_limit(3000);
    $SQL_CONTENT = (strlen($sql_file_OR_content) > 300 ? $sql_file_OR_content : file_get_contents($sql_file_OR_content));
    $allLines = explode("\n", $SQL_CONTENT);
    $mysqli = new mysqli($host, $user, $pass, $dbname);
    if (mysqli_connect_errno()) {
        $result__[1] = "Failed to connect to MySQL: " . mysqli_connect_error();
        $result__[0] = false;
    } else {
        $zzzzzz = $mysqli->query('SET foreign_key_checks = 0');
        preg_match_all("/\nCREATE TABLE(.*?)\`(.*?)\`/si", "\n" . $SQL_CONTENT, $target_tables);
        foreach ($target_tables[2] as $table) {
            $mysqli->query('DROP TABLE IF EXISTS ' . $table);
        }
        $zzzzzz = $mysqli->query('SET foreign_key_checks = 1');
        $mysqli->query("SET NAMES 'utf8'");
        $templine = '';    // Temporary variable, used to store current query
        foreach ($allLines as $line) {                                            // Loop through each line
            if (substr($line, 0, 2) != '--' && $line != '') {
                $templine .= $line;    // (if it is not a comment..) Add this line to the current segment
                if (substr(trim($line), -1, 1) == ';') {        // If it has a semicolon at the end, it's the end of the query
                    if (!$mysqli->query($templine)) {
                        $result__[1] .= 'Error performing query \'<strong>' . $templine . '\': ' . $mysqli->error . '<br /><br />';
                        $result__[0] = false;
                    }
                    $templine = ''; // set variable to empty, to start picking up the lines after ";"
                }
            }
        }
    }
    return $result__;
}   //see also export.php


/* =====================SERVICE; DO NOT CHANGE OR DELETE===================================*/
$engine_db_low_level_tools_loaded = 1; //DO NOT CHANGE