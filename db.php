<?php
require_once('./config.php');

class Status {
    private $status_code = 1;
    private $errored = false;
    private $error_text = "";
    private $adv = null;
    
    function setStatusCode($status_code) {
        if($this->errored) return;
        $this->status_code = $status_code;
    }
    
    function setErrored($status_code, $error_text) {
        if($this->errored) return;
        $this->errored = true;
        $this->error_text = $error_text;
        $this->show();
    }
    
    function setAdvanced($adv) {
        if($this->errored) return;
        $this->adv = $adv;
    }
    
    function show() {
        $response;
        if(!$this->errored && $this->adv == null) {
            $response = array('status' => $this->status_code, 'errored' => false);
        } elseif (!$this->errored) {
            $response = array('status' => $this->status_code, 'errored' => false, 'options' => $this->adv);
            
        } else {
            $response = array('status' => $this->status_code, 'errored' => true, 'description' => $this->error_text);
        }
        echo json_encode($response);
        die();
    }
}

if (!class_exists('Config')) {
    header('Location: ./install.php');
    exit;
}



$status = new Status;


$mysqli = new mysqli(Config::$db_host . ':' . Config::$db_port, Config::$db_user, Config::$db_password, Config::$db_name);

if ($mysqli->connect_error) {
    $status->setErrored(3, 'Connect Error (' . $mysqli->connect_errno . ') '
        . $mysqli->connect_error);
    $status->show();
}
//$mysqli->close();