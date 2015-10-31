<?php

namespace phpmyminer;

class Core {

  public function __construct() {
    session_start();
  }

  public function getCsrf() {

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    for ($i = 0; $i < 64; $i++) {
        $token .= $characters[rand(0, strlen($characters) - 1)];
    }

    $_SESSION['csrf_token'] = $token;

    return $_SESSION['csrf_token'];
  }

  public function validCsrf($token) {
    if(isset($_SESSION['csrf_token']) && $token == $_SESSION['csrf_token']) {
      $this->getCsrf();
      return true;
    }

    return false;
  }

  public static function config($value) {
    require 'config.php';

    return $cfg[$value];
  }

}