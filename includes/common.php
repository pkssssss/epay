<?php
//error_reporting(0);
error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR);
if(defined('IN_CRONLITE'))return;
define('VERSION', '3075');
define('DB_VERSION', '2038');
define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(SYSTEM_ROOT).'/');
define('PAYPAGE_ROOT', SYSTEM_ROOT.'pages/');
define('TEMPLATE_ROOT', ROOT.'template/');
define('PLUGIN_ROOT', ROOT.'plugins/');
date_default_timezone_set('Asia/Shanghai');
$date = date("Y-m-d H:i:s");

if(!isset($nosession) || !$nosession){
	session_start();
		if(empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) < 32){
			$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
	}
}

if(!function_exists("get_first_forwarded_value")){
	function get_first_forwarded_value($value) {
		if(!is_string($value)) return '';
		$value = trim($value);
		if(strpos($value, ',') !== false){
			$values = explode(',', $value);
			$value = trim($values[0]);
		}
		return $value;
	}
}

if(!function_exists("is_valid_request_port")){
	function is_valid_request_port($port) {
		$port = get_first_forwarded_value((string)$port);
		if($port === '' || !preg_match('/^[0-9]{1,5}$/', $port)) return false;
		$port = intval($port);
		if($port < 1 || $port > 65535) return false;
		return (string)$port;
	}
}

if(!function_exists("is_valid_request_host")){
	function is_valid_request_host($host) {
		if($host === '' || strlen($host) > 253) return false;
		if(preg_match('/[\x00-\x20\x7f\/\\\\@]/', $host)) return false;
		if(substr($host, 0, 1) === '[' && substr($host, -1) === ']'){
			$ip = substr($host, 1, -1);
			return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
		}
		if(filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) return true;
		$host = rtrim($host, '.');
		if($host === '') return false;
		$labels = explode('.', $host);
		foreach($labels as $label){
			if($label === '' || strlen($label) > 63) return false;
			if(!preg_match('/^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', $label)) return false;
		}
		return true;
	}
}

if(!function_exists("parse_request_host")){
	function parse_request_host($value) {
		$value = get_first_forwarded_value($value);
		if($value === '' || preg_match('/[\x00-\x20\x7f\/\\\\@]/', $value)) return false;
		$host = $value;
		$port = false;

		if(substr($value, 0, 1) === '['){
			$pos = strpos($value, ']');
			if($pos === false) return false;
			$host = substr($value, 0, $pos + 1);
			$remain = substr($value, $pos + 1);
			if($remain !== ''){
				if(substr($remain, 0, 1) !== ':') return false;
				$port = is_valid_request_port(substr($remain, 1));
				if($port === false) return false;
			}
		}else{
			$colonCount = substr_count($value, ':');
			if($colonCount === 1){
				list($host, $rawPort) = explode(':', $value, 2);
				$port = is_valid_request_port($rawPort);
				if($port === false) return false;
			}elseif($colonCount > 1){
				if(filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) return false;
				$host = '['.$value.']';
			}
		}

		if(!is_valid_request_host($host)) return false;
		return ['host' => $host, 'port' => $port];
	}
}

if(!function_exists("get_request_scheme")){
	function get_request_scheme() {
		$proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower(get_first_forwarded_value($_SERVER['HTTP_X_FORWARDED_PROTO'])) : '';
		if($proto === 'https' || $proto === 'http') return $proto;
		$proto = isset($_SERVER['HTTP_X_CLIENT_SCHEME']) ? strtolower(get_first_forwarded_value($_SERVER['HTTP_X_CLIENT_SCHEME'])) : '';
		if($proto === 'https' || $proto === 'http') return $proto;
		$proto = isset($_SERVER['HTTP_EWS_CUSTOME_SCHEME']) ? strtolower(get_first_forwarded_value($_SERVER['HTTP_EWS_CUSTOME_SCHEME'])) : '';
		if($proto === 'https' || $proto === 'http') return $proto;
		if(isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) return 'https';
		$proto = isset($_SERVER['REQUEST_SCHEME']) ? strtolower(get_first_forwarded_value($_SERVER['REQUEST_SCHEME'])) : '';
		if($proto === 'https' || $proto === 'http') return $proto;
		if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) return 'https';
		return 'http';
	}
}

if(!function_exists("is_https")){
	function is_https() {
		return get_request_scheme() === 'https';
	}
}

if(!function_exists("get_request_site_host")){
	function get_request_site_host($scheme) {
		$hostInfo = isset($_SERVER['HTTP_HOST']) ? parse_request_host($_SERVER['HTTP_HOST']) : false;
		$forwardedHostInfo = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? parse_request_host($_SERVER['HTTP_X_FORWARDED_HOST']) : false;
		if($hostInfo === false) $hostInfo = $forwardedHostInfo;
		if($hostInfo === false) $hostInfo = ['host' => 'localhost', 'port' => false];

		$port = $hostInfo['port'];
		if($port === false && $forwardedHostInfo !== false && strcasecmp($hostInfo['host'], $forwardedHostInfo['host']) === 0){
			$port = $forwardedHostInfo['port'];
		}
		if($port === false && isset($_SERVER['HTTP_X_FORWARDED_PORT'])){
			$port = is_valid_request_port($_SERVER['HTTP_X_FORWARDED_PORT']);
		}
		if($port === false && isset($_SERVER['SERVER_PORT'])){
			$port = is_valid_request_port($_SERVER['SERVER_PORT']);
		}

		if(($scheme === 'http' && $port === '80') || ($scheme === 'https' && $port === '443')){
			$port = false;
		}
		return $hostInfo['host'].($port !== false ? ':'.$port : '');
	}
}

$siteScheme = get_request_scheme();
$httpHost = get_request_site_host($siteScheme);
$_SERVER['HTTP_HOST'] = $httpHost;
$siteurl = $siteScheme.'://'.$httpHost.'/';

if(is_file(SYSTEM_ROOT.'360safe/360webscan.php')){//360网站卫士
//    require_once(SYSTEM_ROOT.'360safe/360webscan.php');
}

include_once(SYSTEM_ROOT."autoloader.php");
Autoloader::register();

if($is_defend){
	include_once(SYSTEM_ROOT."txprotect.php");
}

require ROOT.'config.php';
define('DBQZ', $dbconfig['dbqz']);

if(!$dbconfig['user']||!$dbconfig['pwd']||!$dbconfig['dbname'])//检测安装1
{
header('Content-type:text/html;charset=utf-8');
echo '你还没安装！<a href="/install/">点此安装</a>';
exit();
}

$DB = new \lib\PdoHelper($dbconfig);

if($DB->query("select * from pre_config where 1")==FALSE)//检测安装2
{
header('Content-type:text/html;charset=utf-8');
echo '你还没安装！<a href="/install/">点此安装</a>';
exit();
}


$CACHE=new \lib\Cache();
$conf=$CACHE->pre_fetch();
define('SYS_KEY', $conf['syskey']);
if(!$conf['localurl'])$conf['localurl'] = $siteurl;
$password_hash='!@#%!s!0';

if ($conf['version'] < DB_VERSION) {
    if (!$install) {
		header('Content-type:text/html;charset=utf-8');
        echo '请先完成网站升级！<a href="/install/update.php"><font color=red>点此升级</font></a>';
        exit;
    }
}

include_once(SYSTEM_ROOT."functions.php");
include_once(SYSTEM_ROOT."member.php");

require_once SYSTEM_ROOT."vendor/autoload.php";

if (!file_exists(ROOT.'install/install.lock') && file_exists(ROOT.'install/index.php')) {
	sysmsg('<h2>检测到无 install.lock 文件</h2><ul><li><font size="4">如果您尚未安装本程序，请<a href="/install/">前往安装</a></font></li><li><font size="4">如果您已经安装本程序，请手动放置一个空的 install.lock 文件到 /install 文件夹下，<b>为了您站点安全，在您完成它之前我们不会工作。</b></font></li></ul><br/><h4>为什么必须建立 install.lock 文件？</h4>它是安装保护文件，如果检测不到它，就会认为站点还没安装，此时任何人都可以安装/重装你的网站。<br/><br/>');exit;
}

// 公共静态资源 CDN：0=私有CDN（默认），1=南方科大CDN
$cdnpublic_custom = 'https://su.pksss.cn/123/cdn/cdnjs/ajax/libs/';
$cdnpublic_sustech = '//mirrors.sustech.edu.cn/cdnjs/ajax/libs/';
if(isset($conf['cdnpublic']) && (string)$conf['cdnpublic'] === '1'){
	$cdnpublic = $cdnpublic_sustech;
}else{
	$cdnpublic = $cdnpublic_custom;
}

if(empty($conf['public_key'])){
	$key_pair = generate_key_pair();
	$conf['public_key'] = $key_pair['public_key'];
	$conf['private_key'] = $key_pair['private_key'];
	saveSetting('public_key', $conf['public_key']);
	saveSetting('private_key', $conf['private_key']);
	$CACHE->clear();
	unset($key_pair);
}
?>
