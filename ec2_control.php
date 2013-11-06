<?php
error_reporting(E_ALL ^ E_NOTICE);

require_once "aws-autoloader.php";

use Aws\Ec2\Ec2Client;
use Aws\Common\Enum\Region;

$client = Ec2Client::factory(array(
        'key'    => '＜アクセスキーを指定＞',
        'secret' => '＜シークレットキーを指定＞',
        'region' => Region::TOKYO
        )
    );

define('INSTANCE_ID', '＜インスタンスIDを指定＞');
define('ERASTIC_IP',  '＜ErasticIPを指定＞');


//////////////////////////////////////////////////////////////////////////////////////

switch ($_GET['cmd']) {
	case 'chinst':
		// インスタンスタイプの設定
		modifyInstanceType($client, $_GET['type']);
#		break;
	case 'status':
		// EC2のインスタンスデータを取得
		$instance = getInstanceInfo($client);
		showInstanceInfo($instance);
		break;
	case 'start':
		// EC2のインスタンスデータを取得
		$instance = getInstanceInfo($client);
		if ($instance['State']['Name'] != 'stopped') { echo 'ec2 is not stopped. now status is "'. $instance['State']['Name'] .'".'; exit; }
		// EC2のインスタンスを起動 と Erastic IPの設定
		$result = start($client);
		break;
	case 'stop':
		// EC2のインスタンスデータを取得
		$instance = getInstanceInfo($client);
		if ($instance['State']['Name'] != 'running') { echo 'ec2 is not running. now status is "'. $instance['State']['Name'] .'".'; exit; }
		// EC2のインスタンスを停止
		$result = stop($client);
		break;
	default:
		echo 'Usage ec2_control.php?cmd=xxxx (status or start or stop or chinst)';
		exit;
}

//////////////////////////////////////////////////////////////////////////////////////


function getInstanceInfo($client) {
	try {
		$result = $client->describeInstances(array(
		    'InstanceIds' => array(INSTANCE_ID),
		));
		$info = $result->toArray();
#		print_R($info);
		$instance = $info['Reservations'][0]['Instances'][0];
	} catch (Exception $e) {
	  echo $e;
	}
	
	return $instance;
}

function start($client) {
	try {
		// EC2のインスタンスを起動
		$result = $client->startInstances(array(
		    'InstanceIds' => array(INSTANCE_ID),
		));
		echo "start instance!<BR>\n";
		
		// EC2の起動を待つ
		$result = waitForStatus($client, 'running');
		if (! $result) {
			echo "waitting timeout";
			exit;
		}
		echo "<BR>\n";
		echo "instance running<BR>\n";
		
		// ipアドレスを割り当てる
		$result = $client->associateAddress(array(
		    'InstanceId' => INSTANCE_ID,
		    'PublicIp'   => ERASTIC_IP,
		));
		echo "set erastic ip<BR>\n";
	} catch (Exception $e) {
	  echo $e;
	}
	
#	return $result;
}

function modifyInstanceType($client, $instanceType) {
	if (! preg_match("/^(t1.micro|m1.small)$/", $instanceType)) {
		echo 'instanceType error: only use "t1.micro" or "m1.small"'."\n<BR>";
		return false;
	}
	
	// インスタンスが止まってないと変更できぬゎ
	$instance = getInstanceInfo($client);
	if ($instance['State']['Name'] !== 'stopped') {
		echo "do not change instance type. because ec2 is not stopped.";
		return false;
	}
	
	// インスタンスタイプの変更
	$result = $client->modifyInstanceAttribute(array(
		'InstanceId' => INSTANCE_ID,
		'InstanceType' => array(
			'Value' => $instanceType,
		),
	));
	
	return $result;
}

function stop($client) {
	$result = $client->stopInstances(array(
	    'InstanceIds' => array(INSTANCE_ID),
	));
	echo "stop instance!<BR>\n";

	// EC2の停止を待つ
	$result = waitForStatus($client, 'stopped');
	if (! $result) {
		echo "waitting timeout";
		exit;
	}
	echo "<BR>\n";
	echo "instance stopped<BR>\n";
	
#	return $result;
}

function waitForStatus($client, $status) {
	// 15秒毎にインスタンスのステータスをチェック (ただし5分まで)
	$max_timeout = 300;
	$interval = 15;
	
	for ($i = 0 ; $i < ($max_timeout / $interval) ; $i++) {
		$instance = getInstanceInfo($client);
		
		echo ".";
#		echo "status: ". $instance['State']['Name'] ." == $status<BR>\n";
		if ($instance['State']['Name'] == $status) {
			return true;
		}
		
		sleep($interval);
	}
	return false;
}

function showInstanceInfo($instance) {
	echo '
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
</head>
<body>
<dl>
  <dt>インスタンスID</dt>
  <dd>'. $instance['InstanceId'] .'</dd>
  <dt>インスタンスタイプ</dt>
  <dd>'. $instance['InstanceType'] .'</dd>
  <dt>
  <dt>ステータス</dt>
  <dd>'. $instance['State']['Name'] .'</dd>
</body>
</html>';
}
