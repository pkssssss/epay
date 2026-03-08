<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

$writeActs = ['delGroup','saveGroup','saveGroupPrice','addUser','editUser','editUserChannelInfo','delUser','setUser','setUserGroup','resetUser','user_settle_save','user_cert','recharge','addDomain','setDomainStatus','delDomain','setSubChannel','delSubChannel','saveSubChannel','saveSubChannelInfo','addBlack','delBlack','delRecord'];
if(in_array($act, $writeActs, true)){
	if($_SERVER['REQUEST_METHOD'] !== 'POST')exit('{"code":405,"msg":"Method Not Allowed"}');
	if(!checkCsrfToken())exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
}

switch($act){
case 'userList':
	$usergroup = [0=>'默认用户组'];
	$rs = $DB->getAll("SELECT * FROM pre_group");
	foreach($rs as $row){
		$usergroup[$row['gid']] = $row['name'];
	}
	unset($rs);

		$sql=" 1=1";
		$params = [];
		$allowColumns = ['uid','key','account','username','url','qq','phone','email','status','pay','settle','gid','upid'];
		if(isset($_POST['dstatus']) && !empty($_POST['dstatus'])) {
			$dstatus = explode('_', $_POST['dstatus'], 2);
			if(count($dstatus)==2 && in_array($dstatus[0], ['status','pay','settle'], true) && is_numeric($dstatus[1])){
				$sql.=" AND `{$dstatus[0]}`=:dstatus";
				$params[':dstatus']=intval($dstatus[1]);
			}
		}
		if(isset($_POST['gid']) && $_POST['gid']!=='') {
			$gid = intval($_POST['gid']);
			$sql.=" AND `gid`=:gid";
			$params[':gid']=$gid;
		}
		if(isset($_POST['upid']) && $_POST['upid']!=='') {
			$upid = intval($_POST['upid']);
			$sql.=" AND `upid`=:upid";
			$params[':upid']=$upid;
		}
			if(isset($_POST['value']) && $_POST['value']!=='' && isset($_POST['column']) && in_array($_POST['column'], $allowColumns, true)) {
			$sql.=" AND `".$_POST['column']."`=:search_value";
			$params[':search_value']=$_POST['value'];
		}
			$offset = max(0, intval($_POST['offset']));
			$limit = intval($_POST['limit']);
			if($limit < 1)$limit = 20;
			if($limit > 200)$limit = 200;
		$total = $DB->getColumn("SELECT count(*) from pre_user WHERE{$sql}", $params);
		$list = $DB->getAll("SELECT * FROM pre_user WHERE{$sql} order by uid desc limit $offset,$limit", $params);
	$list2 = [];
		foreach($list as $row){
			if($row['endtime']!=null && strtotime($row['endtime'])<time()){
				$DB->exec("UPDATE pre_user SET gid=0,endtime=NULL WHERE uid=:uid", [':uid'=>$row['uid']]);
				$row['gid']=0;
			}elseif($row['endtime']!=null){
				$row['endtime'] = date("Y-m-d", strtotime($row['endtime']));
		}
		$row['groupname'] = $usergroup[$row['gid']];
		$list2[] = $row;
	}

	exit(json_encode(['total'=>$total, 'rows'=>$list2]));
break;

case 'recordList':
		$sql=" 1=1";
		$params = [];
		$allowRecordColumns = ['uid','type','trade_no','action'];
			if(isset($_POST['value']) && $_POST['value']!=='' && isset($_POST['column']) && in_array($_POST['column'], $allowRecordColumns, true)) {
			$sql.=" AND `".$_POST['column']."`=:search_value";
			$params[':search_value']=$_POST['value'];
		}
			$offset = max(0, intval($_POST['offset']));
			$limit = intval($_POST['limit']);
			if($limit < 1)$limit = 20;
			if($limit > 200)$limit = 200;
		$total = $DB->getColumn("SELECT count(*) from pre_record WHERE{$sql}", $params);
		$list = $DB->getAll("SELECT * FROM pre_record WHERE{$sql} order by id desc limit $offset,$limit", $params);

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'userPayStat':
	$startday = trim($_POST['startday']);
	$endday = trim($_POST['endday']);
	$method = trim($_POST['method']);
	$type = intval($_POST['type']);
	if(!$startday || !$endday)exit(json_encode(['code'=>0, 'msg'=>'no day']));
	$data = [];
	$columns = ['uid'=>'商户ID', 'total'=>'总计'];

	if($method == 'type'){
		$paytype = [];
		$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
		foreach($rs as $row){
			$paytype[$row['id']] = $row['showname'];
			if($type == 4){
				$columns['type_'.$row['name']] = $row['showname'];
			}else{
				$columns['type_'.$row['id']] = $row['showname'];
			}
		}
		unset($rs);
	}else{
		$channel = [];
		$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
		foreach($rs as $row){
			$channel[$row['id']] = $row['name'];
		}
		unset($rs);
		}

		if($type == 4){
			$list = $DB->getAll("SELECT uid,type,channel,money FROM pre_transfer WHERE status=1 AND paytime>=:startday AND paytime<=:endday", [':startday'=>$startday, ':endday'=>$endday]);
			foreach($list as $row){
				$money = (float)$row['money'];
				if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
				$data[$row['uid']]['total'] += $money;
			if($method == 'type'){
				$ukey = 'type_'.$row['type'];
				if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
				else $data[$row['uid']][$ukey] += $money;
			}else{
				$ukey = 'channel_'.$row['channel'];
				if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
				else $data[$row['uid']][$ukey] += $money;
				if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
			}
			}
		}else{
			$list = $DB->getAll("SELECT uid,type,channel,money,realmoney,getmoney,profitmoney FROM pre_order WHERE status=1 AND date>=:startday AND date<=:endday", [':startday'=>$startday, ':endday'=>$endday]);
			foreach($list as $row){
				if($type == 3){
					$money = (float)$row['profitmoney'];
				}elseif($type == 2){
				$money = (float)$row['getmoney'];
			}elseif($type == 1){
				$money = (float)$row['realmoney'];
			}else{
				$money = (float)$row['money'];
			}
			if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
			$data[$row['uid']]['total'] += $money;
			if($method == 'type'){
				$ukey = 'type_'.$row['type'];
				if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
				else $data[$row['uid']][$ukey] += $money;
			}else{
				$ukey = 'channel_'.$row['channel'];
				if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
				else $data[$row['uid']][$ukey] += $money;
				if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
			}
		}
	}
	ksort($data);
	$list = [];
	foreach($data as $row){
		$list[] = $row;
	}
	exit(json_encode(['code'=>0, 'columns'=>$columns, 'data'=>$list]));
break;

case 'userTransferStat':
	$startday = trim($_POST['startday']);
	$endday = trim($_POST['endday']);
	$method = trim($_POST['method']);
	if(!$startday || !$endday)exit(json_encode(['code'=>0, 'msg'=>'no day']));
	$data = [];
	$columns = ['uid'=>'商户ID', 'total'=>'总计'];

	if($method == 'type'){
		$paytype = [];
		$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
		foreach($rs as $row){
			$paytype[$row['name']] = $row['showname'];
			$columns['type_'.$row['name']] = $row['showname'];
		}
		unset($rs);
	}else{
		$channel = [];
		$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
		foreach($rs as $row){
			$channel[$row['id']] = $row['name'];
		}
		unset($rs);
	}

		$list = $DB->getAll("SELECT uid,type,channel,money FROM pre_transfer WHERE status=1 AND paytime>=:startday AND paytime<=:endday", [':startday'=>$startday, ':endday'=>$endday]);
		foreach($list as $row){
			$money = (float)$row['money'];
			if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
			$data[$row['uid']]['total'] += $money;
		if($method == 'type'){
			$ukey = 'type_'.$row['type'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
		}else{
			$ukey = 'channel_'.$row['channel'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
			if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
		}
	}
	ksort($data);
	$list = [];
	foreach($data as $row){
		$list[] = $row;
	}
	exit(json_encode(['code'=>0, 'columns'=>$columns, 'data'=>$list]));
break;

	case 'logList':
		$sql=" 1=1";
		$params = [];
		$allowLogColumns = ['uid','type','ip'];
		if(isset($_POST['value']) && $_POST['value']!=='' && isset($_POST['column']) && in_array($_POST['column'], $allowLogColumns, true)) {
			$sql.=" AND `".$_POST['column']."`=:search_value";
			$params[':search_value']=$_POST['value'];
		}
			$offset = max(0, intval($_POST['offset']));
			$limit = intval($_POST['limit']);
			if($limit < 1)$limit = 20;
			if($limit > 200)$limit = 200;
		$total = $DB->getColumn("SELECT count(*) from pre_log WHERE{$sql}", $params);
		$list = $DB->getAll("SELECT * FROM pre_log WHERE{$sql} order by id desc limit $offset,$limit", $params);

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

	case 'domainList':
		$sql=" 1=1";
		$params = [];
		if(isset($_POST['uid']) && !empty($_POST['uid'])) {
			$uid = intval($_POST['uid']);
			$sql.=" AND `uid`=:uid";
			$params[':uid']=$uid;
		}
		if(isset($_POST['kw']) && !empty($_POST['kw'])) {
			$sql.=" AND `domain`=:domain";
			$params[':domain']=$_POST['kw'];
		}
		if(isset($_POST['dstatus']) && $_POST['dstatus']>-1) {
			$dstatus = intval($_POST['dstatus']);
			$sql.=" AND `status`=:status";
			$params[':status']=$dstatus;
		}
			$offset = max(0, intval($_POST['offset']));
			$limit = intval($_POST['limit']);
			if($limit < 1)$limit = 20;
			if($limit > 200)$limit = 200;
		$total = $DB->getColumn("SELECT count(*) from pre_domain WHERE{$sql}", $params);
		$list = $DB->getAll("SELECT * FROM pre_domain WHERE{$sql} order by id desc limit $offset,$limit", $params);

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

	case 'blackList':
		$sql=" 1=1";
		$params = [];
		if(isset($_POST['kw']) && !empty($_POST['kw'])) {
			$sql.=" AND `content`=:content";
			$params[':content']=$_POST['kw'];
		}
		if(isset($_POST['type']) && $_POST['type']>-1) {
			$type = intval($_POST['type']);
			$sql.=" AND `type`=:type";
			$params[':type']=$type;
		}
			$offset = max(0, intval($_POST['offset']));
			$limit = intval($_POST['limit']);
			if($limit < 1)$limit = 20;
			if($limit > 200)$limit = 200;
		$total = $DB->getColumn("SELECT count(*) from pre_blacklist WHERE{$sql}", $params);
		$list = $DB->getAll("SELECT * FROM pre_blacklist WHERE{$sql} order by id desc limit $offset,$limit", $params);

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'getGroup': //用户组
	$gid=intval($_GET['gid']);
	$row=$DB->getRow("SELECT * FROM pre_group WHERE gid=:gid LIMIT 1", [':gid'=>$gid]);
	if(!$row)
		exit('{"code":-1,"msg":"当前用户组不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','gid'=>$gid,'name'=>$row['name'],'info'=>json_decode($row['info'],true),'config'=>$row['config']?json_decode($row['config'],true):[],'settings'=>$row['settings']];
	exit(json_encode($result));
break;
case 'delGroup':
	$gid=intval($_GET['gid']);
	$row=$DB->getRow("SELECT * FROM pre_group WHERE gid=:gid LIMIT 1", [':gid'=>$gid]);
	if(!$row)
		exit('{"code":-1,"msg":"当前用户组不存在！"}');
	if($DB->exec("DELETE FROM pre_group WHERE gid=:gid", [':gid'=>$gid])){
		$DB->exec("UPDATE pre_user SET gid=0 WHERE gid=:gid", [':gid'=>$gid]);
		exit('{"code":0,"msg":"删除用户组成功！"}');
	}
	else exit('{"code":-1,"msg":"删除用户组失败['.$DB->error().']"}');
break;
case 'saveGroup':
	if($_POST['action'] == 'add'){
		$name=trim($_POST['name']);
		$row=$DB->getRow("SELECT * FROM pre_group WHERE name=:name LIMIT 1", [':name'=>$name]);
		if($row)
			exit('{"code":-1,"msg":"用户组名称重复"}');
		$info=json_encode($_POST['info']);
		$config=json_encode($_POST['config']);
		$settings=trim($_POST['settings']);
		if($settings && !checkGroupSettings($settings))exit('{"code":-1,"msg":"用户变量格式不正确"}');
		$data = ['name'=>$name, 'info'=>$info, 'config'=>$config, 'settings'=>$settings];
		if($DB->insert('group', $data))exit('{"code":0,"msg":"新增用户组成功！"}');
		else exit('{"code":-1,"msg":"新增用户组失败['.$DB->error().']"}');
	}elseif($_POST['action'] == 'changebuy'){
		$gid=intval($_POST['gid']);
		$status=intval($_POST['status']);
		if($DB->exec("UPDATE pre_group SET isbuy=:status WHERE gid=:gid", [':status'=>$status, ':gid'=>$gid]))exit('{"code":0,"msg":"修改上架状态成功！"}');
		else exit('{"code":-1,"msg":"修改上架状态失败['.$DB->error().']"}');
	}else{
		$gid=intval($_POST['gid']);
		$name=trim($_POST['name']);
		$row=$DB->getRow("SELECT * FROM pre_group WHERE name=:name AND gid<>:gid LIMIT 1", [':name'=>$name, ':gid'=>$gid]);
		if($row)
			exit('{"code":-1,"msg":"用户组名称重复"}');
		$info=json_encode($_POST['info']);
		$config=json_encode($_POST['config']);
		$settings=trim($_POST['settings']);
		if($settings && !checkGroupSettings($settings))exit('{"code":-1,"msg":"用户变量格式不正确"}');
		$data = ['name'=>$name, 'info'=>$info, 'config'=>$config, 'settings'=>$settings];
		if($DB->update('group', $data, ['gid'=>$gid])!==false)exit('{"code":0,"msg":"修改用户组成功！"}');
		else exit('{"code":-1,"msg":"修改用户组失败['.$DB->error().']"}');
	}
break;
case 'saveGroupPrice':
	$prices = $_POST['price'];
	$expires = $_POST['expire'];
	$sorts = $_POST['sort'];
	foreach($prices as $gid=>$item){
		$gid = intval($gid);
		$price = trim($item);
		$expire = intval($expires[$gid]);
		$sort = trim($sorts[$gid]);
		if(empty($price)||!is_numeric($price))exit('{"code":-1,"msg":"GID:'.$gid.'的售价填写错误"}');
		$DB->exec("UPDATE pre_group SET price=:price,expire=:expire,sort=:sort WHERE gid=:gid", [':price'=>$price, ':expire'=>$expire, ':sort'=>$sort, ':gid'=>$gid]);
	}
	exit('{"code":0,"msg":"保存成功！"}');
break;

case 'addUser':
	$key = random(32);
	$data = [
		'gid' => intval($_POST['gid']),
		'key' => $key,
		'settle_id' => intval($_POST['settle_id']),
		'account' => trim($_POST['account']),
		'username' => trim($_POST['username']),
		'money' => '0.00',
		'url' => trim($_POST['url']),
		'email' => trim($_POST['email']),
		'qq' => trim($_POST['qq']),
		'phone' => trim($_POST['phone']),
		'mode' => intval($_POST['mode']),
		'cert' => 0,
		'pay' => intval($_POST['pay']),
		'settle' => intval($_POST['settle']),
		'status' => intval($_POST['status']),
		'addtime' => 'NOW()',
	];

	if(empty($data['account']) || empty($data['username'])) exit('{"code":-1,"msg":"必填项不能为空！"}');

	if(!empty($data['phone'])){
		if($DB->find('user','*',['phone'=>$data['phone']])) exit('{"code":-1,"msg":"手机号已存在！"}');
	}
	if(!empty($data['email'])){
		if($DB->find('user','*',['email'=>$data['email']])) exit('{"code":-1,"msg":"邮箱已存在！"}');
	}

	$uid = $DB->insert('user', $data);
	if($uid!==false){
		if(!empty($_POST['pwd'])){
			$pwd = hashUserPassword(trim($_POST['pwd']));
			$DB->update('user', ['pwd'=>$pwd], ['uid'=>$uid]);
		}
		exit(json_encode(['code'=>0, 'uid'=>$uid, 'key'=>$key]));
	}else{
		exit('{"code":-1,"msg":"添加商户失败！'.$DB->error().'"}');
	}
break;
case 'editUser':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
	if(!$rows) exit('{"code":-1,"msg":"当前商户不存在！"}');
	$data = [
		'gid' => intval($_POST['gid']),
		'settle_id' => intval($_POST['settle_id']),
		'account' => trim($_POST['account']),
		'username' => trim($_POST['username']),
		'money' => trim($_POST['money']),
		'url' => trim($_POST['url']),
		'email' => trim($_POST['email']),
		'qq' => trim($_POST['qq']),
		'phone' => trim($_POST['phone']),
		'cert' => intval($_POST['cert']),
		'certtype' => intval($_POST['certtype']),
		'certmethod' => intval($_POST['certmethod']),
		'certno' => trim($_POST['certno']),
		'certname' => trim($_POST['certname']),
		'certcorpno' => trim($_POST['certcorpno']),
		'certcorpname' => trim($_POST['certcorpname']),
		'ordername' => trim($_POST['ordername']),
		'mode' => intval($_POST['mode']),
		'pay' => intval($_POST['pay']),
		'settle' => intval($_POST['settle']),
		'status' => intval($_POST['status']),
	];

	if(empty($data['account']) || empty($data['username'])) exit('{"code":-1,"msg":"必填项不能为空！"}');

	if($DB->update('user', $data, ['uid'=>$uid])!==false){
		if(!empty($_POST['pwd'])){
			$pwd = hashUserPassword(trim($_POST['pwd']));
			$DB->update('user', ['pwd'=>$pwd], ['uid'=>$uid]);
		}
		exit('{"code":0}');
	}else{
		exit('{"code":-1,"msg":"修改商户信息失败！'.$DB->error().'"}');
	}
break;
case 'editUserChannelInfo':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
	if(!$rows) exit('{"code":-1,"msg":"当前商户不存在！"}');
	$setting=$_POST['setting'];
	$channelinfo = json_encode($setting);
	if($DB->update('user', ['channelinfo'=>$channelinfo], ['uid'=>$uid])!==false){
		exit('{"code":0}');
	}else{
		exit('{"code":-1,"msg":"修改商户信息失败！'.$DB->error().'"}');
	}
break;
case 'delUser':
	$uid=intval($_GET['uid']);
	if($DB->exec("DELETE FROM pre_user WHERE uid=:uid", [':uid'=>$uid])){
		$DB->exec("DELETE FROM pre_subchannel WHERE uid=:uid", [':uid'=>$uid]);
		exit('{"code":0}');
	}else{
		exit('{"code":-1,"msg":"删除商户失败！'.$DB->error().'"}');
	}
break;
case 'setUser':
	$uid=intval($_POST['uid']);
	$type=trim($_POST['type']);
	$status=intval($_POST['status']);
	if($type=='pay')$column = 'pay';
	elseif($type=='settle')$column = 'settle';
	elseif($type=='group')$column = 'gid';
	else $column = 'status';
	$sql = "UPDATE pre_user SET `{$column}`=:status WHERE uid=:uid";
	if($DB->exec($sql, [':status'=>$status, ':uid'=>$uid])!==false)exit('{"code":0,"msg":"修改用户成功！"}');
	else exit('{"code":-1,"msg":"修改用户失败['.$DB->error().']"}');
break;
case 'setUserGroup':
	$uid=intval($_POST['uid']);
	$gid=intval($_POST['gid']);
	$endtime=trim($_POST['endtime']);
	if(changeUserGroup($uid, $gid, $endtime)!==false)exit('{"code":0,"msg":"修改用户成功！"}');
	else exit('{"code":-1,"msg":"修改用户失败['.$DB->error().']"}');
break;
case 'resetUser':
	$uid=intval($_GET['uid']);
	$key = random(32);
	if($DB->exec("UPDATE pre_user SET `key`=:userkey WHERE uid=:uid", [':userkey'=>$key, ':uid'=>$uid])!==false)exit('{"code":0,"msg":"重置密钥成功","key":"'.$key.'"}');
	else exit('{"code":-1,"msg":"重置密钥失败['.$DB->error().']"}');
break;
case 'user_settle_info':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
	if(!$rows)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	$data = '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算方式</div><select class="form-control" id="pay_type" default="'.$rows['settle_id'].'">'.($conf['settle_alipay']?'<option value="1">支付宝</option>':null).''.($conf['settle_wxpay']?'<option value="2">微信</option>':null).''.($conf['settle_qqpay']?'<option value="3">QQ钱包</option>':null).''.($conf['settle_bank']?'<option value="4">银行卡</option>':null).'</select></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算账号</div><input type="text" id="pay_account" value="'.$rows['account'].'" class="form-control" required/></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">真实姓名</div><input type="text" id="pay_name" value="'.$rows['username'].'" class="form-control" required/></div></div>';
	$data .= '<input type="submit" id="save" onclick="saveInfo('.$uid.')" class="btn btn-primary btn-block" value="保存">';
	$result=array("code"=>0,"msg"=>"succ","data"=>$data,"pay_type"=>$rows['settle_id']);
	exit(json_encode($result));
break;
case 'user_settle_save':
	$uid=intval($_POST['uid']);
	$pay_type=trim(daddslashes($_POST['pay_type']));
	$pay_account=trim(daddslashes($_POST['pay_account']));
	$pay_name=trim(daddslashes($_POST['pay_name']));
	$sds=$DB->exec("UPDATE pre_user SET settle_id=:settle_id, account=:account, username=:username WHERE uid=:uid", [':settle_id'=>$pay_type, ':account'=>$pay_account, ':username'=>$pay_name, ':uid'=>$uid]);
	if($sds!==false)
		exit('{"code":0,"msg":"修改记录成功！"}');
	else
		exit('{"code":-1,"msg":"修改记录失败！'.$DB->error().'"}');
break;
case 'user_cert':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("SELECT cert,certtype,certmethod,certno,certname,certcorpno,certcorpname,certtime FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
	if(!$rows)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	$rows['certmethodname'] = show_cert_method($rows['certmethod']);
	$result = ['code'=>0,'msg'=>'succ','uid'=>$uid,'data'=>$rows];
	exit(json_encode($result));
break;
case 'recharge':
	$uid=intval($_POST['uid']);
	$do=$_POST['actdo'];
	$rmb=floatval($_POST['rmb']);
	$row=$DB->getRow("SELECT uid,money FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
	if(!$row)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	if($do==1 && $rmb>$row['money'])$rmb=$row['money'];
	if($do==0){
		changeUserMoney($uid, $rmb, true, '后台加款');
	}else{
		changeUserMoney($uid, $rmb, false, '后台扣款');
	}
	exit('{"code":0,"msg":"succ"}');
break;

case 'addDomain':
	$uid=intval($_POST['uid']);
	$domain = trim(daddslashes($_POST['domain']));
	if(empty($domain))exit('{"code":-1,"msg":"域名不能为空"}');
	if(!checkDomain($domain))exit('{"code":-1,"msg":"域名格式不正确"}');
	$row=$DB->getRow("SELECT uid FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
	if(!$row)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	if($DB->getRow("select * from pre_domain where uid=:uid and domain=:domain limit 1", [':uid'=>$uid, ':domain'=>$domain]))
		exit('{"code":-1,"msg":"该域名已存在，请勿重复添加"}');
	if(!$DB->exec("INSERT INTO `pre_domain` (`uid`,`domain`,`status`,`addtime`,`endtime`) VALUES (:uid, :domain, 1, NOW(), NOW())", [':uid'=>$uid, ':domain'=>$domain]))exit('{"code":-1,"msg":"添加失败'.$DB->error().'"}');
	exit(json_encode(['code'=>0, 'msg'=>'添加域名成功！']));
break;
case 'setDomainStatus':
	$id=intval($_POST['id']);
	$status=intval($_POST['status']);
	if($DB->exec("UPDATE pre_domain SET status=:status,endtime=NOW() WHERE id=:id", [':status'=>$status, ':id'=>$id])!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"修改失败['.$DB->error().']"}');
break;
case 'delDomain':
	$id=intval($_POST['id']);
	if($DB->exec("DELETE FROM pre_domain WHERE id=:id", [':id'=>$id])!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
break;

case 'getChannels':
	$typeid = intval($_GET['typeid']);
	$type=$DB->getColumn("SELECT name FROM pre_type WHERE id=:typeid", [':typeid'=>$typeid]);
	if(!$type)
		exit('{"code":-1,"msg":"当前支付方式不存在！"}');
	$list=$DB->getAll("SELECT id,name FROM pre_channel WHERE `type`=:typeid AND status=1 ORDER BY id ASC", [':typeid'=>$typeid]);
	if($list){
		$result = ['code'=>0,'msg'=>'succ','data'=>$list];
		exit(json_encode($result));
	}
	else exit('{"code":-1,"msg":"该支付方式下没有可用的支付通道"}');
break;
case 'getSubChannel':
	$id=intval($_GET['id']);
	$row=$DB->getRow("SELECT A.*,B.type FROM pre_subchannel A LEFT JOIN pre_channel B ON A.channel=B.id WHERE A.id=:id", [':id'=>$id]);
	if(!$row)
		exit('{"code":-1,"msg":"当前子通道不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','data'=>$row];
	exit(json_encode($result));
break;
case 'setSubChannel':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	$row=$DB->getRow("SELECT * FROM pre_subchannel WHERE id=:id", [':id'=>$id]);
	if(!$row)
		exit('{"code":-1,"msg":"当前子通道不存在！"}');
	if($DB->exec("UPDATE pre_subchannel SET status=:status WHERE id=:id", [':status'=>$status, ':id'=>$id]))exit('{"code":0,"msg":"修改子通道成功！"}');
	else exit('{"code":-1,"msg":"修改子通道失败['.$DB->error().']"}');
break;
case 'delSubChannel':
	$id=intval($_GET['id']);
	$row=$DB->getRow("SELECT * FROM pre_subchannel WHERE id=:id", [':id'=>$id]);
	if(!$row)
		exit('{"code":-1,"msg":"当前子通道不存在！"}');
	if($DB->exec("DELETE FROM pre_subchannel WHERE id=:id", [':id'=>$id]))exit('{"code":0,"msg":"删除子通道成功！"}');
	else exit('{"code":-1,"msg":"删除子通道失败['.$DB->error().']"}');
break;
case 'saveSubChannel':
	if($_POST['action'] == 'add'){
		$uid=intval($_POST['uid']);
		$name=trim($_POST['name']);
		$type=intval($_POST['type']);
		$channel=intval($_POST['channel']);
		$row=$DB->getRow("SELECT * FROM pre_subchannel WHERE name=:name AND uid=:uid LIMIT 1", [':name'=>$name, ':uid'=>$uid]);
		if($row)
			exit('{"code":-1,"msg":"子通道备注重复"}');
		$data = ['channel'=>$channel, 'uid'=>$uid, 'name'=>$name, 'addtime'=>'NOW()', 'usetime'=>'NOW()'];
		if($DB->insert('subchannel', $data))exit('{"code":0,"msg":"新增子通道成功！"}');
		else exit('{"code":-1,"msg":"新增子通道失败['.$DB->error().']"}');
	}else{
		$id=intval($_POST['id']);
		$row=$DB->getRow("SELECT * FROM pre_subchannel WHERE id=:id", [':id'=>$id]);
		if(!$row) exit('{"code":-1,"msg":"当前子通道不存在！"}');
		$uid=intval($_POST['uid']);
		$name=trim($_POST['name']);
		$type=intval($_POST['type']);
		$channel=intval($_POST['channel']);
		$nrow=$DB->getRow("SELECT * FROM pre_subchannel WHERE name=:name AND uid=:uid AND id<>:id LIMIT 1", [':name'=>$name, ':uid'=>$uid, ':id'=>$id]);
		if($nrow)
			exit('{"code":-1,"msg":"子通道名称重复"}');
		$data = ['channel'=>$channel, 'name'=>$name];
		if($DB->update('subchannel', $data, ['id'=>$id])!==false){
			exit('{"code":0,"msg":"修改子通道成功！"}');
		}else exit('{"code":-1,"msg":"修改子通道失败['.$DB->error().']"}');
	}
break;
case 'subChannelInfo':
	$id=intval($_GET['id']);
	$subrow=$DB->getRow("SELECT * FROM pre_subchannel WHERE id=:id", [':id'=>$id]);
	if(!$subrow)
		exit('{"code":-1,"msg":"当前子通道不存在！"}');
	$row=$DB->getRow("SELECT * FROM pre_channel WHERE id=:channel", [':channel'=>$subrow['channel']]);
	if(!$row)
		exit('{"code":-1,"msg":"当前子通道对应支付通道不存在！"}');
	$typename = $DB->getColumn("SELECT name FROM pre_type WHERE id=:type", [':type'=>$row['type']]);
	$plugin = \lib\Plugin::getConfig($row['plugin']);
	if(!$plugin)
		exit('{"code":-1,"msg":"当前支付插件不存在！"}');

	$info = json_decode($subrow['info'], true);
	$config = json_decode($row['config'],true);
	$data = '<div class="modal-body"><form class="form" id="form-info">';
	foreach($plugin['inputs'] as $key=>$input){
		if(substr($config[$key],0,1)=='['){
			$key = substr($config[$key],1,-1);
			if($input['type'] == 'textarea'){
				$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><textarea id="'.$key.'" name="info['.$key.']" rows="2" class="form-control" placeholder="'.$input['note'].'">'.$info[$key].'</textarea></div>';
			}elseif($input['type'] == 'select'){
				$addOptions = '';
				foreach($input['options'] as $k=>$v){
					$addOptions.='<option value="'.$k.'" '.($info[$key]==$k?'selected':'').'>'.$v.'</option>';
				}
				$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><select class="form-control" name="info['.$key.']" default="'.$info[$key].'">'.$addOptions.'</select></div>';
			}else{
				$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><input type="text" id="'.$key.'" name="info['.$key.']" value="'.$info[$key].'" class="form-control" placeholder="'.$input['note'].'"/></div>';
			}
		}
	}

	$data .= '<button type="button" id="save" onclick="saveInfo('.$id.')" class="btn btn-primary btn-block">保存</button></form></div>';
	$result=array("code"=>0,"msg"=>"succ","data"=>$data);
	exit(json_encode($result));
break;
case 'saveSubChannelInfo':
	$id=intval($_GET['id']);
	$info=$_POST['info'];
	$info = $info ? json_encode($info) : null;
	if($DB->update('subchannel', ['info'=>$info], ['id'=>$id])!==false)exit('{"code":0,"msg":"修改自定义支付参数成功！"}');
	else exit('{"code":-1,"msg":"修改自定义支付参数失败['.$DB->error().']"}');
break;

case 'addBlack':
	$type=intval($_POST['type']);
	$content = trim($_POST['content']);
	$days=intval($_POST['days']);
	$remark = trim($_POST['remark']);
	if(empty($content))exit('{"code":-1,"msg":"拉黑内容不能为空"}');
	if($DB->getRow("select * from pre_blacklist where type=:type and content=:content limit 1", [':type'=>$type, ':content'=>$content]))
		exit('{"code":-1,"msg":"该黑名单记录已存在，请勿重复添加"}');
	$endtime = $days > 0 ? date('Y-m-d H:i:s', strtotime('+'.$days.' days')) : null;
	$data = ['type'=>$type, 'content'=>$content, 'addtime'=>'NOW()', 'endtime'=>$endtime, 'remark'=>$remark];
	if($DB->insert('blacklist', $data))exit(json_encode(['code'=>0, 'msg'=>'添加黑名单成功！']));
	else exit('{"code":-1,"msg":"添加失败'.$DB->error().'"}');
break;
case 'delBlack':
	$id=intval($_POST['id']);
	if($DB->exec("DELETE FROM pre_blacklist WHERE id=:id", [':id'=>$id])!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
break;

case 'delRecord':
	$id=intval($_GET['id']);
	if($DB->exec("DELETE FROM pre_record WHERE id=:id", [':id'=>$id])!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
break;

case 'checkuid':
	$uid=intval($_GET['uid']);
	$row=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
	if($row)
		exit('{"code":0,"msg":"succ"}');
	else
		exit('{"code":-1,"msg":"当前商户ID不存在"}');
break;

default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}
