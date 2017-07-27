<?php

class Agent extends My {

	public static $agent_level_name_arr = array(
			'0' => '普通创客',
			'1' => '金牌创客',
			'2' => '白金创客',
			'3' => '钻石创客',
			'4' => '皇冠创客',
			'5' => '股东创客'
		);

	public static $vip_level_name_arr = array(
			'0' => '普通创客',
			'1' => 'V1',
			'2' => 'V2',
			'3' => 'V3',
			'4' => 'V4',
			'5' => 'V5',
			'6' => 'V6'
		);

	//销售数据
	public function selldata() {
		global $_W;

		$selldata = pdo_fetch("SELECT ms.*,mi.is_agent, mi.order_credit_count FROM `ims_bj_qmxk_member_info` mi LEFT JOIN `ims_bj_qmxk_member_selldata` ms ON mi.member_id=ms.member_id WHERE mi.member_id='{$_W['member_id']}'");

		//VIP等级
		$vlevel = CreditModel::getInstance()->calVipLevelByCredit($selldata['order_credit_count']);
		$vip_level = $vlevel > 0 ? 'V'.$vlevel : '普通用户';

		//小店VIP数量
		$my_vip_number = MemberModel::getInstance()->getShopVipUsersNum($_W['member_id']);

		//小店销售额
		$my_sales_price = $selldata['sales_price'] ? $selldata['sales_price'] : '0.00';

		//分店销售额
		$level_2_sales_price = pdo_fetchcolumn("SELECT SUM(ms.sales_price) FROM `ims_bj_qmxk_member_selldata` ms LEFT JOIN `ims_bj_qmxk_member_auth` ma ON ma.id=ms.member_id WHERE ma.sharemaid='{$_W['member_id']}'");
		$level_2_sales_price = $level_2_sales_price ? $level_2_sales_price : '0.00';

		//消费金额
		$spending = $selldata['spending'] ? $selldata['spending'] : '0.00';
		
		//本月消费金额     spending_this_month
		//本月小店销售额   sales_price_this_month
		//本月分店销售额   level_2_sales_price_this_month

		$this_month_start = strtotime('first day of this month midnight');
		$this_month_end = strtotime('first day of next month midnight');

		$last_month_start = strtotime('first day of last month midnight');
		$last_month_end = strtotime('first day of next month midnight', $last_month_start);

		$spending_this_month = pdo_fetchcolumn("SELECT SUM(`goodsprice`) ".
			"FROM `ims_bj_qmxk_order` ".
			"WHERE member_id='{$_W['member_id']}' AND status>'0' AND createtime>='{$this_month_start}' AND createtime<'{$this_month_end}'");
		$spending_this_month = $spending_this_month ? $spending_this_month : '0.00';

		$sales_price_this_month = pdo_fetchcolumn("SELECT SUM(goodsprice) ".
			"FROM `ims_bj_qmxk_order` ".
			"WHERE status>'0' AND shareid='{$_W['mid']}' AND createtime>='{$this_month_start}' AND createtime<'{$this_month_end}'");
		$sales_price_this_month = $sales_price_this_month ? $sales_price_this_month : '0.00';

	/*
		//先找出分店的member_id
		//再找出分店的分店的member_id
		//计算销售额

		$level_2_sales_price_this_month = pdo_fetchcolumn("SELECT SUM(o.goodsprice) ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_member` m ON m.member_id=o.member_id ".
			"WHERE ma.sharemaid='{$_W['member_id']}'");
	*/

		$level_2_sales_price_this_month = $level_2_sales_price_this_month ? $level_2_sales_price_this_month : '0.00';



		//小店创客数，小店会员数
		$member_count_key = md5('FansIndex-count1-'.$_W['member_id']);
		$member_agent_count_key = md5('FansIndex-count1_1-'.$_W['member_id']);
		$update_time_key = md5('FansIndex-update_time-'.$_W['member_id']);
		$update_time = date('Y-m-d H:i:s');
		$next_time_tmp = explode('-', date('Y-m-d-H', strtotime("+1 hour")));
		$expire = mktime($next_time_tmp[3], 0, 0, $next_time_tmp[1], $next_time_tmp[2], $next_time_tmp[0]) - time(); // 整点过期

		$member_count = $_W['mc']->get($member_count_key);
		if($member_count) {
			$member_agent_count = $_W['mc']->get($member_agent_count_key);
			$update_time = $_W['mc']->get($update_time_key);
		} else {
			$member_count = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_member_auth` WHERE sharemaid='{$_W['member_id']}'");
			$member_agent_count = pdo_fetchcolumn("SELECT COUNT(ma.id) FROM `ims_bj_qmxk_member_auth` ma ".
				"LEFT JOIN `ims_bj_qmxk_member_info` mi ON mi.member_id=ma.id WHERE ma.sharemaid='{$_W['member_id']}' AND mi.is_agent='1'");

			$_W['mc']->set($member_count_key, $member_count, 0, $expire);
			$_W['mc']->set($member_agent_count_key, $member_agent_count, 0, $expire);
			$_W['mc']->set($update_time_key, $update_time, 0, $expire);
		}
		$member_count = intval($member_count);
		$member_agent_count = intval($member_agent_count);

/*
		// 团队销售额
		$sales_price = $selldata['group_sales_price'] ? $selldata['group_sales_price'] : '0.00';
		$sales_rank = $selldata['group_rank'] ? $selldata['group_rank'] : 10000000;

		// 消费金额
		$spending = $selldata['spending'] ? $selldata['spending'] : '0.00';
		// 小店销售额
		$my_sales_price = $selldata['sales_price'] ? $selldata['sales_price'] : '0.00';

		// 店铺访问量
		$clickcount = $profile['clickcount'];

		// 直属金牌代理
		$member_gold_count = $selldata['member_gold_count'] ? $selldata['member_gold_count'] : '0';

		// 团队金牌代理
		$group_gold_count = $selldata['agent_level'] >= 4 ? ($selldata['group_gold_count'] ? $selldata['group_gold_count'] : '统计中') : '只对皇冠创客开放';
//		$group_gold_count = $selldata['agent_level'] == 5 ? '2000+' : $group_gold_count;
		$group_gold_count = $selldata['agent_level'] == 5 ? '2000+' : '暂不显示';

		// 代理级别
		$level_update_msg = '';

		$agent_level = intval($selldata['agent_level']);

		if($selldata['is_agent']) {
			$agent_level_name = self::$agent_level_name_arr[$agent_level];
			if(empty($agent_level)) {
				$level_update_msg = '再增加' . ceil(280 - $spending) . '点成长指数可升级为金牌创客';
			} elseif($agent_level == 1) {
				$level_update_msg = '再直推' . (5 - $member_gold_count) . '名金牌或以上创客可升级为白金创客';
			} elseif($agent_level == 2) {
				$level_update_msg = '再直推' . (10 - $member_gold_count) . '名金牌或以上创客可升级为钻石创客';
			} elseif($agent_level == 3) {
				$level_update_msg = '再直推' . (20 - $member_gold_count) . '名金牌或以上创客可升级为皇冠创客';
			}
		} else {
			$agent_level_name = '非创客';
		//	$level_update_msg = '购买一单可升级为普通创客';
			$level_update_msg = '';
		}
*/

	/*
		$result = array(
			'agent_level_name' => $agent_level_name,
			'member_gold_count' => $member_gold_count,
			'group_gold_count' => $group_gold_count,
			'spending' => $spending,
			'my_sales_price' => $my_sales_price,
			'sales_price' => $sales_price,
			'sales_rank' => $sales_rank,
			'member_count' => $member_count,
			'member_agent_count' => $member_agent_count
		);
	*/

if(self::$platform == 'Android' || (self::$platform == 'IOS' AND version_compare(self::$client_version, '1.0.6', '>'))) {
		$result = array(
			'agent_level_name' => '升级中',
			'member_gold_count' => '升级中',
			'group_gold_count' => '升级中',
			'spending' => $spending,
			'my_sales_price' => $my_sales_price,
			'sales_price' => '升级中',
			'sales_rank' => '升级中',
			'member_count' => $member_count,
			'member_agent_count' => $member_agent_count,
			'vip_level' => $vip_level,
			'my_vip_number' => $my_vip_number,
			'level_2_sales_price' => $level_2_sales_price,
			'spending_this_month' => $spending_this_month,
			'sales_price_this_month' => $sales_price_this_month,
			'level_2_sales_price_this_month' => $level_2_sales_price_this_month
		);
} else {
		$result = array(
			'agent_level_name' => '升级中',
			'member_gold_count' => '升级中',
			'group_gold_count' => '升级中',
			'spending' => '升级中',
			'my_sales_price' => '升级中',
			'sales_price' => '升级中',
			'sales_rank' => '升级中',
			'member_count' => '升级中',
			'member_agent_count' => '升级中',
			'vip_level' => $vip_level,
			'my_vip_number' => $my_vip_number,
			'level_2_sales_price' => $level_2_sales_price,
			'spending_this_month' => $spending_this_month,
			'sales_price_this_month' => $sales_price_this_month,
			'level_2_sales_price_this_month' => $level_2_sales_price_this_month
		);
}


		return self::responseOk($result);
	}

	//我的动力指数
	public function commission() {
		global $_W;

		$show_week = '0'; //显示奖励的周
		$comm_week = '0'; //可提现奖励的周

		//现在是星期几
		$this_weekday = date('w'); //0是周日
		//判断要显示第几周的奖励
		/*if($this_weekday > 3 || $this_weekday == 0 || ($this_weekday == 3 && date('G') >= 6)) {
			$show_week = date('YW');
			$comm_week = date('YW', strtotime('-1 week'));
		}
		if($this_weekday == 1 || $this_weekday == 2 || ($this_weekday == 3 && date('G') < 6)) {
			$show_week = date('YW', strtotime('-1 week'));
			$comm_week = date('YW', strtotime('-2 weeks'));
		}*/

        //判断要显示第几周的奖励,时间调整
        if($this_weekday > 1 || $this_weekday == 0 || ($this_weekday == 1 && date('G') >= 6)) {
            $show_week = date('YW', strtotime('-1 week'));
            $comm_week = date('YW', strtotime('-2 weeks'));
        }
        if(($this_weekday == 1 && date('G') < 6)) {
            $show_week = date('YW', strtotime('-2 weeks'));
            $comm_week = date('YW', strtotime('-3 weeks'));
        }

        $show_week = $comm_week = '201727';

		$commtime_day = 8; //佣金申请周期
		//if($_W['member_id'] == 18) $_W['member_id'] = 42577687;

		$profile = pdo_fetch("SELECT agent_time AS flagtime,commission FROM `ims_bj_qmxk_member_info` WHERE member_id='{$_W['member_id']}'");

		if(empty($profile['flagtime'])) {
			return self::responseError(9200, '参数错误.');
		}

		$commission_key = md5('commission_key-member_id-'.$_W['member_id']);
		$commission_lock_key = md5('commission_lock_key-member_id-'.$_W['member_id']);
		$update_time_key = md5('update_time_key-member_id-'.$_W['member_id']);
		$expire = 0;

		$commission = '';

		if(defined('PROMOTION_MODEL')) { //推广期间，无限缓存
			$commission = $_W['mc']->get($commission_key);
		} else { //平时，缓存10分钟
			$last_update_time = $_W['mc']->get($update_time_key);
			if(TIMESTAMP <= $last_update_time + 600) {
				$commission = $_W['mc']->get($commission_key);
			}
		}

		if($commission) {
			$commission = unserialize($commission);
			return self::responseOk($commission);
		}
                

		if($_W['mc']->get($commission_lock_key)) { //已经请求了统计操作，本次请求无效
			return self::responseError(5000, 'Please wait.');
		}

		$_W['mc']->set($commission_lock_key, '1', 0, 5*60);

		ignore_user_abort(true);

		$commtime = mktime(0, 0, 0, date('m'), date('d'), date('Y')) + 86400 - intval($commtime_day) * 24 * 60 * 60;
                
                

		//动力指数(预估)
		$where_1_1 = "AND os.createtime<'1471795200'";
		$where_1 = "AND o.status>='1'";
		$list_1 = pdo_fetchall(
		"SELECT SUM(og.commission*og.total) AS commission " . 
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1} ".

		"UNION ALL (SELECT SUM(og.commission2*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid2 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.commission3*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid3 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_1}) ".

		"UNION ALL(SELECT SUM(og.commission4*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid4 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_1}) ".

		"UNION ALL(SELECT SUM(og.commission5*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid5 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_1}) ".

		"UNION ALL(SELECT SUM(og.commission6*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid6 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_1}) ".

		"UNION ALL(SELECT SUM(og.commission7*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid7 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_1}) ".

		"UNION ALL(SELECT SUM(og.commission8*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid8 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_1}) "
		);
		//print_r(count($list));

		$commission_extra_1 = 0;
		foreach($list_1 AS $oeder_list) {
			$commission_extra_1 += $oeder_list['commission'];
		}
		$commission_forecast = number_format($commission_extra_1, 2, '.', '');
		//动力指数(预估) 结束

		//额外奖励(预估)
		$where_1 = "AND os.createtime<'1471795200' AND o.status>='1'";
		$list_1 = pdo_fetchall(
		"SELECT SUM(og.extra_commission*og.total) AS commission " . 
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1} ".

		"UNION ALL (SELECT SUM(og.extra_commission2*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid2 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission3*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid3 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission4*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid4 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission5*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid5 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission6*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid6 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission7*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid7 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission8*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid8 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission9*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid9 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission10*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid10 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission11*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid11 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission12*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid12 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission13*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid13 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission14*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid14 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission15*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid15 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission16*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid16 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission17*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid17 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission18*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid18 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission19*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid19 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) ".

		"UNION ALL(SELECT SUM(og.extra_commission20*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid20 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1}) "
		);
		//print_r(count($list));

		$commission_extra = 0;
		foreach($list_1 AS $order_list) {
			$commission_extra += $order_list['commission'];
		}
		$commission_extra = number_format($commission_extra, 2, '.', '');
		//额外奖励(预估) 结束

		//已结算
		$commission_success = number_format($profile['commission'], 2, '.', '');;
		//已结算 结束


		//我的收益(包括已提现)
		$where_all_1 = "AND o.status>='1'";
		$list_all_1 = pdo_fetchall(
		"SELECT SUM(og.commission*og.total) AS commission " . 
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_1} ".

		"UNION ALL (SELECT SUM(og.commission2*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid2 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_1}) ".

		"UNION ALL(SELECT SUM(og.commission3*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid3 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_all_1}) ".

		"UNION ALL(SELECT SUM(og.commission4*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid4 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_all_1}) ".

		"UNION ALL(SELECT SUM(og.commission5*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid5 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_all_1}) ".

		"UNION ALL(SELECT SUM(og.commission6*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid6 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_all_1}) ".

		"UNION ALL(SELECT SUM(og.commission7*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid7 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_all_1}) ".

		"UNION ALL(SELECT SUM(og.commission8*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid8 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_1_1} {$where_all_1})"
		);

		$commission_all_1 = 0;
		foreach($list_all_1 AS $oeder_list) {
			$commission_all_1 += $oeder_list['commission'];
		}

		$where_all_2 = "AND os.createtime<'1471795200' AND o.status>='1'";
		$list_all_2 = pdo_fetchall(
		"SELECT SUM(og.extra_commission*og.total) AS commission " . 
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2} ".

		"UNION ALL (SELECT SUM(og.extra_commission2*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid2 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission3*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid3 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission4*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid4 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission5*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid5 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission6*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid6 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission7*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid7 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission8*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid8 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission9*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid9 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission10*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid10 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission11*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid11 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission12*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid12 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission13*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid13 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission14*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid14 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission15*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid15 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission16*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid16 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission17*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid17 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission18*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid18 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission19*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid19 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2}) ".

		"UNION ALL(SELECT SUM(og.extra_commission20*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid20 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_all_2})"
		);

		$commission_all_2 = 0;
		foreach($list_all_2 AS $order_list) {
			$commission_all_2 += $order_list['commission'];
		}

		$commission_all = number_format($commission_all_1 + $commission_all_2, 2, '.', '');
		//我的收益(包括已提现) 结束


		//可提现
		// 按0点为截至时间，利用sql缓存
		$commtime = mktime(0, 0, 0, date('m'), date('d'), date('Y')) + 86400 - intval($commtime_day) * 24 * 60 * 60;

		//$where_2 = "AND o.status>=3 AND (o.updatetime=0 OR (o.updatetime>0 AND o.updatetime<'{$commtime}'))";
		$where_2_1 = "AND os.createtime<'1471795200'";
		$where_2 = "AND o.status>=3 AND o.updatetime<'{$commtime}'";

		$list_2 = pdo_fetchall(
		"SELECT SUM(og.commission*og.total) AS commission " . 
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.status = 0 ".

		"UNION ALL (SELECT SUM(og.commission2*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid2 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.status2 = 0) ".

		"UNION ALL(SELECT SUM(og.commission3*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid3 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2_1} {$where_2} AND og.status3 = 0) ".

		"UNION ALL(SELECT SUM(og.commission4*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid4 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2_1} {$where_2} AND og.status4 = 0) ".

		"UNION ALL(SELECT SUM(og.commission5*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid5 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2_1} {$where_2} AND og.status5 = 0) ".

		"UNION ALL(SELECT SUM(og.commission6*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid6 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2_1} {$where_2} AND og.status6 = 0) ".

		"UNION ALL(SELECT SUM(og.commission7*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid7 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2_1} {$where_2} AND og.status7 = 0) ".

		"UNION ALL(SELECT SUM(og.commission8*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.shareid8 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2_1} {$where_2} AND og.status8 = 0)"
		);

		$commission_2 = 0;
		foreach($list_2 AS $order_list) {
			$commission_2 += $order_list['commission'];
		}
                

		$where_2 = "AND os.createtime<'1471795200' AND o.status='3' AND o.updatetime<'{$commtime}' ";

		$list_2 = pdo_fetchall(
		"SELECT SUM(og.extra_commission*og.total) AS commission " . 
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status = 0 ".

		"UNION ALL (SELECT SUM(og.extra_commission2*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid2 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status2 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission3*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid3 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status3 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission4*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid4 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status4 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission5*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid5 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status5 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission6*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid6 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status6 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission7*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid7 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status7 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission8*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid8 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status8 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission9*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid9 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status9 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission10*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid10 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status10 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission11*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid11 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status11 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission12*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid12 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status12 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission13*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid13 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status13 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission14*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid14 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status14 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission15*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid15 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status15 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission16*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid16 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status16 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission17*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid17 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status17 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission18*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid18 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status18 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission19*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid19 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status19 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission20*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order_shareid` os ".
			"LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE os.extra_shareid20 ='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status20 = 0) ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160807` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160814` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160821` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160828` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160904` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160911` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160918` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_reward` ".
			"WHERE member_id='{$_W['member_id']}' AND week<='{$comm_week}' AND settlemented='0')"
		);

		$commission_extra_2 = 0;
		foreach($list_2 AS $order_list) {
			$commission_extra_2 += $order_list['commission'];
		}
		$commission_available = number_format($commission_2+$commission_extra_2, 2, '.', '');

		//可提现 结束

		//把数据进行缓存

		$prize = pdo_fetchcolumn("SELECT prize FROM `ims_bj_qmxk_member_selldata2.20160807` WHERE member_id='{$_W['member_id']}'");
		$prize2 = pdo_fetchcolumn("SELECT prize FROM `ims_bj_qmxk_member_selldata2.20160814` WHERE member_id='{$_W['member_id']}'");
		$prize3 = pdo_fetchcolumn("SELECT prize FROM `ims_bj_qmxk_member_selldata2.20160821` WHERE member_id='{$_W['member_id']}'");
		$prize4 = pdo_fetchcolumn("SELECT prize FROM `ims_bj_qmxk_member_selldata2.20160828` WHERE member_id='{$_W['member_id']}'");
		$prize5 = pdo_fetchcolumn("SELECT prize FROM `ims_bj_qmxk_member_selldata2.20160904` WHERE member_id='{$_W['member_id']}'");
		$prize6 = pdo_fetchcolumn("SELECT prize FROM `ims_bj_qmxk_member_selldata2.20160911` WHERE member_id='{$_W['member_id']}'");
		$prize7 = pdo_fetchcolumn("SELECT prize FROM `ims_bj_qmxk_member_selldata2.20160918` WHERE member_id='{$_W['member_id']}'");
		$prize8 = pdo_fetchcolumn("SELECT SUM(prize) FROM `ims_reward` WHERE member_id='{$_W['member_id']}' AND week<='{$show_week}'");
		$commission_extra = number_format($commission_extra + $prize + $prize2 + $prize3 + $prize4 + $prize5 + $prize6 + $prize7 + $prize8, 2, '.', '');
		$commission_all = number_format($commission_all + $prize + $prize2 + $prize3 + $prize4 + $prize5 + $prize6 + $prize7 + $prize8, 2, '.', '');

		$last_prize = pdo_fetchcolumn("SELECT prize FROM `ims_reward` WHERE member_id='{$_W['member_id']}' AND week='{$show_week}'");
                $last_prize = number_format($last_prize,2);

		$commission_arr = array(
		//	'forecast' => $commission_forecast, //动力指数(预估)
		//	'extra'		=> $commission_extra, //额外奖励(预估)
			'forecast' => '-', //动力指数(预估)
			'extra'		=> '-', //额外奖励(预估)
			'last_prize' => $last_prize,
			'success' => $commission_success, //已结算(预估)
			'all' => $commission_all, //我的收益，包括已提现
			'available' => $commission_available, //可提现
			'updatetime' => date('Y-m-d H:i') //更新时间
		);

		$_W['mc']->set($commission_key, serialize($commission_arr), 0, $expire);
		$_W['mc']->set($update_time_key, TIMESTAMP, 0, $expire);

		return self::responseOk($commission_arr);
	}

	//获取佣金明细
	//可选参数: count=获取数量，最大40，默认10
	//可选参数: page=分页
	public function commissionList() {
		global $_W;

		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$count = ($count && $count <= 40) ? $count : 20;
		$limit = ($page-1) * $count.', '.$count;

		$item_arr = pdo_fetchall("SELECT createtime,commission,status FROM `ims_bj_qmxk_commissions` WHERE member_id='{$_W['member_id']}' ORDER BY id DESC LIMIT {$limit}");

		$items = array();
		foreach($item_arr AS $item) {
			$item['createtime'] = date('Y-m-d H:i:s', $item['createtime']);
			if($item['status'] == 1) {
				$item['status'] = '已审核';
			} elseif($item['status'] == 2) {
				$item['status'] = '已打款';
			} elseif($item['status'] == 3) {
				$item['status'] = '拒绝';
			} else {
				$item['status'] = '申请中';
			}

			$items[] = $item;
		}

		$result = array();
		$result['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_commissions` WHERE member_id='{$_W['member_id']}'");
		$result['itemCount'] = intval($result['itemCount']);
		$result['allPage'] = ceil($result['itemCount']/$count);
		$result['page'] = $page;
		$result['count'] = $count;
		$result['items'] = $items;

		return self::responseOk($result);
	}

	//小店订单
	public function orderList() {
		global $_W;

		$order_status_name = array(
			0	=> '待付款',
			1	=> '已付款',
			2	=> '待收货',
			3	=> '已完成',
			-1	=> '已关闭',
			-2	=> '退款中',
			-3	=> '换货中',
			-4	=> '退货中',
			-5	=> '已退货',
			-6	=> '已退款'
		);

		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$count = ($count && $count <= 40) ? $count : 20;
		$limit = ($page-1) * $count.', '.$count;

		$profile = pdo_fetch("SELECT agent_time AS flagtime FROM `ims_bj_qmxk_member_info` WHERE member_id='{$_W['member_id']}'");

		if($page == 1) {
                $today_begin = strtotime(date('Y-m-d 00:00:00', strtotime('0 day')));

			$condition = '';
			//$condition = "AND from_user!='{$from_user}'";

                $order_count = pdo_fetch("select * from	`ims_bj_qmxk_order_count` where member_id='{$_W['member_id']}'");

			if($order_count) {
				$allcount = $order_count['count_all'];
			$allcount_goods = $order_count['count_all_goods'];
			$allcount_dzd = $order_count['count_all_dzd'];
				$countToday = $order_count['count_today'];
				$countYestay = $order_count['count_yestay'];
				
				if($order_count['updatetime'] < $today_begin) { // 昨天更新的
					if($order_count['updatetime'] == 0) {
						// $allcount = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('bj_qmxk_order') . " WHERE createtime>='{$profile['flagtime']}' {$condition}");
						$allcount = 0;


						$count_arr = pdo_fetchall("SELECT COUNT(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid='{$_W['id']}' AND createtime>='{$profile['flagtime']}' {$condition} " /*. 

						"UNION ALL (SELECT COUNT(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid2='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid3='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid4='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid5='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid6='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid7='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid8='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition})"*/);
						foreach($count_arr as $count_val) {
							$allcount += $count_val['ordernum'];
						}
				/*
						$count_arr = pdo_fetchall("SELECT COUNT(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition} " . 

						"UNION ALL (SELECT COUNT(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid2 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid3 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid4 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid5 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid6 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid7 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

						"UNION ALL (SELECT COUNT(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid8 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition})");
						foreach($count_arr as $count_val) {
							$allcount += $count_val['ordernum'];
						}
				*/
					}
					$yestay_begin = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
					$yestay_begin = $yestay_begin >= $profile['flagtime'] ? $yestay_begin : $profile['flagtime'];
					$yestay_end = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
					if($yestay_end >= $profile['flagtime']) {
						// $countYestay = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('bj_qmxk_order') . " WHERE {$condition}");
						$countYestay = 0;


						$count_arr = pdo_fetchall("SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition} " /*. 

						"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid2='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid3='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid4='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid5='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid6='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid7='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid8='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition})"*/);
						foreach($count_arr AS $count_val) {
							$countYestay += $count_val['ordernum'];
						}

				/*
						$count_arr = pdo_fetchall("SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition} " . 

						"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid2 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid3 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid4 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid5 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid6 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid7 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

						"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid8 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition})");
						foreach($count_arr as $count_val) {
							$countYestay += $count_val['ordernum'];
						}
				*/
					} else {
						$countYestay = 0;
					}
					
					$today_begin = $today_begin >= $profile['flagtime'] ? $today_begin : $profile['flagtime'];
					$today_end = strtotime(date('Y-m-d 23:59:59', strtotime('0 day')));
					
					$countToday = 0;


					$count_arr = pdo_fetchall("SELECT count(*) AS ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition} " /*. 

					"UNION ALL (SELECT count(*) AS ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid2='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) AS ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid3='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) AS ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid4='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) AS ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid5='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) AS ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid6='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) AS ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid7='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) AS ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid8='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition})"*/);
					foreach($count_arr AS $count_val) {
						$countToday += $count_val['ordernum'];
					}
					if($order_count['updatetime'] == 0) {
						pdo_query("UPDATE `ims_bj_qmxk_order_count` SET `count_all`='{$allcount}',`count_today`='{$countToday}',`count_yestay`='{$countYestay}',`updatetime`='" . TIMESTAMP . "' WHERE `member_id`='{$_W['member_id']}'");
					} else {
						pdo_query("UPDATE `ims_bj_qmxk_order_count` SET `count_today`='{$countToday}',`count_yestay`='{$countYestay}',`updatetime`='".TIMESTAMP."' WHERE `member_id`='{$_W['member_id']}'");
					}

			/*
					$count_arr = pdo_fetchall("SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition} " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid2 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid3 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid4 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid5 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid6 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid7 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid8 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition})");
					foreach($count_arr as $count_val) {
						$countToday += $count_val['ordernum'];
					}
					if($order_count['updatetime'] == 0) {
						pdo_query("UPDATE " . tablename('bj_qmxk_order_count') . " SET `count_all`='{$allcount}',`count_today`='{$countToday}',`count_yestay`='{$countYestay}',`updatetime`='" . TIMESTAMP . "' WHERE `profileid`='{$_W['mid']}'");
					} else {
						pdo_query("UPDATE " . tablename('bj_qmxk_order_count') . " SET `count_today`='{$countToday}',`count_yestay`='{$countYestay}',`updatetime`='" . TIMESTAMP . "' WHERE `profileid`='{$_W['mid']}'");
					}
			*/
				}
			} else { // 统计表中尚不存在该用户的记录
				$allcount = 0;


				$count_arr = pdo_fetchall("SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition} " /*. 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid2='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid3='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid4='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid5='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid6='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid7='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid8='{$_W['member_id']}' AND createtime>='{$profile['flagtime']}' {$condition})"*/);
				foreach($count_arr as $count_val) {
					$allcount += $count_val['ordernum'];
				}

		/*
				$count_arr = pdo_fetchall("SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition} " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid2 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid3 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid4 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid5 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid6 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid7 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid8 = '{$_W['mid']}' AND createtime>='{$profile['flagtime']}' {$condition})");
				foreach($count_arr as $count_val) {
					$allcount += $count_val['ordernum'];
				}
		*/
                        
				$yestay_begin = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
				$yestay_begin = $yestay_begin >= $profile['flagtime'] ? $yestay_begin : $profile['flagtime'];
				$yestay_end = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
				if($yestay_end >= $profile['flagtime']) {			
					$countYestay = 0;



					$count_arr = pdo_fetchall("SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition} " /*. 

					"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid2='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid3='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid4='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid5='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid6='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid7='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid8='{$_W['member_id']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition})"*/);
					foreach($count_arr as $count_val) {
						$countYestay += $count_val['ordernum'];
					}


			/*
					$count_arr = pdo_fetchall("SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition} " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid2 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid3 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid4 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid5 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid6 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid7 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition}) " . 

					"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid8 = '{$_W['mid']}' AND createtime>='{$yestay_begin}' AND createtime<='{$yestay_end}' {$condition})");
					foreach($count_arr as $count_val) {
						$countYestay += $count_val['ordernum'];
					}
			*/
                } else {
					$countYestay = 0;
				}
				
				$today_begin = $today_begin >= $profile['flagtime'] ? $today_begin : $profile['flagtime'];
				$today_end = strtotime(date('Y-m-d 23:59:59', strtotime('0 day')));
				// $countToday = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('bj_qmxk_order') . " WHERE createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}");
			
				$countToday = 0;


				$count_arr = pdo_fetchall("SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition} " /*. 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid2='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid3='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid4='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid5='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid6='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid7='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum FROM `ims_bj_qmxk_order_shareid` WHERE shareid8='{$_W['member_id']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition})"*/);

				foreach($count_arr as $count_val) {
					$countToday += $count_val['ordernum'];
				}

				pdo_query("INSERT INTO `ims_bj_qmxk_order_count` SET `profileid`='{$profile['id']}',`member_id`='{$_W['member_id']}',`count_all`='{$allcount}',`count_today`='{$countToday}',`count_yestay`='{$countYestay}',`updatetime`='".TIMESTAMP."'");


		/*
				$count_arr = pdo_fetchall("SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition} " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid2 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid3 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid4 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid5 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid6 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid7 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition}) " . 

				"UNION ALL (SELECT count(*) as ordernum " . "FROM `ims_bj_qmxk_order` WHERE shareid8 = '{$_W['mid']}' AND createtime>='{$today_begin}' AND createtime<='{$today_end}' {$condition})");
				foreach($count_arr as $count_val) {
					$countToday += $count_val['ordernum'];
				}
				
				pdo_query("INSERT INTO `ims_bj_qmxk_order_count` SET `profileid`='{$_W['mid']}',`count_all`='{$allcount}',`count_today`='{$countToday}',`count_yestay`='{$countYestay}',`updatetime`='" . TIMESTAMP . "'");
		*/
			}
		} else {
			$order_count = pdo_fetch("select * from	`ims_bj_qmxk_order_count` where member_id='{$_W['member_id']}'");
			$allcount = $order_count['count_all'];
			$allcount_goods = $order_count['count_all_goods'];
			$allcount_dzd = $order_count['count_all_dzd'];
			$countToday = $order_count['count_today'];
			$countYestay = $order_count['count_yestay'];
		}
		
		// 不是代理的不记录，自己的订单不记录
			

		$conditionx = '';


		$condition1 = $conditionx . "os.shareid='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' AND os.goodstype='1'";
		$condition2 = $conditionx . "os.shareid2='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' AND os.goodstype='1'";
		$condition3 = $conditionx . "os.shareid3='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' AND os.goodstype='1'";
		$condition4 = $conditionx . "os.shareid4='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' AND os.goodstype='1'";
		$condition5 = $conditionx . "os.shareid5='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' AND os.goodstype='1'";
		$condition6 = $conditionx . "os.shareid6='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' AND os.goodstype='1'";
		$condition7 = $conditionx . "os.shareid7='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' AND os.goodstype='1'";
		$condition8 = $conditionx . "os.shareid8='{$_W['member_id']}' AND os.createtime>='{$profile['flagtime']}' AND os.goodstype='1'";

		$select_field = "o.id,o.ordersn,o.status,o.price,o.createtime,o.order_type, mi.nickname AS realname,'' as commissions,";
		$left_join = 'LEFT JOIN `ims_bj_qmxk_member_info` mi ON mi.member_id=os.member_id LEFT JOIN `ims_bj_qmxk_order` o ON o.id=os.orderid';

		$list = pdo_fetchall("SELECT {$select_field} 1 as level FROM `ims_bj_qmxk_order_shareid` os FORCE INDEX(`s1_c_g`) {$left_join} WHERE {$condition1} " . 
	/*
		"UNION ALL (SELECT {$select_field} 2 as level FROM `ims_bj_qmxk_order_shareid` os FORCE INDEX(`s2_c_g`) {$left_join} WHERE {$condition2}) " . 
		"UNION ALL (SELECT {$select_field} 3 as level FROM `ims_bj_qmxk_order_shareid` os FORCE INDEX(`s3_c_g`) {$left_join} WHERE {$condition3}) " . 
		"UNION ALL (SELECT {$select_field} 4 as level FROM `ims_bj_qmxk_order_shareid` os FORCE INDEX(`s4_c_g`) {$left_join} WHERE {$condition4}) " . 
		"UNION ALL (SELECT {$select_field} 5 as level FROM `ims_bj_qmxk_order_shareid` os FORCE INDEX(`s5_c_g`) {$left_join} WHERE {$condition5}) " . 
		"UNION ALL (SELECT {$select_field} 6 as level FROM `ims_bj_qmxk_order_shareid` os FORCE INDEX(`s6_c_g`) {$left_join} WHERE {$condition6}) " . 
            		"UNION ALL (SELECT {$select_field} 8 as level FROM `ims_bj_qmxk_order_shareid` os FORCE INDEX(`s8_c_g`) {$left_join} WHERE {$condition8}) " .
	*/
		"ORDER BY createtime DESC LIMIT {$limit}", array(), '', true);

		if(!empty($list)) {
			foreach($list as $lkey => $l) {
				$commissions = pdo_fetchall("SELECT g.id AS goodsId,g.title,g.thumb,".
					"og.commission,og.commission2,og.commission3,og.commission4,og.commission5,og.commission6,og.commission7,og.commission8,".
					"og.total FROM `ims_bj_qmxk_order_goods` og LEFT JOIN `ims_bj_qmxk_goods` g ON og.goodsid=g.id WHERE og.orderid='{$l['id']}'");

				foreach($commissions as $key => $commission) {
					$commissions[$key]['thumb'] = $commission['thumb'] ?  $_W['attachurl'].$commission['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';

					if($l['level'] == 1) {
						$commissions[$key]['commission'] = $commission['commission'] * $commission['total'];
					}
					if($l['level'] == 2) {
						$commissions[$key]['commission'] = $commission['commission2'] * $commission['total'];
					}
					if($l['level'] == 3) {
						$commissions[$key]['commission'] = $commission['commission3'] * $commission['total'];
					}
					if($l['level'] == 4) {
						$commissions[$key]['commission'] = $commission['commission4'] * $commission['total'];
					}
					if($l['level'] == 5) {
						$commissions[$key]['commission'] = $commission['commission5'] * $commission['total'];
					}
					if($l['level'] == 6) {
						$commissions[$key]['commission'] = $commission['commission6'] * $commission['total'];
					}
					if($l['level'] == 7) {
						$commissions[$key]['commission'] = $commission['commission7'] * $commission['total'];
					}
					if($l['level'] == 8) {
						$commissions[$key]['commission'] = $commission['commission8'] * $commission['total'];
					}

					unset($commissions[$key]['commission2']);
					unset($commissions[$key]['commission3']);
					unset($commissions[$key]['commission4']);
					unset($commissions[$key]['commission5']);
					unset($commissions[$key]['commission6']);
					unset($commissions[$key]['commission7']);
					unset($commissions[$key]['commission8']);
					//$commissions[$key]['goodsid'] = $commission['goodsid'];
				}

				unset($list[$lkey]['id']);
				unset($list[$lkey]['level']);
				unset($list[$lkey]['level']);

				$list[$lkey]['status'] = $order_status_name[$l['status']];
				$list[$lkey]['createtime'] = date('Y-m-d', $l['createtime']);

				$list[$lkey]['goodsList'] = $commissions;
			}
		}

		$total = $allcount_goods;


		//平时展示最近10000条记录，忙时展示最近2000条记录
		$total = min($total, defined('PROMOTION_MODEL') ? 2000 : 10000);
		//$pager = pagination2($total, $pindex, $psize);

		//include $this->mobilePage('fansorder2');
		//$page_content = ob_get_contents();
		//ob_end_clean();
		//$_W['mc']->set($page_key, serialize($page_content), MEMCACHE_COMPRESSED, $expire);

		$result = array();

		//重新计算订单总数-------------start--20170315
          $ddcount=pdo_fetchcolumn("SELECT count(*) AS ddcount FROM `ims_bj_qmxk_order_shareid` os  WHERE {$condition1}");
          if(empty($ddcount)){
              $allcount=0;
          }
          else{
              $allcount=$ddcount;
          }
        //重新计算订单总数-------------end


		if($page == 1) {
			$result['countToday']	= $countToday;
			$result['countYestay']	= $countYestay;
			$result['countAll']		= $allcount;
		}

		$result['itemCount']	= intval($total);
       //$result['allPage']		= ceil($total/$count);
		$result['allPage']		= ceil($allcount/$count);
		$result['page']			= $page;
		$result['count']		= $count;
		$result['items']		= $list;

		return self::responseOk($result);
	}

	//小店会员
	public function fansList() {
		global $_W;

		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$count = ($count && $count <= 40) ? $count : 20;
		$limit = ($page-1) * $count.', '.$count;

		$fansall = pdo_fetchall("SELECT mi.nickname AS realname, mi.is_agent AS flag, mi.order_credit_count, ma.regtime AS createtime,ms.agent_level, mi.avatar ".
			"FROM `ims_bj_qmxk_member_auth` ma ".
			"LEFT JOIN `ims_bj_qmxk_member_info` mi ON mi.member_id=ma.id ".
			"LEFT JOIN `ims_bj_qmxk_member_selldata` ms ON ms.member_id=ma.id ".
			"WHERE ma.sharemaid='{$_W['member_id']}' ORDER BY ma.regtime DESC LIMIT {$limit}");

		foreach($fansall as $key => $c) {
			$c['agent_level'] = intval($c['agent_level']);
			$fansall[$key]['agent_level_name'] = $c['flag'] > 0 ? self::$agent_level_name_arr[$c['agent_level']] : '普通用户';

			$c['vip_level'] = CreditModel::getInstance()->calVipLevelByCredit($c['order_credit_count']);
			$fansall[$key]['vip_level_name'] = $c['flag'] > 0 ? self::$vip_level_name_arr[$c['vip_level']] : '普通用户';

			$fansall[$key]['createtime'] = date('Y-m-d', $c['createtime']);
			unset($fansall[$key]['flag']);
			unset($fansall[$key]['agent_level']);
		}


/*
		$fansall = pdo_fetchall("SELECT m.realname AS nickname, m.createtime,m.agent_level,m.flag,f.avatar ".
			"FROM ims_bj_qmxk_member m ".
			"LEFT JOIN ims_fans f ON m.from_user=f.from_user ".
			"WHERE m.shareid={$_W['mid']} ORDER BY m.createtime DESC LIMIT {$limit}");

		foreach($fansall as $key => $c) {
			$fansall[$key]['agent_level_name'] = $c['flag'] ? self::$agent_level_name_arr[$c['agent_level']] : '普通用户';
			$fansall[$key]['createtime'] = date('Y-m-d', $c['createtime']);
			unset($fansall[$key]['flag']);
			unset($fansall[$key]['agent_level']);
			//$fansall[$key]['fanscount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_member WHERE shareid ='{$c['id']}'");
		}
*/

		$total = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_member_auth` WHERE sharemaid='{$_W['member_id']}'");

		//$_W['mc']->set($list_key, serialize($fansall), 0, $expire);
		//$_W['mc']->set($total_key, $total, 0, $expire);
		//$_W['mc']->set($update_time_key, $update_time, 0, $expire);

		$result = array();
		$result['itemCount']	= intval($total);
		$result['allPage']		= ceil($total/$count);
		$result['page']			= $page;
		$result['count']		= $count;
		$result['items']		= $fansall;

		return self::responseOk($result);
	}

	//获取转发话术
	public function getPatter($internalCall = false) {

		$title_arr = array(
			1 => '顺联动力商城'
		);

		$send_arr = array(
			1 => '24小时不打烊，自助购物下单',
			2 => '所有产品7天无理由退换货',
			3 => '汇聚民族好品牌，打造购物好平台',
			4 => '正品货源，欢迎选购',
			5 => '互联网上的创业小镇，创客空间'
		);

		if($internalCall) {
			return array('title'=>$title_arr, 'send'=>$send_arr);
		}

		return self::responseOk(array('title'=>$title_arr, 'send'=>$send_arr));
	}

	//设置转发话术
	//必须参数: titleId
	//必须参数: sendId
	public function setDzd() {
		global $_W;

		$titleId = intval($_POST['titleId']);
		$sendId = intval($_POST['sendId']);

		if(empty($titleId)) return self::responseError(9001, 'Parameter [titleId] is missing.');
		if(empty($sendId)) return self::responseError(9002, 'Parameter [sendId] is missing.');

		$patter = self::getPatter(true);
		if(!$patter['title'][$titleId]) return self::responseError(9003, 'Parameter [titleId] is invalid.');
		if(!$patter['send'][$sendId]) return self::responseError(9004, 'Parameter [sendId] is invalid.');

		$dzdtitle = $patter['title'][$titleId];
		$dzdsendtext = $patter['send'][$sendId];

		pdo_update('bj_qmxk_member', array(
			'dzdtitle' => $dzdtitle,
			'dzdsendtext' => $dzdsendtext
		), array('id' => $_W['mid']));

		return self::responseOk('设置成功.');
	}

	//获取二维码
	public function getQrcode() {
		global $_W;

		require IA_ROOT.'/source/libs/phpqrcode.php';

		$member_id = compute_id($_W['member_id'], 'ENCODE');

		$value = $_W['qrcode_domain'] . '?r='.$member_id;

		$errorCorrectionLevel = 'L';
		$matrixPointSize = '4';

		$target_file = false;

		QRcode::png($value, $target_file, $errorCorrectionLevel, $matrixPointSize);
	}

	//可提现佣金
	public function commissionCanApply() {
		global $_W;

		//可提现
		// 按0点为截至时间，利用sql缓存
		$commtime = mktime(0, 0, 0, date('m'), date('d'), date('Y')) + 86400;
		$cfg['commtime'] = 8;

		if(! empty($cfg['commtime'])) {
			$commtime = mktime(0, 0, 0, date('m'), date('d'), date('Y')) + 86400 - 8 * 24 * 60 * 60;
			// $commtime = time() - 8 * 24 * 60 * 60;
		}

		$where_2 = "AND o.status>=3 AND o.member_id != '{$_W['member_id']}' AND o.updatetime<'{$commtime}'";

		//基本佣金
		$list_2 = pdo_fetchall(
		"SELECT SUM(og.commission*og.total) AS commission " . 
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.status = 0 ".

		"UNION ALL (SELECT SUM(og.commission2*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid2 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.status2 = 0) ".

		"UNION ALL(SELECT SUM(og.commission3*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid3 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.status3 = 0) ".

		"UNION ALL(SELECT SUM(og.commission4*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid4 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.status4 = 0) ".

		"UNION ALL(SELECT SUM(og.commission5*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid5 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.status5 = 0) ".

		"UNION ALL(SELECT SUM(og.commission6*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid6 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.status6 = 0) ".

		"UNION ALL(SELECT SUM(og.commission7*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid7 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.status7 = 0) ".

		"UNION ALL(SELECT SUM(og.commission8*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid8 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.status8 = 0)"
		);

		$commission_2 = 0;
		foreach($list_2 AS $oeder_list) {
			$commission_2 += $oeder_list['commission'];
		}

		$where_2 = "AND o.status='3' AND o.updatetime<'{$commtime}' ";

		//额外佣金
		$list_2 = pdo_fetchall(
		"SELECT SUM(og.extra_commission*og.total) AS commission " . 
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status = 0 ".

		"UNION ALL (SELECT SUM(og.extra_commission2*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid2 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status2 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission3*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid3 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status3 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission4*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid4 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status4 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission5*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid5 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status5 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission6*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid6 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status6 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission7*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid7 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status7 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission8*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid8 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status8 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission9*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid9 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status9 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission10*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid10 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status10 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission11*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid11 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status11 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission12*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid12 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status12 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission13*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid13 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status13 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission14*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid14 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status14 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission15*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid15 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status15 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission16*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid16 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status16 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission17*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid17 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status17 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission18*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid18 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status18 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission19*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid19 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status19 = 0) ".

		"UNION ALL(SELECT SUM(og.extra_commission20*og.total) AS commission ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid20 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status20 = 0) ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160807` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160814` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160821` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160828` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160904` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160911` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') "
		);

		$commission_extra_2 = 0;
		foreach($list_2 AS $oeder_list) {
			$commission_extra_2 += $oeder_list['commission'];
		}
		$commission_available = number_format($commission_2+$commission_extra_2, 2, '.', '');

		$cfg['zhifuCommission'] = 100;
		if($commission_available < $cfg['zhifuCommission']) {
			return self::responseError(9010, '未达到100元结算标准，继续努力.');
		}
		//可提现 结束

		$applyKey = md5('Axpwx88-'.$commission_available.'-'.TIMESTAMP.'-Axpwx88');
		return self::responseOk(array('commission'=>$commission_available, 'timestamp'=>TIMESTAMP,'applyKey'=>$applyKey));
	}

	//申请佣金提现
	//commission
	//timestamp
	//applyKey
	public function commissionApply() {
		global $_W;

		ignore_user_abort(true);
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		ini_set('memory_limit','6000M');

		// 同一个用户，一小时只允许申请一次
		$commission_applyed_key = md5('api-commission-applyed-'.$_W['member_id']);

		if($_W['mc']->get($commission_applyed_key)) {
			return self::responseError(9020, '已有您的申请在处理中，请稍后再申请.');
		} else {
			$_W['mc']->set($commission_applyed_key, $_W['mid'], 0, 3600);
		}

		// 按0点为截至时间，利用sql缓存
		$commtime = mktime(0, 0, 0, date('m'), date('d'), date('Y')) + 86400;
		// $commtime = time();

		$cfg['commtime'] = 8;
		if(! empty($cfg['commtime'])) {
			$commtime = mktime(0, 0, 0, date('m'), date('d'), date('Y')) + 86400 - 8 * 24 * 60 * 60;
			// $commtime = time() - 8 * 24 * 60 * 60;
		}

		$where_2 = "AND o.status>=3 AND o.member_id != '{$_W['member_id']}' AND o.updatetime<'{$commtime}'";
		$orders = pdo_fetchall(
		"SELECT 1 AS level,og.id,og.commission AS commission,og.total,og.createtime,o.shareid AS shareid " . 
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid ='{$_W['mid']}' AND og.status = 0 AND og.createtime>='{$profile['flagtime']}' {$where_2} ".

		"UNION ALL (SELECT 2 AS level,og.id,og.commission2 AS commission,og.total,og.createtime,o.shareid2 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid2 ='{$_W['mid']}' AND og.status2 = 0 AND og.createtime>='{$profile['flagtime']}' {$where_2}) ".

		"UNION ALL(SELECT 3 AS level,og.id,og.commission3 AS commission,og.total,og.createtime,o.shareid3 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid3 ='{$_W['mid']}' AND og.status3 = 0 AND og.createtime>='{$profile['flagtime']}' {$where_2}) ".

		"UNION ALL(SELECT 4 AS level,og.id,og.commission4 AS commission,og.total,og.createtime,o.shareid4 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid4 ='{$_W['mid']}' AND og.status4 = 0 AND og.createtime>='{$profile['flagtime']}' {$where_2}) ".

		"UNION ALL(SELECT 5 AS level,og.id,og.commission5 AS commission,og.total,og.createtime,o.shareid5 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid5 ='{$_W['mid']}' AND og.status5 = 0 AND og.createtime>='{$profile['flagtime']}' {$where_2}) ".

		"UNION ALL(SELECT 6 AS level,og.id,og.commission6 AS commission,og.total,og.createtime,o.shareid6 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid6 ='{$_W['mid']}' AND og.status6 = 0 AND og.createtime>='{$profile['flagtime']}' {$where_2}) ".

		"UNION ALL(SELECT 7 AS level,og.id,og.commission7 AS commission,og.total,og.createtime,o.shareid7 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid7 ='{$_W['mid']}' AND og.status7 = 0 AND og.createtime>='{$profile['flagtime']}' {$where_2}) ".

		"UNION ALL(SELECT 8 AS level,og.id,og.commission8 AS commission,og.total,og.createtime,o.shareid8 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.shareid8 ='{$_W['mid']}' AND og.status8 = 0 AND og.createtime>='{$profile['flagtime']}' {$where_2})"
		);

		$where_2 = "AND o.status='3' AND o.updatetime<'{$commtime}'";
		$orders_extra = pdo_fetchall(
		"SELECT 1 AS level,og.id,og.extra_commission AS commission,og.total,og.createtime,o.extra_shareid AS shareid " . 
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status = 0 ".

		"UNION ALL (SELECT 2 AS level,og.id,og.extra_commission2 AS commission,og.total,og.createtime,o.extra_shareid2 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid2 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status2 = 0) ".

		"UNION ALL(SELECT 3 AS level,og.id,og.extra_commission3 AS commission,og.total,og.createtime,o.extra_shareid3 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid3 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status3 = 0) ".

		"UNION ALL(SELECT 4 AS level,og.id,og.extra_commission4 AS commission,og.total,og.createtime,o.extra_shareid4 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid4 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status4 = 0) ".

		"UNION ALL(SELECT 5 AS level,og.id,og.extra_commission5 AS commission,og.total,og.createtime,o.extra_shareid5 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid5 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status5 = 0) ".

		"UNION ALL(SELECT 6 AS level,og.id,og.extra_commission6 AS commission,og.total,og.createtime,o.extra_shareid6 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid6 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status6 = 0) ".

		"UNION ALL(SELECT 7 AS level,og.id,og.extra_commission7 AS commission,og.total,og.createtime,o.extra_shareid7 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid7 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status7 = 0) ".

		"UNION ALL(SELECT 8 AS level,og.id,og.extra_commission8 AS commission,og.total,og.createtime,o.extra_shareid8 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid8 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status8 = 0) ".

		"UNION ALL(SELECT 9 AS level,og.id,og.extra_commission9 AS commission,og.total,og.createtime,o.extra_shareid9 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid9 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status9 = 0) ".

		"UNION ALL(SELECT 10 AS level,og.id,og.extra_commission10 AS commission,og.total,og.createtime,o.extra_shareid10 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid10 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status10 = 0) ".

		"UNION ALL(SELECT 11 AS level,og.id,og.extra_commission11 AS commission,og.total,og.createtime,o.extra_shareid11 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid11 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status11 = 0) ".

		"UNION ALL(SELECT 12 AS level,og.id,og.extra_commission12 AS commission,og.total,og.createtime,o.extra_shareid12 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid12 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status12 = 0) ".

		"UNION ALL(SELECT 13 AS level,og.id,og.extra_commission13 AS commission,og.total,og.createtime,o.extra_shareid13 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid13 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status13 = 0) ".

		"UNION ALL(SELECT 14 AS level,og.id,og.extra_commission14 AS commission,og.total,og.createtime,o.extra_shareid14 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid14 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status14 = 0) ".

		"UNION ALL(SELECT 15 AS level,og.id,og.extra_commission15 AS commission,og.total,og.createtime,o.extra_shareid15 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid15 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status15 = 0) ".

		"UNION ALL(SELECT 16 AS level,og.id,og.extra_commission16 AS commission,og.total,og.createtime,o.extra_shareid16 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid16 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status16 = 0) ".

		"UNION ALL(SELECT 17 AS level,og.id,og.extra_commission17 AS commission,og.total,og.createtime,o.extra_shareid17 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid17 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status17 = 0) ".

		"UNION ALL(SELECT 18 AS level,og.id,og.extra_commission18 AS commission,og.total,og.createtime,o.extra_shareid18 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid18 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status18 = 0) ".

		"UNION ALL(SELECT 19 AS level,og.id,og.extra_commission19 AS commission,og.total,og.createtime,o.extra_shareid19 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid19 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status19 = 0) ".

		"UNION ALL(SELECT 20 AS level,og.id,og.extra_commission20 AS commission,og.total,og.createtime,o.extra_shareid20 AS shareid ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
			"WHERE o.extra_shareid20 ='{$_W['mid']}' AND o.createtime>='{$profile['flagtime']}' {$where_2} AND og.extra_status20 = 0) "
		);

		$prizes = pdo_fetchall(
		"SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160807` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0' ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160814` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160821` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160828` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160904` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') ".

		"UNION ALL(SELECT prize AS commission ".
			"FROM `ims_bj_qmxk_member_selldata2.20160911` ".
			"WHERE member_id='{$_W['member_id']}' AND settlemented='0') "
		);

		//先判断金额是否达标
		$almoney = 0;
		foreach($orders as $order) {
			if($order['shareid'] == $_W['mid']) {
				if(! empty($order['commission']) && $order['commission'] > 0 && $order['createtime'] >= $profile['flagtime']) {
					$almoney = $almoney + $order['commission'] * $order['total'];
				}
			}
		}
		foreach($orders_extra as $order) {
			if($order['shareid'] == $_W['mid']) {
				if(! empty($order['commission']) && $order['commission'] > 0 && $order['createtime'] >= $profile['flagtime']) {
					$almoney = $almoney + $order['commission'] * $order['total'];
				}
			}
		}
		foreach($prizes as $_prize) {
			$almoney = $almoney + $_prize['commission'];
		}

		$almoney = round($almoney, 2);

		$zhifucommission = $cfg['zhifuCommission'];
		if($almoney < $zhifucommission) {
			return self::responseError(9020, '未达到100结算标准，继续努力.');
		}

		$almoney = 0;
		$ogids = '';
		$comm = '';
		foreach($orders as $order) {
			if($order['shareid'] == $_W['mid']) {
				if(!empty($order['commission']) && $order['commission'] > 0 && $order['createtime'] >= $profile['flagtime']) {
					$update = array(
						'status' . ($order['level'] == 1 ? '' : $order['level']) => 1,
						'applytime' . ($order['level'] == 1 ? '' : $order['level']) => TIMESTAMP
					);

					pdo_update('bj_qmxk_order_goods', $update, array('id' => $order['id']));

					$almoney = $almoney + $order['commission'] * $order['total'];
					$ogids .= $comm . $order['id'];
					$comm = ',';
				}
			}
		}

		$extra_ogids = '';
		$comm = '';
		foreach($orders_extra as $order) {
			if($order['shareid'] == $_W['mid']) {
				if(!empty($order['commission']) && $order['commission'] > 0 && $order['createtime'] >= $profile['flagtime']) {
					$update = array(
						'extra_status' . ($order['level'] == 1 ? '' : $order['level']) => 1,
						'extra_applytime' . ($order['level'] == 1 ? '' : $order['level']) => TIMESTAMP
					);

					pdo_update('bj_qmxk_order_goods', $update, array('id' => $order['id']));

					$almoney = $almoney + $order['commission'] * $order['total'];
					$extra_ogids .= $comm . $order['id'];
					$comm = ',';
				}
			}
		}
		foreach($prizes as $_prize) {
			pdo_update('bj_qmxk_member_selldata2.20160807', array('settlemented'=>'1'), array('member_id' => $_W['member_id']));
			pdo_update('bj_qmxk_member_selldata2.20160814', array('settlemented'=>'1'), array('member_id' => $_W['member_id']));
			pdo_update('bj_qmxk_member_selldata2.20160821', array('settlemented'=>'1'), array('member_id' => $_W['member_id']));
			pdo_update('bj_qmxk_member_selldata2.20160828', array('settlemented'=>'1'), array('member_id' => $_W['member_id']));
			pdo_update('bj_qmxk_member_selldata2.20160904', array('settlemented'=>'1'), array('member_id' => $_W['member_id']));
			pdo_update('bj_qmxk_member_selldata2.20160911', array('settlemented'=>'1'), array('member_id' => $_W['member_id']));

			$almoney = $almoney + $_prize['commission'];
		}
		$almoney = round($almoney, 2);

		$data = array(
			'profileid'		=> $_W['mid'],
			'commission'	=> $almoney,
			'createtime'	=> TIMESTAMP,
			'status'		=> 0,
			'ogids'			=> $ogids,
			'extra_ogids'	=> $extra_ogids
		);
		pdo_insert('bj_qmxk_commissions', $data);

		$tagent = $this->getMember($this->getShareId());
		$this->sendyjsqtz($almoney, $profile['realname'], $tagent['from_user']);

		message('申请成功！', $this->createMobileUrl('commission'), 'success');

	}

	// 计算某个用户下团队用户数
	private function member_group_count($profileid, $flag = 0) {
		global $_W;
		
		if($flag) $sql_ext = " AND flag='{$flag}'";
		
		$agent_member_1 = pdo_fetchall("SELECT id FROM `ims_bj_qmxk_member` WHERE `shareid`='{$profileid}'{$sql_ext}", array(), '', true);
		$str = '';
		$comm = '';
		$agent_count_1 = 0;
		foreach($agent_member_1 as $member) {
			$str .= $comm . $member['id'];
			$comm = ',';
			$agent_count_1 += 1;
		}
		$level_1 = $agent_count_1;
		
		if($str) {
			$agent_member_2 = pdo_fetchall("SELECT id FROM `ims_bj_qmxk_member` WHERE `shareid` IN ({$str}){$sql_ext}", array(), '', true);
			$str = '';
			$comm = '';
			$agent_count_2 = 0;
			foreach($agent_member_2 as $member) {
				$str .= $comm . $member['id'];
				$comm = ',';
				$agent_count_2 += 1;
			}
			$level_2 = $agent_count_2;
		}
		
		if($str) {
			$agent_member_3 = pdo_fetchall("SELECT id FROM `ims_bj_qmxk_member` WHERE `shareid` IN ({$str}){$sql_ext}", array(), '', true);
			$str = '';
			$comm = '';
			$agent_count_3 = 0;
			foreach($agent_member_3 as $member) {
				$str .= $comm . $member['id'];
				$comm = ',';
				$agent_count_3 += 1;
			}
			$level_3 = $agent_count_3;
		}
		
		if($str) {
			$agent_member_4 = pdo_fetchall("SELECT id FROM `ims_bj_qmxk_member` WHERE `shareid` IN ({$str}){$sql_ext}", array(), '', true);
			$str = '';
			$comm = '';
			$agent_count_4 = 0;
			foreach($agent_member_4 as $member) {
				$str .= $comm . $member['id'];
				$comm = ',';
				$agent_count_4 += 1;
			}
			$level_4 = $agent_count_4;
		}
		
		if($str) {
			$agent_member_5 = pdo_fetchall("SELECT id FROM `ims_bj_qmxk_member` WHERE `shareid` IN ({$str}){$sql_ext}", array(), '', true);
			$str = '';
			$comm = '';
			$agent_count_5 = 0;
			foreach($agent_member_5 as $member) {
				$str .= $comm . $member['id'];
				$comm = ',';
				$agent_count_5 += 1;
			}
			$level_5 = $agent_count_5;
		}
		
		if($str) {
			$agent_member_6 = pdo_fetchall("SELECT id FROM `ims_bj_qmxk_member` WHERE `shareid` IN ({$str}){$sql_ext}", array(), '', true);
			$str = '';
			$comm = '';
			$agent_count_6 = 0;
			foreach($agent_member_6 as $member) {
				$str .= $comm . $member['id'];
				$comm = ',';
				$agent_count_6 += 1;
			}
			$level_6 = $agent_count_6;
		}
		
		if($str) {
			$agent_member_7 = pdo_fetchall("SELECT id FROM `ims_bj_qmxk_member` WHERE `shareid` IN ({$str}){$sql_ext}", array(), '', true);
			$str = '';
			$comm = '';
			$agent_count_7 = 0;
			foreach($agent_member_7 as $member) {
				$str .= $comm . $member['id'];
				$comm = ',';
				$agent_count_7 += 1;
			}
			$level_7 = $agent_count_7;
		}
		
		if($str) {
			$agent_member_8 = pdo_fetchall("SELECT id FROM `ims_bj_qmxk_member` WHERE `shareid` IN ({$str}){$sql_ext}", array(), '', true);
			$str = '';
			$comm = '';
			$agent_count_8 = 0;
			foreach($agent_member_8 as $member) {
				$str .= $comm . $member['id'];
				$comm = ',';
				$agent_count_8 += 1;
			}
			$level_8 = $agent_count_8;
		}
		// print_r($level_1 .":". $level_2 .":". $level_3 .":". $level_4 .":". $level_5 .":". $level_6 .":". $level_7 .":". $level_8);
		
		$group_number = $level_1 + $level_2 + $level_3 + $level_4 + $level_5 + $level_6 + $level_7 + $level_8;
		
		return intval($group_number);
		// $list_key_group_count = md5('group-gold-count-'.$_W['weid'].'-'.$profileid);//团队人数
		// $_W['mc']->set($list_key_group_count, $group_number, 0, 0);
	}

}
