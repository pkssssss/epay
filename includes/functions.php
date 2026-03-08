<?php
function curl_get($url)
{
	global $conf;
	$ch=curl_init($url);
	if($conf['proxy'] == 1){
		$proxy_server = $conf['proxy_server'];
		$proxy_port = intval($conf['proxy_port']);
		$proxy_userpwd = $conf['proxy_user'].':'.$conf['proxy_pwd'];
		if($conf['proxy_type'] == 'https'){
			$proxy_type = CURLPROXY_HTTPS;
		}elseif($conf['proxy_type'] == 'sock4'){
			$proxy_type = CURLPROXY_SOCKS4;
		}elseif($conf['proxy_type'] == 'sock5'){
			$proxy_type = CURLPROXY_SOCKS5;
		}else{
			$proxy_type = CURLPROXY_HTTP;
		}
		curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_PROXY, $proxy_server);
		curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_userpwd);
		curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy_type);
	}
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Connection: close";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36');
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	$content=curl_exec($ch);
	curl_close($ch);
	return $content;
}
function get_curl($url, $post=0, $referer=0, $cookie=0, $header=0, $ua=0, $nobaody=0, $addheader=0, $location=0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Connection: close";
	if($addheader){
		$httpheader = array_merge($httpheader, $addheader);
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if ($header) {
		curl_setopt($ch, CURLOPT_HEADER, true);
	}
	if ($cookie) {
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	if($referer){
		curl_setopt($ch, CURLOPT_REFERER, $referer);
	}
	if ($ua) {
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);
	}
	else {
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.0.4; es-mx; HTC_One_X Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0");
	}
	if ($nobaody) {
		curl_setopt($ch, CURLOPT_NOBODY, 1);
	}
	if ($location) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	}
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$ret = curl_exec($ch);
	curl_close($ch);
	return $ret;
}
function real_ip($type=0){
$ip = $_SERVER['REMOTE_ADDR'];
if($type<=0 && isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
	foreach ($matches[0] AS $xip) {
		if (filter_var($xip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			$ip = $xip;
			break;
		}
	}
} elseif ($type<=0 && isset($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif ($type<=1 && isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
	$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif ($type<=1 && isset($_SERVER['HTTP_X_REAL_IP']) && filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
	$ip = $_SERVER['HTTP_X_REAL_IP'];
}
return $ip;
}
function get_ip_city($ip)
{
    $url = 'https://www.bt.cn/api/panel/get_ip_info?ip=' . $ip;
    $response = get_curl($url);
    $result = json_decode($response, true);
	if(isset($result[$ip])){
		$data = $result[$ip];
		if($data['country'] == '中国'){
			return $data['province'].$data['city'];
		}else{
			return $data['country'].$data['province'].$data['city'];
		}
	}
	return false;
}
function send_mail($to, $sub, $msg) {
	global $conf;
	if($conf['mail_cloud']==1){
		$mail = new \lib\mail\Sendcloud($conf['mail_apiuser'], $conf['mail_apikey']);
		return $mail->send($to, $sub, $msg, $conf['mail_name2'], $conf['sitename']);
	}elseif($conf['mail_cloud']==2){
		$mail = new \lib\mail\Aliyun($conf['mail_apiuser'], $conf['mail_apikey']);
		return $mail->send($to, $sub, $msg, $conf['mail_name2'], $conf['sitename']);
	}else{
		if(!$conf['mail_name'] || !$conf['mail_port'] || !$conf['mail_smtp'] || !$conf['mail_pwd'])return false;
		$port = intval($conf['mail_port']);
		$mail = new \lib\mail\PHPMailer\PHPMailer(true);
		try{
			$mail->SMTPDebug = 0;
			$mail->CharSet = 'UTF-8';
			$mail->Timeout = 5;
			$mail->isSMTP();
			$mail->Host = $conf['mail_smtp'];
			$mail->SMTPAuth = true;
			$mail->Username = $conf['mail_name'];
			$mail->Password = $conf['mail_pwd'];
			if($port == 587) $mail->SMTPSecure = 'tls';
			else if($port >= 465) $mail->SMTPSecure = 'ssl';
			else $mail->SMTPAutoTLS = false;
			$mail->Port = intval($conf['mail_port']);
			$mail->setFrom($conf['mail_name'], $conf['sitename']);
			$mail->addAddress($to);
			$mail->addReplyTo($conf['mail_name'], $conf['sitename']);
			$mail->isHTML(true);
			$mail->Subject = $sub;
			$mail->Body = $msg;
			$mail->send();
			return true;
		} catch (Exception $e) {
			return $mail->ErrorInfo;
		}
	}
}
function send_sms($phone, $code, $scope='reg'){
	global $conf;
	if($scope == 'reg'){
		$moban = $conf['sms_tpl_reg'];
	}elseif($scope == 'login'){
		$moban = $conf['sms_tpl_login'];
	}elseif($scope == 'find'){
		$moban = $conf['sms_tpl_find'];
	}elseif($scope == 'edit'){
		$moban = $conf['sms_tpl_edit'];
	}
	if($conf['sms_api']==1){
		$sms = new \lib\sms\Qcloud($conf['sms_appid'], $conf['sms_appkey']);
		$arr = $sms->send($phone, $moban, [$code], $conf['sms_sign']);
		if(isset($arr['result']) && $arr['result']==0){
			return true;
		}else{
			return $arr['errmsg'];
		}
	}elseif($conf['sms_api']==2){
		$sms = new \lib\sms\Aliyun($conf['sms_appid'], $conf['sms_appkey']);
		$arr = $sms->send($phone, $code, $moban, $conf['sms_sign'], $conf['sitename']);
		if(isset($arr['Code']) && $arr['Code']=='OK'){
			return true;
		}else{
			return $arr['Message'];
		}
	}elseif($conf['sms_api']==3){
		$app=$conf['sitename'];
		$url = 'https://api.topthink.com/sms/send';
		$param = ['appCode'=>$conf['sms_appkey'], 'signId'=>$conf['sms_sign'], 'templateId'=>$moban, 'phone'=>$phone, 'params'=>json_encode(['code'=>$code])];
		$data=get_curl($url, http_build_query($param));
		$arr=json_decode($data,true);
		if(isset($arr['code']) && $arr['code']==0){
			return true;
		}else{
			return $arr['message'];
		}
	}elseif($conf['sms_api']==4){
		$sms = new \lib\sms\SmsBao($conf['sms_appid'], $conf['sms_appkey']);
		return $sms->send($phone, $code, $moban, $conf['sms_sign']);
	}else{
		$app=$conf['sitename'];
		$url = 'http://sms.php.gs/sms/send/yzm';
		$param = ['appkey'=>$conf['sms_appkey'], 'phone'=>$phone, 'moban'=>$moban, 'code'=>$code, 'app'=>$app];
		$data=get_curl($url, http_build_query($param));
		$arr=json_decode($data,true);
		if($arr['status']=='200'){
			return true;
		}else{
			return $arr['error_msg_zh'];
		}
	}
}
function daddslashes($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = daddslashes($val);
		}
	} else {
		$string = addslashes($string);
	}
	return $string;
}

function strexists($string, $find) {
	return !(strpos($string, $find) === FALSE);
}

function dstrpos($string, $arr) {
	if(empty($string)) return false;
	foreach((array)$arr as $v) {
		if(strpos($string, $v) !== false) {
			return true;
		}
	}
	return false;
}

function checkmobile() {
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	$ualist = array('android', 'midp', 'nokia', 'mobile', 'iphone', 'ipod', 'blackberry', 'windows phone');
	if((dstrpos($useragent, $ualist) || strexists($_SERVER['HTTP_ACCEPT'], "VND.WAP") || strexists($_SERVER['HTTP_VIA'],"wap")))
		return true;
	else
		return false;
}
function checkwechat(){
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger/') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'WindowsWechat') === false)
		return true;
	else
		return false;
}
function checkalipay(){
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient/') !== false)
		return true;
	else
		return false;
}
function checkmobbileqq(){
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/') !== false)
		return true;
	else
		return false;
}
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	if($operation == 'DECODE') {
		if(((int)substr($result, 0, 10) == 0 || (int)substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

function random($length, $numeric = 0) {
	$length = intval($length);
	if($length <= 0) return '';
	$chars = $numeric ? '0123456789' : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$charsLen = strlen($chars);
	$hash = '';
	while(strlen($hash) < $length){
		$bytes = getSecureRandomBytes($length);
		for($i = 0; $i < strlen($bytes) && strlen($hash) < $length; $i++){
			$hash .= $chars[ord($bytes[$i]) % $charsLen];
		}
	}
	return $hash;
}
function showmsg($content = '未知的异常',$type = 4,$back = false)
{
switch($type)
{
case 1:
	$panel="success";
break;
case 2:
	$panel="info";
break;
case 3:
	$panel="warning";
break;
case 4:
	$panel="danger";
break;
}

echo '<div class="panel panel-'.$panel.'">
      <div class="panel-heading">
        <h3 class="panel-title">提示信息</h3>
        </div>
        <div class="panel-body">';
echo $content;

if ($back) {
	echo '<hr/><a href="'.$back.'"><< 返回上一页</a>';
}
else
    echo '<hr/><a href="javascript:history.back(-1)"><< 返回上一页</a>';

echo '</div>
    </div>';
	exit;
}
function sysmsg($msg = '未知的异常',$title = '站点提示信息') {
    ?>  
    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title?></title>
        <style type="text/css">
html{background:#eee}body{background:#fff;color:#333;font-family:"微软雅黑","Microsoft YaHei",sans-serif;margin:2em auto;padding:1em 2em;max-width:700px;-webkit-box-shadow:10px 10px 10px rgba(0,0,0,.13);box-shadow:10px 10px 10px rgba(0,0,0,.13);opacity:.8}h1{border-bottom:1px solid #dadada;clear:both;color:#666;font:24px "微软雅黑","Microsoft YaHei",sans-serif;margin:30px 0 0 0;padding:0;padding-bottom:7px}#error-page{margin-top:50px}h3{text-align:center}#error-page p{font-size:9px;line-height:1.5;margin:25px 0 20px}#error-page code{font-family:Consolas,Monaco,monospace}ul li{margin-bottom:10px;font-size:9px}a{color:#21759B;text-decoration:none;margin-top:-10px}a:hover{color:#D54E21}.button{background:#f7f7f7;border:1px solid #ccc;color:#555;display:inline-block;text-decoration:none;font-size:9px;line-height:26px;height:28px;margin:0;padding:0 10px 1px;cursor:pointer;-webkit-border-radius:3px;-webkit-appearance:none;border-radius:3px;white-space:nowrap;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;-webkit-box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);vertical-align:top}.button.button-large{height:29px;line-height:28px;padding:0 12px}.button:focus,.button:hover{background:#fafafa;border-color:#999;color:#222}.button:focus{-webkit-box-shadow:1px 1px 1px rgba(0,0,0,.2);box-shadow:1px 1px 1px rgba(0,0,0,.2)}.button:active{background:#eee;border-color:#999;color:#333;-webkit-box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5);box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5)}table{table-layout:auto;border:1px solid #333;empty-cells:show;border-collapse:collapse}th{padding:4px;border:1px solid #333;overflow:hidden;color:#333;background:#eee}td{padding:4px;border:1px solid #333;overflow:hidden;color:#333}
        </style>
    </head>
    <body id="error-page">
        <?php echo '<h3>'.$title.'</h3>';
        echo $msg; ?>
    </body>
    </html>
    <?php
    exit;
}
function returnTemplate($return_url) {
	$url = base64_encode($return_url);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>支付成功跳转页面</title>
        <style type="text/css">
body{margin:0;padding:0}
p{position:absolute;left:50%;top:50%;height:35px;margin:-35px 0 0 -160px;padding:20px;font:bold 16px/30px "宋体",Arial;background:#f9fafc url(/assets/img/loading.gif) no-repeat 20px 20px;text-indent:40px;border:1px solid #c5d0dc}
#waiting{font-family:Arial}
        </style>
    </head>
    <body id="return-page">
        <p>支付成功，正在跳转请稍候...</p>
    </body>
	<script>window.location.href=window.atob("<?php echo $url?>");</script>
    </html>
    <?php
    exit;
}
function submitTemplate($html_text){
	?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>正在为您跳转到支付页面，请稍候...</title>
	<style type="text/css">
body{margin:0;padding:0}
#waiting{position:absolute;left:50%;top:50%;height:35px;margin:-35px 0 0 -160px;padding:20px;font:16px/30px "Helvetica Neue",Helvetica,Arial,sans-serif;background:#f9fafc url(/assets/img/loading.gif) no-repeat 20px 20px;text-indent:40px;border:1px solid #c5d0dc}
	</style>
</head>
<body>
<p id="waiting">正在为您跳转到支付页面，请稍候...</p>
<?php echo $html_text?>
</body>
</html>
	<?php
	exit;
}
function getSecureRandomBytes($length = 16) {
	$length = intval($length);
	if($length <= 0) $length = 16;
	try{
		return random_bytes($length);
	}catch(Exception $e){
		if(function_exists('openssl_random_pseudo_bytes')){
			$strong = false;
			$bytes = openssl_random_pseudo_bytes($length, $strong);
			if($bytes !== false && strlen($bytes) === $length){
				return $bytes;
			}
		}
		$bytes = '';
		for($i = 0; $i < $length; $i++){
			$bytes .= chr(mt_rand(0, 255));
		}
		return $bytes;
	}
}

function generateSecureToken($bytes = 16) {
	return bin2hex(getSecureRandomBytes($bytes));
}

function getSid() {
	return generateSecureToken(16);
}
function getMd5Pwd($pwd, $salt=null) {
    return md5(md5($pwd) . md5('1277180438'.$salt));
}

function isPasswordHashValue($hash) {
	return is_string($hash) && (strpos($hash, '$2y$') === 0 || strpos($hash, '$argon2') === 0);
}

function hashUserPassword($pwd) {
	return password_hash($pwd, PASSWORD_BCRYPT);
}

function verifyUserPassword($pwd, $uid, $storedPwd) {
	if($storedPwd === null || $storedPwd === '') return false;
	if(isPasswordHashValue($storedPwd)) return password_verify($pwd, $storedPwd);
	return hash_equals((string)$storedPwd, getMd5Pwd($pwd, $uid));
}

function userPasswordNeedsRehash($storedPwd) {
	if(!isPasswordHashValue($storedPwd)) return true;
	return password_needs_rehash($storedPwd, PASSWORD_BCRYPT);
}

function hashAdminPassword($pwd) {
	return password_hash($pwd, PASSWORD_BCRYPT);
}

function verifyAdminPassword($pwd, $storedPwd) {
	if($storedPwd === null || $storedPwd === '') return false;
	if(isPasswordHashValue($storedPwd)) return password_verify($pwd, $storedPwd);
	return hash_equals((string)$storedPwd, (string)$pwd);
}

function getAdminSessionSignature($adminUser, $adminPwdHash) {
	global $password_hash;
	return md5($adminUser.$adminPwdHash.$password_hash);
}

function getUserSessionSignature($uid, $userKey) {
	global $password_hash;
	return md5($uid.$userKey.$password_hash);
}

function setSecureCookie($name, $value, $expire, $path='/', $httpOnly=true) {
	$secure = function_exists('is_https') ? is_https() : false;
	if (PHP_VERSION_ID >= 70300) {
		return setcookie($name, $value, [
			'expires' => $expire,
			'path' => $path,
			'domain' => '',
			'secure' => $secure,
			'httponly' => $httpOnly,
			'samesite' => 'Lax'
		]);
	}
	return setcookie($name, $value, $expire, $path.'; samesite=Lax', '', $secure, $httpOnly);
}

function checkRefererHost(){
	$referer = $_SERVER['HTTP_REFERER'] ?? ($_SERVER['HTTP_ORIGIN'] ?? '');
	if(empty($referer)) return false;
	$refererHost = parse_url($referer, PHP_URL_HOST);
	$currentHost = parse_url((is_https() ? 'https://' : 'http://').($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
	if(empty($refererHost) || empty($currentHost)) return false;
	return strtolower($refererHost) === strtolower($currentHost);
}

function getCsrfToken() {
	if(session_status() !== PHP_SESSION_ACTIVE) return null;
	return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : null;
}

function checkCsrfToken($token=null) {
	if(session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['csrf_token'])) return false;
	if($token === null){
		if(isset($_POST['csrf_token'])){
			$token = $_POST['csrf_token'];
		}elseif(isset($_SERVER['HTTP_X_CSRF_TOKEN'])){
			$token = $_SERVER['HTTP_X_CSRF_TOKEN'];
		}else{
			return false;
		}
	}
	return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function makeSidSignature($sid) {
	return hash_hmac('sha256', $sid, SYS_KEY);
}

function verifySidSignature($sid, $sign) {
	return is_string($sign) && hash_equals(makeSidSignature($sid), $sign);
}

function getSignedSidFromRequest() {
	if(!isset($_GET['sid'])) return null;
	$sid = trim($_GET['sid']);
	$sidSign = isset($_GET['sid_sign']) ? trim($_GET['sid_sign']) : '';
	if(!preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $sid)) return null;
	if(!verifySidSignature($sid, $sidSign)) return null;
	return $sid;
}

function buildSignedSidQuery($sid) {
	return 'sid='.rawurlencode($sid).'&sid_sign='.makeSidSignature($sid);
}

function sanitizeRedirectPath($path) {
	if($path === null) return '';
	$path = trim($path);
	if($path === '') return '';
	if(strpos($path, "\r") !== false || strpos($path, "\n") !== false) return '';
	if(preg_match('#^(https?:)?//#i', $path)) return '';
	$path = ltrim($path, '/');
	if(!preg_match('/^[a-zA-Z0-9_\-\.\/\?=&%]*$/', $path)) return '';
	return $path;
}

/**
 * 取中间文本
 * @param string $str
 * @param string $leftStr
 * @param string $rightStr
 */
function getSubstr($str, $leftStr, $rightStr)
{
	$left = strpos($str, $leftStr);
	$start = $left+strlen($leftStr);
	$right = strpos($str, $rightStr, $start);
	if($left < 0) return '';
	if($right>0){
		return substr($str, $start, $right-$start);
	}else{
		return substr($str, $start);
	}
}
function isNullOrEmpty($str){
	return $str === null || $str === '';
}

function getSetting($k, $force = false){
	global $DB,$CACHE;
	if($force) return $DB->getColumn("SELECT v FROM pre_config WHERE k=:k LIMIT 1", [':k'=>$k]);
	$cache = $CACHE->get($k);
	return $cache[$k];
}
function saveSetting($k, $v){
	global $DB;
	return $DB->exec("REPLACE INTO pre_config SET v=:v,k=:k", [':v'=>$v, ':k'=>$k]);
}
function checkGroupSettings($str){
	foreach(explode(',',$str) as $row){
		if(!strpos($row,':'))return false;
	}
	return true;
}
function isEmpty($value)
{
	return $value === null || trim($value) === '';
}

function creat_callback($data){
	global $DB,$conf;
	$type=$DB->getColumn("SELECT name FROM pre_type WHERE id=:id LIMIT 1", [':id'=>$data['type']]);
	if($data['version'] == 1){
		$array=array('pid'=>$data['uid'],'trade_no'=>$data['trade_no'],'out_trade_no'=>$data['out_trade_no'],'type'=>$type,'name'=>$data['name'],'money'=>(float)$data['money'],'trade_status'=>'TRADE_SUCCESS');
		if(!empty($data['api_trade_no']))$array['api_trade_no']=$data['api_trade_no'];
		if(!empty($data['buyer']))$array['buyer']=$data['buyer'];
		if(!empty($data['param']))$array['param']=$data['param'];
		if($conf['notifyordername']==1)$array['name']='product';
		$array['timestamp'] = time().'';
		$array['sign_type'] = 'RSA';
		$array['sign'] = \lib\Payment::makeSign($array, '');
	}else{
		$key=$DB->getColumn("SELECT `key` FROM pre_user WHERE uid=:uid LIMIT 1", [':uid'=>$data['uid']]);
		$array=array('pid'=>$data['uid'],'trade_no'=>$data['trade_no'],'out_trade_no'=>$data['out_trade_no'],'type'=>$type,'name'=>$data['name'],'money'=>(float)$data['money'],'trade_status'=>'TRADE_SUCCESS');
		if(!empty($data['param']))$array['param']=$data['param'];
		if($conf['notifyordername']==1)$array['name']='product';
		$array['sign'] = \lib\Payment::makeSign($array, $key);
		$array['sign_type'] = 'MD5';
	}
	$query_str = http_build_query($array);
	if(strpos($data['notify_url'],'?'))
		$url['notify']=$data['notify_url'].'&'.$query_str;
	else
		$url['notify']=$data['notify_url'].'?'.$query_str;
	if(strpos($data['return_url'],'?'))
		$url['return']=$data['return_url'].'&'.$query_str;
	else
		$url['return']=$data['return_url'].'?'.$query_str;
	if($data['tid']>0){
		$url['return']=$data['return_url'];
	}
	return $url;
}

function getdomain($url){
	$arr=parse_url($url);
	$host = $arr['host'];
	if(isset($arr['port']) && $arr['port']!=80 && $arr['port']!=443)$host .= ':'.$arr['port'];
	return $host;
}
function get_host($url){
	$arr=parse_url($url);
	return $arr['host'];
}

function get_main_host($url){
	$arr=parse_url($url);
	$host = $arr['host'];
	if(filter_var($host, FILTER_VALIDATE_IP))return $host;
	if(substr_count($host, '.')>1){
		$host = substr($host, strpos($host, '.')+1);
	}
	return $host;
}

function isPublicIpAddress($ip){
	if(!is_string($ip) || $ip==='') return false;
	return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function validateNotifyUrl($url, &$error = null){
	$error = null;
	$url = trim((string)$url);
	if($url === ''){
		$error = '通知地址(notify_url)不能为空';
		return false;
	}

	$arr = parse_url($url);
	if($arr === false || empty($arr['scheme']) || empty($arr['host'])){
		$error = '通知地址(notify_url)格式不正确';
		return false;
	}

	$scheme = strtolower($arr['scheme']);
	if($scheme !== 'http' && $scheme !== 'https'){
		$error = '通知地址(notify_url)仅支持http或https协议';
		return false;
	}

	$host = strtolower(trim($arr['host']));
	if($host === 'localhost'){
		$error = '通知地址(notify_url)不允许使用localhost';
		return false;
	}

	$ipList = [];
	if(filter_var($host, FILTER_VALIDATE_IP)){
		$ipList[] = $host;
	}else{
		$dnsType = 0;
		if(defined('DNS_A')) $dnsType |= DNS_A;
		if(defined('DNS_AAAA')) $dnsType |= DNS_AAAA;
		if($dnsType > 0 && function_exists('dns_get_record')){
			$records = @dns_get_record($host, $dnsType);
			if(is_array($records)){
				foreach($records as $record){
					if(isset($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP)){
						$ipList[] = $record['ip'];
					}elseif(isset($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP)){
						$ipList[] = $record['ipv6'];
					}
				}
			}
		}
		if(empty($ipList)){
			$ipv4List = @gethostbynamel($host);
			if(is_array($ipv4List)){
				$ipList = array_merge($ipList, $ipv4List);
			}
		}
	}

	$ipList = array_values(array_unique($ipList));
	if(empty($ipList)){
		$error = '通知地址(notify_url)域名解析失败';
		return false;
	}

	foreach($ipList as $ip){
		if(!isPublicIpAddress($ip)){
			$error = '通知地址(notify_url)不允许使用内网或保留IP';
			return false;
		}
	}

	return true;
}

function do_notify($url){
	$url = trim((string)$url);
	if($url === ""){ 
		return false;
	}
	$notifyError = null;
	if(!validateNotifyUrl($url, $notifyError)){
		return false;
	}
	$return = curl_get($url);
	if(strpos($return, "success")!==false || strpos($return, "SUCCESS")!==false || strpos($return, "Success")!==false){
		return true;
	}else{
		return false;
	}
}

function randFloat($min=0, $max=1){
	return $min + mt_rand()/mt_getrandmax() * ($max-$min);
}

function check_cert($idcard, $name, $phone){
	global $conf;
	$appcode = $conf['cert_appcode'];
	$url = 'http://phone3.market.alicloudapi.com/phonethree';
	$post = ['idcard'=>$idcard, 'phone'=>$phone, 'realname'=>$name];
	$data = get_curl($url.'?'.http_build_query($post), 0,0,0,0,0,0, ['Authorization: APPCODE '.$appcode, 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
	$arr=json_decode($data,true);
	if(array_key_exists('code',$arr) && $arr['code']==200){
		return ['code'=>0, 'msg'=>$arr['msg']];
	}elseif(array_key_exists('msg',$arr)){
		return ['code'=>-1, 'msg'=>$arr['msg']];
	}else{
		return ['code'=>-2, 'msg'=>'返回结果解析失败'];
	}
}
function check_corp_cert($companyName, $creditNo, $legalPerson){
	global $conf;
	$appcode = $conf['cert_appcode2'];
	$url = 'http://companythree.shumaidata.com/companythree/check';
	$post = ['companyName'=>$companyName, 'creditNo'=>$creditNo, 'legalPerson'=>$legalPerson];
	$data = get_curl($url.'?'.http_build_query($post), 0, 0,0,0,0,0, ['Authorization: APPCODE '.$appcode, 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);
	$arr=json_decode($data,true);
	if(array_key_exists('code',$arr) && $arr['code']==200){
		if($arr['data']['result']==0){
			return ['code'=>0, 'msg'=>$arr['data']['desc']];
		}else{
			return ['code'=>-1, 'msg'=>$arr['data']['desc']=='不一致'?'公司与法人信息不一致':$arr['data']['desc']];
		}
	}elseif(array_key_exists('msg',$arr)){
		return ['code'=>-1, 'msg'=>$arr['msg']];
	}else{
		return ['code'=>-2, 'msg'=>'返回结果解析失败'];
	}
}
function show_cert_type($certtype){
	if($certtype == 1){
		return '企业实名认证';
	}else{
		return '个人实名认证';
	}
}
function show_cert_method($certmethod){
	if($certmethod == 1){
		return '微信快捷认证';
	}elseif($certmethod == 2){
		return '手机号三要素认证';
	}elseif($certmethod == 3){
		return '人工审核认证';
	}else{
		return '支付宝快捷认证';
	}
}

function randomFloat($min = 0, $max = 1) {
	$num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
	return sprintf("%.2f",$num);
}

function wx_get_access_token($appid, $secret) {
	global $DB;
	$row = $DB->getRow("SELECT id FROM pre_weixin WHERE appid=:appid LIMIT 1", [':appid'=>$appid]);
	if($row) return $row['id'];
	return false;
}

function wxminipay_jump_scheme($wid, $orderid){
	global $conf, $order, $siteurl;
	if($conf['wxminipay_path']) {
		$path = $conf['wxminipay_path'];
		$query = 'orderid='.$orderid.'&sign='.md5(SYS_KEY.$orderid.SYS_KEY);
	}else{
		$jump_url = $siteurl.'pay/wxminipay/'.$orderid.'/';
		$path = 'pages/pay/pay';
		$query = 'money='.$order['realmoney'].'&url='.$jump_url;
	}
	$wechat = new \lib\wechat\WechatAPI($wid);
	return $wechat->generate_scheme($path, $query);
}

function checkDomain($domain){
	if(empty($domain) || !preg_match('/^[-$a-z0-9_*.]{2,512}$/i', $domain) || (stripos($domain, '.') === false) || substr($domain, -1) == '.' || substr($domain, 0 ,1) == '.' || substr($domain, 0 ,1) == '*' && substr($domain, 1 ,1) != '.' || substr_count($domain, '*')>1 || strpos($domain, '*')>0 || strlen($domain)<4) return false;
	return true;
}

//微信合单支付，返回所有子单金额
function combinepay_submoneys($money){
	global $conf;
	if(!$conf['wxcombine_open'] || !$conf['wxcombine_minmoney']) return false;
	if($money >= intval($conf['wxcombine_minmoney']*100)){
		$subnum = 3;
		$submoney = intval($money/$subnum);
		while($submoney > intval($conf['wxcombine_submoney']*100)){
			$subnum++;
			$submoney = intval($money/$subnum);
			if($subnum==50)break;
		}
		$submoneys = [];
		for($i=0;$i<$subnum;$i++){
			$submoneys[] = $submoney;
		}
		$mod = $money%$subnum;
		if($mod > 0){
			for($i=0;$i<$mod;$i++){
				$submoneys[$i] += 1;
			}
		}
		return $submoneys;
	}
	return false;
}

function get_invite_code($uid){
	$str = (string)$uid;
	$tmp = '';
	for($i=0;$i<strlen($str);$i++){
		$tmp.=substr($str,$i,1) ^ substr(SYS_KEY,$i,1);
	}
	return str_replace('=','',base64_encode($tmp));
}

function get_invite_uid($code){
	$str = base64_decode($code);
	$tmp = '';
	for($i=0;$i<strlen($str);$i++){
		$tmp.=substr($str,$i,1) ^ substr(SYS_KEY,$i,1);
	}
	return $tmp;
}

function currency_convert($from, $to, $amount){
	$param = [
		'from' => $from,
		'to' => $to,
		'amount' => $amount
	];
	$url = 'https://api.exchangerate.host/convert?'.http_build_query($param);
	$data = get_curl($url);
	$arr = json_decode($data, true);
	if($arr['success']===true){
		return $arr['result'];
	}else{
		throw new Exception('汇率转换失败');
	}
}

function checkPayVerifyOpen($pid){
	global $DB, $conf, $clientip;
	if($conf['pay_verify'] == 3) return true;
	elseif($conf['pay_verify'] == 2){
		$uid_arr = explode('|', $conf['pay_verify_check_uid']);
		if(in_array($pid, $uid_arr)) return true;
	}
		elseif($conf['pay_verify'] == 1){
			$second = intval($conf['pay_verify_check_second']);
			$count = intval($conf['pay_verify_check_count']);
			$sucrate = floatval($conf['pay_verify_check_rate']);
			if($second>0 || $count>0 || $sucrate>0){
				$starttime = date('Y-m-d H:i:s', time()-$second);
				$total_num=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid=:uid AND addtime>=:starttime", [':uid'=>$pid, ':starttime'=>$starttime]);
				$succ_num=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid=:uid AND addtime>=:starttime AND status>0", [':uid'=>$pid, ':starttime'=>$starttime]);
				if($total_num >= $count){
					$succ_rate = round($succ_num * 100 / $total_num, 2);
					if($succ_rate < $sucrate){
						return true;
					}
				}
			}
			$ipcheck = intval($conf['pay_verify_check_ip']);
			if($ipcheck>0){
				$orders = $DB->getAll("SELECT status FROM pre_order WHERE `ip`=:ip AND addtime>=DATE_SUB(NOW(), INTERVAL 3600 SECOND) ORDER BY addtime DESC LIMIT {$ipcheck}", [':ip'=>$clientip]);
				$fail_num = 0;
				foreach($orders as $row){
				if($row['status'] == 0) $fail_num++;
			}
			if($fail_num>=$ipcheck){
				return true;
			}
		}
	}
	return false;
}

function showPayVerifyPage($defend_key, $query_arr){
	global $conf, $cdnpublic;
	if($conf['pay_verify_type'] == 0){
		$key = time().$defend_key.rand(111111,999999);
		include PAYPAGE_ROOT.'verify_jump.php';
	}elseif($conf['pay_verify_type'] == 1){
		include PAYPAGE_ROOT.'verify_invisible.php';
	}elseif($conf['pay_verify_type'] == 2){
		include PAYPAGE_ROOT.'verify_slide.php';
	}
	exit;
}

function getDefendKey($pid, $trade_no){
	return md5(SYS_KEY.$pid.'_'.$trade_no.SYS_KEY);
}

//极验3.0服务端验证
function verify_captcha($user_id = 'public'){
	global $conf, $clientip;
	$GtSdk = new \lib\GeetestLib($conf['captcha_id'], $conf['captcha_key']);
	$data = array(
		'user_id' => $user_id,
		'client_type' => "web",
		'ip_address' => $clientip
	);
	if ($_SESSION['gtserver'] == 1) {   //服务器正常
		return $GtSdk->success_validate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'], $data);
	}else{  //服务器宕机,走failback模式
		return $GtSdk->fail_validate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode']);
	}
}

//极验4.0服务端验证
function verify_captcha4(){
	global $conf;
    if(!isset($_POST['captcha_id']) || !isset($_POST['lot_number']) || !isset($_POST['pass_token']) || !isset($_POST['gen_time']) || !isset($_POST['captcha_output'])) return false;
    $real_ip = real_ip();
    $url = !empty($conf['captcha4_verify_url']) ? trim($conf['captcha4_verify_url']) : 'https://gt4.geetest.com/demov4/demo/login';
    if(stripos($url, 'http://') === 0){
    	$url = 'https://'.substr($url, 7);
    }
    $param = ['captcha_id'=>$_POST['captcha_id'], 'lot_number'=>$_POST['lot_number'], 'pass_token'=>$_POST['pass_token'], 'gen_time'=>$_POST['gen_time'], 'captcha_output'=>$_POST['captcha_output']];
    $referer = 'https://gt4.geetest.com/demov4/invisible-bind-zh.html';
    $httpheader[] = "X-Real-IP: ".$real_ip;
	$httpheader[] = "X-Forwarded-For: ".$real_ip;
    $data = get_curl($url.'?'.http_build_query($param),0,$referer,0,0,0,0,$httpheader);
    $arr = json_decode($data, true);
    if(isset($arr['result']) && $arr['result'] == 'success'){
        return true;
    }
    return false;
}

function getGroupConfig($gid){
	global $DB;
	$input_key = ['settle_rate', 'transfer_rate', 'invite_rate'];
	$grouprow=$DB->getRow("SELECT config FROM pre_group WHERE gid=:gid LIMIT 1", [':gid'=>$gid]);
	if(!$grouprow)$grouprow=$DB->getRow("SELECT config FROM pre_group WHERE gid=0 LIMIT 1");
	$config = [];
	if(!$grouprow) return $config;
	if($grouprow['config']){
		$arr = json_decode($grouprow['config'], true);
		foreach($arr as $key=>$value){
			if(in_array($key, $input_key) && !isNullOrEmpty($value) || !in_array($key, $input_key) && $value>0){
				if($key == 'settle_type') $value = $value-1;
				$config[$key] = $value;
			}
		}
	}
	return $config;
}

function get_alipay_userid(){
	global $conf;
	if($conf['login_alipay']==0) throw new Exception('未开启支付宝快捷登录');
	$channel = \lib\Channel::get($conf['login_alipay']);
	if(!$channel) throw new Exception('当前支付通道信息不存在');
	$alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
	try{
		$oauth = new \Alipay\AlipayOauthService($alipay_config);
		if(isset($_GET['auth_code'])){
			$result = $oauth->getToken($_GET['auth_code']);
			if(!empty($result['user_id'])){
				$user_id = $result['user_id'];
				$user_type = 'userid';
			}else{
				$user_id = $result['open_id'];
				$user_type = 'openid';
			}
			return [$user_type, $user_id];
		}else{
			$redirect_uri = (is_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$oauth->oauth($redirect_uri);
		}
	}catch(Exception $e){
		throw new Exception('支付宝快捷登录失败！'.$e->getMessage());
	}
}

function getBankCardInfo($cardno){
	$url = 'http://api.cccyun.cc/bankcard.php?cardno='.$cardno;
	$data = get_curl($url);
	$arr = json_decode($data, true);
	if(isset($arr['code']) && $arr['code']==0){
		return $arr['data'];
	}else{
		throw new Exception($arr['msg']?$arr['msg']:'银行卡信息查询失败');
	}
}

function convert_channel_data(){
	global $DB;
	$data_list = $DB->getAll("SELECT * FROM pre_channel WHERE config IS NULL");
	foreach($data_list as $row){
		$config = [];
		if($row['appid']) $config['appid'] = $row['appid'];
		if($row['appkey']) $config['appkey'] = $row['appkey'];
		if($row['appsecret']) $config['appsecret'] = $row['appsecret'];
		if($row['appurl']) $config['appurl'] = $row['appurl'];
		if($row['appmchid']) $config['appmchid'] = $row['appmchid'];
		if($row['appswitch']) $config['appswitch'] = $row['appswitch'];
		$configstr = json_encode($config);
		$DB->update('channel', ['config'=>$configstr], ['id'=>$row['id']]);
	}
	if(file_exists(TEMPLATE_ROOT.'index1/doc.php')){
		unlink(TEMPLATE_ROOT.'index1/doc.php');
	}
}

function generate_key_pair(){
	$config = [
		"private_key_bits" => 2048,
	];
	$res = openssl_pkey_new($config);
	$privateKey = '';
	openssl_pkey_export($res, $privateKey, null, $config);
	$pubKey = openssl_pkey_get_details($res);
	openssl_pkey_free($res);
	return ['public_key'=>pemToBase64($pubKey["key"]), 'private_key'=>pemToBase64($privateKey)];
}

function pemToBase64($data){
	$line = explode("\n", $data);
	$base64 = '';
	foreach($line as $row){
		if(strpos($row, '-----BEGIN')!==false || strpos($row, '-----END')!==false) continue;
		$base64 .= trim($row);
	}
	return $base64;
}

function base64ToPem($data, $type){
	if(empty($data) || strpos($data, '-----BEGIN')!==false) return $data;
	$pem = "-----BEGIN ".$type."-----\n" .
        wordwrap($data, 64, "\n", true) .
        "\n-----END ".$type."-----";
    return $pem;
}

function echojson($array){
	@header('Content-Type: application/json; charset=UTF-8');
	exit(json_encode($array, JSON_UNESCAPED_UNICODE));
}

function echojsonmsg($msg, $code = -1){
	echojson(['code'=>$code, 'msg'=>$msg]);
}

function checkIfActive($pages){
	$scriptName = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
	$current = pathinfo($scriptName, PATHINFO_FILENAME);
	if($current === '') $current = 'index';
	$list = array_filter(array_map('trim', explode(',', (string)$pages)), function($item){
		return $item !== '';
	});
	return in_array($current, $list, true) ? 'active' : '';
}


function changeUserMoney($uid, $money, $add=true, $type=null, $trade_no=null){
	global $DB;
	$uid = intval($uid);
	$money = round(floatval($money), 2);
	if($uid <= 0 || $money <= 0) return false;
	if(!empty($trade_no) && !empty($type)){
		$record = $DB->getRow("SELECT newmoney FROM pre_record WHERE uid=:uid AND trade_no=:trade_no AND type=:type LIMIT 1", [':uid'=>$uid, ':trade_no'=>$trade_no, ':type'=>$type]);
		if($record) return round(floatval($record['newmoney']), 2);
	}
	$userrow = $DB->find('user', 'money', ['uid'=>$uid]);
	if(!$userrow) return false;
	$oldmoney = round(floatval($userrow['money']), 2);
	$newmoney = $add ? round($oldmoney + $money, 2) : round($oldmoney - $money, 2);
	if($DB->update('user', ['money'=>$newmoney], ['uid'=>$uid]) !== false){
		$DB->insert('record', ['uid'=>$uid, 'action'=>$add?1:2, 'money'=>$money, 'oldmoney'=>$oldmoney, 'newmoney'=>$newmoney, 'type'=>$type, 'trade_no'=>$trade_no, 'date'=>'NOW()']);
		return $newmoney;
	}
	return false;
}

function changeUserGroup($uid, $gid, $endtime=null){
	global $DB;
	$uid = intval($uid);
	$gid = intval($gid);
	if($uid <= 0 || $gid < 0) return false;
	return $DB->update('user', ['gid'=>$gid, 'endtime'=>$endtime === null ? null : $endtime], ['uid'=>$uid]);
}

function processNotify($order, $api_trade_no, $buyer=null){
	return \lib\Payment::processOrder(true, $order, $api_trade_no, $buyer);
}

function runOrderPostActions($actions){
	global $DB;
	foreach((array)$actions as $action){
		if(empty($action['type'])) continue;
		if($action['type'] === 'mail'){
			send_mail($action['to'], $action['subject'], $action['content']);
		}elseif($action['type'] === 'notice'){
			\lib\MsgNotice::send($action['scene'], $action['uid'], $action['param']);
		}elseif($action['type'] === 'notify'){
			if(do_notify($action['url'])){
				$DB->update('order', ['notify'=>0, 'notifytime'=>null], ['trade_no'=>$action['trade_no']]);
			}else{
				$DB->update('order', ['notify'=>1, 'notifytime'=>date('Y-m-d H:i:s', time()+60)], ['trade_no'=>$action['trade_no']]);
			}
		}
	}
}

function processOrder($order, $isnotify=false, &$afterCommit=null, $skipTransaction=false){
	global $DB, $CACHE, $conf;
	if(!is_array($order) || empty($order['trade_no'])) return false;
	$manageAfterCommit = !is_array($afterCommit);
	if($manageAfterCommit) $afterCommit = [];

	$runner = function() use (&$order, $isnotify, &$afterCommit, $DB, $CACHE, $conf){
		$trade_no = $order['trade_no'];
		$uid = intval($order['uid']);
		$tid = intval($order['tid']);
		$getmoney = round(floatval(!empty($order['getmoney']) ? $order['getmoney'] : $order['money']), 2);

		if($tid === 1){
			$cacheData = @unserialize($CACHE->read('reg_'.$trade_no), ['allowed_classes'=>false]);
			if(is_array($cacheData)){
				$exists = false;
				if(!empty($cacheData['phone'])){
					$exists = $DB->getRow("SELECT uid FROM pre_user WHERE phone=:phone LIMIT 1", [':phone'=>$cacheData['phone']]);
				}
				if(!$exists && !empty($cacheData['email'])){
					$exists = $DB->getRow("SELECT uid FROM pre_user WHERE email=:email LIMIT 1", [':email'=>$cacheData['email']]);
				}
				if(!$exists){
					$key = random(32);
					$paystatus = $conf['user_review']==1?2:1;
					$DB->exec("INSERT INTO `pre_user` (`upid`, `key`, `money`, `email`, `phone`, `addtime`, `pay`, `settle`, `keylogin`, `apply`, `status`) VALUES (:upid, :key, '0.00', :email, :phone, NOW(), :paystatus, 1, 0, 0, 1)", [':upid'=>intval($cacheData['upid']), ':key'=>$key, ':email'=>$cacheData['email'], ':phone'=>$cacheData['phone'], ':paystatus'=>$paystatus]);
					$newuid = $DB->lastInsertId();
					if($newuid){
						$pwd = hashUserPassword($cacheData['pwd']);
						$DB->exec("UPDATE pre_user SET pwd=:pwd WHERE uid=:uid", [':pwd'=>$pwd, ':uid'=>$newuid]);
						if(!empty($cacheData['email'])){
							$sub = $conf['sitename'].' - 注册成功通知';
							$msg = '<h2>商户注册成功通知</h2>感谢您注册'.$conf['sitename'].'！<br/>您的登录账号：'.$cacheData['email'].'<br/>您的商户ID：'.$newuid.'<br/>您的商户秘钥：'.$key.'<br/>'.$conf['sitename'].'官网：<a href="http://'.$_SERVER['HTTP_HOST'].'/" target="_blank">'.$_SERVER['HTTP_HOST'].'</a><br/>【<a href="'.((is_https() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].'/user/').'" target="_blank">商户管理后台</a>】';
							$afterCommit[] = ['type'=>'mail', 'to'=>$cacheData['email'], 'subject'=>$sub, 'content'=>$msg];
						}
						if(!empty($cacheData['invitecodeid'])){
							$DB->update('invitecode', ['status'=>1, 'uid'=>$newuid, 'usetime'=>'NOW()'], ['id'=>intval($cacheData['invitecodeid'])]);
						}
						if($paystatus == 2){
							$account = !empty($cacheData['email']) ? $cacheData['email'] : $cacheData['phone'];
							$afterCommit[] = ['type'=>'notice', 'scene'=>'regaudit', 'uid'=>0, 'param'=>['uid'=>$newuid, 'account'=>$account]];
						}
					}
				}
				$CACHE->delete('reg_'.$trade_no);
			}
			if($getmoney > 0){
				changeUserMoney($uid, $getmoney, true, '商户申请', $trade_no);
			}
		}elseif($tid === 2){
			if($getmoney > 0){
				changeUserMoney($uid, $getmoney, true, '余额充值', $trade_no);
			}
		}elseif($tid === 4){
			$orderParam = json_decode($order['param'], true);
			if(is_array($orderParam) && !empty($orderParam['gid'])){
				changeUserGroup($uid, intval($orderParam['gid']), $orderParam['endtime'] ?? null);
			}
		}else{
			if($getmoney > 0){
				changeUserMoney($uid, $getmoney, true, $tid === 3 ? '测试收款' : '订单收款', $trade_no);
			}
			if($tid === 0 && !empty($conf['invite_open']) && !empty($conf['invite_rate'])){
				$inviteUid = intval($DB->findColumn('user', 'upid', ['uid'=>$uid]));
				if($inviteUid > 0){
					$maxInviteMoney = round(max(floatval($order['realmoney']) - floatval($order['getmoney']), 0), 2);
					$inviteMoney = round(floatval($order['money']) * floatval($conf['invite_rate']) / 100, 2);
					if($inviteMoney > $maxInviteMoney) $inviteMoney = $maxInviteMoney;
					if($inviteMoney > 0){
						changeUserMoney($inviteUid, $inviteMoney, true, '邀请返现', $trade_no);
						$DB->update('order', ['invite'=>$inviteUid, 'invitemoney'=>$inviteMoney], ['trade_no'=>$trade_no]);
					}
				}
			}
			$typeName = $DB->findColumn('type', 'showname', ['id'=>$order['type']]);
			if(!$typeName){
				$typeName = $DB->findColumn('type', 'name', ['id'=>$order['type']]);
			}
			$afterCommit[] = ['type'=>'notice', 'scene'=>'order', 'uid'=>$uid, 'param'=>['trade_no'=>$trade_no, 'out_trade_no'=>$order['out_trade_no'], 'name'=>$order['name'], 'money'=>$order['money'], 'type'=>$typeName, 'time'=>date('Y-m-d H:i:s')]];
		}

		if($isnotify && ($tid === 0 || $tid === 3) && !empty($order['notify_url'])){
			$url = creat_callback($order);
			$afterCommit[] = ['type'=>'notify', 'trade_no'=>$trade_no, 'url'=>$url['notify']];
		}
		$CACHE->save('orderproc_'.$trade_no, '1');
		return true;
	};

	$result = $skipTransaction ? $runner() : $DB->transaction(function() use (&$runner){
		return $runner();
	});
	if($result !== false && $manageAfterCommit){
		runOrderPostActions($afterCommit);
	}
	return $result !== false;
}

function checkBlockUser($userid, $trade_no=null){
	global $DB;
	$userid = trim((string)$userid);
	if($userid === '') return false;
	$row = $DB->getRow("SELECT * FROM pre_blacklist WHERE type=0 AND content=:content AND (endtime IS NULL OR endtime='' OR endtime>:now) LIMIT 1", [':content'=>$userid, ':now'=>date('Y-m-d H:i:s')]);
	if(!$row) return false;
	return ['type'=>'error', 'msg'=>'当前支付账号存在风险，无法完成支付'];
}

function processReturn($order, $api_trade_no, $buyer=null){
	return \lib\Payment::processOrder(false, $order, $api_trade_no, $buyer);
}

function ordername_replace($template, $name, $uid, $trade_no, $out_trade_no=null){
	global $DB;
	$template = trim((string)$template);
	if($template === '') return $name;
	$uid = intval($uid);
	$qq = $uid > 0 ? $DB->findColumn('user', 'qq', ['uid'=>$uid]) : null;
	$phone = $uid > 0 ? $DB->findColumn('user', 'phone', ['uid'=>$uid]) : null;
	$result = str_replace(
		['[name]', '[order]', '[outorder]', '[time]', '[qq]', '[phone]'],
		[(string)$name, (string)$trade_no, (string)$out_trade_no, (string)time(), (string)$qq, (string)$phone],
		$template
	);
	return $result === '' ? $name : $result;
}

function is_idcard($idcard){
	$idcard = strtoupper(trim((string)$idcard));
	if(preg_match('/^\d{15}$/', $idcard)) return true;
	if(!preg_match('/^\d{17}[0-9X]$/', $idcard)) return false;
	$city = ['11','12','13','14','15','21','22','23','31','32','33','34','35','36','37','41','42','43','44','45','46','50','51','52','53','54','61','62','63','64','65','71','81','82','91'];
	if(!in_array(substr($idcard, 0, 2), $city, true)) return false;
	$birthday = substr($idcard, 6, 8);
	$year = intval(substr($birthday, 0, 4));
	$month = intval(substr($birthday, 4, 2));
	$day = intval(substr($birthday, 6, 2));
	if(!checkdate($month, $day, $year)) return false;
	$factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
	$verify = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
	$sum = 0;
	for($i = 0; $i < 17; $i++){
		$sum += intval($idcard[$i]) * $factor[$i];
	}
	return $verify[$sum % 11] === $idcard[17];
}


function displayPayTypeLabel($type){
	$type = intval($type);
	if($type === 1) return '支付宝';
	if($type === 2) return '微信';
	if($type === 3) return 'QQ钱包';
	if($type === 4) return '银行卡';
	return (string)$type;
}

function convertPayTypeCode($type){
	$type = intval($type);
	if($type === 1) return 'alipay';
	if($type === 2) return 'wxpay';
	if($type === 3) return 'qqpay';
	if($type === 4) return 'bank';
	return '';
}

function displayDomainStatusHtml($status){
	$status = intval($status);
	if($status === 1) return '<font color="green">正常</font>';
	if($status === 2) return '<font color="red">拒绝</font>';
	return '<font color="blue">审核中</font>';
}

function displayWeixinTypeLabel($type){
	return intval($type) === 1 ? '微信小程序' : '微信公众号';
}
