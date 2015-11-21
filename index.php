<?php
namespace phpmyminer;

require 'vendor/autoload.php';

$core = new Core;
$api = new Api;

$messages = array();
# https://github.com/ckolivas/cgminer/blob/master/API-README
if(isset($_POST['token']) && $token = $core->validCsrf($_POST['token'])) {
  switch($_POST['cmd']) {
    case 'switchpool':
      $result = $api->command($_POST['miner'], 'switchpool', $_POST['pool']);
    break;

    case 'disablepool':
      $result = $api->command($_POST['miner'], 'disablepool', $_POST['pool']);
    break;

    case 'enablepool':
      $result = $api->command($_POST['miner'], 'enablepool', $_POST['pool']);
    break;

    case 'removepool':
      $result = $api->command($_POST['miner'], 'removepool', $_POST['pool']);
    break;

    case 'addpool':
      $poolData = $_POST['url'].','.$_POST['username'].','.$_POST['password'];
      $result = $api->command($_POST['miner'], 'addpool', $poolData);
    break;
  }
  $messages[] = $result['STATUS'][0]['Msg'];
}

$token = $token = $core->getCsrf();


$loader = new \Twig_Loader_Filesystem('.');
$twig = new \Twig_Environment($loader, array('autoescape' => false));

$miners = Core::config('miners');
$diff = file_get_contents('https://blockchain.info/q/getdifficulty');

foreach($miners as $minerId => $minerData) {
  $summary = $api->command($minerId, 'summary');
  $summary['SUMMARY'][0]['max_diff'] = bcadd((float)$diff,0,0);
  $summary['SUMMARY'][0]['share_diff'] = bcdiv(bcmul($summary['SUMMARY'][0]['best_share'], 100, 0), $summary['SUMMARY'][0]['max_diff'], 0);
  $summary['SUMMARY'][0]['best_share'] = number_format($summary['SUMMARY'][0]['best_share'], 0, ',', '.');
  $summary['SUMMARY'][0]['max_diff'] = number_format($summary['SUMMARY'][0]['max_diff'], 0, ',', '.');

  if($summary === false) {
    $minerHtml[$minerId] = 'Unable to connect to '. $miners[$minerId];
    $messages[] = $minerHtml[$minerId];
  } else {
    $config = $api->command($minerId, 'config');
    $stats = $api->command($minerId, 'stats');
    $pools = $api->command($minerId, 'pools');
    $minerHtml[$minerId] = $twig->render('miner.twig', array(
      'summary' => $summary,
      'stats'   => $stats,
      'strategy'   => $config['CONFIG'][0]['Strategy'],
      'pools'   => $pools['POOLS'],
      'token'   => $token,
      'ID'      => $minerId,
    ));
  }
}

echo $twig->render('index.twig', array(
  'miners'    => $miners,
  'minerHtml' => $minerHtml,
  'token'     => $token,
  'msgs'      => $messages,
));
