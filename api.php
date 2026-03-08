<?php
$nosession = true;
define('API_INIT', true);
require './includes/common.php';

if(isset($_GET['s'])){
	\lib\ApiHelper::load_api($_GET['s']);
	exit;
}

$act=isset($_GET['act'])?daddslashes($_GET['act']):null;
@header('Content-Type: application/json; charset=UTF-8');

$legacyApiVerify = function($userrow, $query){
	try{
		\lib\ApiHelper::api_verify($userrow, $query);
	}catch(Exception $e){
		exit(json_encode(['code'=>-3, 'msg'=>'旧查询接口已升级为签名模式，请改用签名请求：'.$e->getMessage()]));
	}
};
if($act=='query')
{
	$pid=intval($_GET['pid']);
	$key=isset($_GET['key']) ? trim($_GET['key']) : '';
	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$pid]);
	if(!$userrow) exit(json_encode(['code'=>-3, 'msg'=>'商户ID不存在']));
	$legacyApiVerify($userrow, $_GET);

	$orders=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid=:uid", [':uid'=>$pid]);
	$lastday=date("Y-m-d",strtotime("-1 day"));
	$today=date("Y-m-d");
	$order_today=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid=:uid AND status=1 AND date=:today", [':uid'=>$pid, ':today'=>$today]);
	$order_lastday=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid=:uid AND status=1 AND date=:lastday", [':uid'=>$pid, ':lastday'=>$lastday]);

	$result=array("code"=>1,"pid"=>$pid,"active"=>$userrow['status'],"money"=>$userrow['money'],"type"=>$userrow['settle_id'],"account"=>$userrow['account'],"username"=>$userrow['username'],"orders"=>$orders,"orders_today"=>$order_today,"orders_lastday"=>$order_lastday);
	exit(json_encode($result));
}
elseif($act=='settle')
{
	$pid=intval($_GET['pid']);
	$key=isset($_GET['key']) ? trim($_GET['key']) : '';
	$limit=isset($_GET['limit'])?intval($_GET['limit']):10;
	$offset=isset($_GET['offset'])?intval($_GET['offset']):0;
	if($limit<1)$limit=10;
	if($limit>50)$limit=50;
	if($offset<0)$offset=0;
	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$pid]);
	if(!$userrow) exit(json_encode(['code'=>-3, 'msg'=>'商户ID不存在']));
	$legacyApiVerify($userrow, $_GET);

	$data = [];
	$list=$DB->getAll("SELECT * FROM pre_settle WHERE uid=:uid ORDER BY id DESC LIMIT {$offset},{$limit}", [':uid'=>$pid]);
	foreach($list as $row){
		$data[]=$row;
	}
	if($list!==false){
		$result=array("code"=>1,"msg"=>"查询结算记录成功！","data"=>$data);
	}else{
		$result=array("code"=>-1,"msg"=>"查询结算记录失败！");
	}
	exit(json_encode($result));
}
elseif($act=='order')
{
	if(isset($_GET['sign']) && isset($_GET['trade_no'])){
		$trade_no=trim($_GET['trade_no']);
		if(!preg_match('/^[a-zA-Z0-9_-]{6,64}$/', $trade_no)) exit(json_encode(['code'=>-4, 'msg'=>'订单号格式错误']));
		if(empty($_GET['sign']) || md5(SYS_KEY.$trade_no.SYS_KEY) !== $_GET['sign']) exit(json_encode(['code'=>-3, 'msg'=>'verify sign failed']));
		$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no LIMIT 1", [':trade_no'=>$trade_no]);
	}else{
		$pid=intval($_GET['pid']);
		$key=isset($_GET['key']) ? trim($_GET['key']) : '';
		$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$pid]);
		if(!$userrow) exit(json_encode(['code'=>-3, 'msg'=>'商户ID不存在']));
		$legacyApiVerify($userrow, $_GET);

		if(!empty($_GET['trade_no'])){
			$trade_no=trim($_GET['trade_no']);
			if(!preg_match('/^[a-zA-Z0-9_-]{6,64}$/', $trade_no)) exit(json_encode(['code'=>-4, 'msg'=>'订单号格式错误']));
			$row=$DB->getRow("SELECT * FROM pre_order WHERE uid=:uid AND trade_no=:trade_no LIMIT 1", [':uid'=>$pid, ':trade_no'=>$trade_no]);
		}elseif(!empty($_GET['out_trade_no'])){
			$out_trade_no=trim($_GET['out_trade_no']);
			$row=$DB->getRow("SELECT * FROM pre_order WHERE uid=:uid AND out_trade_no=:out_trade_no LIMIT 1", [':uid'=>$pid, ':out_trade_no'=>$out_trade_no]);
		}else{
			exit(json_encode(['code'=>-4, 'msg'=>'订单号不能为空']));
		}
	}
	if($row){
		$type=$DB->getColumn("SELECT name FROM pre_type WHERE id=:id LIMIT 1", [':id'=>$row['type']]);
		$result=array("code"=>1,"msg"=>"succ","trade_no"=>$row['trade_no'],"out_trade_no"=>$row['out_trade_no'],"api_trade_no"=>$row['api_trade_no'],"type"=>$type,"pid"=>$row['uid'],"addtime"=>$row['addtime'],"endtime"=>$row['endtime'],"name"=>$row['name'],"money"=>$row['money'],"param"=>$row['param'],"buyer"=>$row['buyer'],"status"=>$row['status'],"payurl"=>$row['payurl']);
	}else{
		$result=array("code"=>-1,"msg"=>"订单号不存在");
	}
	exit(json_encode($result));
}
elseif($act=='orders')
{
	$pid=intval($_GET['pid']);
	$key=isset($_GET['key']) ? trim($_GET['key']) : '';
	$limit=isset($_GET['limit'])?intval($_GET['limit']):10;
	$offset=isset($_GET['offset'])?intval($_GET['offset']):0;
	if($limit<1)$limit=10;
	if($limit>50)$limit=50;
	if($offset<0)$offset=0;
	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$pid]);
	if(!$userrow) exit(json_encode(['code'=>-3, 'msg'=>'商户ID不存在']));
	$legacyApiVerify($userrow, $_GET);

	$sql = " A.uid=:uid";
	$params = [':uid'=>$pid];
	if(isset($_GET['status'])){
		$status = intval($_GET['status']);
		$sql .= " AND A.status=:status";
		$params[':status'] = $status;
	}

	$data = [];
	$list=$DB->getAll("SELECT A.*,B.name typename FROM pre_order A LEFT JOIN pre_type B ON A.type=B.id WHERE{$sql} ORDER BY trade_no DESC LIMIT {$offset},{$limit}", $params);
	foreach($list as $row){
		$data[]=["trade_no"=>$row['trade_no'],"out_trade_no"=>$row['out_trade_no'],"type"=>$row['typename'],"pid"=>$row['uid'],"addtime"=>$row['addtime'],"endtime"=>$row['endtime'],"name"=>$row['name'],"money"=>$row['money'],"param"=>$row['param'],"buyer"=>$row['buyer'],"status"=>$row['status']];
	}
	if($list!==false){
		$result=array("code"=>1,"msg"=>"查询订单记录成功！","count"=>count($data),"data"=>$data);
	}else{
		$result=array("code"=>-1,"msg"=>"查询订单记录失败！");
	}
	exit(json_encode($result));
}
elseif($act=='refund')
{
	if($_SERVER['REQUEST_METHOD'] !== 'POST') exit(json_encode(['code'=>-5, 'msg'=>'Request method error']));
	if(!$conf['user_refund']) exit(json_encode(['code'=>-4, 'msg'=>'未开启商户后台自助退款']));
	$pid=intval($_POST['pid']);
	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$pid]);
	if(!$userrow) exit(json_encode(['code'=>-3, 'msg'=>'商户ID不存在']));
	if($userrow['refund'] == 0) exit(json_encode(['code'=>-2, 'msg'=>'商户未开启订单退款API接口']));
	try{
		\lib\ApiHelper::api_verify($userrow, $_POST);
	}catch(Exception $e){
		exit(json_encode(['code'=>-3, 'msg'=>'旧退款接口已升级为签名模式，请改用签名请求：'.$e->getMessage()]));
	}

	$money = trim($_POST['money']);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit(json_encode(['code'=>-1, 'msg'=>'金额输入错误']));

	if(!empty($_POST['trade_no'])){
		$trade_no=trim($_POST['trade_no']);
		if(!preg_match('/^[a-zA-Z0-9_-]{6,64}$/', $trade_no)) exit(json_encode(['code'=>-4, 'msg'=>'订单号格式错误']));
	}elseif(!empty($_POST['out_trade_no'])){
		$out_trade_no=trim($_POST['out_trade_no']);
		$trade_no = $DB->findColumn('order', 'trade_no', ['out_trade_no'=>$out_trade_no, 'uid'=>$pid]);
		if(!$trade_no) exit(json_encode(['code'=>-1, 'msg'=>'当前订单不存在！']));
	}else{
		exit(json_encode(['code'=>-4, 'msg'=>'订单号不能为空']));
	}

	$refund_no = date("YmdHis").rand(11111,99999);
	$result = \lib\Order::refund($refund_no, $trade_no, $money, 1, $pid);
	if($result['code'] == 0){
		$result['msg'] = '退款成功！退款金额￥'.$result['money'];
	}
	exit(json_encode($result));
}
else
{
	exit(json_encode(['code'=>-5, 'msg'=>'No Act!']));
}
