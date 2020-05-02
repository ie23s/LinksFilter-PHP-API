<?php
//error_reporting(0);
$file = file_get_contents('./config.php.txt', 'true');
$form = file_get_contents('./form.html', 'true');

if(!is_writable ('./config.php') || !is_writable ('./')) {
	$form = str_replace('{text}', "Welcome to LinksFilter API installer!<BR>".
		"Yoy need to make writable config.php and this directory!", $form );
	$form = str_replace('{hide}', 'style="display:none;"', $form );
	die($form);
}

if(!isset($_POST['db_host'])) {
	$form = str_replace('{text}', "Welcome to LinksFilter API installer!", $form );
	$form = str_replace('{hide}', "", $form );
	die($form);
}

function saveConfig($config_text) {
	$cfg_file = fopen('./config.php', 'w');
	fwrite($cfg_file, $config_text);
	fclose($cfg_file);
}

$search  = array('@db_host', '@db_port', '@db_user', '@db_password', '@db_name', '@db_prefix');
$replace = array($_POST['db_host'], $_POST['db_port'], $_POST['db_user'], $_POST['db_password'], $_POST['db_name'], $_POST['db_prefix']);


$file = str_replace($search, $replace, $file);
$api = false;
$redirect = false;

if(isset($_POST['whitelist'])) {
	$file = str_replace('@api_white', 'true', $file);
	$api = true;
} else {
	$file = str_replace('@api_white', 'false', $file);
}

if(isset($_POST['blacklist'])) {
	$file = str_replace('@api_black', 'true', $file);
	$api = true;
} else {
	$file = str_replace('@api_black', 'false', $file);
}

if(isset($_POST['shortlinks_a'])) {
    $file = str_replace('@api_short', 'true', $file);
    $file = str_replace('@module_api_short_link', $_POST['redirect_path'], $file);
	$api = true;
} else {
	$file = str_replace('@api_short', 'false', $file);
}

if(isset($_POST['shortlinks_r'])) {
	$file = str_replace('@mysql_shortlinks', 'true', $file);
	$redirect = true;
} else {
	$file = str_replace('@mysql_shortlinks', 'false', $file);
}

$file = str_replace('@api_key', md5(uniqid(rand(), true)), $file);
saveConfig($file);
require_once('./config.php');

$mysqli = new mysqli(Config::$db_host . ':' . Config::$db_port, Config::$db_user, Config::$db_password, Config::$db_name);

$ans = "";
if ($mysqli->connect_error) {
	$ans = 'Install finished with FAIL!<br>' . 'Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error;
	$form = str_replace('{text}', $ans, $form);
	$form = str_replace('{hide}', "", $form );
	saveConfig('');
	die($form);
} else {
	$ans = 'Install finished with SUCCESS!<br>API Key: ' . Config::$api_key;
	$form = str_replace('{text}', $ans ,$form);
	$form = str_replace('{hide}', 'style="display:none;"', $form );
	$prefix = Config::$db_prefix;
	if(Config::$module_api_white) {
		$mysqli->query("CREATE TABLE IF NOT EXISTS `{$prefix}whitelist` (" .
					"  `id` int(11) NOT NULL AUTO_INCREMENT," .
					"  `host` varchar(255) NOT NULL," .
					"  PRIMARY KEY (`id`)," .
					"  UNIQUE KEY `host` (`host`)" .
					") ENGINE=MyISAM DEFAULT CHARSET=utf8;");
	}
	if(Config::$module_api_black) {
		$mysqli->query("CREATE TABLE IF NOT EXISTS `{$prefix}blacklist` (" .
					"  `id` int(11) NOT NULL AUTO_INCREMENT," .
					"  `host` varchar(255) NOT NULL," .
					"  PRIMARY KEY (`id`)," .
					"  UNIQUE KEY `host` (`host`)" .
					") ENGINE=MyISAM DEFAULT CHARSET=utf8;");
		
	}
	if(Config::$module_api_short || Config::$module_mysql_shortlinks) {
	    $mysqli->query("CREATE TABLE IF NOT EXISTS `{$prefix}shortlink` (" .
	    "  `id` int(11) NOT NULL AUTO_INCREMENT," .
	    "  `url` text NOT NULL," .
	    "  `username` varchar(255) NOT NULL," .
	    "  `time` int(11) NOT NULL," .
	    "  PRIMARY KEY (`id`)" .
	    ") ENGINE=MyISAM DEFAULT CHARSET=utf8;");
	    
	}
	unlink('./config.php.txt');
	unlink('./form.html');
	unlink('./install.php');
	if(!$api)
		unlink('./api.php');
	if(!$redirect)
		unlink('./redirect.php');
	die($form);
}
