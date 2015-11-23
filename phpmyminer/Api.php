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
    $miner = $miners[$miner];
    if (!is_array($miner)) $miner = array($miner);
    $return = array();
    foreach($miner as $_miner) {
      list($ip, $port) = explode(':', $_miner);
      $socket = $this->connect($ip, $port);

      if($socket === false) {
        return false;
      }

      socket_write($socket, json_encode($json), strlen(json_encode($json)));
      $line = $this->read($socket);
      socket_close($socket);

      $line = str_replace('}{', '},{', $line);
      $line = str_replace('"Best Share"', '"best_share"', $line);
      $return[] = json_decode($line, true);
      if (in_array($cmd, array('coin'))) break;
    }
    if ($c=count($miner)>1)
      switch($cmd) {
        case 'pools':
          $_return = $return[0];
          foreach($_return['POOLS'] as $k=>$rp) {
            foreach($return as $_r) {
              if (!isset($_r['POOLS'][$k]) or $rp['URL']!=$_r['POOLS'][$k]['URL']) {
                unset($_return['POOLS'][$k]);
                break;
              }
            }
          }
          $return = array($_return);
          break;
        case 'stats':
          $_return = $return[0];
          foreach($return as $r) {
            if ($r['STATS'][1]['temp_avg']>$_return['STATS'][1]['temp_avg'])
              $_return['STATS'][1]['temp_avg'] = $r['STATS'][1]['temp_avg'];
          }
          $_return['united'] = true;
          $return = array($_return);
          break;
        case 'summary':
          $_return = $return[0];
          $_return['SUMMARY'][0]['GHS 5s'] = $_return['SUMMARY'][0]['GHS av'] = 0;
          foreach($return as $r) {
            if ($r['SUMMARY'][0]['best_share']>$_return['SUMMARY'][0]['best_share'])
              $_return['SUMMARY'][0]['best_share'] = $r['SUMMARY'][0]['best_share'];
            $_return['SUMMARY'][0]['GHS 5s'] += $r['SUMMARY'][0]['GHS 5s'];
            $_return['SUMMARY'][0]['GHS av'] += $r['SUMMARY'][0]['GHS av'];
          }
          $return = array($_return);
          break;
      }
    return isset($_POST)?$return:$return[0];
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