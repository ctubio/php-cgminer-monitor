<?php
namespace phpmyminer;

require 'vendor/autoload.php';

$core = new Core;
$api = new Api;

$results = $messages = array();
# https://github.com/ckolivas/cgminer/blob/master/API-README
if(isset($_POST['cmd'])) {
  switch($_POST['cmd']) {
    case 'restart':
      $results[] = $api->command($_POST['miner'], $_POST['cmd']);
      break;
    case 'switchpool':
    case 'disablepool':
    case 'enablepool':
    case 'removepool':
      $results[] = $api->command($_POST['miner'], $_POST['cmd'], $_POST['pool']);
      break;
    case 'addpool':
      $poolData = $_POST['url'].','.$_POST['username'].','.$_POST['password'];
      $results[] = $api->command($_POST['miner'], 'addpool', $poolData);
      break;
    case 'poolquota':
      foreach($_POST['pool'] as $k=>$v) {
        $poolData = $_POST['pool'][$k].','.$_POST['quota'][$k];
        $results[] = $api->command($_POST['miner'], 'poolquota', $poolData);
      }
      break;
  }
  foreach($results as $_result)
    foreach($_result as $result)
      $messages[] = is_array($result['STATUS'])?$result['STATUS'][0]['Msg']:$result['STATUS'];
}

$loader = new \Twig_Loader_Filesystem('.');
$twig = new \Twig_Environment($loader, array('autoescape' => false));

if ($messages) die($twig->render('msg.twig', array(
  'date'      => '['.$_POST['miner'].'] '.date('H:i:s'),
  'msgs'      => $messages,
  'reset'      => isset($_POST['reset'])?$_POST['reset']:true,
  'tab'      => $_POST['tab'],
)));

unset($_POST);

$miners = Core::config('miners');
foreach($miners as $k=>&$miner)
  $miner = array(
    'id'=>$k,
    'united'=>is_array($miner),
    'title'=>is_array($miner)?'<span class="glyphicon glyphicon-'.strtr(rand(1,3),array(1=>'fire',2=>'heart-empty',3=>'gift')).'"></span>':$miner,
    'active'=>$k?NULL:'active'
  );
$tab = 0;
foreach($miners as $minerId => $minerData) {
  $summary = $api->command($minerId, 'summary');
  if($summary === false) {
    $minerHtml[$minerId] = '<p style="margin:10px;">Unable to connect to '. $miners[$minerId]['title'].'</p>';
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
      'tab'   => $tab,
      'ID'      => $minerId,
      'URL'=>!$miners[$minerId]['united']?'http://'.substr($miners[$minerId]['title'],0,strpos($miners[$minerId]['title'],':')+1).'/cgi-bin/minerStatus.cgi':NULL,
    ));
  }
  $tab++;
}

echo $twig->render('index.twig', array(
  'miners'    => $miners,
  'minerHtml' => $minerHtml,
  'msgs'      => $messages,
  'tab'      => isset($_GET['tab'])?$_GET['tab']:0,
));
