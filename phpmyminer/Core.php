<?php

namespace phpmyminer;

class Core {

  public static function config($value) {
    require 'config.php';

    return $cfg[$value];
  }

}