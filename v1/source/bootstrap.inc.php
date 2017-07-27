<?php
define('IN_IA', true);
define('IA_ROOT', str_replace("\\",'/', dirname(dirname(__FILE__))));
define('MAGIC_QUOTES_GPC', (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) || @ini_get('magic_quotes_sybase'));
define('TIMESTAMP', time());
ob_start();
define('PDO_DEBUG', false);
$_W = $_GPC = array();

$pdo = $_W['pdo'] = null;


$config = array();

$config['db']['host'] = '10.66.169.138';
$config['db']['username'] = 'whole';
$config['db']['password'] = '0ZGziwpWqg9uwfap';
$config['db']['port'] = '3306';
$config['db']['database'] = 'weixin';
$config['db']['charset'] = 'utf8';
$config['db']['pconnect'] = 0;
$config['db']['tablepre'] = 'ims_';

$config['weixin']['app']['appid'] = 'wxbffd8c8c412e4c73';
$config['weixin']['app']['secret'] = '9a35fd41d86a9004dd91b92d0e9d851c';


// -------------------------- CONFIG COOKIE --------------------------- //
$config['cookie']['pre'] = 'api_';
$host=strtolower($_SERVER['HTTP_HOST']);
$host_ary=explode('.',$host);
if($host_ary[0]=='api-test'){
	$config['db']['host'] = '10.66.153.82';
	$config['cookie']['domain'] = '.api-test.shunliandongli.com';
}elseif($host_ary[0]=='api-pro'){
    $config['cookie']['domain'] = '.api-pro.shunliandongli.com';
}else{
	$config['cookie']['domain'] = '.api.shunliandongli.com';
}

$config['cookie']['path'] = '/';

// -------------------------- CONFIG SETTING --------------------------- //
$config['setting']['charset'] = 'utf-8';
$config['setting']['cache'] = 'mysql';
$config['setting']['timezone'] = 'Asia/Shanghai';
$config['setting']['memory_limit'] = '256M';
$config['setting']['filemode'] = 0644;
$config['setting']['authkey'] = 'This\'s ShuanLianDongli.com Powered By Axpwx';
$config['setting']['development'] = 0;
$config['setting']['referrer'] = 0;

$config['setting']['share_status'] = 1;    //分享状态 确认跳转url，1跳转到对应的分享页面，2跳转到下载页面
$config['setting']['share_close_status'] = 1;    //截图分享关闭状态，1开启，2关闭
$config['download_url'] = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.shunlian.app';   //下载链接

// -------------------------- CONFIG UPLOAD --------------------------- //
$config['upload']['image']['extentions'] = array(
	'gif',
	'jpg',
	'jpeg',
	'png'
);
$config['upload']['image']['limit'] = 5000;
$config['upload']['attachurl'] = 'http://img01.shunliandongli.com/attachment/';
$config['upload']['attachdir'] = 'resource/attachment/';

$config['mc']['host']		= '127.0.0.1';
$config['mc']['port']		= '11211';
$config['mc']['pconnect']	= 1;
$config['mc']['timeout']	= 1;
$config['mc']['prefix']		= 'ims_';

$config['mcq']['host']		= '127.0.0.1';
$config['mcq']['port']		= '22201';
$config['mcq']['pconnect']	= 1;
$config['mcq']['timeout']	= 1;
$config['mcq']['prefix']	= 'ims_';

$config['redis']['host']		= '10.104.5.34';
$config['redis']['port']		= '11211';
$config['redis']['pconnect']	= 0;
$config['redis']['timeout']		= 1;
$config['redis']['prefix']		= 'ims_';

$config['db_slave']['host'] = '10.66.128.56';
$config['db_slave']['username'] = 'slave1';
$config['db_slave']['password'] = 'ppuFU9U5tozRp8r8';
$config['db_slave']['port'] = '3306';
$config['db_slave']['database'] = 'weixin';
$config['db_slave']['charset'] = 'utf8';
$config['db_slave']['pconnect'] = 0;
$config['db_slave']['tablepre'] = 'ims_';

require IA_ROOT . '/source/api.php';
require IA_ROOT . '/source/libs/global.func.php';
require IA_ROOT . '/source/libs/pdo.func.php';
require IA_ROOT . '/source/libs/db.class.php';
require IA_ROOT . '/source/libs/sms.class.php';

//自动加载类
require IA_ROOT . '/source/libs/Autoloader.class.php';
Autoloader::init();

define('CLIENT_IP', getip());

//针对memcache的错误提示控制
ini_set('display_errors','1');
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

$_W['mc'] = new Memcache;
if($config['mc']['pconnect']) {
	$_W['mc']->pconnect($config['mc']['host'], $config['mc']['port'], $config['mc']['timeout']);
} else {
	$_W['mc']->connect($config['mc']['host'], $config['mc']['port'], $config['mc']['timeout']);
}

$_W['mcq'] = new Memcache;
if($config['mcq']['pconnect']) {
	$_W['mcq']->pconnect($config['mcq']['host'], $config['mcq']['port'], $config['mcq']['timeout']);
} else {
	$_W['mcq']->connect($config['mcq']['host'], $config['mcq']['port'], $config['mcq']['timeout']);
}

$promotion_time = $_W['mc']->get('promotion_time');

if($promotion_time) {
	$promotion_time = unserialize($promotion_time);
	$promotion_time['start'] = intval($promotion_time['start']);
	$promotion_time['end'] = intval($promotion_time['end']);
	if(TIMESTAMP >= $promotion_time['start'] && TIMESTAMP <= $promotion_time['end']) {
		define('PROMOTION_MODEL', true);
	}
}

if(defined('USE_SLAVEDB')) {
	$config['db_tmp'] = $config['db'];
	$config['db'] = $config['db_slave'];

	$link = mysqli_connect($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database']);

	if ($link) {
		if ($result = mysqli_query($link, "SHOW slave STATUS")) {
			while($slave_ret_row = mysqli_fetch_assoc($result)){
				$slave_ret[] = $slave_ret_row;
			}
			mysqli_free_result($result);
		}
	}
	mysqli_close($link);

	if($slave_ret['Seconds_Behind_Master'] > 30 || $slave_ret['Slave_IO_Running'] == 'No' || $slave_ret['Slave_SQL_Running'] == 'No') {
		$config['db'] = $config['db_tmp'];
	}
	unset($config['db_tmp']);
}

$_W['config'] = $config;
$_W['timestamp'] = TIMESTAMP;
$_W['charset'] = 'utf-8';
$_W['token'] = token();
$_W['clientip'] = CLIENT_IP;
$_W['device_id'] = $_SERVER['HTTP_X_DEVICE_ID'];

$_W['member_id'] = 0;
$_W['mid'] = 0;
$_W['regtime'] = 0;

$_W['share_domain'] = 'http://m.shunliandongli.com/';
$_W['shunlian_domain'] = 'https://wx.shunliandongli.com/';
$_W['qrcode_domain'] = 'http://m.shunliandongli.com/qrcode';

//define('DEVELOPMENT', $_W['config']['setting']['development'] == 1);

if(defined('USE_SLAVEDB')) {
	ini_set('display_errors','1');
	error_reporting(E_ALL ^ E_NOTICE);
} else {
	//error_reporting(0);
	ini_set('display_errors','1');
	error_reporting(E_ALL ^ E_NOTICE);
}

if(function_exists('date_default_timezone_set')){
	date_default_timezone_set('Asia/Shanghai');
}

$_W['isajax'] = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$_W['ispost'] = $_SERVER['REQUEST_METHOD'] == 'POST';

if(MAGIC_QUOTES_GPC) {
	$_GET = istripslashes($_GET);
	$_POST = istripslashes($_POST);
	$_COOKIE = istripslashes($_COOKIE);
}

/**/
$cplen = strlen($_W['config']['cookie']['pre']);
foreach($_COOKIE as $key => $value) {
	unset($_COOKIE[$key]);
	if(substr($key, 0, $cplen) == $_W['config']['cookie']['pre']) {
		$_COOKIE[substr($key, $cplen)] = $value;
	}
}

$_GPC = array_merge($_GET, $_POST, $_GPC);
$_GPC = ihtmlspecialchars($_GPC);
$_GET = ihtmlspecialchars($_GET);
$_POST = ihtmlspecialchars($_POST);

unset($config);
unset($cplen);

$_W['attachurl'] = 'https://img01.shunliandongli.com/attachment/';

//register_shutdown_function('session_write_close');
