<?php
require_once ('./db.php');

if (!isset($_GET['api_key']) || $_GET['api_key'] != Config::$api_key) {
    $status->setErrored(8, "API Key is wrong.");
}

$do = $_GET['do'];

$prefix = Config::$db_prefix;

switch ($do) {
    case "shortlinks":
        if(!Config::$module_api_short) {
            $status->setErrored(9, "This function is disabled!");
        }
        if (! isset($_GET['link']) || ! isset($_GET['username'])) {
            $status->setErrored(3, "Not enough arguments.");
        }
        
        $id = 1;
        
        $res = $mysqli->query("SELECT MIN(`t1`.ID + 1) AS `nextID` FROM `{$prefix}shortlink` `t1` LEFT JOIN `{$prefix}shortlink` `t2` ON `t1`.ID + 1 = `t2`.ID WHERE `t2`.ID IS NULL");
        
        if ($res) {
            
            $id = $res->fetch_row()[0];
        }
        $res->close();
        
        $stmt = $mysqli->prepare("INSERT INTO `{$prefix}shortlink`(`id`, `url`, `username`, `time`) VALUES (?, ?, ?, ?)");
        $time = time();
        $stmt->bind_param('issi', $id, $_GET['link'], $_GET['username'], $time);
        $stmt->execute();
        $stmt->close();
        
        $status->setAdvanced(array(
            'link' => Config::$module_api_short_link . dechex($id)
        ));
        break;
    case "whitelist":
        if(!Config::$module_api_white) {
            $status->setErrored(9, "This function is disabled!");
        }
        if (! isset($_GET['func']) || ! isset($_GET['host'])) {
            $status->setErrored(3, "Not enough arguments.");
        }
        
        if ($_GET['func'] == "check") {
            if (isset($_GET['subhosts']) && !empty($_GET['subhosts'])) {
                $subhosts = explode('|', $_GET['subhosts']);
            } else {
                $subhosts = array();
            }
            $subhosts[count($subhosts)] = $_GET['host'];
            
            $in = str_repeat('?,', count($subhosts) - 1) . '?';
            $sql = "SELECT COUNT(*) FROM `{$prefix}whitelist` WHERE `host` IN ({$in})";
            
            $stmt = $mysqli->prepare($sql);
            $types = str_repeat('s', count($subhosts));
            $stmt->bind_param($types, ...$subhosts);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_row()[0] > 0;
            $status->setAdvanced(array(
                'found' => $res
            ));
            $stmt->close();
        } elseif ($_GET['func'] == "add") {
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM `{$prefix}whitelist` WHERE `host` = ?");
            $stmt->bind_param('s', $_GET['host']);
            $stmt->execute();
            if($stmt->get_result()->fetch_row()[0] > 0){
                $stmt->close();
                $status->setErrored(5, "This site is on this list.");
            }
            $stmt->close();
            
            $stmt = $mysqli->prepare("INSERT INTO `{$prefix}whitelist`(`id`, `host`) VALUES (null,?)");
            $stmt->bind_param('s', $_GET['host']);
            $stmt->execute();
            $stmt->close();
        } elseif ($_GET['func'] == "rem") {
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM `{$prefix}whitelist` WHERE `host` = ?");
            $stmt->bind_param('s', $_GET['host']);
            $stmt->execute();
            if($stmt->get_result()->fetch_row()[0] == 0){
                $stmt->close();
                $status->setErrored(6, "This site is not found in this list.");
            }
            $stmt->close();
            
            $stmt = $mysqli->prepare("DELETE FROM `{$prefix}whitelist` WHERE `host` = ?");
            $stmt->bind_param('s', $_GET['host']);
            $stmt->execute();
            $stmt->close();
        }
        break;
    case "blacklist":
        if(!Config::$module_api_black) {
            $status->setErrored(9, "This function is disabled!");
        }
        if (! isset($_GET['func']) || ! isset($_GET['host'])) {
            $status->setErrored(3, "Not enough arguments.");
        }

        if ($_GET['func'] == "check") {
            if (isset($_GET['subhosts']) && ! empty($_GET['subhosts'])) {
                $subhosts = explode('|', $_GET['subhosts']);
            } else {
                $subhosts = array();
            }
            $subhosts[count($subhosts)] = $_GET['host'];

            $in = str_repeat('?,', count($subhosts) - 1) . '?';
            $sql = "SELECT COUNT(*) FROM `{$prefix}blacklist` WHERE `host` IN ({$in})";

            $stmt = $mysqli->prepare($sql);
            $types = str_repeat('s', count($subhosts));
            $stmt->bind_param($types, ...$subhosts);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_row()[0] > 0;
            $status->setAdvanced(array(
                'found' => $res
            ));
            $stmt->close();
        } elseif ($_GET['func'] == "add") {
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM `{$prefix}blacklist` WHERE `host` = ?");
            $stmt->bind_param('s', $_GET['host']);
            $stmt->execute();
            if ($stmt->get_result()->fetch_row()[0] > 0) {
                $stmt->close();
                $status->setErrored(5, "This site is on this list.");
            }
            $stmt->close();

            $stmt = $mysqli->prepare("INSERT INTO `{$prefix}blacklist`(`id`, `host`) VALUES (null,?)");
            $stmt->bind_param('s', $_GET['host']);
            $stmt->execute();
            $stmt->close();
        } elseif ($_GET['func'] == "rem") {
            $stmt = $mysqli->prepare("SELECT COUNT(*) FROM `{$prefix}blacklist` WHERE `host` = ?");
            $stmt->bind_param('s', $_GET['host']);
            $stmt->execute();
            if ($stmt->get_result()->fetch_row()[0] == 0) {
                $stmt->close();
                $status->setErrored(6, "This site is not found in this list.");
            }
            $stmt->close();

            $stmt = $mysqli->prepare("DELETE FROM `{$prefix}blacklist` WHERE `host` = ?");
            $stmt->bind_param('s', $_GET['host']);
            $stmt->execute();
            $stmt->close();
        }
        break;
    default:
        $status->setErrored(7, "Not found path.");
}
$status->show();
?>