<?php

class Setting extends My {

    private static $account_info = array();

	//核心账户信息
	function __construct() {
		global $_W;
		//$_W['member_id'] = '16184536';

		self::$account_info = pdo_fetch("SELECT ma.mid, ma.username, ma.mobile, ma.unionid, ma.password, ma.mobile_verified ".
			"FROM `ims_bj_qmxk_member_auth` ma ".
			"WHERE ma.id='{$_W['member_id']}'");

		self::$account_info['username_status'] = 0; //是否设置用户名
		self::$account_info['password_status'] = 0; //是否设置密码
		self::$account_info['mobile_status'] = 0; //是否填写手机
		self::$account_info['mobile_verified_status'] = 0; //手机是否已验证
		self::$account_info['weixin_bind'] = 0; //是否绑定微信
		self::$account_info['weixin_mp_status'] = 0; //是否微信端存量用户

		if(strpos(self::$account_info['username'], 'sldl_') === false) {
			self::$account_info['username_status'] = 1;
		}

		if(strlen(self::$account_info['password']) == 40) {
			self::$account_info['password_status'] = 1;
		}

		if(is_mobile(self::$account_info['mobile'])) {
			self::$account_info['mobile_status'] = 1;
		}

		if(self::$account_info['mobile_verified']) {
			self::$account_info['mobile_verified_status'] = 1;
		}

		if(strpos(self::$account_info['unionid'], 'sldl_') === false) {
			self::$account_info['weixin_bind'] = 1;
		}

		if(self::$account_info['mid'] > 0) {
			self::$account_info['weixin_mp_status'] = 1;
		}
	}

	//设置登陆账号
	public function setUsername() {
		global $_W;

		//检查登录账号是否已设置
		if(self::$account_info['username_status'] > 0) return self::responseError(9100, '您已经设置了用户名.');

		$username = trim($_POST['username']);

		if(empty($username)) return self::responseError(9101, '请输入用户名.');

		if(strlen($username) < 4) return self::responseError(9102, '用户名不能小于4个字符(2个汉字).');
		if(strlen($username) > 24) return self::responseError(9103, '用户名不能小于24个字符(12个汉字).');
		if(preg_match('/^[\d]+$/', $username)) return self::responseError(9104, '用户名不能为纯数字.');
		if(preg_match('/^[sld\_](.+)$/', $username)) return self::responseError(9105, '保留的用户名，请更换.');
		if(!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-_\-]+$/u', $username)) return self::responseError(9106, '用户名格式不正确.');

		if(pdo_fetch("SELECT id FROM `ims_bj_qmxk_member_auth` WHERE username='{$username}'")) {
			return self::responseError(9107, '该用户名已存在.');
		}

		pdo_update('bj_qmxk_member_auth', array('username' => $username), array('id' => $_W['member_id']));

		return self::responseOk('设置成功.');
	}

	//设置昵称
	public function setNickname() {
		global $_W;

		//昵称违规关键字
		$nickname = trim($_POST['nickname']);

		if(preg_match("/[\x{7ea2}]+(.*)[\x{5305}]+/u", $nickname))  return self::responseError(9200, '昵称中含有违规字符.');
		if(strpos($nickname, '顺联') !== false)  return self::responseError(9200, '昵称中含有违规字符.');

		$member_info = pdo_fetch("SELECT member_id FROM `ims_bj_qmxk_member_info` WHERE member_id='{$_W['member_id']}'");
		if($member_info['member_id']) {
			pdo_update('bj_qmxk_member_info', array('nickname' => $nickname), array('member_id' => $_W['member_id']));
		} else {
			pdo_insert('bj_qmxk_member_info', array('member_id' => $_W['member_id'], 'nickname' => $nickname));
		}

		return self::responseOk('设置成功.');
	}

	//设置手机号
	public function setMobile() {
		global $_W;

		//检查手机号是否已绑定
		if(self::$account_info['mobile_verified_status']) return self::responseError(9300, '您已经绑定过手机号.');

		$mobile = trim($_POST['mobile']);
		$mobile_code = trim($_POST['mobile_code']);

		if(empty($mobile)) return self::responseError(9301, '请输入手机号.');
		if(empty($mobile_code)) return self::responseError(9302, '请输入手机验证码.');

		if(!is_mobile($mobile)) return self::responseError(9303, '请输入正确的手机号.');

		$error_count = 0;
		$error_count = $_W['mc']->get(md5('api-mobile-verify-'.$mobile));
		if($error_count >= 3) {
			return self::responseError(50, '短信验证码输入错误次数太多，请5分钟后重试.');
		}

		if(!is_mobile($mobile)) return self::responseError(9304, '您输入的手机号不正确.');
		if(strlen($mobile_code) != 6) return self::responseError(9305, '手机验证码不正确.');

		if($user = pdo_fetch("SELECT id,mobile_verified FROM `ims_bj_qmxk_member_auth` WHERE mobile='{$mobile}'")) {
			if($user['id'] != $_W['member_id'] && $user['mobile_verified']) {
				return self::responseError(9306, '该手机号已经被其他用户绑定，请更换.');
			}
		}

		$sms = pdo_fetch("SELECT * FROM `ims_sms_log` WHERE `mobile` = '{$mobile}' ORDER BY dateline DESC LIMIT 1");

		if($sms['code'] != $mobile_code || (TIMESTAMP-$sms['dateline']) > 60*10) {
			$_W['mc']->set(md5('api-mobile-verify-'.$mobile), ($error_count+1), 0, 300);
			return self::responseError(9307, '您输入的短信验证码不正确.');
		}

		pdo_update('bj_qmxk_member_auth', array('mobile'=>$mobile,'mobile_verified'=>1), array('id' => $_W['member_id']));

		return self::responseOk('设置成功.');
	}

	//更换手机号
	//如果已验证手机号，需要手机验证码
	//如果未验证手机号，直接验证当前手机号
	//step=1|2
	public function changeMobile() {
		global $_W;

		//检查当前是否绑定手机号
		if(!self::$account_info['mobile_verified_status']) return self::responseError(9400, '您尚未绑定手机.');

		$step = intval($_POST['step']);
		$step = $step ? $step : 1;

		if($step == 1) { //验证原手机号
			$mobile = self::$account_info['mobile'];
			$mobile_code = trim($_POST['mobile_code']);
			if(empty($mobile_code)) return self::responseError(9401, '请输入手机验证码.');

			$error_count = 0;
			$error_count = $_W['mc']->get('api-mobile-verify-'.$_W['member_id']);
			if($error_count >= 3) return self::responseError(9402, '短信验证码输入错误次数太多，请5分钟后重试.');

			$sms = pdo_fetch("SELECT * FROM ims_sms_log WHERE `member_id`='{$_W['member_id']}' ORDER BY id DESC LIMIT 1");
			if($sms['mobile'] == $mobile && $sms['code'] == $mobile_code && (TIMESTAMP-$sms['dateline']) <= 60*10 ) {
				$_W['mc']->set('api-modify_mobile-'.$_W['member_id'], '1', 0, 1800);//后续加入session验证，退出则验证状态失效
				$_W['mc']->delete('api-mobile-verify-'.$_W['member_id']);

				return self::responseOk('原手机验证成功.');
			} else {
				$_W['mc']->set('api-mobile-verify-'.$_W['member_id'], ($error_count+1), 0, 300);

				return self::responseError(9403, '您输入的短信验证码不正确.');
			}
		} else { //验证新手机号
			//是否处于修改进程中
			if(!$_W['mc']->get('api-modify_mobile-'.$_W['member_id'])) return self::responseError(9404, '请先验证旧手机.');

			//新验证码是否正确
			$mobile = trim($_POST['mobile']);
			$mobile_code = trim($_POST['mobile_code']);

			if(empty($mobile)) return self::responseError(9405, '请输入手机号码！.');
			if(empty($mobile_code)) return self::responseError(9406, '请输入手机验证码.');

			!is_mobile($mobile) && message('请输入正确的手机号.');

			$error_count = 0;
			$error_count = $_W['mc']->get('api-mobile-verify-'.$_W['member_id']);
			if($error_count >= 3) return self::responseError(9407, '短信验证码输入错误次数太多，请5分钟后重试.');

			if($user = pdo_fetch("SELECT id,mobile_verified FROM `ims_bj_qmxk_member_auth` WHERE mobile='{$mobile}'")) {
				if($user['id'] != $_W['member_id'] && $user['mobile_verified']) {
					return self::responseError(9408, '该手机号已经被其他用户绑定，请更换.');
				}
			}

		//	$sms = pdo_fetch("SELECT * FROM ims_sms_log WHERE `member_id` = '{$_W['member_id']}' ORDER BY id DESC LIMIT 1");
		//这样做会导致别人的手机号被当前用户验证
			$sms = pdo_fetch("SELECT * FROM ims_sms_log WHERE `mobile` = '{$mobile}' ORDER BY id DESC LIMIT 1");
			if($sms['mobile'] == $mobile && $sms['code'] == $mobile_code && (TIMESTAMP-$sms['dateline']) <= 60*10 ) {

				pdo_update('bj_qmxk_member_auth', array('mobile'=>$mobile, 'mobile_verified'=>1), array('id' => $_W['member_id']));

				$_W['mc']->delete('api-modify_mobile-'.$_W['member_id']);
				$_W['mc']->delete('api-mobile-verify-'.$_W['member_id']);
	
				return self::responseOk('新手机绑定成功.');
			} else {

				//pdo_update('bj_qmxk_member_auth', array('mobile'=>$mobile, 'mobile_verified'=>0), array('id' => $_W['member_id']));
	
				$_W['mc']->set('api-mobile-verify-'.$_W['member_id'], ($error_count+1), 0, 300);

				return self::responseError(9409, '您输入的短信验证码不正确.');
			}
		}

		return self::responseError(9410, '未知错误.');
	}

	//设置密码
	//仅针对微信端存量尚未设置密码的用户
	public function setPassword() {
		global $_W;

		//检查密码是否已设置
		if(self::$account_info['password_status'] > 0) return self::responseError(9500, '您已经设置了密码.');

		$password = $_POST['password'];
		if(empty($password)) return self::responseError(9501, '请输入密码.');

		$salt = random(8);
		$password = sha1($salt.$password.$salt);

		pdo_update('bj_qmxk_member_auth', array('password'=>$password,'salt'=>$salt), array('id' => $_W['member_id']));


		$member_id = compute_id($_W['member_id'], 'ENCODE');
		$cookie = array('pin'=>$member_id, 'wskey'=>authcode("{$member_id}\t{$password}\t{$user['username']}\t".substr(md5($_W['device_id']), 8, 8), 'ENCODE'));
		//设置cookie

		isetcookie('pin', $cookie['pin'], 60*60*24*365);
		isetcookie('wskey', $cookie['wskey'], 60*60*24*365);

		$result = array(
			'cookieInfo'	=> $cookie
		);

		return self::responseOk($result);

		return self::responseOk('密码设置成功.');
	}


	//修改密码前置检查
	public function changePasswordCheckEnv() {
		global $_W;

		$checkType = '';

		if(self::$account_info['password_status'] > 0) {
			$checkType = 'oldpassword';
		} else {
			$checkType = 'none';
		}

		if(self::$account_info['mobile_status']) {
			$checkType = 'mobile';
		}

		return self::responseOk(array('checkType' => $checkType));
		//return self::responseOk(array('checkType' => $checkType,'member_id'=>compute_id($_W['member_id'], 'ENCODE'), 'cookie'=>explode("\t", authcode($_COOKIE['wskey'], 'DECODE'))));
	}

	//修改密码
	//如果已验证手机号，需要手机验证码
	//如果未验证手机号，需要输入当前密码
	//如果当前密码没有设置，直接设置密码
	public function changePassword() {
		global $_W, $user;

		$oldpassword = $_POST['oldpassword'];
		$password = $_POST['password'];
		$mobile_code = trim($_POST['mobile_code']);

		if(self::$account_info['password_status']) {
			if(empty($mobile_code)) return self::responseError(9600, '请输入短信验证码.');

			$error_count = 0;
			$error_count = $_W['mc']->get('api-mobile-verify-'.$_W['member_id']);
			if($error_count >= 3) return self::responseError(9601, '短信验证码输入错误次数太多，请5分钟后重试.');

			$sms = pdo_fetch("SELECT * FROM ims_sms_log WHERE `member_id` = '{$_W['member_id']}' ORDER BY id DESC LIMIT 1");
			if(!($sms['mobile'] == self::$account_info['mobile'] && $sms['code'] == $mobile_code && (TIMESTAMP-$sms['dateline']) <= 60*10)) {
				$_W['mc']->set('api-mobile-verify-'.$_W['member_id'], ($error_count+1), 0, 300);
				return self::responseError(9602, '您输入的短信验证码不正确.');
			}
		} else {
			if(self::$account_info['password_status']) {
				if(empty($oldpassword)) return self::responseError(9603, '请输入旧密码.');
				$oldpassword = sha1($user['salt'].$oldpassword.$user['salt']);
				if($oldpassword != $user['password']) return self::responseError(9604, '您输入的旧密码不正确.');
			}
		}

		if(empty($password)) return self::responseError(9605, '请输入新密码.');

		$salt = random(8);
		$password = sha1($salt.$password.$salt);

		pdo_update('bj_qmxk_member_auth', array('password'=>$password, 'salt'=>$salt), array('id' => $_W['member_id']));

		$member_id = compute_id($_W['member_id'], 'ENCODE');
		$cookie = array('pin'=>$member_id, 'wskey'=>authcode("{$member_id}\t{$password}\t{$user['username']}\t".substr(md5($_W['device_id']), 8, 8), 'ENCODE'));
		//设置cookie

		isetcookie('pin', $cookie['pin'], 60*60*24*365);
		isetcookie('wskey', $cookie['wskey'], 60*60*24*365);

		$result = array(
			'cookieInfo'	=> $cookie
		);

		return self::responseOk($result);

		return self::responseOk('密码修改成功.');
	}

	//绑定微信
	public function bindWeixin() {
		global $_W;

		if(self::$account_info['weixin_bind']) return self::responseError(9700, '您已经绑定过微信号.');

		$code = trim($_POST['code']);
		$state = trim($_POST['state']);

		//判断参数
		if(empty($code)) return self::responseError(9701, 'Parameter [code] is missing.');
		if(empty($state)) return self::responseError(9702, 'Parameter [state] is missing.');
		if($state != $_W['device_id']) return self::responseError(9703, 'Parameter [state] is invalid.'.$_W['device_id']);

		//获取access_token
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$_W['config']['weixin']['app']['appid']}&secret={$_W['config']['weixin']['app']['secret']}&code={$code}&grant_type=authorization_code";
		$token = weixin_curl($url);

		$access_token = $token['access_token'];
		$openid	= $token['openid'];
		$unionid = $token['unionid'];
		$scope = $token['scope'];

		//判断是否获取成功
		if(!empty($token['errcode'])) return self::responseError(9704, '授权失败，请重试。'.$token);
		if(empty($access_token) || empty($openid) || empty($unionid) || empty($scope)) {
			return self::responseError(9705, '授权失败，请重试(2)。'.$token);
		}

		$url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}";
		$info = weixin_curl($url);

		if(empty($info['openid'])) return self::responseError(9706, '授权失败，请重试(3)。');

		$result = array(
			'reg_status'		=> 0,
			'weixin_reg_session'=> authcode("{$info['unionid']}\t{$info['openid']}\t{$info['headimgurl']}\t{$info['nickname']}\t".substr(md5($_W['device_id']), 8, 8), 'ENCODE')
		);

		$user = pdo_fetch("SELECT id FROM `ims_bj_qmxk_member_auth` WHERE `unionid` = '{$info['unionid']}'");
		if($user['id'] && $user['id'] != $_W['member_id']) {
			return self::responseError(9707, '该微信号已被其他用户绑定.');
		}

		//插入member_info表(覆盖插入)
		$member_info = pdo_fetch("SELECT * FROM `ims_bj_qmxk_member_info` WHERE member_id='{$_W['member_id']}'");
		if(empty($member_info)) {
			$member_info_insert = array();
			$member_info_insert['nickname'] = $info['nickname'];
			$member_info_insert['avatar'] = $info['headimgurl'];
			pdo_insert('bj_qmxk_member_info', $member_info_insert, true);
		}

		//如果这个微信用户以后访问微信端，自动关联

		pdo_update('bj_qmxk_member_auth', array('unionid'=>$info['unionid']), array('id' => $_W['member_id']));

		return self::responseOk('微信绑定成功.');
	}

	//设置推荐人(预览)
	public function setShareidPreview() {
		global $_W;

		$shareid = $_POST['shareid'];
		if(empty($shareid)) return self::responseError(9900, '请输入您要设置的推荐人.');

		if(preg_match('/^u\d+$/', $shareid)) { //ma表ID
			$shareid = compute_id(str_replace('u', '', $shareid));
			$type = 'ma';
		} else { //member表ID
			$shareid = compute_id($shareid);
			$type = 'member';
		}

		if(empty($shareid)) return self::responseError(9901, '您输入的推荐人ID不存在.');

		$shareUserInfo = array();

		if($type == 'ma') {
			$shareuser = pdo_fetch("SELECT member_id,nickname,avatar FROM `ims_bj_qmxk_member_info` WHERE member_id='{$shareid}'");
			if(empty($shareuser)) return self::responseError(9901, '您输入的推荐人ID不存在.');

			$shareUserInfo['uid'] = 'u'.compute_id($shareuser['member_id'], 'ENCODE');
			$shareUserInfo['nickname'] = $shareuser['nickname'];
			$shareUserInfo['avatar'] = $shareuser['avatar'];
		} else {
			$shareuser = pdo_fetch("SELECT m.id,m.realname,f.avatar FROM `ims_bj_qmxk_member` m ".
				"LEFT JOIN `ims_fans` f ON f.from_user=m.from_user WHERE m.id='{$shareid}'");
			if(empty($shareuser)) return self::responseError(9901, '您输入的推荐人ID不存在.');

			$shareUserInfo['uid'] = compute_id($shareuser['id'], 'ENCODE');
			$shareUserInfo['nickname'] = $shareuser['realname'];
			$shareUserInfo['avatar'] = $shareuser['avatar'];
		}

		$result = array(
			'shareUserInfo' => $shareUserInfo
		);

		return self::responseOk($result);
	}

	//设置推荐人
	public function setShareid() {
		global $_W;

		$shareid = trim($_POST['shareid']);
		if(empty($shareid)) return self::responseError(9900, '请输入您要设置的推荐人.');

		if(preg_match('/^u\d+$/', $shareid)) { //ma表ID
			$shareid = compute_id(str_replace('u', '', $shareid));
			$type = 'ma';
		} else { //member表ID
			$shareid = compute_id($shareid);
			$type = 'member';
		}

		if(empty($shareid)) return self::responseError(9901, '您输入的推荐人ID不存在.');


		$member = pdo_fetch("SELECT shareid,from_user,id,member_id,realname FROM ims_bj_qmxk_member WHERE id='{$_W['mid']}'");
		
		if($member['shareid'] > 0) {
			return self::responseError(9902, '您已经有推荐人，无法再次变更.');
		}

		if(($type == 'ma' && $shareid == $_W['member_id']) || ($type == 'member' && $shareid == $_W['mid'])) {
			return self::responseError(9903, '不能指定自己为推荐人.');
		}

		//查询自己下20级情况
		$share_user = pdo_fetch("SELECT id,shareid FROM ims_bj_qmxk_member WHERE id='{$shareid}'");

		if(empty($share_user)) {
			return self::responseError(9904, '您输入的推荐人ID不存在(2).');
		}

		$share_user2 = pdo_fetch("SELECT id,shareid FROM ims_bj_qmxk_member WHERE id='{$share_user['shareid']}'");
		if($share_user2['id'] == $_W['mid']) {
			return self::responseError(9905, '不能指定自己的下级为推荐人.');
		}

		for ($i = 2; $i <= 20; $i++) {
			if($share_user['shareid'] > 0) {
				$share_user = pdo_fetch("SELECT id,shareid FROM ims_bj_qmxk_member WHERE id='{$share_user['shareid']}'");
				if($share_user['id'] == $_W['mid']) {
					return self::responseError(9906, '不能指定自己的下级为推荐人(-'.$i.')');
				}
			}
		}


		//检查被指定人关系链
		$share_user_info =  pdo_fetch("SELECT * FROM ims_bj_qmxk_member_shareid WHERE mid='{$shareid}'");
		if(empty($share_user_info)) {
			return self::responseError(9907, '您输入的推荐人ID不存在(3)');
		}

		$profileids = '';
		$share_user_info['shareid'] && $profileids .= $share_user_info['shareid'] . ",";
		$share_user_info['shareid2'] && $profileids .= $share_user_info['shareid2'] . ",";
		$share_user_info['shareid3'] && $profileids .= $share_user_info['shareid3'] . ",";
		$share_user_info['shareid4'] && $profileids .= $share_user_info['shareid4'] . ",";
		$share_user_info['shareid5'] && $profileids .= $share_user_info['shareid5'] . ",";
		$share_user_info['shareid6'] && $profileids .= $share_user_info['shareid6'] . ",";
		$share_user_info['shareid7'] && $profileids .= $share_user_info['shareid7'] . ",";
		$share_user_info['shareid8'] && $profileids .= $share_user_info['shareid8'] . ",";
		$share_user_info['shareid9'] && $profileids .= $share_user_info['shareid9'] . ",";
		$share_user_info['shareid10'] && $profileids .= $share_user_info['shareid10'] . ",";
		$share_user_info['shareid11'] && $profileids .= $share_user_info['shareid11'] . ",";
		$share_user_info['shareid12'] && $profileids .= $share_user_info['shareid12'] . ",";
		$share_user_info['shareid13'] && $profileids .= $share_user_info['shareid13'] . ",";
		$share_user_info['shareid14'] && $profileids .= $share_user_info['shareid14'] . ",";
		$share_user_info['shareid15'] && $profileids .= $share_user_info['shareid15'] . ",";
		$share_user_info['shareid16'] && $profileids .= $share_user_info['shareid16'] . ",";
		$share_user_info['shareid17'] && $profileids .= $share_user_info['shareid17'] . ",";
		$share_user_info['shareid18'] && $profileids .= $share_user_info['shareid18'] . ",";
		$share_user_info['shareid19'] && $profileids .= $share_user_info['shareid19'] . ",";
		$share_user_info['shareid20'] && $profileids .= $share_user_info['shareid20'] . ",";

		if($profileids) {
			$is_dzd = 0;
			if(preg_match('/\,$/', $profileids)) {
				$profileids = substr($profileids, 0, - 1);
			}

			$profileids_arr = explode(',', $profileids);

			foreach($profileids_arr as $profile_id) {
				if($profile_id == $member['id']) {
					return self::responseError(9908, '您是该ID的推荐人');
				}
			}
		}
		
		
		
		$share_user = pdo_fetch("SELECT id,shareid,member_id FROM ims_bj_qmxk_member WHERE id='{$shareid}'");
		pdo_update('bj_qmxk_member', array("shareid" => $share_user['id']), array('id' => $member['id']));
		$sharemaid = $share_user['member_id'];
		$mid = $member['id'];//当前用户member表id
		$seid = $share_user['id'];//上级member表id

		if($mid && $seid) {
			$member2 = pdo_fetch("SELECT m2.shareid AS shareid2,m3.shareid AS shareid3,m4.shareid AS shareid4,m5.shareid AS shareid5,".
					"m6.shareid AS shareid6,m7.shareid AS shareid7,m8.shareid AS shareid8,m9.shareid AS shareid9,m10.shareid AS shareid10,".
					"m11.shareid AS shareid11,m12.shareid AS shareid12,m13.shareid AS shareid13,m14.shareid AS shareid14,m15.shareid AS shareid15,".
					"m16.shareid AS shareid16,m17.shareid AS shareid17,m18.shareid AS shareid18,m19.shareid AS shareid19,m20.shareid AS shareid20 ".
					"FROM ims_bj_qmxk_member m2 ".
					"LEFT JOIN ims_bj_qmxk_member m3 ON m3.id=m2.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m4 ON m4.id=m3.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m5 ON m5.id=m4.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m6 ON m6.id=m5.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m7 ON m7.id=m6.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m8 ON m8.id=m7.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m9 ON m9.id=m8.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m10 ON m10.id=m9.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m11 ON m11.id=m10.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m12 ON m12.id=m11.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m13 ON m13.id=m12.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m14 ON m14.id=m13.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m15 ON m15.id=m14.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m16 ON m16.id=m15.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m17 ON m17.id=m16.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m18 ON m18.id=m17.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m19 ON m19.id=m18.shareid ".
					"LEFT JOIN ims_bj_qmxk_member m20 ON m20.id=m19.shareid ".
					"WHERE m2.id='{$seid}'");


			pdo_insert('bj_qmxk_member_shareid', array(
					'mid'=>$mid,
					'member_id'=>$member['member_id'],
					'shareid'=>$seid,
					'shareid2'=>$member2['shareid2'],
					'shareid3'=>$member2['shareid3'],
					'shareid4'=>$member2['shareid4'],
					'shareid5'=>$member2['shareid5'],
					'shareid6'=>$member2['shareid6'],
					'shareid7'=>$member2['shareid7'],
					'shareid8'=>$member2['shareid8'],
					'shareid9'=>$member2['shareid9'],
					'shareid10'=>$member2['shareid10'],
					'shareid11'=>$member2['shareid11'],
					'shareid12'=>$member2['shareid12'],
					'shareid13'=>$member2['shareid13'],
					'shareid14'=>$member2['shareid14'],
					'shareid15'=>$member2['shareid15'],
					'shareid16'=>$member2['shareid16'],
					'shareid17'=>$member2['shareid17'],
					'shareid18'=>$member2['shareid18'],
					'shareid19'=>$member2['shareid19'],
					'shareid20'=>$member2['shareid20']
				), true);
			}


			$share_user_auth = pdo_fetch("SELECT id,sharemaid FROM ims_bj_qmxk_member_auth WHERE id='{$sharemaid}'");

			$member_id = $member['member_id'];//当前用户member_auth表id
			$semaid = $share_user_auth['id'];//上级member_auth表id

			pdo_update('bj_qmxk_member_auth', array('sharemaid'=>$semaid), array('id'=>$member_id));


			if($member_id && $semaid) {
				$member2 = pdo_fetch("SELECT m2.sharemaid AS shareid2,m3.sharemaid AS shareid3,m4.sharemaid AS shareid4,m5.sharemaid AS shareid5,".
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
					"WHERE m2.id='{$semaid}'");


				pdo_insert('bj_qmxk_member_auth_shareid', array(
					'member_id'=>$member['member_id'],
					'shareid'=>$semaid,
					'shareid2'=>$member2['shareid2'],
					'shareid3'=>$member2['shareid3'],
					'shareid4'=>$member2['shareid4'],
					'shareid5'=>$member2['shareid5'],
					'shareid6'=>$member2['shareid6'],
					'shareid7'=>$member2['shareid7'],
					'shareid8'=>$member2['shareid8'],
					'shareid9'=>$member2['shareid9'],
					'shareid10'=>$member2['shareid10'],
					'shareid11'=>$member2['shareid11'],
					'shareid12'=>$member2['shareid12'],
					'shareid13'=>$member2['shareid13'],
					'shareid14'=>$member2['shareid14'],
					'shareid15'=>$member2['shareid15'],
					'shareid16'=>$member2['shareid16'],
					'shareid17'=>$member2['shareid17'],
					'shareid18'=>$member2['shareid18'],
					'shareid19'=>$member2['shareid19'],
					'shareid20'=>$member2['shareid20']
				), true);
			}
		return self::responseOk('设置成功.');
	}

	//选择头像 
	public function selectAvatar() {
		global $_W;

		$avatarUrl = 'https://img01.shunliandongli.com/avatar/system/';
		$result = array(
			'male'		=> array(
				array(
					'selected'	=> 'no',
					'avatarId'	=> '1',
					'avatar'	=> $avatarUrl.'1.png?1'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '2',
					'avatar'	=> $avatarUrl.'2.png?2'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '3',
					'avatar'	=> $avatarUrl.'3.png?3'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '4',
					'avatar'	=> $avatarUrl.'4.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '5',
					'avatar'	=> $avatarUrl.'5.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '6',
					'avatar'	=> $avatarUrl.'6.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '7',
					'avatar'	=> $avatarUrl.'7.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '8',
					'avatar'	=> $avatarUrl.'8.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '9',
					'avatar'	=> $avatarUrl.'9.png'
				)
			),
			'female'	=> array(
				array(
					'selected'	=> 'no',
					'avatarId'	=> '10',
					'avatar'	=> $avatarUrl.'10.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '11',
					'avatar'	=> $avatarUrl.'11.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '12',
					'avatar'	=> $avatarUrl.'12.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '13',
					'avatar'	=> $avatarUrl.'13.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '14',
					'avatar'	=> $avatarUrl.'14.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '15',
					'avatar'	=> $avatarUrl.'15.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '16',
					'avatar'	=> $avatarUrl.'16.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '17',
					'avatar'	=> $avatarUrl.'17.png'
				),
				array(
					'selected'	=> 'no',
					'avatarId'	=> '18',
					'avatar'	=> $avatarUrl.'18.png'
				)
			)
		);

		return self::responseOk($result);
	}

	//设置头像
	public function setAvatar() {
		global $_W;

		$avatarId = intval($_POST['avatarId']);
		if(empty($avatarId)) return self::responseError(9801, '请选择头像.');

		$avatar = 'https://img01.shunliandongli.com/avatar/system/'.$avatarId.'.png';

		$member_info = pdo_fetch("SELECT member_id FROM `ims_bj_qmxk_member_info` WHERE member_id='{$_W['member_id']}'");
		if($member_info['member_id']) {
			pdo_update('bj_qmxk_member_info', array('avatar' => $avatar), array('member_id' => $_W['member_id']));
		} else {
			pdo_insert('bj_qmxk_member_info', array('member_id' => $_W['member_id'], 'avatar' => $avatar));
		}

		return self::responseOk('设置成功.');
	}

	//上传头像
	public function uploadAvatar() {
		global $_W;

		return self::responseError(9900, '暂不支持上传头像.');
	}

	//获取推送状态
	public function getPushStatus() {
		global $_W;

		$push_status = pdo_fetch("SELECT member_id FROM `ims_app_push_status` WHERE member_id='{$_W['member_id']}'");
		$status = ($push_status && $push_status['member_id'] > 0) ? 'off' : 'on';

		return self::responseOk(array('status'=>$status));
	}

	//设置推送状态
	public function setPushStatus() {
		global $_W;

		//设置一个关闭表，关闭则记录在表中，打开则删除
		$push_status = pdo_fetch("SELECT member_id FROM `ims_app_push_status` WHERE member_id='{$_W['member_id']}'");

		if(($push_status && $push_status['member_id'] > 0)) {
			$new_status = 'on';
			pdo_delete('app_push_status', array('member_id'=>$_W['member_id']));
		} else {
			$new_status = 'off';
			pdo_insert('app_push_status', array('member_id'=>$_W['member_id']));
		}

		return self::responseOk(array('result'=>'设置成功.','status'=>$new_status));
	}

	//发送验证码
	public function sendMobileCodeToUser() {
		global $_W;

		//检查当前是否绑定手机号
		if(!self::$account_info['mobile_verified_status']) return self::responseError(9800, '您尚未绑定手机.');

		//发送验证码
		$r = User::mobileCode(true, self::$account_info['mobile'], '', false);
		if($r == true) {
			return self::responseOk('OK');
		} else {
			return self::responseError(50, '发送失败，请稍后重试。');
		}
	}

	//意见反馈
	public function report() {
		global $_W;

		$content = $_POST['content'];
		if($content) {
			pdo_insert('app_report', array(
				'member_id'=>$_W['member_id'], 
				'dateline'=>TIMESTAMP, 
				'status'=>'0',
				'content'=>$content
			));
		}

		return self::responseOk('提交成功.');
	}

}