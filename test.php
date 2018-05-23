<?php
header("content-type:text/html;charset=utf-8");
ini_set('date.timezone','Asia/Shanghai');
$config=(require_once dirname(dirname(__FILE__)).'/data/conf/db.php');
 
$mysqli=new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PWD'], $config['DB_NAME'],$config['DB_PORT']);
$mysqli->set_charset('utf8');


