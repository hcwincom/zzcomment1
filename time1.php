<?php
//系统任务，在linux中crontab设置每日0点1分启动
date_default_timezone_set('PRC');
header("content-type:text/html;charset=utf-8");
set_time_limit(600);
// $url="http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$log='1.log';
error_log(date('Y-m-d H:i:s')."\r\n",'3',$log);

$db = new mysqli( 'localhost', 'root', 'root','zzcomment','3306') or die();
// 数据库编码方式
mysqli_set_charset($db, 'utf8');
$time=time();
$sql="update cm_active set status=3 where status=2 and end_time<{$time}";
$db->query($sql);
$row=$db->affected_rows;
error_log(date('Y-m-d H:i:s')."检查动态过期，改变了".$row."行\r\n",'3',$log);

$sql="update cm_top_active set status=3 where status=2 and end_time<{$time}";
$db->query($sql);
$row=$db->affected_rows;
error_log(date('Y-m-d H:i:s')."检查动态置顶过期，改变了".$row."行\r\n",'3',$log);

$sql="update cm_top_goods set status=3 where status=2 and end_time<{$time}";
$db->query($sql);
$row=$db->affected_rows;
error_log(date('Y-m-d H:i:s')."检查商品置顶过期，改变了".$row."行\r\n",'3',$log);

$sql="update cm_top_seller set status=3 where status=2 and end_time<{$time}";
$db->query($sql);
$row=$db->affected_rows;
error_log(date('Y-m-d H:i:s')."检查店铺置顶过期，改变了".$row."行\r\n",'3',$log);
$db->close(); 
error_log(date('Y-m-d H:i:s')."系统任务执行完成\r\n",'3',$log);
// $today=date('Y-m-d',$time);
// $sleep=strtotime($today)+3600*24+2-$time;
// error_log(date('Y-m-d H:i:s')."sleep时间".($sleep/3600)."小时\r\n",'3',$log);
// sleep($sleep);
// error_log(date('Y-m-d H:i:s')."\r\n",'3',$log);
// file_get_contents($url);

exit;
?>