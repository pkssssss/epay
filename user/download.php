<?php
include("../includes/common.php");

if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

switch($act){

case 'wximg':
	if(!checkRefererHost())exit();
	$channelid = intval($_GET['channel']);
	$media_id = trim($_GET['mediaid']);
	$trade_no = isset($_GET['trade_no']) ? trim($_GET['trade_no']) : '';
	if($channelid <= 0 || empty($media_id) || !preg_match('/^[a-zA-Z0-9_-]{6,64}$/', $trade_no))exit();
	$order = $DB->getRow("SELECT trade_no,channel,subchannel FROM pre_order WHERE trade_no=:trade_no AND uid=:uid LIMIT 1", [':trade_no'=>$trade_no, ':uid'=>$uid]);
	if(!$order)exit();
	$allow = intval($order['channel']) === $channelid;
	if(!$allow && !empty($order['subchannel'])){
		$subChannelId = $DB->findColumn('subchannel', 'channel', ['id'=>intval($order['subchannel'])]);
		$allow = intval($subChannelId) === $channelid;
	}
	if(!$allow)exit();
	$channel=\lib\Channel::get($channelid);
	if(!$channel)exit();
	$model = \lib\Complain\CommUtil::getModel($channel);
	$image = $model->getImage($media_id);
	if($image !== false){
		$seconds_to_cache = 3600*24*7;
		header("Cache-Control: max-age=$seconds_to_cache");
		header("Content-Type: image/jpeg");
		echo $image;
	}
break;

default:
	exit('No Act');
break;
}