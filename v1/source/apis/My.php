<?php

class My extends Api {
/*
    public static $agent_level_name_arr = array(
		0 => '普通创客',
		1 => '金牌创客',
		2 => '白金创客',
		3 => '钻石创客',
		4 => '皇冠创客',
		5 => '股东创客'
	);
*/
    public static $agent_level_name_arr = array(
		0 => '普通创客',
		1 => 'V1',
		2 => 'V2',
		3 => 'V3',
		4 => 'V4',
		5 => 'V5',
		6 => 'V6'
	);
	//登陆验证
	function __construct() {
		global $_W;
/**/
		require_once IA_ROOT.'/source/apis/User.php';
		if(!User::checklogin()) {
			return self::responseError(1000, '尚未登陆。');
		}

		if(empty($_W['member_id'])) {
			return self::responseError(1001, '尚未登陆。');
		}
	}
	//客服列表
	public function kefu(){
		$list = pdo_fetchall("SELECT m.uid,m.name,m.remark,pm.seller_tel as tel,'http://statics.shunliandongli.com/resource/image/logo/system.png' as headurl FROM ims_members as m left join ims_members_profile as pm on pm.uid=m.uid WHERE m.groupid = '6' AND m.status = '0'");
		//客户列表 随机随机排序开始
		$data = $list;
		$na = array();
		$num = count($data);
		$dks = array();
		for($i=0; $i<$num ; $i++){
			$dks[$i]=$i;
		}
		$num1 = $num;
		while(count($na) < $num){
			$i = rand(0,($num1-1));
			$na[] = $dks[$i];
			unset($dks[$i]);
			$num1--;
			$ndks=array();
			foreach($dks as $k=>$v){
				$ndks[]=$v;
			}
			$dks = $ndks;
		}
		$new_data = array();
		foreach($na as $k=>$v){
			$new_data[] = $data[$v];
		}
		$list=$new_data;
		//客服列表随机随机排序开始
		return self::responseOk($list);
	}

	//用户信息
	public function info($internalCall = false) {
		global $_W;

		//增加消息推送开关返回

		$needDo = array(
			'setUsername'	=> 'no',
			'setNickname'	=> 'no',
			'setPassword'	=> 'no',
			'setMobile'		=> 'no',
			'setAvatar'		=> 'no',
			'bindWexin'		=> 'yes'
		);

		$memberInfo = array();
/*
		$item = pdo_fetch("SELECT ma.id AS member_id, ma.mid, ma.username, ma.mobile, ma.unionid, ma.password,ma.regtime, ".
			"ma.mobile_verified, mi.avatar AS mi_avatar, f.avatar AS f_avatar, ".

			"m.shareid,m.commission,m.dzdflag AS is_agent,m.createtime AS reg_time,m.flagtime AS m_agent_time,m.credit2,".
			"m.credit2_freeze, m.realname,m.member_gold_count,m.group_gold_count,m.spending,m.agent_level, ".

			"mi.nickname,mi.commission AS mi_commission,mi.is_agent AS mi_is_agent,mi.agent_time AS mi_agent_time,".
			"mi.credit2 AS mi_credit2,mi.credit2_freeze AS mi_credit2_freeze,mi.member_gold_count AS mi_member_gold_count,".
			"mi.group_gold_count AS mi_group_gold_count,mi.spending AS mi_spending,mi.agent_level AS mi_agent_level ".

			"FROM `ims_bj_qmxk_member_auth` ma ".
			"LEFT JOIN `ims_bj_qmxk_member` m ON m.id=ma.mid ".
			"LEFT JOIN `ims_fans` f ON f.from_user=m.from_user ".
			"LEFT JOIN `ims_bj_qmxk_member_info` mi ON mi.member_id=ma.id ".
			"WHERE ma.id='{$_W['member_id']}'");

		if($_W['member_id'] == 18) {
			$_W['member_id'] = 79657;
		}
*/		$item = pdo_fetch("SELECT ma.id AS member_id, m.id AS mid, ma.username, ma.mobile, ma.unionid, ma.password,ma.regtime, ".
			"ma.mobile_verified, mi.avatar AS mi_avatar, f.avatar AS f_avatar, ".
			"ma.sharemaid,mi.commission,mi.is_agent,m.createtime AS reg_time,mi.agent_time,mi.credit2,".
			"mi.credit2_freeze,ms.member_gold_count,ms.group_gold_count,ms.spending,ms.agent_level, ".
			"mi.nickname, mi.order_credit_count ".
			"FROM `ims_bj_qmxk_member_auth` ma ".
			"LEFT JOIN `ims_bj_qmxk_member` m ON m.id=ma.mid ".
			"LEFT JOIN `ims_bj_qmxk_member_info` mi ON mi.member_id=ma.id ".
			"LEFT JOIN `ims_bj_qmxk_member_selldata` ms ON ms.member_id=ma.id ".
			"LEFT JOIN `ims_fans` f ON f.from_user=m.from_user ".
			"WHERE ma.id='{$_W['member_id']}'");

		$memberInfo['nickname'] = $item['nickname'] ? $item['nickname'] : '';
		$memberInfo['uid'] = $item['mid'] ? compute_id($item['mid'], 'ENCODE') : 'u'.compute_id($item['member_id'], 'ENCODE');
		$memberInfo['reg_time'] = $item['reg_time'] ? date('Y-m-d', $item['reg_time']) : date('Y-m-d', $item['regtime']);

		$memberInfo['avatar'] = $item['f_avatar'] ? $item['f_avatar'] : $item['mi_avatar'];
		$memberInfo['avatar'] = $item['mi_avatar'] ? $item['mi_avatar'] : $memberInfo['avatar'];
		$memberInfo['avatar'] = $memberInfo['avatar'] ? $memberInfo['avatar'] : 'http://statics.shunliandongli.com/resource/image/avatar.png';
		$memberInfo['is_agent'] = ($item['is_agent'] || $item['mi_is_agent']) ? 1 : 0;
		$memberInfo['agent_time'] = $item['m_agent_time'] ? date('Y-m-d', $item['m_agent_time']) : '';
		$memberInfo['agent_time'] = $memberInfo['agent_time'] ? $memberInfo['agent_time'] : $memberInfo['reg_time'];

		if($memberInfo['is_agent']) {
			$item['vip_level'] = CreditModel::getInstance()->calVipLevelByCredit($item['order_credit_count']);
			$memberInfo['agent_level_name'] = self::$agent_level_name_arr[$item['vip_level']];
		} else {
			$memberInfo['agent_level_name'] = '普通会员';
		}

		$memberInfo['vip_level'] = $item['is_agent'] ? $item['vip_level'] : -1;
		$item['shareid'] = $item['sharemaid'];
		$memberInfo['has_shareid'] = $item['sharemaid'] > 0 ? 1: 0;

		$memberInfo['commission'] = sprintf('%.2f', $item['commission']);
		$memberInfo['credit2'] = sprintf('%.2f', $item['credit2']);
		$memberInfo['credit2_freeze'] = sprintf('%.2f', $item['credit2_freeze']);
		$memberInfo['spending'] = sprintf('%.2f', $item['spending']);
		$memberInfo['member_gold_count'] = intval($item['member_gold_count']);
		$memberInfo['group_gold_count'] = intval($item['group_gold_count']);

		if(strpos($item['username'], 'sldl_') !== false) {
			$memberInfo['username'] = '';
		} else {
			$memberInfo['username'] = $item['username'];
		}

		if(is_mobile($item['mobile'])) {
			$memberInfo['mobile'] = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1****$3', $item['mobile']);
		} else {
			$memberInfo['mobile'] = '';
		}

		if($item['sharemaid'] > 0) {
			$shareNickname = pdo_fetchcolumn("SELECT nickname FROM `ims_bj_qmxk_member_info` WHERE member_id='{$item['sharemaid']}'");
			$memberInfo['shareNickname'] = $shareNickname ? $shareNickname : '您的推荐人未设置昵称';
		}

		$memberInfo['shareNickname'] = $memberInfo['shareNickname'] ? $memberInfo['shareNickname'] : '无';

		if(empty($memberInfo['username'])) {
			$needDo['setUsername'] = 'yes';
		}
		if(empty($item['nickname'])) {
			$needDo['setNickname'] = 'yes';
		}
		if(empty($item['password']) || strlen($item['password']) < 40) {
			$needDo['setPassword'] = 'yes';
		}
		if(empty($memberInfo['mobile']) || empty($item['mobile_verified'])) {
			$needDo['setMobile'] = 'yes';
		}
		if(empty($item['f_avatar']) && empty($item['mi_avatar'])) {
			$needDo['setAvatar'] = 'yes';
		}
		if(strpos($item['unionid'], 'sldl_') === false) {
			$needDo['bindWexin'] = 'no';
		}

		unset($item);

		$result = array(
			'memberInfo'=> $memberInfo,
			'needDo'	=> $needDo
		);

		if($internalCall) return $result;

		return self::responseOk($result);
	}

	//首页
	public function home() {
		global $_W;

		$selfInfo = self::info(true);

		require IA_ROOT.'/source/apis/Order.php';
		$orderCount = Order::count(true);

		require IA_ROOT.'/source/apis/Favorite.php';
		$favoriteCount = Favorite::count(true);

		$commentCount = CommentModel::getInstance()->getCommentCounts($_W['member_id'], 'home');

		$otherCount = array();
        //贡献值排名
        $otherCount['work_sort'] = 0;

		$agent_info = pdo_fetch("SELECT agent_level FROM `ims_bj_qmxk_member_selldata.20160807` WHERE member_id='{$_W['member_id']}'");
		if($agent_info['agent_level'] > 0) {
			$can_sign = 'yes';
		} else {
			$can_sign = 'no';
		}
		
		//大赛数据
		$member_id = $_W['member_id'];
		$user_level =  FightModel::getInstance()->get_user_level($member_id);//会员等级
		$fight_user_info = FightModel::getInstance()->get_user($member_id);
		$fight_info = FightModel::getInstance()->get_fight_info();
		
		$fight_info['state'] = 1;
		
		//是否注册大赛
		$is_fight_sign = 0;
		if($fight_user_info){
		    $is_fight_sign = 1;
		}
		
		$time = time();
		$fight_status = 0;//0报名前，1报名中，2，比赛中，3，比赛结束。
		if($time >= $fight_info['sign_start_time'] && $time <= $fight_info['sign_end_time']){
		    $fight_status = 1;
		}
		if($time >= $fight_info['start_time'] && $time <= $fight_info['end_time']){
		    $fight_status = 2;
		}
		if($time > $fight_info['end_time']){
		    $fight_status = 3;
		}
		$info = array();
		$info['start_time'] = date('Y-m-d H:i:s',$fight_info['start_time']);
		$info['end_time'] = date('Y-m-d H:i:s',$fight_info['end_time']);
		$info['sign_start_time'] = date('Y-m-d H:i:s',$fight_info['sign_start_time']);
		$info['sign_end_time'] = date('Y-m-d H:i:s',$fight_info['sign_end_time']);



		$haoping = CreditModel::getInstance()->getHaoPing($member_id);

		$result = array(
			'memberInfo' 	=> $selfInfo['memberInfo'],
			'orderCount' 	=> $orderCount,
			'commentCount' 	=> $commentCount,
			'otherCount' 	=> $otherCount,
			'favoriteCount' => $favoriteCount,
			'needDo' 		=> $selfInfo['needDo'],
			'can_sign' 		=> $can_sign,
			'haoping'		=> $haoping,
		    'fight'=>array('fight_status'=>$fight_status,'user_level'=>$user_level,'can_reg_level'=>0,'is_fight_sign'=>$is_fight_sign,'state'=>$fight_info['state'],'fight_info'=>$info)
		);

		return self::responseOk($result);
	}

	private function child_call($class) {

		if(empty(self::$child_func)) {
			self::responseError(400, 'This method is not support. ');
		}

		require IA_ROOT.'/source/apis/My/'.$class.'.php';

		$api_object = new $class();

		if (!method_exists($class, self::$child_func)) {
			self::responseError(400, 'Method '.self::$child_func.' is not found.');
		}

		call_user_func_array(array($class, self::$child_func), array());
	}

	public function agent() {
		self::child_call('Agent');
	}

	public function setting() {
		self::child_call('Setting');
	}

	public function balance() {
		self::child_call('Balance');
	}

	public function notice() {
		self::child_call('Notice');
	}

	public function checkin() {
		self::child_call('CheckIn');
	}

	public function voucher() {
		self::child_call('Voucher');
	}

	public function sign() {
		self::child_call('Sign');
	}

    public function attractinvestment() {
		self::child_call('AttractInvestment');
	}
	public function comment() {
		self::child_call('Comment');
	}

	public function credit() {
		self::child_call('Credit');
	}

}