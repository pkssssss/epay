<?php
$clientip=real_ip($conf['ip_type']?$conf['ip_type']:0);

if(isset($_COOKIE["admin_token"]))
{
	$token=authcode(daddslashes($_COOKIE['admin_token']), 'DECODE', SYS_KEY);
	list($user, $sid, $expiretime) = explode("\t", $token);
	$session=getAdminSessionSignature($conf['admin_user'], $conf['admin_pwd']);
	if($session==$sid && $expiretime>time()) {
		$islogin=1;
	}
}
if(isset($_COOKIE["user_token"]))
{
	$token=authcode(daddslashes($_COOKIE['user_token']), 'DECODE', SYS_KEY);
	list($uid, $sid, $expiretime) = explode("\t", $token);
	$uid = intval($uid);
	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid limit 1", [':uid'=>$uid]);
	$session = '';
	if($userrow){
		$session=getUserSessionSignature($userrow['uid'], $userrow['key']);
	}
	if($userrow && $session==$sid && $expiretime>time()) {
		$islogin2=1;
	}
}
?>
