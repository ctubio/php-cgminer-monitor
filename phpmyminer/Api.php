<?php

namespace phpmyminer;

class Api {

  protected $socket = null;

  public function connect($ip, $port) {
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 10, 'usec' => 0));
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 10, 'usec' => 0));

    $res = @socket_connect($socket, $ip, $port);
    if(@socket_getpeername($socket, $ip)) {
      return $socket;
    } else {
      return false;
    }
  }

  public function command($miner, $cmd, $param = null) {
    $result = array();

    $json = array(
      'command' => $cmd,
    );

    if($param !== null) {
      $json['parameter'] = $param;
    }

    $miners = Core::config('miners');

    list($ip, $port) = explode(':', $miners[$miner]);
    $socket = $this->connect($ip, $port);

    if($socket === false) {
      return false;
    }

    socket_write($socket, json_encode($json), strlen(json_encode($json)));
    $line = $this->read($socket);
    socket_close($socket);

    $line = str_replace('}{', '},{', $line);
    $line = str_replace('"Best Share"', '"best_share"', $line);
    $json = json_decode($line, true);

    return $json;
  }

  private function read($socket) {
    $line = '';
    while (true) {
      $byte = socket_read($socket, 1);
      if ($byte === false || $byte === '') {
        break;
      }
      if ($byte === "\0") {
        break;
      }
      $line .= $byte;
    }
    return $line;
  }

}