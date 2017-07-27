<?php

class User extends Api {

	//环境检查
	//必须参数checkType=login|register
	//注册方式，regType=username|mobile
	public function checkEnv() {
		global $_W;

		$checkType = trim($_GET['checkType']);
		if(empty($checkType)) return self::responseError(50, 'Parameter is missing.');

		if($checkType == 'register') {
			$regType = 'mobile';
			$showCaptcha = 0;

			if($regType == 'username') {
				$regtime = strtotime('-1 month');
				//如果当前设备一个月内注册过账号，显示验证码
				$device_count = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_member_auth` WHERE regdevice='{$_W['device_id']}' AND regtime>'{$regtime}'");
				if($device_count > 0) {
					$showCaptcha = 1;
				}
				if($showCaptcha == 0) {
					$regtime = strtotime('-3 days');
					//如果当前IP3天内注册超过3个账号，显示验证码
					$ip_count = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_member_auth` WHERE regip='{CLIENT_IP}' AND regtime>'{$regtime}'");
					if($device_count > 0) {
						$showCaptcha = 1;
					}
				}
				if($showCaptcha == 0) {
					//如果短时间注册量异常，显示验证码
				}
			} else { //如果使用手机号注册，不显示验证码
				$showCaptcha = 0;
			}

			$result = array(
				'showCaptcha' => $showCaptcha,
				'regType' => $regType
			);
		} else {
			$showCaptcha = 0;

			//判断密码错误次数，决定是否显示验证码

			$result = array(
				'showCaptcha' => $showCaptcha
			);
		}

		return self::responseOk($result);
	}

	//登陆
	public function login() {
		global $_W;

		$username = trim($_POST['username']);
		$password = $_POST['password'];
		$captcha = $_POST['captcha'];

		if(empty($username)) {
			return self::responseError(50, '请输入用户名！');
		}

		if(empty($password)) {
			return self::responseError(50, '请输入密码！');
		}
/*
		if(empty($captcha)) {
			return self::responseError(50, '请输入验证码！');
		}

		$hash = md5($captcha . $_W['config']['setting']['authkey']);
		if($_COOKIE['captcha'] != $hash) {
			isetcookie('captcha', '');
			return self::responseError(13, '图片验证码不正确！');
		}
		isetcookie('captcha', '');
*/
		if(preg_match('/^\d{11}$/', $username)) {
			$user = pdo_fetch("SELECT id AS member_id,username,password,salt,mobile_verified,status FROM `ims_bj_qmxk_member_auth` WHERE mobile='{$username}'");
			if(!empty($user['member_id']) && empty($user['mobile_verified'])) {
				return self::responseError(50, '您的手机尚未验证，请使用用户名登陆！');
			}
		} else {
			$user = pdo_fetch("SELECT id AS member_id,username,password,salt,mobile_verified,status FROM `ims_bj_qmxk_member_auth` WHERE username='{$username}'");
		}

		if(empty($user['member_id'])) {
			return self::responseError(11, '登陆失败，用户名或密码错误。');
		}

		if(empty($user['status'])) {
			//该账户已被禁用，请联系客服。
			return self::responseError(12, '该账户已被禁用，请联系客服。');
		}

		if($user['password'] == sha1($user['salt']. $password. $user['salt'])) {
			//登陆成功
			//输出一次用户信息

			$member = pdo_fetch("SELECT member_id,shareid,realname,mobile,from_user,commission,zhifu,dzdflag,flagtime,dzdtitle,credit2,credit2_freeze,member_gold_count,group_gold_count,spending,agent_level FROM `ims_bj_qmxk_member` WHERE member_id='{$user['member_id']}'");
			//输出cookie
			$mi = pdo_fetch("SELECT avatar FROM `ims_bj_qmxk_member_info` WHERE member_id='{$user['member_id']}'");
			$member['avatar'] = $mi['avatar'] ? $mi['avatar'] : 'http://statics.shunliandongli.com/resource/image/avatar.png';
			
			$user['member_id'] = compute_id($user['member_id'], 'ENCODE');
			$member['member_id'] = compute_id($member['member_id'], 'ENCODE');
			$cookie = array('pin'=>$user['member_id'], 'wskey'=>authcode("{$user['member_id']}\t{$user['password']}\t{$user['username']}\t".substr(md5($_W['device_id']), 8, 8), 'ENCODE'));
			//设置cookie
			isetcookie('pin', $cookie['pin'], 60*60*24*365);
			isetcookie('wskey', $cookie['wskey'], 60*60*24*365);

			$result = array(
				'memberInfo'	=> $member,
				'cookieInfo'	=> $cookie
			);

			return self::responseOk($result);
		}

		return self::responseError(10, '登陆失败，用户名或密码错误。');
	}

	//微信登陆
	//必须参数code
	//必须参数state=device_id
	public function weixinLogin() {
		global $_W;

		$code = trim($_POST['code']);
		$state = trim($_POST['state']);

		//判断参数
		if(empty($code)) return self::responseError(110, 'Parameter [code] is missing.');
		if(empty($state)) return self::responseError(111, 'Parameter [state] is missing.');
		if($state != $_W['device_id']) return self::responseError(112, 'Parameter [state] is invalid.'.$_W['device_id']);

		//获取access_token
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$_W['config']['weixin']['app']['appid']}&secret={$_W['config']['weixin']['app']['secret']}&code={$code}&grant_type=authorization_code";
		$token = weixin_curl($url);

		$access_token = $token['access_token'];
		$openid	= $token['openid'];
		$unionid = $token['unionid'];
		$scope = $token['scope'];

		//判断是否获取成功
		if(!empty($token['errcode'])) return self::responseError(113, '授权失败，请重试。'.json_encode($token));
		if(empty($access_token) || empty($openid) || empty($unionid) || empty($scope)) {
			 return self::responseError(114, '授权失败，请重试(2)。'.$token);
		}

		//判断是否已注册
		$user = pdo_fetch("SELECT id AS member_id,username,password,salt,mobile_verified,status FROM `ims_bj_qmxk_member_auth` WHERE unionid='{$unionid}'");
		if($user['member_id']) { //已注册
			
			$member = pdo_fetch("SELECT member_id,shareid,realname,mobile,from_user,commission,zhifu,dzdflag,flagtime,dzdtitle,credit2,credit2_freeze,member_gold_count,group_gold_count,spending,agent_level FROM `ims_bj_qmxk_member` WHERE member_id='{$user['member_id']}'");
			//输出cookie
			$mi = pdo_fetch("SELECT avatar FROM `ims_bj_qmxk_member_info` WHERE member_id='{$user['member_id']}'");
			$member['avatar'] = $mi['avatar'] ? $mi['avatar'] : 'http://statics.shunliandongli.com/resource/image/avatar.png';

			$user['member_id'] = compute_id($user['member_id'], 'ENCODE');
			$member['member_id'] = compute_id($member['member_id'], 'ENCODE');
			$cookie = array('pin'=>$user['member_id'], 'wskey'=>authcode("{$user['member_id']}\t{$user['password']}\t{$user['username']}\t".substr(md5($_W['device_id']), 8, 8), 'ENCODE'));		
			//设置cookie

			isetcookie('pin', $cookie['pin'], 60*60*24*365);
			isetcookie('wskey', $cookie['wskey'], 60*60*24*365);

			$result = array(
				'reg_status'	=> 1,
				'memberInfo'	=> $member,
				'cookieInfo'	=> $cookie
			);

			return self::responseOk($result);
		} else { //未注册，获取用户信息并引导注册

			//nickname
			//headimgurl
			//openid
			//unionid
			$url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}";
			$info = weixin_curl($url);

			if(empty($info['openid'])) return self::responseError(115, '授权失败，请重试(3)。');

			$result = array(
				'reg_status'		=> 0,
				'weixin_reg_session'=> authcode("{$info['unionid']}\t{$info['openid']}\t{$info['headimgurl']}\t{$info['nickname']}\t".substr(md5($_W['device_id']), 8, 8), 'ENCODE')
			);
		}

		return self::responseOk($result);
	}

	public function checklogin() {
		global $_W, $user ,$_GPC;
		$host=strtolower($_SERVER['HTTP_HOST']);
		$host_ary=explode('.',$host);
		if($host_ary[0]=='api-test'){//测试环境，web/wap前端登录用
			if(!empty($_GPC['pin'])){
				$member_id = compute_id($_GPC['pin']);
				$user = pdo_fetch("SELECT id,mid,username,unionid,password,salt,mobile_verified,status,regtime FROM `ims_bj_qmxk_member_auth` WHERE id='{$member_id}'");
					if(!$user['status']) return false;
					
					$_W['member_id'] = $member_id ;
					$_W['mid'] = $user['mid'];
					$_W['regtime'] = $user['regtime'];
					return true;		
			}
		}
		if((isset($_COOKIE['pin']) && $_COOKIE['pin']) && (isset($_COOKIE['wskey']) && $_COOKIE['wskey'])) {
			@list($member_id, $password, $username, $device_id) = explode("\t", authcode($_COOKIE['wskey'], 'DECODE'));
			$member_id = intval($member_id);
			if((strtolower(self::$request_api) == 'games')){//h5 ajax请求，不用判断设备
				$right = (!empty($member_id) && ($member_id == $_COOKIE['pin']));
			} else {
				$right = (!empty($member_id) && ($member_id == $_COOKIE['pin']) && ($device_id == substr(md5($_W['device_id']), 8, 8)));		}
			if($right) {//微信登陆可以没有密码
				$member_id = compute_id($member_id);
				$user = pdo_fetch("SELECT id,mid,username,unionid,password,salt,mobile_verified,status,regtime FROM `ims_bj_qmxk_member_auth` WHERE id='{$member_id}'");
				if(empty($password)) {//没有密码的情况下，只允许已经绑定了微信的用户
					if(empty($user['unionid']) || strpos($user['unionid'], 'sldl_') !== false) {
						return false;
					}
				}
				if(!$user['status']) return false;
				if(($user['password'] == $password)) {
					$_W['member_id'] = $member_id ;
					$_W['mid'] = $user['mid'];
					$_W['regtime'] = $user['regtime'];
					return true;
				}
				$_W['member_id'] = 0;
				$_W['mid'] = 0;
				return false;
			}
			clearcookie();
			return false;
		}

		return false;
	}

	//退出
	public function logout() {

		clearcookie();

		return self::responseOk('退出成功。');
	}

	//注册
	//注册方式，type=username|mobile
	public function register() {
		global $_W;

		//支持扫码注册，二维码内容为上级ID

		//return self::responseError(50, '暂停注册');

		if($_POST['regType'] == 'username') {
			$username = trim($_POST['username']);
			$password = $_POST['password'];
			$captcha = trim($_POST['captcha']);

			if(empty($username)) return self::responseError(50, '请输入用户名！');
			if(empty($password)) return self::responseError(50, '请输入密码！');

			if(strlen($username) < 4) return self::responseError(50, '用户名不能小于4个字符(2个汉字)！');
			if(strlen($username) > 24) return self::responseError(50, '用户名不能小于24个字符(12个汉字)！');
			if(preg_match('/^[\d]+$/', $username)) return self::responseError(50, '用户名不能为纯数字！');
			if(preg_match('/^[sld\_](.+)$/', $username)) return self::responseError(50, '保留的用户名，请更换！');
			if(!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-_\-]+$/u', $username)) return self::responseError(50, '用户名格式不正确！');


			if($captcha) {
				if(md5($captcha . $_W['config']['setting']['authkey']) != $captcha) {
					isetcookie('captcha', '');
					message('图片验证码不正确！');
				}
				isetcookie('captcha', '');
			}

			if(pdo_fetch("SELECT id FROM `ims_bj_qmxk_member_auth` WHERE username='{$username}'")) {
				return self::responseError(50, '该用户名已存在！');
			}
		} else {
			$mobile = trim($_POST['mobile']);
			$password = $_POST['password'];
			$captcha = trim($_POST['captcha']);
			$mobile_code = trim($_POST['mobile_code']);

			if(empty($mobile)) return self::responseError(50, '请输入手机号！');
			if(empty($password)) return self::responseError(50, '请输入密码！');
			if(empty($mobile_code)) return self::responseError(50, '请输入手机验证码！');

			$username = 'sldl_'.random(12);

			$error_count = 0;
			$error_count = $_W['mc']->get(md5('api-mobile-verify-'.$mobile));
			if($error_count >= 3) {
				return self::responseError(50, '短信验证码输入错误次数太多，请5分钟后重试！');
			}

			if(!is_mobile($mobile)) return self::responseError(50, '您输入的手机号不正确！');
			if(strlen($mobile_code) != 6) return self::responseError(50, '手机验证码不正确！');
/*
			if(md5($_COOKIE['captcha'] . $_W['config']['setting']['authkey']) != $captcha) {
				isetcookie('captcha', '');
				message('图片验证码不正确！');
			}

			isetcookie('captcha', '');
*/
			if(pdo_fetch("SELECT id FROM `ims_bj_qmxk_member_auth` WHERE username='{$username}'")) {
				$username = 'sldl_'.random(12);
				if(pdo_fetch("SELECT id FROM `ims_bj_qmxk_member_auth` WHERE username='{$username}'")) {
					return self::responseError(50, '注册失败请重试(-2)');
				}
			}

			if($user = pdo_fetch("SELECT id,mobile_verified FROM `ims_bj_qmxk_member_auth` WHERE mobile='{$mobile}'")) {
				if($user['mobile_verified']) {
					return self::responseError(50, '该手机号已经被其他用户绑定，请更换！');
				}
			}

			$sms = pdo_fetch("SELECT * FROM ims_sms_log WHERE `mobile` = '{$mobile}' ORDER BY dateline DESC LIMIT 1");

			if($sms['code'] != $mobile_code || (TIMESTAMP-$sms['dateline']) > 60*10) {
				$_W['mc']->set(md5('api-mobile-verify-'.$mobile), ($error_count+1), 0, 300);
				return self::responseError(50, '您输入的短信验证码不正确！');
			}
		}

		$salt = random(8);
		$password = sha1($salt.$password.$salt);

		$insert = array(
			'username' => $username,
			'mobile' => $mobile,
			'unionid'=>'sldl_'.random(23),
			'password' => $password,
			'salt' => $salt,
			'regip' => CLIENT_IP,
			'regtime' => TIMESTAMP,
			'regdevice' => $_W['device_id'],
			'lastip' => CLIENT_IP,
			'lasttime' => TIMESTAMP,
			'lastdevice' => $_W['device_id'],
			'logintimes' => '1',
			'mobile_verified' => '1',
			'status' => '1'
		);

		//通过微信登陆进行注册
		$weixin_reg_session = isset($_COOKIE['weixin_reg_session']) ? $_COOKIE['weixin_reg_session'] : $_POST['weixin_reg_session'];
		@list($unionid, $openid, $headimgurl, $nickname, $device_id) = explode("\t", authcode($weixin_reg_session, 'DECODE'));

		if(!empty($unionid) && strlen($unionid) == 28) {
			$insert['unionid'] = $unionid;
		}
	//	return self::responseError(50, json_encode(explode("\t", authcode($weixin_reg_session, 'DECODE'))));


		//查询当前用是否通过别人的分享注册的
		$dateline = TIMESTAMP - 60*60*10; //有效期10小时
		$hash = substr(md5(preg_replace('/\.\d+$/', '', CLIENT_IP).'-'.self::$platform), 8, 16);
		$share_info = pdo_fetch("SELECT member_id FROM `ims_app_click` WHERE hash='{$hash}' AND dateline>='{$dateline}' ORDER BY dateline DESC LIMIT 1");

		if($share_info['member_id'] > 0) {
			$insert['sharemaid'] = $share_info['member_id'];
		}

		pdo_insert('bj_qmxk_member_auth', $insert);
		$member_id = pdo_insertid();
		if(empty($member_id)) {
			return self::responseError(50, '注册失败，请重试。'.json_encode($insert));
		}

		if(!empty($headimgurl)) {
			//插入fans表，member表
		}

		$insert_member_info = array();
		$insert_member_info['member_id'] = $member_id;
		$headimgurl && $insert_member_info['avatar'] = $headimgurl;
		if(!empty($unionid) && strlen($unionid) == 28) {
			$insert_member_info['nickname'] = yz_expression($nickname);
		}

		pdo_insert('bj_qmxk_member_info', $insert_member_info);


		if($share_info['member_id'] > 0) { //通过别人的分享注册的
			$member_auth_level = pdo_fetch("SELECT m2.sharemaid AS shareid2,m3.sharemaid AS shareid3,m4.sharemaid AS shareid4,m5.sharemaid AS shareid5,".
				"m6.sharemaid AS shareid6,m7.sharemaid AS shareid7,m8.sharemaid AS shareid8,m9.sharemaid AS shareid9,m10.sharemaid AS shareid10,".
				"m11.sharemaid AS shareid11,m12.sharemaid AS shareid12,m13.sharemaid AS shareid13,m14.sharemaid AS shareid14,m15.sharemaid AS shareid15,".
				"m16.sharemaid AS shareid16,m17.sharemaid AS shareid17,m18.sharemaid AS shareid18,m19.sharemaid AS shareid19,m20.sharemaid AS shareid20 ".
				"FROM ims_bj_qmxk_member_auth m2 ".
				"LEFT JOIN ims_bj_qmxk_member_auth m3 ON m3.id=m2.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m4 ON m4.id=m3.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m5 ON m5.id=m4.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m6 ON m6.id=m5.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m7 ON m7.id=m6.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m8 ON m8.id=m7.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m9 ON m9.id=m8.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m10 ON m10.id=m9.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m11 ON m11.id=m10.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m12 ON m12.id=m11.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m13 ON m13.id=m12.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m14 ON m14.id=m13.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m15 ON m15.id=m14.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m16 ON m16.id=m15.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m17 ON m17.id=m16.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m18 ON m18.id=m17.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m19 ON m19.id=m18.sharemaid ".
				"LEFT JOIN ims_bj_qmxk_member_auth m20 ON m20.id=m19.sharemaid ".
				"WHERE m2.id='{$share_info['member_id']}'");
		} else {
			$member_auth_level['shareid2'] = $member_auth_level['shareid3'] = $member_auth_level['shareid4'] = $member_auth_level['shareid5'] = $member_auth_level['shareid6'] = $member_auth_level['shareid7'] = $member_auth_level['shareid8'] = $member_auth_level['shareid9'] = $member_auth_level['shareid10'] = $member_auth_level['shareid11'] = $member_auth_level['shareid12'] = $member_auth_level['shareid13'] = $member_auth_level['shareid14'] = $member_auth_level['shareid15'] = $member_auth_level['shareid16'] = $member_auth_level['shareid17'] = $member_auth_level['shareid18'] = $member_auth_level['shareid19'] = $member_auth_level['shareid20'] = 0;
		}

		pdo_insert('bj_qmxk_member_auth_shareid', array(
			'member_id'=>$member_id,
			'shareid'=>$share_info['member_id'],
			'shareid2'=>$member_auth_level['shareid2'],
			'shareid3'=>$member_auth_level['shareid3'],
			'shareid4'=>$member_auth_level['shareid4'],
			'shareid5'=>$member_auth_level['shareid5'],
			'shareid6'=>$member_auth_level['shareid6'],
			'shareid7'=>$member_auth_level['shareid7'],
			'shareid8'=>$member_auth_level['shareid8'],
			'shareid9'=>$member_auth_level['shareid9'],
			'shareid10'=>$member_auth_level['shareid10'],
			'shareid11'=>$member_auth_level['shareid11'],
			'shareid12'=>$member_auth_level['shareid12'],
			'shareid13'=>$member_auth_level['shareid13'],
			'shareid14'=>$member_auth_level['shareid14'],
			'shareid15'=>$member_auth_level['shareid15'],
			'shareid16'=>$member_auth_level['shareid16'],
			'shareid17'=>$member_auth_level['shareid17'],
			'shareid18'=>$member_auth_level['shareid18'],
			'shareid19'=>$member_auth_level['shareid19'],
			'shareid20'=>$member_auth_level['shareid20']
		), true);

		$member_id = compute_id($member_id, 'ENCODE');

		$cookie = array('pin'=>$member_id, 'wskey'=>authcode("{$member_id}\t{$insert['password']}\t{$insert['username']}\t".substr(md5($_W['device_id']), 8, 8), 'ENCODE'));

		//设置cookie
		isetcookie('pin', $cookie['pin'], 60*60*24*365);
		isetcookie('wskey', $cookie['wskey'], 60*60*24*365);

		$result = array(
			'cookieInfo'	=> $cookie
	//		'debug'			=> json_encode(explode("\t", authcode($weixin_reg_session, 'DECODE')))
		);

		return self::responseOk($result);
	}

	//验证手机号是否存在
	public function isexistMobile() {
		global $_W;


	}

	//验证用户名是否存在
	public function isexistUsername() {
		global $_W;

	}

	//找回密码
	public function forget() {
		global $_W;

		$username = trim($_POST['username']);
		$captcha = trim($_POST['captcha']);
		$session_id = trim($_POST['session_id']);
		$password = $_POST['password'];
		$mobile_code = trim($_POST['mobile_code']);
		$user_type = 'username';


		if($session_id && $mobile_code) { //第二步
			$session_username = $_W['mc']->get($session_id);

			if(empty($username)) {
				return self::responseError(50, '参数错误[username].');
			}
			if($username != $session_username) {
				return self::responseError(50, '验证失败，请重新找回.');
			}

			if(empty($password)) return self::responseError(50, '请输入新密码.');

			if(strlen($mobile_code) != 6) return self::responseError(50, '手机验证码不正确！');

			if(is_mobile($username)) $user_type = 'mobile';

			if($user_type == 'mobile') {
				$user_info = pdo_fetch("SELECT mobile,mobile_verified FROM `ims_bj_qmxk_member_auth` WHERE mobile='{$username}'");
			} else {
				$user_info = pdo_fetch("SELECT mobile,mobile_verified FROM `ims_bj_qmxk_member_auth` WHERE username='{$username}'");
			}


			if(empty($user_info['mobile'])) return self::responseError(50, '您尚未绑定手机号，无法在线找回密码，请联系客服.');
			if(empty($user_info['mobile_verified'])) return self::responseError(50, '您的手机号尚未验证，无法在线找回密码，请联系客服.');

			$error_count = 0;
			$error_count = $_W['mc']->get(md5('api-mobile-verify-'.$user_info['mobile']));
			if($error_count >= 3) {
				return self::responseError(50, '短信验证码输入错误次数太多，请5分钟后重试！');
			}

			$sms = pdo_fetch("SELECT * FROM ims_sms_log WHERE `mobile` = '{$user_info['mobile']}' ORDER BY dateline DESC LIMIT 1");

			if($sms['code'] != $mobile_code || (TIMESTAMP-$sms['dateline']) > 60*10) {
				$_W['mc']->set(md5('api-mobile-verify-'.$mobile), ($error_count+1), 0, 300);
				return self::responseError(50, '您输入的短信验证码不正确！');
			}

			$salt = random(8);
			$password = sha1($salt.$password.$salt);

			pdo_update('bj_qmxk_member_auth', array(
				'salt'		=> $salt,
				'password'	=> $password
			), array(
				'mobile' => $user_info['mobile']
			));

			return self::responseOk('密码修改成功,请使用过新密码登陆.');
		} else { //第一步
			if(empty($username)) return self::responseError(50, '请输入用户名或手机号码.');
			if(empty($captcha)) return self::responseError(50, '请输入图片验证码.');

			if(is_mobile($username)) $user_type = 'mobile';

			$hash = md5($captcha . $_W['config']['setting']['authkey']);
			if($_COOKIE['captcha'] != $hash) {
				isetcookie('captcha', '');
				return self::responseError(50, '你输入的图片验证码不正确, 请重新输入.');
			}
			isetcookie('captcha', '');

			if($user_type == 'mobile') {
				$user_info = pdo_fetch("SELECT mobile,mobile_verified FROM `ims_bj_qmxk_member_auth` WHERE mobile='{$username}'");
			} else {
				$user_info = pdo_fetch("SELECT mobile,mobile_verified FROM `ims_bj_qmxk_member_auth` WHERE username='{$username}'");
			}

			if(empty($user_info['mobile'])) return self::responseError(50, '您尚未绑定手机号，无法在线找回密码，请联系客服.');
			if(empty($user_info['mobile_verified'])) return self::responseError(50, '您的手机号尚未验证，无法在线找回密码，请联系客服.');

			self::mobileCode(true, $user_info['mobile'], '', false);

			$session_id = md5('forget-'.$username.'-'.CLIENT_IP);
			$_W['mc']->set($session_id, $username, 0, 600);

			$result = array(
				'msg'			=> '验证码已发送到您的手机.',
				'session_id'	=> $session_id
			);

			return self::responseOk($result);
		}
	}

	//获取图片验证码
	public function captcha() {
		global $_W;

		$alphanum = '0123456789';
		$num = 4;
		$width	= 160;
		$height	= 32;
		$swirl	= 10;
		$offset_y = 10;
		$font_size = 32;
		$swirl = 10;
		$expire = 300;

		$Imagick = new Imagick();
		$Draw = new ImagickDraw();

		$Draw->setFont(IA_ROOT . '/source/libs/fonts/captcha4.ttf');
		$Draw->setFontSize($font_size);

		$Imagick->newImage($width-2, $height-2, new ImagickPixel('rgb('.mt_rand(220, 255).' , '.mt_rand(220, 255).' , '.mt_rand(220, 255).')'));
		$Imagick->borderImage(new ImagickPixel('black'), 1, 1);

		for($i=0; $i<$num; $i++) {
			$code = mb_substr(str_shuffle($alphanum), mt_rand(0, strlen($alphanum) - 1), 1);
			empty($code) && $code = mb_substr($alphanum, mt_rand(0, strlen($alphanum) - 1), 1);
			empty($code) && $code = '0';
			$j = !$i ? 2 : $j+$width/$num;
			$Draw->setFillColor(new ImagickPixel('rgb('.mt_rand(0, 180).' , '.mt_rand(0, 180).' , '.mt_rand(0, 255).')'));
			$Imagick->annotateImage($Draw, $j, mt_rand(18, 22), mt_rand($offset_y+2, $offset_y+15), $code);
			$randcode .= $code;
		}

		$Pixel = new ImagickPixel();
		for($i=0; $i<6; $i++) {
			$Pixel->setColor('rgb('.mt_rand(20, 160).' , '.mt_rand(20, 160).' , '.mt_rand(20, 160).')');
			$Draw->setFillColor($Pixel);
			$Draw->line(mt_rand(0, $width-2), mt_rand(0, $height-2), mt_rand(0, $width-2), mt_rand(0, $height-2));
		}

		for($i=0; $i<$num*20; $i++) {
			$Pixel->setColor('rgb('.mt_rand(0, 255).' , '.mt_rand(0, 255).' , '.mt_rand(0, 255).')');
			$Draw->setFillColor($Pixel);
			$Draw->point(mt_rand(0, $width-2), mt_rand(0, $height-2));
		}

		$Imagick->swirlImage($swirl);
		$Imagick->drawImage($Draw);
		$Imagick->setImageFormat('png');

		header('Expires: -1');
		header('Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0', FALSE);
		header('Pragma: no-cache');
		//header('Content-Disposition: attachment; filename=fcm_captcha.png');
		header('Content-Type: image/png');

		//isetcookie('captcha', authcode(substr(md5($_SERVER['HTTP_USER_AGENT'].strtolower($randcode)), 8, 6), 'ENCODE', '', $expire));
		$hash = md5($randcode . $_W['config']['setting']['authkey']);
		isetcookie('captcha', $hash);

		//!self::$test_mode && self::cookie($randcode);
		echo $Imagick->getImageBlob();
		$Draw->clear();
		$Draw->destroy();
		$Imagick->clear();
		$Imagick->destroy();
		exit;
	}

	public function check_captcha() {
		//
	}

	public function createQrcode() {

	}

	public function scanQrcode() {

	}

	public function readQrcode() {

	}
	
	public function getUser(){
		global $_W;
		$mobile = trim($_POST['mobile']);
		$sign = trim($_POST['sign']);
		if(!is_mobile($mobile)) return self::responseError(50, '暂不支持您输入的手机号码！');
		$sign_string = $mobile.ConfigModel::$SECRET_KEY;
		if($sign != md5($sign_string.md5(ConfigModel::$SECRET_KEY))){
			return self::responseError(51, '非法请求！');
		}
		$user = pdo_fetch(
		"SELECT ma.id AS member_id,ma.unionid,mi.nickname,mi.avatar,mi.order_credit_count,m.from_user AS openid,m.id AS mid ".
		"FROM ims_bj_qmxk_member_auth AS ma ". 
		"LEFT JOIN ims_bj_qmxk_member AS m ON m.member_id = ma.id ". 
		"LEFT JOIN ims_bj_qmxk_member_info AS mi ON mi.member_id = ma.id ".
		"WHERE ma.mobile='$mobile' ");
		
		if(empty($user['member_id'])){
			return self::responseError(52, '该手机号未在顺联注册！');
		}
		
		if(empty($user['mid'])){
			$user['ID'] = 'u'.compute_id($user['member_id'], 'ENCODE');
		}else{
			$user['ID'] = compute_id($user['mid'], 'ENCODE');
		}
		unset($user['mid']);
		$user['vip_level'] = CreditModel::getInstance()->calVipLevelByCredit($user['order_credit_count']);
		unset($user['order_credit_count']);
		return self::responseOk($user);
	}


	//获取手机验证码
	//
	public function mobileCode($internalCall = false, $mobile='', $captcha='', $need_captcha=true) {
		global $_W;

		if(!$internalCall) {
			$mobile = trim($_POST['mobile']);
			$captcha = trim($_POST['captcha']);
		}

		if(empty($mobile)) return self::responseError(50, '请输入手机号码！');
		if(empty($captcha) && $need_captcha) return self::responseError(50, '请输入图片验证码！');

		if(($mobile!='oldmobile' && $mobile!='login_mobile') && !is_mobile($mobile)) return self::responseError(50, '暂不支持您输入的手机号码！');

		if($need_captcha) {
			$hash = md5($captcha . $_W['config']['setting']['authkey']);
			if($_COOKIE['captcha'] != $hash) {
				isetcookie('captcha', '');
				return self::responseError(50, '你输入的图片验证码不正确, 请重新输入.');
			}
			isetcookie('captcha', '');
		}

		if($mobile == 'oldmobile') {
			$mobile_info = pdo_fetch("SELECT safe_mobile,mobile_verified FROM ims_members WHERE `uid` = '{$_W['member_id']}'");
			if(empty($mobile_info['safe_mobile']) || !$mobile_info['safe_mobile'] || !$mobile_info['mobile_verified']) {
				return self::responseError(50, '没有已经绑定的手机号。');
			}
			$mobile = $mobile_info['safe_mobile'];
		} elseif($mobile == 'login_mobile') {
			if(!preg_match('/^[a-f0-9]{32}$/', $_POST['checksms'])) {
				return self::responseError(50, '登录中发生错误，请重新登录。');
			}

			$record = $_W['mc']->get($_POST['checksms']);
			$record = unserialize($record);
			$checksms_key = md5($record['member_id'].'-'.CLIENT_IP.'-'.$record['lastvisit']);

			if($record['member_id'] && $record['lastvisit'] && $record['mobile'] && ($checksms_key == $_POST['checksms'])) {
				$mobile_info = pdo_fetch("SELECT safe_mobile,mobile_verified FROM ims_members WHERE `uid` = '{$record['member_id']}'");
				if(empty($mobile_info['safe_mobile']) || !$mobile_info['safe_mobile'] || !$mobile_info['mobile_verified']) {
					return self::responseError(50, '没有已经绑定的手机号。');
				}
				$mobile = $mobile_info['safe_mobile'];
			} else {
				return self::responseError(50, '登录中发生错误，请重新登录。');
			}
		} else {
			if(!$internalCall) {
				$mobile_info2 = pdo_fetch("SELECT id,mobile_verified FROM `ims_bj_qmxk_member_auth` WHERE `mobile` = '{$mobile}'");
				if($mobile_info2['mobile_verified']) {
					if($mobile_info2['id'] != $_W['member_id']) {
						return self::responseError(50, '您输入的手机号已被其他账号绑定！');
					} else {
						return self::responseError(50, '您已绑定过该手机！');
					}
				}
			}
		}

		//60秒获取1次，5分钟最多三次，一天最多60次
		//查询上次发送时间，间隔60s
		$item = pdo_fetch("SELECT dateline FROM ims_sms_log WHERE mobile='{$mobile}' ORDER BY id DESC LIMIT 1");
		if(TIMESTAMP - 60 < $item['dateline']) {
			return self::responseError(50, '请'.(60-(TIMESTAMP-$item['dateline'])).'秒后再试。');
		}

		//查询5分钟发送次数，最多3次
		$time_begin = TIMESTAMP - 5*60;
		$item = pdo_fetch("SELECT COUNT(*) AS count FROM ims_sms_log WHERE mobile='{$mobile}' AND dateline>'{$time_begin}'");
		if($item['count'] >= 3) {
			return self::responseError(50, '5分钟内最多发送三次，请稍后重试。');
		}

		//查询一天发送次数，最多60次
		$time_begin = strtotime('today');
		$item = pdo_fetch("SELECT COUNT(*) AS count FROM ims_sms_log WHERE mobile='{$mobile}' AND dateline>'{$time_begin}'");
		if($item['count'] >= 60) {
			return self::responseError(50, '运营商限制一天最多发送60条短信，请明天再试。');
		}

		$smscode = random(6, 1);

		$SMS_obj = new SMS();

		$SMS_obj->setParameter('mobile', $mobile);
		$SMS_obj->setParameter('text', $smscode);

		$result = $SMS_obj->send_code();

		if($result['code'] == 0) {
			$sms_log['member_id'] = $_W['member_id'];
			$sms_log['dateline'] = TIMESTAMP;
			$sms_log['mobile'] = $mobile;
			$sms_log['code'] = $smscode;
			$sms_log['sid'] = $result['result']['sid'];
			pdo_insert('sms_log', $sms_log);

			if(!$internalCall) {
				return self::responseOk('OK');
			} else {
				return true;
			}
		} else {
			//print_r($result);
			if(!$internalCall) {
				return self::responseError(50, '发送失败，请稍后重试。');
			} else {
				return false;
			}
		}
	}
}