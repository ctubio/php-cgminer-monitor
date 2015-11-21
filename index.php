<?php
namespace phpmyminer;

require 'vendor/autoload.php';

$core = new Core;
$api = new Api;

$messages = array();
# https://github.com/ckolivas/cgminer/blob/master/API-README
if(isset($_POST['token']) && $token = $core->validCsrf($_POST['token'])) {
  switch($_POST['cmd']) {
    case 'restart':
      $result = $api->command($_POST['miner'], $_POST['cmd']);
      break;
    case 'switchpool':
    case 'disablepool':
    case 'enablepool':
    case 'removepool':
      $result = $api->command($_POST['miner'], $_POST['cmd'], $_POST['pool']);
      break;
    case 'addpool':
      $poolData = $_POST['url'].','.$_POST['username'].','.$_POST['password'];
      $result = $api->command($_POST['miner'], 'addpool', $poolData);
      break;
    case 'poolquota':
      $poolData = $_POST['pool'].','.$_POST['quota'];
      $result = $api->command($_POST['miner'], 'poolquota', $poolData);
      break;
  }
  $messages[] = is_array($result['STATUS'])?$result['STATUS'][0]['Msg']:$result['STATUS'];
}

$loader = new \Twig_Loader_Filesystem('.');
$twig = new \Twig_Environment($loader, array('autoescape' => false));

if ($messages) die($twig->render('msg.twig', array(
  'msgs'      => $messages,
  'tab'      => $_POST['tab'],
)));

$token = $token = $core->getCsrf();

$miners = Core::config('miners');
$tab = 0;
foreach($miners as $minerId => $minerData) {
  $summary = $api->command($minerId, 'summary');
  if($summary === false) {
    $minerHtml[$minerId] = '<p style="margin:10px;">Unable to connect to '. $miners[$minerId].'</p>';
    $messages[] = $minerHtml[$minerId];
  } else {
    $config = $api->command($minerId, 'config');
    $stats = $api->command($minerId, 'stats');
    $pools = $api->command($minerId, 'pools');
    foreach($pools['POOLS'] as $k=>$v){
      $pools['POOLS'][$k]['isWorking'] = $config['CONFIG'][0]['Strategy']=='Failover'
        ? !$pools['POOLS'][$k]['Priority'] : $pools['POOLS'][$k]['Status']=='Alive';
    }
    $coin = $api->command($minerId, 'coin');
    $summary['SUMMARY'][0]['max_diff'] = bcadd($coin['COIN'][0]["Network Difficulty"],0,0);
    $summary['SUMMARY'][0]['share_diff'] = bcdiv(bcmul($summary['SUMMARY'][0]['best_share'], 100, 0), $summary['SUMMARY'][0]['max_diff'], 0);
    $summary['SUMMARY'][0]['best_share'] = number_format($summary['SUMMARY'][0]['best_share'], 0, ',', '.');
    $summary['SUMMARY'][0]['max_diff'] = number_format($summary['SUMMARY'][0]['max_diff'], 0, ',', '.');

    $minerHtml[$minerId] = $twig->render('miner.twig', array(
      'summary' => $summary,
      'stats'   => $stats,
      'strategy'   => $config['CONFIG'][0]['Strategy'],
      'pools'   => $pools['POOLS'],
      'token'   => $token,
      'tab'   => $tab,
      'ID'      => $minerId,
    ));
  }
  $tab++;
}

echo $twig->render('index.twig', array(
  'miners'    => $miners,
  'minerHtml' => $minerHtml,
  'token'     => $token,
  'msgs'      => $messages,
  'tab'      => isset($_GET['tab'])?$_GET['tab']:0,
));
