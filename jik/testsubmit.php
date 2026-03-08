<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$submit2=true;

$trade_no=isset($_GET['trade_no'])?trim($_GET['trade_no']):'';
if(!preg_match('/^[a-zA-Z0-9_-]{6,64}$/', $trade_no))sysmsg('订单号格式不正确');
$order=$DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no LIMIT 1", [':trade_no'=>$trade_no]);
if(!$order)sysmsg('该订单号不存在，请返回来源地重新发起请求！');

$paytype=$DB->getRow("SELECT id,name,status FROM pre_type WHERE id=:id LIMIT 1", [':id'=>$order['type']]);
if(!$paytype)sysmsg('支付方式不存在');

$channelrow=$DB->getRow("SELECT id,plugin,apptype FROM pre_channel WHERE id=:id LIMIT 1", [':id'=>$order['channel']]);
if(!$channelrow)sysmsg('支付通道不存在');

$order['typename'] = $paytype['name'];
$order['profits'] = \lib\Payment::updateOrderProfits($order, $channelrow['plugin']);
$order['profits2'] = \lib\Payment::updateOrderProfits2($order, $channelrow['plugin']);

try{
	$result = \lib\Plugin::loadForSubmit($channelrow['plugin'], $trade_no);
	$result['submit'] = true;
	\lib\Payment::echoDefault($result);
}catch(Exception $e){
	sysmsg($e->getMessage());
}
