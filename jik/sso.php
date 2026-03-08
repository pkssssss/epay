<?php
include("../includes/common.php");

if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !checkRefererHost() || !checkCsrfToken())sysmsg('CSRF TOKEN ERROR');

$uid=intval($_POST['uid']);

$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$uid]);
if(!$userrow)sysmsg('当前用户不存在！');

$session=getUserSessionSignature($uid, $userrow['key']);
$expiretime=time()+604800;
$token=authcode("{$uid}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
setSecureCookie("user_token", $token, time() + 604800, '/user', true);

exit("<script language='javascript'>window.location.href='../user/';</script>");
