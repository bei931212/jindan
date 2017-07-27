<?php
/**
 * 公共函数
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
defined('IN_IA') or exit('Access Denied');

/**
 * 转义引号字符串
 * 支持单个字符与数组
 *
 * @param
 *        	string or array $var
 * @return string or array
 *         返回转义后的字符串或是数组
 */
function istripslashes($var) {
	if(is_array($var)) {
		foreach($var as $key => $value) {
			$var[stripslashes($key)] = istripslashes($value);
		}
	} else {
		$var = stripslashes($var);
	}
	return $var;
}
//截取字符串
function utf_substr($len,$str,$from=0){
	// 将字符串分解为单元
	preg_match_all("/./us", $str, $match);
	// 返回单元个数
	$string_length= count($match[0]);
	$result =  preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.
		'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
		'$1',$str);
	if($string_length>$len)
		$result = $result.'...';
	return $result;
}
/**
 * 转义字符串的HTML
 * 
 * @param
 *        	string or array $var
 * @return string or array
 *         返回转义后的字符串或是数组
 */
function ihtmlspecialchars($var) {
	if(is_array($var)) {
		foreach($var as $key => $value) {
			$var[htmlspecialchars($key)] = ihtmlspecialchars($value);
		}
	} else {
		$var = str_replace('&amp;', '&', htmlspecialchars($var, ENT_QUOTES));
	}
	return $var;
}

/**
 * 写入cookie值
 * 
 * @param string $key
 *        	cookie名称
 * @param string $value
 *        	cookie值
 * @param int $maxage
 *        	cookie的生命周期,当前时间开始的$maxage秒
 * @return boolean
 */
function isetcookie($key, $value, $maxage = 0) {
	global $_W;
	$expire = $maxage != 0 ? time() + $maxage : 0;
	return setcookie($_W['config']['cookie']['pre'] . $key, $value, $expire, $_W['config']['cookie']['path'], $_W['config']['cookie']['domain']);
}

function clearcookie() {
	global $_W;

	//obclean();
	isetcookie('pin', '', -86400 * 365);
	isetcookie('wskey', '', -86400 * 365);
	isetcookie('captcha', '', -86400 * 365);

	$_W['member_id'] = 0;
}

/**
 * 获取客户ip
 * 
 * @return string 返回IP地址
 *         如果未获取到返回unknown
 */
function getip() {
	static $ip = '';
	$ip = $_SERVER['REMOTE_ADDR'];
	if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {
		$ip = $_SERVER['HTTP_CDN_SRC_IP'];
	} elseif(isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) and preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
		foreach($matches[0] as $xip) {
			if(! preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
				$ip = $xip;
				break;
			}
		}
	}
	return $ip;
}

/**
 * 消息提示窗
 * 
 * @param string $msg
 *        	提示消息内容
 *        	
 * @param string $redirect
 *        	跳转地址
 *        	
 * @param string $type
 *        	提示类型
 *        	success		成功
 *        	error		错误
 *        	question	询问(问号)
 *        	attention	注意(叹号)
 *        	tips		提示(灯泡)
 *        	ajax		json
 */
function message($msg, $redirect = '', $type = '') {
	global $_W;
	if($type == 'auth') {
		checkauth();
		exit();
	}
	if($redirect == 'refresh') {
		$redirect = $_W['script_name'] . '?' . $_SERVER['QUERY_STRING'];
	}
	if($redirect == '') {
		$type = in_array($type, array(
			'success',
			'error',
			'tips',
			'ajax',
			'sql'
		)) ? $type : 'error';
	} else {
		$type = in_array($type, array(
			'success',
			'error',
			'tips',
			'ajax',
			'sql'
		)) ? $type : 'success';
	}
	if($_W['isajax'] || $type == 'ajax') {
		$vars = array();
		$vars['message'] = $msg;
		$vars['redirect'] = $redirect;
		$vars['type'] = $type;
		exit(json_encode($vars));
	}
	if(defined('IN_MOBILE')) {
		$message = "<script type=\"text/javascript\">alert('$msg');";
		$redirect && $message .= "location.href = \"{$redirect}\";";
		$message .= "</script>";
		include template('message', TEMPLATE_INCLUDEPATH);
		exit();
	}
	if(empty($msg) && ! empty($redirect)) {
		header('Location: ' . $redirect);
	}
	return $msg;
}

/**
 * 生成token
 */
function token($specialadd = '') {
	global $_W;
	$hashadd = defined('IN_MANAGEMENT') ? 'for management' : '';
	return substr(md5($_W['config']['setting']['authkey'] . $hashadd . $specialadd), 8, 8);
}

function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	if($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		$length--;
	}
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed{mt_rand(0, $max)};
	}
	return $hash;
}

function build_ordersn($member_id=0) {
	$length = 14;
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT'].$member_id), 16, 10);
	$seed = str_replace('0', '', $seed).'012340567890';

	$position = rand(0, 13);

	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		if($i == $position) {
			$hash_char = chr(rand(1, 26) + 64);
			if($hash_char == 'O') $hash_char = '0';
			if($hash_char == 'I') $hash_char = '1';
			if($hash_char == 'Z') $hash_char = '2';
			if($hash_char == 'G') $hash_char = '3';
			if($hash_char == 'J') $hash_char = '4';
			$hash .= $hash_char;
		} else {
			$hash .= $seed{mt_rand(0, $max)};
		}
	}

	return date('ymd').$hash;
}

function build_paysn($member_id=0) {
	$length = 17;
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT'].$member_id), 16, 10);
	$seed = str_replace('0', '', $seed).'012340567890';

	$position = rand(1, 17);

	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		if($i == $position) {
			$hash_char = chr(rand(1, 26) + 64);
			if($hash_char == 'O') $hash_char = '0';
			if($hash_char == 'I') $hash_char = '1';
			if($hash_char == 'Z') $hash_char = '2';
			if($hash_char == 'G') $hash_char = '3';
			if($hash_char == 'J') $hash_char = '4';
			$hash .= $hash_char;
		} else {
			$hash .= $seed{mt_rand(0, $max)};
		}
	}

	return 'PAY'.$hash;
}

/**
 * 运行钩子
 * 
 * @param string $name
 *        	钩子名称
 * @param mixed $context
 *        	传递给钩子函数的上下文数据，引用传递
 * @return void
 */
function hooks($name, &$context = null) {
}

/**
 * 提交来源检查
 */
function checksubmit($var = 'submit', $allowget = 0) {
	global $_W, $_GPC;
	if(empty($_GPC[$var])) {return FALSE;}
	if($allowget || (($_W['ispost'] && ! empty($_W['token']) && $_W['token'] == $_GPC['token']) && (empty($_SERVER['HTTP_REFERER']) || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {return TRUE;}
	return FALSE;
}

/**
 * 检查是否登录
 * 
 * @param boolean $redirect
 *        	是否自动跳转登录
 * @return boolean
 */
function checklogin() {
	global $_W;
	
	if(empty($_W['uid'])) {
		// 商家平台采用不同的登陆方式
		// if($GLOBALS['entry'] && $GLOBALS['entry']['module'] == 'seller') {
		if(isset($_SERVER['SERVER_SUBDOMAIN']) && $_SERVER['SERVER_SUBDOMAIN'] == 'seller') {
			message('抱歉，您无权进行该操作，请先登录！', create_url('member/sellerlogin'), 'error');
		} elseif(isset($_SERVER['SERVER_SUBDOMAIN']) && $_SERVER['SERVER_SUBDOMAIN'] == 'mp') {
			message('抱歉，您无权进行该操作，请先登录！', create_url('member/login'), 'error');
		} else {
			exit();
		}
	}
	return true;
}

function checkauth($redirect = true) {
	global $_W, $_GPC;
	if(empty($_W['fans']['from_user'])) {
		if($redirect) {
			$site = $GLOBALS['site'];
			$account = $GLOBALS['_W']['account'];
			$rid = intval($_GPC['rid']);
			if(! empty($rid)) {
				$keywords_key = 'rule_keyword-' . $rid;
				$keywords = $_W['mc']->get($keywords_key);
				
				if($keywords) {
					$keywords = unserialize($keywords);
				} else {
					$keywords = pdo_fetchall("SELECT content FROM " . tablename('rule_keyword') . " WHERE rid = '{$rid}'");
					$keywords && $_W['mc']->set($keywords_key, serialize($keywords), 0, 600);
				}
			}
			if(! empty($GLOBALS['entry'])) {
				$rule_key = md5('rule_keyword-' . serialize($GLOBALS['entry']) . '-' . $account['weid']);
				$rule = $_W['mc']->get(rule_key);
				
				if($rule) {
					$rule = unserialize($rule);
				} else {
					$rule = pdo_fetch("SELECT rid FROM " . tablename('cover_reply') . " WHERE module = '{$GLOBALS['entry']['module']}' AND do = '{$GLOBALS['entry']['do']}' AND weid = '{$account['weid']}'");
					$_W['mc']->set($rule_key, serialize($rule), 0, 600);
				}
				
				$keywords_key2 = 'rule_keyword-' . $rule['rid'];
				$keywords = $_W['mc']->get($keywords_key2);
				
				if($keywords) {
					$keywords = unserialize($keywords);
				} else {
					$keywords = pdo_fetchall("SELECT content FROM " . tablename('rule_keyword') . " WHERE rid = '{$rule['rid']}'");
					$keywords && $_W['mc']->set($keywords_key2, serialize($keywords), 0, 600);
				}
			}
			include template('auth', TEMPLATE_INCLUDEPATH);
		} else {
			message('非法访问，请重新点击链接进入个人中心！');
		}
		exit();
	}
}

/**
 * 返回完整数据表名(加前缀)
 * 
 * @param string $table        	
 * @return string
 */
function tablename($table) {
	return $GLOBALS['_W']['config']['db']['tablepre'].$table;
}

function router($controller, $action) {
	$controllerfile = IA_ROOT . '/source/controller/' . ($controller ? $controller . '/' : '') . $action . '.ctrl.php';
	if(file_exists($controllerfile)) {
		return $controllerfile;
	} else {
		trigger_error('Invalid Controller "' . $action . '"', E_USER_ERROR);
		return '';
	}
}

function model($model) {
	$file = IA_ROOT . '/source/model/' . $model . '.mod.php';
	if(file_exists($file)) {
		return $file;
	} else {
		trigger_error('Invalid Model ' . $model, E_USER_ERROR);
		return '';
	}
}

function func($func) {
	$file = IA_ROOT . '/source/function/' . $func . '.func.php';
	if(file_exists($file)) {
		return $file;
	} else {
		trigger_error('Invalid Function Helper ' . $func, E_USER_ERROR);
		return '';
	}
}

/**
 * 该函数从一个数组中取得若干元素。该函数测试（传入）数组的每个键值是否在（目标）数组中已定义；如果一个键值不存在，该键值所对应的值将被置为FALSE，或者你可以通过传入的第3个参数来指定默认的值。
 * 
 * @param array $items
 *        	需要筛选的键名定义
 * @param array $array
 *        	要进行筛选的数组
 * @param mixed $default
 *        	如果原数组未定义的键，则使用此默认值返回
 * @return array
 */
function array_elements($items, $array, $default = FALSE) {
	$return = array();
	if(! is_array($items)) {
		$items = array(
			$items
		);
	}
	foreach($items as $item) {
		if(isset($array[$item])) {
			$return[$item] = $array[$item];
		} else {
			$return[$item] = $default;
		}
	}
	return $return;
}

/**
 * JSON编码,加上转义操作,适合于JSON入库
 *
 * @param string $value        	
 */
function ijson_encode($value) {
	if(empty($value)) {return false;}
	return addcslashes(json_encode($value), "\\\'\"");
}

/**
 * 序列化操作
 *
 * @param string $value        	
 */
function iserializer($value) {
	return serialize($value);
}

/**
 * 解序列化
 *
 * @param array $value        	
 */
function iunserializer($value) {
	if(empty($value)) {return '';}
	if(! is_serialized($value)) {return $value;}
	$result = unserialize($value);
	if($result === false) {
		$temp = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $value);
		return unserialize($temp);
	}
	return $result;
}

function is_serialized($data, $strict = true) {
	if(! is_string($data)) {return false;}
	$data = trim($data);
	if('N;' == $data) {return true;}
	if(strlen($data) < 4) {return false;}
	if(':' !== $data[1]) {return false;}
	if($strict) {
		$lastc = substr($data, - 1);
		if(';' !== $lastc && '}' !== $lastc) {return false;}
	} else {
		$semicolon = strpos($data, ';');
		$brace = strpos($data, '}');
		// Either ; or } must exist.
		if(false === $semicolon && false === $brace) return false;
		// But neither must be in the first X characters.
		if(false !== $semicolon && $semicolon < 3) return false;
		if(false !== $brace && $brace < 4) return false;
	}
	$token = $data[0];
	switch($token) {
		case 's':
			if($strict) {
				if('"' !== substr($data, - 2, 1)) {return false;}
			} elseif(false === strpos($data, '"')) {return false;}
		// or else fall through
		case 'a':
		case 'O':
			return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
		case 'b':
		case 'i':
		case 'd':
			$end = $strict ? '$' : '';
			return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
	}
	return false;
}

function toimage($src) {
	global $_W;
	if(empty($src)) {return '';}
	$t = strtolower($src);
	if(substr($t, 0, 6) == 'avatar') {return $_W['siteroot'] . "resource/image/avatar/" . $src;}
	if(substr($t, 0, 8) == './themes') {return $_W['siteroot'] . $src;}
	if(substr($t, 0, 1) == '.') {return $_W['siteroot'] . substr($src, 2);}
	if(! strexists($t, 'http://') && ! strexists($t, 'https://')) {
		$src = $_W['attachurl'] . '/' . $src;
	}
	return $src;
}

/**
 * 构造错误数组
 *
 * @param int $errno
 *        	错误码，0为无任何错误。
 * @param string $message
 *        	错误信息，通知上层应用具体错误信息。
 * @return array
 */
function error($code, $msg = '') {
	return array(
		'errno' => $code,
		'message' => $msg
	);
}

/**
 * 检测返回值是否产生错误
 *
 * 产生错误则返回true，否则返回false
 *
 * @param mixed $data
 *        	待检测的数据
 * @return boolean
 */
function is_error($data) {
	if(empty($data) || ! is_array($data) || ! array_key_exists('errno', $data) || (array_key_exists('errno', $data) && $data['errno'] == 0)) {
		return false;
	} else {
		return true;
	}
}

/**
 * 生成URL，统一生成方便管理
 * 
 * @param string $router        	
 * @param array $params        	
 * @return string
 */
function create_url($router, $params = array()) {
	list($module, $controller, $do) = explode('/', $router);
	$queryString = http_build_query($params, '', '&');
	return $module . '.php?act=' . $controller . (empty($do) ? '' : '&do=' . $do) . '&' . $queryString;
}

/**
 * 是否包含子串
 */
function strexists($string, $find) {
	return ! (strpos($string, $find) === FALSE);
}

function cutstr($string, $length, $havedot = 0, $charset = '') {
	global $_W;
	if(empty($charset)) {
		$charset = $_W['charset'];
	}
	if(strtolower($charset) == 'gbk') {
		$charset = 'gbk';
	} else {
		$charset = 'utf8';
	}
	if(istrlen($string, $charset) <= $length) {return $string;}
	if(function_exists('mb_strcut')) {
		$string = mb_substr($string, 0, $length, $charset);
	} else {
		$pre = '{%';
		$end = '%}';
		$string = str_replace(array(
			'&amp;',
			'&quot;',
			'&lt;',
			'&gt;'
		), array(
			$pre . '&' . $end,
			$pre . '"' . $end,
			$pre . '<' . $end,
			$pre . '>' . $end
		), $string);
		
		$strcut = '';
		$strlen = strlen($string);
		
		if($charset == 'utf8') {
			$n = $tn = $noc = 0;
			while($n < $strlen) {
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1;
					$n ++;
					$noc ++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2;
					$n += 2;
					$noc ++;
				} elseif(224 <= $t && $t <= 239) {
					$tn = 3;
					$n += 3;
					$noc ++;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4;
					$n += 4;
					$noc ++;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5;
					$n += 5;
					$noc ++;
				} elseif($t == 252 || $t == 253) {
					$tn = 6;
					$n += 6;
					$noc ++;
				} else {
					$n ++;
				}
				if($noc >= $length) {
					break;
				}
			}
			if($noc > $length) {
				$n -= $tn;
			}
			$strcut = substr($string, 0, $n);
		} else {
			while($n < $strlen) {
				$t = ord($string[$n]);
				if($t > 127) {
					$tn = 2;
					$n += 2;
					$noc ++;
				} else {
					$tn = 1;
					$n ++;
					$noc ++;
				}
				if($noc >= $length) {
					break;
				}
			}
			if($noc > $length) {
				$n -= $tn;
			}
			$strcut = substr($string, 0, $n);
		}
		$string = str_replace(array(
			$pre . '&' . $end,
			$pre . '"' . $end,
			$pre . '<' . $end,
			$pre . '>' . $end
		), array(
			'&amp;',
			'&quot;',
			'&lt;',
			'&gt;'
		), $strcut);
	}
	
	if($havedot) {
		$string = $string . "...";
	}
	
	return $string;
}

function istrlen($string, $charset = '') {
	global $_W;
	if(empty($charset)) {
		$charset = $_W['charset'];
	}
	if(strtolower($charset) == 'gbk') {
		$charset = 'gbk';
	} else {
		$charset = 'utf8';
	}
	if(function_exists('mb_strlen')) {
		return mb_strlen($string, $charset);
	} else {
		$n = $noc = 0;
		$strlen = strlen($string);
		
		if($charset == 'utf8') {
			
			while($n < $strlen) {
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$n ++;
					$noc ++;
				} elseif(194 <= $t && $t <= 223) {
					$n += 2;
					$noc ++;
				} elseif(224 <= $t && $t <= 239) {
					$n += 3;
					$noc ++;
				} elseif(240 <= $t && $t <= 247) {
					$n += 4;
					$noc ++;
				} elseif(248 <= $t && $t <= 251) {
					$n += 5;
					$noc ++;
				} elseif($t == 252 || $t == 253) {
					$n += 6;
					$noc ++;
				} else {
					$n ++;
				}
			}
		} else {
			
			while($n < $strlen) {
				$t = ord($string[$n]);
				if($t > 127) {
					$n += 2;
					$noc ++;
				} else {
					$n ++;
					$noc ++;
				}
			}
		}
		
		return $noc;
	}
}

function emotion($message = '', $size = '24px') {
	$emotions = array(
		"/::)",
		"/::~",
		"/::B",
		"/::|",
		"/:8-)",
		"/::<",
		"/::$",
		"/::X",
		"/::Z",
		"/::'(",
		"/::-|",
		"/::@",
		"/::P",
		"/::D",
		"/::O",
		"/::(",
		"/::+",
		"/:--b",
		"/::Q",
		"/::T",
		"/:,@P",
		"/:,@-D",
		"/::d",
		"/:,@o",
		"/::g",
		"/:|-)",
		"/::!",
		"/::L",
		"/::>",
		"/::,@",
		"/:,@f",
		"/::-S",
		"/:?",
		"/:,@x",
		"/:,@@",
		"/::8",
		"/:,@!",
		"/:!!!",
		"/:xx",
		"/:bye",
		"/:wipe",
		"/:dig",
		"/:handclap",
		"/:&-(",
		"/:B-)",
		"/:<@",
		"/:@>",
		"/::-O",
		"/:>-|",
		"/:P-(",
		"/::'|",
		"/:X-)",
		"/::*",
		"/:@x",
		"/:8*",
		"/:pd",
		"/:<W>",
		"/:beer",
		"/:basketb",
		"/:oo",
		"/:coffee",
		"/:eat",
		"/:pig",
		"/:rose",
		"/:fade",
		"/:showlove",
		"/:heart",
		"/:break",
		"/:cake",
		"/:li",
		"/:bome",
		"/:kn",
		"/:footb",
		"/:ladybug",
		"/:shit",
		"/:moon",
		"/:sun",
		"/:gift",
		"/:hug",
		"/:strong",
		"/:weak",
		"/:share",
		"/:v",
		"/:@)",
		"/:jj",
		"/:@@",
		"/:bad",
		"/:lvu",
		"/:no",
		"/:ok",
		"/:love",
		"/:<L>",
		"/:jump",
		"/:shake",
		"/:<O>",
		"/:circle",
		"/:kotow",
		"/:turn",
		"/:skip",
		"/:oY",
		"/:#-0",
		"/:hiphot",
		"/:kiss",
		"/:<&",
		"/:&>"
	);
	foreach($emotions as $index => $emotion) {
		$message = str_replace($emotion, '<img style="width:' . $size . ';vertical-align:middle;" src="http://res.mail.qq.com/zh_CN/images/mo/DEFAULT2/' . $index . '.gif" />', $message);
	}
	return $message;
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

	$ckey_length = 4;
	$key = md5($key != '' ? $key : $GLOBALS['_W']['config']['setting']['authkey']);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), - $ckey_length)) : '';
	
	$cryptkey = $keya . md5($keya . $keyc);
	$key_length = strlen($cryptkey);
	
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
	$string_length = strlen($string);
	
	$result = '';
	$box = range(0, 255);
	
	$rndkey = array();
	for($i = 0; $i <= 255; $i ++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	
	for($j = $i = 0; $i < 256; $i ++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	
	for($a = $j = $i = 0; $i < $string_length; $i ++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	
	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace('=', '', base64_encode($result));
	}
}

function sizecount($size) {
	if($size >= 1073741824) {
		$size = round($size / 1073741824 * 100) / 100 . ' GB';
	} elseif($size >= 1048576) {
		$size = round($size / 1048576 * 100) / 100 . ' MB';
	} elseif($size >= 1024) {
		$size = round($size / 1024 * 100) / 100 . ' KB';
	} else {
		$size = $size . ' Bytes';
	}
	return $size;
}

/**
 * 将一个数组转换为 XML 结构的字符串
 * 
 * @param array $arr
 *        	要转换的数组
 * @param int $level
 *        	节点层级, 1 为 Root.
 * @return string XML 结构的字符串
 */
function array2xml($arr, $level = 1) {
	$s = $level == 1 ? "<xml>" : '';
	foreach($arr as $tagname => $value) {
		if(is_numeric($tagname)) {
			$tagname = $value['TagName'];
			unset($value['TagName']);
		}
		if(! is_array($value)) {
			$s .= "<{$tagname}>" . (! is_numeric($value) ? '<![CDATA[' : '') . $value . (! is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
		} else {
			$s .= "<{$tagname}>" . array2xml($value, $level + 1) . "</{$tagname}>";
		}
	}
	$s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
	return $level == 1 ? $s . "</xml>" : $s;
}

/**
 * 将unicode编码值转换为utf-8编码字符
 */
function utf8_bytes($cp) {
	if($cp > 0x10000) {
		// 4 bytes
		return chr(0xF0 | (($cp & 0x1C0000) >> 18)) . chr(0x80 | (($cp & 0x3F000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
	} else if($cp > 0x800) {
		// 3 bytes
		return chr(0xE0 | (($cp & 0xF000) >> 12)) . chr(0x80 | (($cp & 0xFC0) >> 6)) . chr(0x80 | ($cp & 0x3F));
	} else if($cp > 0x80) {
		// 2 bytes
		return chr(0xC0 | (($cp & 0x7C0) >> 6)) . chr(0x80 | ($cp & 0x3F));
	} else {
		// 1 byte
		return chr($cp);
	}
}

function test_mc() {
	global $_W;
	
	$current_value = $_W['mc']->increment('test', 1);
	print_r($current_value);
}

function compute_id($id, $operation = 'DECODE') {
	if(! $id) return 0;
	if($operation == 'DECODE') {
		$id_ret = $id / 101 - 108;
		return is_int($id_ret) ? $id_ret : 0;
	}
	
	return sprintf('%1.0f', ($id + 108) * 101);
}

function format_avatar_size($avatar, $size = 64) {
	return preg_replace('/\/0$/', '/' . $size, $avatar);
}

function hashdir($id) {
	$hash = str_split(md5($id));
	return $hash[3] . '/' . $hash[4] . '/' . $hash[5];
}

/**
 * 生成从开始月份到结束月份的月份数组
 * @param int $start 开始时间戳
 * @param int $end 结束时间戳
 */
function monthList($start,$end){
	if(!is_numeric($start)||!is_numeric($end)||($end<=$start)) return '';
	$start=date('Y-m',$start);
	$end=date('Y-m',$end);
	//转为时间戳
	$start=strtotime($start.'-01');
	$end=strtotime($end.'-01');
	$i=0;//http://www.phpernote.com/php-function/224.html
	$d=array();
	while($start<=$end){
		//这里累加每个月的的总秒数 计算公式：上一月1号的时间戳秒数减去当前月的时间戳秒数
		$d[$i]=trim(date('Y-m',$start),' ');
		$start+=strtotime('+1 month',$start)-$start;
		$i++;
	}
	return $d;
}

function getNextMonthDays($date){
    $timestamp=strtotime($date);
    $arr=getdate($timestamp);
    if($arr['mon'] == 12){
        $year=$arr['year'] +1;
        $month=$arr['mon'] -11;
        $firstday=$year.'-0'.$month.'-01';
        $lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day"));
    }else{
        $firstday=date('Y-m-01',strtotime(date('Y',$timestamp).'-'.(date('m',$timestamp)+1).'-01'));
        $lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day"));
    }
    return array($firstday,$lastday);
}

function fcm_gmdate($dateformat, $timestamp='', $format=0) {
	global $_SC, $_SG;

	empty($dateformat) && $dateformat = 'Y-m-d H:i:s';

	$result = '';
	if($format) {
		$time = TIMESTAMP - $timestamp;
		if($time > 259200) {
			$result = gmdate($dateformat, $timestamp + 8 * 3600);
		} elseif($time > 86400) {
			$result = intval($time/86400).'天'.'前';
		} elseif ($time > 3600) {
			$result = intval($time/3600).'小时'.'前';
		} elseif ($time > 60) {
			$result = intval($time/60).'分钟'.'前';
		} elseif ($time > 0) {
			$result = $time.'秒'.'前';
		} else {
			$result = '现在';
		}
	} else {
		$result = gmdate($dateformat, $timestamp + 8 * 3600);
	}

	return $result;
}

/*
function fcm_gmdate($timestamp='') {

	$result = '';
	$time = time()-$timestamp;
	if($time > 86400) {
		$result = intval($time/86400).'天'.'前';
	} elseif ($time > 3600) {
		$result = intval($time/3600).'小时'.'前';
	} elseif ($time > 60) {
		$result = intval($time/60).'分钟'.'前';
	} elseif ($time > 0) {
		$result = $time.'秒'.'前';
	} else {
		$result = '现在';
	}

	return $result;
}
*/

function is_mobile($number) {

	return preg_match('/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|14[57]{1}[0-9]{8}$|17[0135678]{1}[0-9]{8}/', $number);
}

function weixin_curl($url, $gzip=false, $timeout=10) {
	if(!function_exists('curl_init')) return false;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	strpos($url, 'https') === 0 && curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, 0);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept:application/json;charset=utf-8", "Content-Type:application/x-www-form-urlencoded;charset=utf-8"));
	$gzip && curl_setopt($ch, CURLOPT_ENCODING , 'gzip');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_POST, 0);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_data);

	$data = curl_exec($ch);
	curl_close($ch);

	if($data) {
		return json_decode($data, true);
	} else {
		return '';
	}
}

function curl_get($url, $gzip=false, $timeout=30) {
	if(!function_exists('curl_init')) return false;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	strpos($url, 'https') === 0 && curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, 0);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept:application/json;charset=utf-8", "Content-Type:application/x-www-form-urlencoded;charset=utf-8"));
	$gzip && curl_setopt($ch, CURLOPT_ENCODING , 'gzip');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_POST, 0);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_data);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; chromeframe/13.0.782.215)');

	$data = curl_exec($ch);
	curl_close($ch);

	return $data;
}

function curl_post($url, $data, $timeout=30) {
	if(!function_exists('curl_init')) return false;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	strpos($url, 'https') === 0 && curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_FORBID_REUSE, 0);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	$result = curl_exec($ch);
	$status = curl_getinfo($ch);
	curl_close($ch);
	if($status['http_code'] !='200'){
		return false;
	}
	return $result;
}
/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author
 */
function list_to_tree($list, $pk = 'id', $pid = 'parent_id', $child = '_child', $root = 0) {
	 $tree = array();
	 if (is_array($list)) {
		  // 创建基于主键的数组引用
		  $refer = array();
		  foreach ($list as $key => $data) {
				$refer[$data[$pk]] = & $list[$key];
		  }
		  foreach ($list as $key => $data) {
				// 判断是否存在parent
				$parentId = $data[$pid];
				if ($root == $parentId) {
					 $tree[$data[$pk]] = & $list[$key];
				} else {
					 if (isset($refer[$parentId])) {
						  $parent = & $refer[$parentId];
						  $parent[$child][$data[$pk]] = & $list[$key];
					 }
				}
		  }
	 }
	 return $tree;
}
//去除特殊字符
function yz_expression($value)
{

        $value = preg_replace_callback('/[\xf0-\xf7].{3}/', function($r) { return '@E' . base64_encode($r[0]);},$value);
 
        $countt=substr_count($value,"@");
            for ($i=0; $i < $countt; $i++) {
                $c = stripos($value,"@");
                $value=substr($value,0,$c).substr($value,$c+10,strlen($value)-1);
            }
            $value = preg_replace_callback('/@E(.{6}==)/', function($r) {return base64_decode($r[1]);}, $value);
			return $value;
 
        
}


/**
 * 获取输入数据 支持默认值和过滤
 * @param string    $key 获取的变量名
 * @param mixed     $default 默认值
 * @param string    $filter 过滤方法
 * @return mixed
 */
function input($key = '', $default = null, $filter = array('trim'))
{
    if ($pos = strpos($key, '.')) {
        // 指定参数来源
        list($method, $key) = explode('.', $key, 2);
        if (!in_array($method, array('get', 'post', 'request', 'file'))) {
            $key    = $method . '.' . $key;
            $method = 'request';
        }
    } else {
        // 默认为自动判断
        $method = 'request';
    }
    switch ($method) {
    	case 'get':
    		# code...
    		break;
    	
    	default:
    		# code...
    		break;
    }
    $obj = &$GLOBALS['_'.strtoupper($method)];

    if (isset($obj[$key])) {
        $data = $obj[$key];
        $filter[] = $default;
        if (is_array($data)) {
            array_walk_recursive($data, 'filterValue', $filter);
            reset($data);
        } else {
            filterValue($data, $name, $filter);
        }

        return $data;
    } else {
        return $default;
    }
}
/**
 * 递归过滤给定的值
 * @param mixed     $value 键值
 * @param mixed     $key 键名
 * @param array     $filters 过滤方法+默认值
 * @return mixed
 */
function filterValue(&$value, $key, $filters)
{
    $default = array_pop($filters);
    foreach ($filters as $filter) {
        if (is_callable($filter)) {
            // 调用函数或者方法过滤
            $value = call_user_func($filter, $value);
        } elseif (is_scalar($value)) {
            if (strpos($filter, '/')) {
                // 正则过滤
                if (!preg_match($filter, $value)) {
                    // 匹配不成功返回默认值
                    $value = $default;
                    break;
                }
            } elseif (!empty($filter)) {
                // filter函数不存在时, 则使用filter_var进行过滤
                // filter为非整形值时, 调用filter_id取得过滤id
                $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                if (false === $value) {
                    $value = $default;
                    break;
                }
            }
        }
    }
}
/**
* 时间转描述
*/
function time_tran($the_time, $def_format='Y-m-d H:i') {  
    if(!preg_match('#^[0-9]+$#', $the_time)){
    	$the_time = strtotime($the_time);
    }
    $now_time = time();  
    $show_time = $the_time;  
    $dur = $now_time - $show_time;  
    if ($dur < 0) {  
        return date($def_format, $the_time);  
    } else {  
        if ($dur < 60*3) {  
            return '刚刚';  
        }else if ($dur < 3600) {  
            return floor($dur / 60) . '分钟前';  
        }else if($dur < 86400){
        	return floor($dur / 3600) . '小时前';  
        }else if ($dur < 86400*4) { //n天内  
            return floor($dur / 86400) . '天前';  
        }else if ($dur < 86400*8) {
            return '一周内';  
        }else{
        	return date($def_format, $the_time);   
        }   
    }  
}
/**
* 添加*号
*/
function maskName($str, $msask_len=2, $encode='utf-8'){
	$l = mb_strlen($str, $encode);
	if($l==0){
		return $str;
	}else if($l<=2){
		return mb_substr($str, 0, 1, $encode) . str_repeat('*', $msask_len);
	}else if($l==3){
		return mb_substr($str, 0, 1, $encode) . str_repeat('*', $msask_len) . mb_substr($str, -1, 1, $encode);
	}else{
		return mb_substr($str, 0, 2, $encode) . str_repeat('*', $msask_len) . mb_substr($str, -1, 1, $encode);
	}
}