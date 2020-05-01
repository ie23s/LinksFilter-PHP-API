<?php
require_once ('./db.php');
if(!isset($_GET['do']) || !Config::$module_mysql_shortlinks) {
    die("Error!");
}
    
$do = hexdec($_GET['do']);

$stmt = $mysqli->prepare("SELECT COUNT(*), url FROM `lf_shortlink` where `id` = ?");
$stmt->bind_param('i', $do);
$stmt->execute();
$res = $stmt->get_result()->fetch_row();
if($res[0] == 0){
    die("Error!");
}

header("Location: " . $res[1],TRUE,307);