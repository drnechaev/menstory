<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

define('nwDS', DIRECTORY_SEPARATOR); 

require_once("engine".nwDS."core".nwDS."nw_Core.php");


$NWeb = new nw_Core();


$NWeb->process();

/*
$db = nw_Core::getDB();

$db->query("select * from os_products limit 5");

$res = $db->results();

print_r($res);
*/
?>
