<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 29.08.20
 * Time: 21:51
 */
require_once('lib/tar_tools.php');
$__hpbe_internal_dont_echo_text_http_200 = true;
require_once "core.php";
resolve_site_config();
//$createZip = new createDirZip;
//$createZip->get_files_from_folder('./', '');
//$fileName = 'archive.zip';
/*$fd = fopen ($fileName, 'wb');
$out = fwrite ($fd, $createZip->getZippedfile());
fclose ($fd);
$createZip->forceDownload($fileName);*/

//header("Content-Length: 10000");

//echo $createZip->getZippedfile();
/*
 * 100 байт name — название (может содержать относительный путь);
8 байт mode file mode
8 байт uid — user ID
8 байт gid — group ID
12 байт size — размер файла, байт (кодирована в восьмеричной системе)
12 байт mtime – дата и время последней модификации в секундах эпохи UNIX (кодирована в восьмеричной системе)
8 байт chksum – контрольная сумма заголовка (не файла!)
1 байт typeflag – определяет файл у нас, или каталог: файл – 0, каталог — 5
100 байт linkname – ссылка на файл
— дальше – поля «нового» формата — 6 байт magic – содержит слово «ustar», т.е. признак «нового» формата
2 байт version – версия нового формата (может отсутствовать)
32 байт uname – имя владельца
32 байт gname – имя группы владельца
8 байт devmajor – старший байт кода устройства
8 байт devminor – младший байт кода устройства
155 байт prefix – префикс (расширение) имени

 */
//HEADER SIZE = 512 bytes;
/*
$mtime = sprintf("%11s ", DecOct(filemtime("__admin.php")));
$size = "".decoct(filesize("__admin.php"));
$tar_header = tar_generate_header("__admin.php", filesize("__admin.php"), filemtime("__admin.php"));
echo $tar_header;
$file = fopen("__admin.php", "r" );
$tar_headers_length = strlen($tar_header);
$add = 5120 - (($tar_headers_length + filesize("__admin.php")) - ((int)(($tar_headers_length + filesize("__admin.php")) /  512) * 512));
//echo $add;
//$addition = pack()
echo fread($file,filesize("__admin.php"));
echo pack("a".$add, "");
fclose($file);



*/

global $db_host;
global $db_login;
global $db_name;
global $db_password;
global $engine_debug_mode;

if (!($engine_debug_mode || isCurrentUserAdministrator())) {
    http_response_code(403);
    echo "HPBE_DAO<br><H1>403 forbidden.</H1>" . isCurrentUserAdministrator();
    die();
}

$tarfile = new TarFile();
if (isset($_GET["db"]) && $_GET["db"] === "1") {
    $tarfile->add_artificial_dir("db_backup/");
    $tarfile->add_artificial_file("db_backup/db_dump.sql", EXPORT_DATABASE($db_host, $db_login, $db_password, $db_name));
    $tarfile->add_artificial_file("db_backup/.htaccess", "order allow,deny\ndeny from all");
}
$tarfile->add_artificial_file("backup-info.json", json_encode(["site_name" => $sitename, "date" => time(),
    "conf" => $_GET["conf"], "db" => $_GET["db"], "engine" => $_GET["engine"], "version" => HPBE_DAO_VERSION]));
$tarfile->add_dir("$dir[0]", '');
if (isset($_GET["conf"]) && $_GET["conf"] === "0") $tarfile->exclude("engine/config");
$tarfile->exclude(".tar");
$tarfile->exclude(".git");
$tarfile->exclude(".idea");
$tarfile->exclude("engine/forbidden");
if (isset($_GET["engine"]) && $_GET["engine"] === "0") {
    $tarfile->exclude("engine/lib");
    $tarfile->exclude("engine/res");
    $tarfile->exclude("engine/tools");
    $tarfile->exclude("engine/api");
    $tarfile->exclude("engine/core");
    $tarfile->exclude("engine/initial_configuration");
    $tarfile->exclude("engine/locales");
    $tarfile->exclude("__admin");
    $tarfile->exclude("backup.php");
}


$tarfile->stream_to_client("\"" . translit($sitename) . "-backup-" . date("d.m.Y", time()) . ".tar\"");
//IMPORT_TABLES($db_host, $db_login, $db_password, $db_name, './db_backup/db_dump.sql');66981




