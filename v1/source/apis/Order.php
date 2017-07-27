<?php

class Order extends Api {

	//登陆验证
	function __construct() {
		global $_W;
/* */
		if(empty($_W['member_id'])) {
			require IA_ROOT.'/source/apis/User.php';
			if(!User::checklogin()) {
				return self::responseError(1000, '尚未登陆。');
			}

			if(empty($_W['member_id'])) {
				return self::responseError(1001, '尚未登陆。');
			}
		}

//	$_W['member_id'] = '18';
	}

	//所有订单接口
	//可选参数: status=all|0(待付款)|1(待发货)|2(待收货)|3(已完成)|-5(退换货)|-30(待评价，不实际保存在status)
	//可选参数: keyword=关键字
	//可选参数: count=获取数量，最大40，默认10
	//可选参数: usePage=yes|no，是否需要分页，不传输默认否
	//可选参数: page=分页
	public function all() {
		global $_W;

		$status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$force_index = '';
		$orderby = '';

		$where = "WHERE o.member_id='{$_W['member_id']}'";
		$limit = 'LIMIT ';

		$count = ($count && $count <= 40) ? $count : 20;

		$tb_idx = '';
		if($_GET['usePage'] == 'yes') {
			$limit .= ($page-1)*$count.','.$count;
		} else {
			$limit .= ($count && $count <= 40) ? $count : 20;
		}

		if($status == - 5) {//退款中、退货中、换货中
			$where .= " AND (o.status='-2' OR o.status='-3' OR o.status='-4')";
		}else if($status == -30){//待评价
			//查找允许发货的订单
			$oec_ordersns = pdo_fetchallcolumn("SELECT ordersn FROM `ims_bj_qmxk_order_enable_cmt` WHERE member_id=:member_id AND enable_cmt=1", array(':member_id'=>$_W['member_id']));

			$where .= !empty($oec_ordersns)? " AND o.ordersn IN ('". implode("', '", $oec_ordersns) ."') " : " AND 1=2 ";
			$tb_idx = ' FORCE INDEX(member_id) '; //没有status情况使用
		} else {
			if($status == 3) {//已退款、已退货、已换货
				$where .= " AND (o.status='3' OR o.status='-5' OR o.status='-6')";
			} else {
				if($status != 'all') {
					$status = intval($status);
					$where .= " AND o.status='{$status}'";
				}else{
					$tb_idx = ' FORCE INDEX(member_id) ';//没有status情况使用
				}
			}
		}

		

		$orders = array();

		$orders = pdo_fetchall("SELECT o.id AS orderId,o.ordersn,o.sellerid,mp.seller_name,o.price,o.goodsprice,".
			"o.dispatchprice AS freight,o.status,o.paytype,".
			"o.addressid AS addressId,o.expresscom,o.expresssn,o.express,o.createtime,".
			"o.updatetime,o.sendtime,o.tid,o.order_type, ".
			"oec.enable_cmt ".
			"FROM `ims_bj_qmxk_order` o {$tb_idx} ".
			"LEFT JOIN `ims_members_profile` mp ON mp.uid=o.sellerid ".
			"LEFT JOIN `ims_bj_qmxk_order_enable_cmt` oec ON oec.ordersn=o.ordersn ".
			"{$where} ORDER BY o.createtime DESC {$limit}");

		$orders_end = array();

		foreach($orders AS $order) {
			$goods_all = pdo_fetchall("SELECT g.id AS goodsId,og.optionid,g.title,og.optionname,og.act_id, a.little_word, ".
                                "g.thumb,og.price AS marketprice,g.marketprice AS marketprices,og.total,go.title AS go_title ".
				"FROM `ims_bj_qmxk_order_goods` og ".
				"LEFT JOIN `ims_bj_qmxk_goods` g ON g.id=og.goodsid ".
				"LEFT JOIN `ims_bj_qmxk_goods_option` go ON go.id=og.optionid ".
                                "LEFT JOIN `ims_bj_qmxk_activity` a ON a.id=og.act_id ".
				"WHERE og.orderid='{$order['orderId']}'");
			$goodsnum=0;
			foreach ($goods_all AS $key => $goods) {
				$goods_all[$key]['thumb'] = $goods_all[$key]['thumb'] ?  $_W['attachurl'].$goods_all[$key]['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
                                $goods_all[$key]['little_word'] = empty($goods['little_word'])?'':$goods['little_word'];
				$goodsnum+=$goods['total'];
				  //奖励订单商品价格替换成商品价格
            if($order['order_type']==3){
                $goods_all[$key]['marketprice'] = $goods['marketprices'];
                unset($goods_all[$key]['marketprices']);
            }else{
                 unset($goods_all[$key]['marketprices']);
            }

				if( empty($goods['optionname']) && !empty($goods['go_title']) ) {
					$goods_all[$key]['optionname'] = $goods['go_title'];
				}
				unset($goods_all[$key]['go_title']);
			}
			unset($order['orderId']);

			$order['goods'] = $goods_all;
			$order['goodsnum']=$goodsnum;

			//是否可评价
			if($order['status']==3){
				if($order['enable_cmt']==1){
					$order['status'] = '-30';
				}else if($order['enable_cmt']==2){
					$order['status'] = '-31';
				}
			}
			$order['enable_cmt'] = $order['enable_cmt']==1? 'yes' : 'no';

			$orders_end[] = $order;
		}

		$results = array();

		if($_GET['usePage'] == 'yes') {
			$results['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_order` o LEFT JOIN `ims_bj_qmxk_order_enable_cmt` oec ON oec.ordersn=o.ordersn  {$where}");
			$results['itemCount'] = intval($results['itemCount']);
			$results['allPage'] = ceil($results['itemCount']/$count);
			$results['page'] = $page;
			$results['count'] = $count;
		}

		$results['orders'] = $orders_end;

		return self::responseOk($results);
	}

	//订单详情
	//必须参数：ordersn
	public function item() {
		global $_W;

		$ordersn = trim($_GET['ordersn']);

		if(empty($ordersn)) return self::responseError(230, 'Parameter [ordersn] is missing.');
		if(!preg_match('/^([A-Z0-9]+)$/', $ordersn)) return self::responseError(230, 'Parameter [ordersn] is invalid.');

		$item = pdo_fetch("SELECT o.id AS orderId,o.member_id,o.ordersn,o.price,o.goodsprice,".
			"o.dispatchprice AS freight,o.sellerid,mp.seller_name,seller_tel,seller_qq,seller_return_info,".
			"o.status,o.paytype,o.expresscom,o.expresssn,o.express,o.createtime,".
			"o.updatetime,o.sendtime,o.addressid AS addressId,a.address,a.realname,a.mobile,o.tid,o.order_type, ".
			"oec.enable_cmt ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_members_profile` mp ON mp.uid=o.sellerid ".
			"LEFT JOIN `ims_bj_qmxk_address` a ON a.id=o.addressid ".
			"LEFT JOIN `ims_bj_qmxk_order_enable_cmt` oec ON oec.ordersn=o.ordersn ".
			"WHERE o.ordersn='{$ordersn}'");

		if(empty($item) || $item['member_id'] != $_W['member_id']) {
			return self::responseError(231, '抱歉，该订单不存在。');
		}


		//if(@preg_match('/^([^\d]+)?(\d{6,12})(.+)$/i', $item['seller_qq'], $qq)) {
		//	$item['seller_qq'] = $qq[2];
		//}

		empty($item['seller_name']) && $item['seller_name'] = '-';
		empty($item['seller_tel']) && $item['seller_tel'] = '-';
		empty($item['seller_qq']) && $item['seller_qq'] = '-';
		empty($item['seller_return_info']) && $item['seller_return_info'] = '-';

		$goods_all = pdo_fetchall("SELECT g.id AS goodsId,og.optionid,g.title,og.optionname,og.act_id, a.little_word,".
                        " g.thumb,og.price AS marketprice,g.marketprice AS marketprices ,og.total,og.voucher_id,go.title AS go_title ".
			"FROM `ims_bj_qmxk_order_goods` og ".
			"LEFT JOIN `ims_bj_qmxk_goods` g ON g.id=og.goodsid ".
			"LEFT JOIN `ims_bj_qmxk_goods_option` go ON go.id=og.optionid ".
                        "LEFT JOIN `ims_bj_qmxk_activity` a ON a.id=og.act_id ".
			"WHERE og.orderid='{$item['orderId']}'");
		$voucher_id=0;
		foreach ($goods_all AS $key => $goods) {
			$voucher_id=$goods['voucher_id'];
			$goods_all[$key]['thumb'] = $goods_all[$key]['thumb'] ?  $_W['attachurl'].$goods_all[$key]['thumb'] : 'http://statics.sldl.fcmsite.com/empty.gif';
                        $goods_all[$key]['little_word'] = empty($goods['little_word'])?'':$goods['little_word'];
             //奖励订单商品价格替换成商品价格

            if($item['order_type']==3){
                $goods_all[$key]['marketprice'] = $goods['marketprices'];
                unset($goods_all[$key]['marketprices']);
            }else{
                  unset($goods_all[$key]['marketprices']);
            }
			if( empty($goods['optionname']) && !empty($goods['go_title']) ) {
				$goods_all[$key]['optionname'] = $goods['go_title'];
			}
			unset($goods_all[$key]['go_title']);
		}
		unset($item['orderId']);
		unset($item['member_id']);

		//是否在退换货期限内
		if(TIMESTAMP <= ($item['updatetime'] + 7 * 24 * 60 * 60)) {
			$item['can_return'] = 1;
		} else {
			$item['can_return'] = 0;
		}
		$item['mobile'] = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1****$3', $item['mobile']);

		$item['goods'] = $goods_all;
		
		if($voucher_id > 0){
			$voucher=pdo_fetch("SELECT id as voucher_user_id,voucher_id,status,title,remark,use_time,end_time,price,discount,type,is_top,create_time FROM ims_bj_qmxk_voucher_user WHERE id='$voucher_id'");
		}
		
		if(!empty($voucher)){
			$item['voucher']=$voucher;
		}else{
			$item['voucher']=NULL;
		}
		$creatDateTime=array();
		$creatDateTime['y']=date('Y',$item['createtime']);
		$creatDateTime['m']=date('m',$item['createtime']);
		$creatDateTime['d']=date('d',$item['createtime']);
		$creatDateTime['h']=date('H',$item['createtime']);
		$creatDateTime['i']=date('i',$item['createtime']);
		$creatDateTime['s']=date('s',$item['createtime']);
		$item['creatDateTime']=$creatDateTime;

		//是否可评价
		if($item['status']==3){
			if($item['enable_cmt']==1){
				$item['status'] = '-30';
			}else if($item['enable_cmt']==2){
				$item['status'] = '-31';
			}
		}
		$item['enable_cmt'] = $item['enable_cmt']==1? 'yes' : 'no';
		
		//聊天功能是否开启
		$seller_chat = pdo_fetch("SELECT * FROM " . tablename('members_chat') . " WHERE `uid` = '{$item['sellerid']}'");
		if($seller_chat && $seller_chat['state'] == 1){
			$chat=1;
		}else{
			$chat=0;
		}
		$item['chat']=$chat;
		return self::responseOk($item);
	}

	//订单数量
	//可选参数: status=all|0(待付款)|1(待发货)|2(待收货)|3(已完成)|-5(退换货)
	public static function count($internalCall = false) {
		global $_W;

		if($internalCall) {
			$pending 	= pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND status='0'");
			$waitsend 	= pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND status='1'");
			$receipt 	= pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND status='2'");
			$return 	= pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND (status='-2' OR status='-3' OR status='-4')");
			//待评价
			$uncomment	= pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order_enable_cmt WHERE member_id='{$_W['member_id']}' AND enable_cmt='1'");

			$result = array(
				'pending'	=> intval($pending),
				'waitsend'	=> intval($waitsend),
				'receipt'	=> intval($receipt),
				'return'	=> intval($return),
				'uncomment'	=> intval($uncomment)
			);

			return $result;
		}

		$status = isset($_GET['status']) ? trim($_GET['status']) : '';

		if(!isset($_GET['status'])) {
			$all = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}'");
			$pending = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND status='0'");
			$receipt = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND status='2'");
			$waitsend = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND status='1'");
			$finished = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND (status='3' OR status='-5' OR status='-6')");
			$return = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' AND (status='-2' OR status='-3' OR status='-4')");

			$result = array(
				'all'		=> intval($all),
				'pending'	=> intval($pending),
				'waitsend'	=> intval($waitsend),
				'receipt'	=> intval($receipt),
				'finished'	=> intval($finished),
				'return'	=> intval($return)
			);

			return self::responseOk($result);
		} else {
			$where = '';
			if($status == 'all') {
				$where .= '';
			} elseif($status == - 5) {//退款中、退货中、换货中
				$where .= "AND (status='-2' OR status='-3' OR status='-4')";
			} elseif($status == 3) {//已退款、已退货、已换货
				$where .= "AND (status='3' OR status='-5' OR status='-6')";
			} elseif($status == 0 || $status == 1 || $status == 2) {
				$where .= "AND status='{$status}'";
			} else {
				return self::responseOk(0);
			}

			$count = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_order WHERE member_id='{$_W['member_id']}' {$where}");
			return self::responseOk(intval($count));
		}
	}

	//取消订单
	//必须参数：ordersn
	public function cancel() {
		global $_W;

		$ordersn = trim($_GET['ordersn']);

		if(empty($ordersn)) return self::responseError(240, 'Parameter [ordersn] is missing.');
		if(!preg_match('/^([A-Z0-9]+)$/', $ordersn)) return self::responseError(240, 'Parameter [ordersn] is invalid.');

		$item = pdo_fetch("SELECT id AS orderId,ordersn,member_id,paytype,status,createtime,order_type FROM `ims_bj_qmxk_order` WHERE ordersn='{$ordersn}'");

		if(empty($item) || $item['member_id'] != $_W['member_id']) {
			return self::responseError(241, '抱歉，该订单不存在。');
		}
		
		if($item['status'] == 1) {
		    return self::responseError(242, '该订单目前已经支付成功，无法取消。');
		}

		if(($item['paytype'] == 3 && $item['status'] == 1) || $item['status'] == 0) {
			pdo_update('bj_qmxk_order', array(
				'status' => - 1,
				'updatetime' => TIMESTAMP
			), array('id' => $item['orderId']));

			self::addOrderLog(array(
				'orderid' => $item['orderId'],
				'ordersn' => $item['ordersn'],
				'status' => 'cancel',
				'action_user' => 'user',
				'member_id' => $_W['member_id'],
				'adminid' => 0,
				'sellerid' => 0,
				'dateline' => TIMESTAMP,
				'remark' => ''
			));
			//如果是拼单订单，拼单状态改为拼单失败
			if($item['order_type']==1){
				pdo_update('ims_bj_qmxk_groupon_member', array(
						'status' => 2
					), array('orderid' => $item['orderId']));
			}
			//返还优惠券
			$goods=pdo_fetch("SELECT voucher_id,sum(offerprice) as dprice FROM `ims_bj_qmxk_order_goods` WHERE orderid='".$item['orderId']."'");
			if(intval($goods['voucher_id']) > 0){
				$voucher=pdo_fetch("SELECT discount FROM `ims_bj_qmxk_voucher_user` WHERE id='".$goods['voucher_id']."'");
				
				if( ($voucher['discount']-0.02 < $goods['dprice'] ) && ($voucher['discount']+0.02 > $goods['dprice'] ) ){//误差2分钱之内，认为对这个店铺用了一张优惠券，这种情况下把优惠券状态改为未使用
					pdo_update('bj_qmxk_voucher_user', array(
						'status' => 0
					), array('id' => $goods['voucher_id']));
				}
			}
			
			

			return self::responseOk(array('result'=>'success','msg'=>'订单取消成功。'));
		}

		if($item['status'] == -1) {
			return self::responseError(242, '该订单目前已经是取消状态。');
		}

		if($item['status'] == 2) {
			return self::responseError(243, '商家已发货无法取消订单。');
		}

		if($item['status'] > 0 && $item['createtime']+30*60 < TIMESTAMP) {
			return self::responseError(244, '已进入发货流程，无法取消订单。');
		}

		return self::responseError(245, '该订单目前无法取消。');
	}

	//申请退款
	//必须参数: ordersn
	//必须参数: rsreson=退款原因
	public function returnpay() {
		global $_W;

		$ordersn = trim($_POST['ordersn']);
		$rsreson = cutstr(htmlspecialchars($_POST['rsreson']), 200);

		if(empty($ordersn)) return self::responseError(250, 'Parameter [ordersn] is missing.');
		if(!preg_match('/^([A-Z0-9]+)$/', $ordersn)) return self::responseError(250, 'Parameter [ordersn] is invalid.');

		$item = pdo_fetch("SELECT id AS orderId,ordersn,goodsprice,addressid,sellerid,member_id,paytype,status,createtime FROM `ims_bj_qmxk_order` WHERE ordersn='{$ordersn}'");

		if(empty($item) || $item['member_id'] != $_W['member_id']) {
			return self::responseError(251, '抱歉，该订单不存在。');
		}

		if($item['paytype'] == 3) {
			return self::responseError(252, '货到付款订单不能进行退款操作。');
		}
		if($item['status'] == 0) {
			return self::responseError(253, '未付款的订单无法申请退款。');
		}
		if($item['status'] == 2) {
			return self::responseError(254, '订单已发货暂时无法退款。');
		}
		if($item['status'] != 1) {
			return self::responseError(254, '订单未付款状态不能申请退款。');
		}

		pdo_update('bj_qmxk_order', array(
			'status' => - 2,
			'rsreson' => $rsreson
		), array('id' => $item['orderId']));

		self::addOrderLog(array(
			'orderid' => $item['orderId'],
			'ordersn' => $item['ordersn'],
			'status' => 'returnpay',
			'action_user' => 'user',
			'member_id' => $_W['member_id'],
			'adminid' => 0,
			'sellerid' => 0,
			'dateline' => TIMESTAMP,
			'remark' => ''
		));
		
		//如果是洪伟公司，同步订单确认收货信息
			$allow_pf = false;//是否允许批发
			$allow_pf = in_array($item['sellerid'],ConfigModel::$WHOLESALE_SHOPS);
			if($allow_pf){
				$pf_config = ConfigModel::$WHOLESALE_CONFIG[$item['sellerid']];
				
				$push = array();
				$push['type'] = 'returnpay';
				$orderid = $item['orderId'];
				$order_push = array();
				$order_push['order_id'] = $orderid;
				$order_push['member_id'] = $item['member_id'];
				$order_push['sellerid'] = $item['sellerid'];
				$order_push['ordersn'] = $item['ordersn'];
				$order_push['order_amount'] = $item['goodsprice'];
				$address = pdo_fetch("SELECT * FROM `ims_bj_qmxk_address` WHERE `id`='{$item['addressid']}' LIMIT 1");
				$order_push['dist_id'] = $address['district_id'];
				
				$sign_string = 'orderid='.$orderid.'&ordersn='.$order_push['ordersn'].'&member_id='.$order_push['member_id'].'&seller_id='.$order_push['seller_id'].'&dist_id='.$order_push['dist_id'].'&order_amount='.$order_push['order_amount'];
				$sign = md5(md5($sign_string).$pf_config['secret_key']);
				$push['order'] = $order_push;
				$push['sign'] = $sign;
				$data = curl_post($pf_config['data_url'],json_encode($push));
				$data = json_decode($data,true);
				if($data['suc'] != 'success'){
					//同步失败暂不做处理，由洪伟系统，计划任务自动确认收货。
				}	
			}

		return self::responseOk(array('result'=>'success','msg'=>'申请退款成功，请等待审核。'));
	}

	//确认收货
	//必须参数：ordersn
	public function finish() {
		global $_W;

		$ordersn = trim($_GET['ordersn']);

		if(empty($ordersn)) return self::responseError(260, 'Parameter [ordersn] is missing.');
		if(!preg_match('/^([A-Z0-9]+)$/', $ordersn)) return self::responseError(260, 'Parameter [ordersn] is invalid.');

		$item = pdo_fetch("SELECT id AS orderId,ordersn,goodsprice,addressid,member_id,sellerid,paytype,status,createtime, order_type FROM `ims_bj_qmxk_order` WHERE ordersn='{$ordersn}'");

		if(empty($item) || $item['member_id'] != $_W['member_id']) {
			return self::responseError(261, '抱歉，该订单不存在。');
		}

		if($item['status'] == 0) {
			return self::responseError(262, '此订单尚未付款。');
		}
		if($item['status'] == 1) {
			return self::responseError(263, '此订单尚未发货。');
		}

		if($item['status'] == 2) {
			//积分
			//$this->setOrderCredit($orderid, $_W['weid']);
		}

		if($item['status'] == 3) {//禁止重复确认收货
			return self::responseError(264, '订单已经确认收货了。');
		}

		//# 并发控制
		$do_token_key   = "do_order_finish_{$ordersn}";
        if(CacheModel::getInstance()->doBegin($do_token_key)==false){
        	return self::responseError(264, '操作太频繁了，请稍后再试');
        }

        //事务开始
        pdo_begin();

		pdo_update('bj_qmxk_order', array(
			'status' => 3,
			'updatetime' => TIMESTAMP
		), array('id' => $item['orderId']));



		self::addOrderLog(array(
			'orderid' => $item['orderId'],
			'ordersn' => $item['ordersn'],
			'status' => 'confirm',
			'action_user' => 'user',
			'member_id' => $_W['member_id'],
			'adminid' => 0,
			'sellerid' => 0,
			'dateline' => TIMESTAMP,
			'remark' => ''
		));

		OrderModel::getInstance()->onFinish($item['member_id'], $item['sellerid'], $item['ordersn']);

		//# 添加好评分,非奖品订单才执行
        if ($item['order_type'] != 3) {
            CreditModel::getInstance()->addExpLogByOrdersn($item['member_id'], ConfigModel::CREDIT_BUY_ACTID, $ordersn, ConfigModel::CREDIT_SON_BUY_ACTID);
			
			//如果是洪伟公司，同步订单确认收货信息
			$allow_pf = false;//是否允许批发
			$allow_pf = in_array($item['sellerid'],ConfigModel::$WHOLESALE_SHOPS);
			if($allow_pf){
				$pf_config = ConfigModel::$WHOLESALE_CONFIG[$item['sellerid']];
				
				$push = array();
				$push['type'] = 'finish_order';
				$orderid = $item['orderId'];
				$order_push = array();
				$order_push['order_id'] = $orderid;
				$order_push['member_id'] = $item['member_id'];
				$order_push['sellerid'] = $item['sellerid'];
				$order_push['ordersn'] = $item['ordersn'];
				$order_push['order_amount'] = $item['goodsprice'];
				$address = pdo_fetch("SELECT * FROM `ims_bj_qmxk_address` WHERE `id`='{$item['addressid']}' LIMIT 1");
				$order_push['dist_id'] = $address['district_id'];
				
				$sign_string = 'orderid='.$orderid.'&ordersn='.$order_push['ordersn'].'&member_id='.$order_push['member_id'].'&seller_id='.$order_push['seller_id'].'&dist_id='.$order_push['dist_id'].'&order_amount='.$order_push['order_amount'];
				$sign = md5(md5($sign_string).$pf_config['secret_key']);
				$push['order'] = $order_push;
				$push['sign'] = $sign;
				$data = curl_post($pf_config['data_url'],json_encode($push));
				$data = json_decode($data,true);
				if($data['suc'] != 'success'){
					//同步失败暂不做处理，由洪伟系统，计划任务自动确认收货。
				}	
			}
        }
		


		//事务处理判断
        try {
        	pdo_commit();
        } catch (Exception $e) {
        	pdo_rollback();//回滚事务
			return self::responseError(264088, '系统繁忙，请重试');
        }

		//# 释放并发控制
        CacheModel::getInstance()->doEnd($do_token_key);


    	/*
		//向上级发送通知、改成队列
		$this->checkisAgent($from_user, $profile);
		$tagent = $this->getMember($this->getShareId($from_user), true);
		$tagent['from_user'] && $this->sendxjdlshtz($item['ordersn'], $item['price'], $profile['realname'], $tagent['from_user'], compute_id($profile['id'], 'ENCODE'));
		*/

		return self::responseOk(array('result'=>'success','msg'=>'确认收货完成。'));
	}

	//申请售后服务
	//必须参数：ordersn
	//必须参数：type=return|replace|repair
	public function service() {
		global $_W;

		return self::responseError(270, '维护中，请稍后再试。');

	}
	private function is_mav(){//判断用户是否有游戏优惠券可用
		global $_W;
		$member_id=$_W['member_id'];
		$time=TIMESTAMP;
		$vu = pdo_fetch("SELECT * FROM `ims_bj_qmxk_voucher_user` WHERE member_id='$member_id' AND is_top = '1' AND pay ='1' and end_time > '$time' and status='0' limit 1");
		if(empty($vu)){
			return false;
		}else{
			return true;
		}
	}
	private function is_youhui($gid,$sid){//判断一个商品是否可以使用优惠券，以及优惠券的类别
		$result=array();
		$time=TIMESTAMP;
		if($this->is_mav()){
			$result['youhui']=true;
			$result['pingtai']=true;
			return $result;
		}
		$is_all=pdo_fetchall("SELECT * FROM `ims_bj_qmxk_voucher` WHERE is_top = '1' AND is_all ='1' and start_time < '$time' and end_time > '$time' and status='1'");
		if($is_all){//如果有平台所有商品通用券，那么所有商品都参加优惠活动
			$result['youhui']=true;
			$result['pingtai']=true;
			return $result;
		}
		$pingtai=pdo_fetchall("SELECT * FROM `ims_bj_qmxk_voucher` WHERE is_top = '1' AND is_all ='0' and start_time < '$time' and end_time > '$time' and status='1'");
		$youhui=false;
		if($pingtai){// 如果有平台非所有商品参加的通用券
			foreach($pingtai as $value){
				$voucher_id=$value['id'];
				$gvoucher=pdo_fetch("SELECT * FROM `ims_bj_qmxk_voucher_goods` WHERE goodsid = '$gid' AND voucher_id ='$voucher_id'");
				if($gvoucher){
					$youhui=true;
					break;
				}
			}
			if($youhui){
				$result['youhui']=true;
				$result['pingtai']=true;
				return $result;
			}
		}
		
		$shangjia_is_all=pdo_fetchall("SELECT * FROM `ims_bj_qmxk_voucher` WHERE sellerid = '$sid' AND is_all ='1' and start_time < '$time' and end_time > '$time' and status='1'");
		
		if($shangjia_is_all){//如果有店铺所有商品通用的优惠券
			$result['youhui']=true;
			$result['pingtai']=false;
			return $result;
		}
		$shangjia=pdo_fetchall("SELECT * FROM `ims_bj_qmxk_voucher` WHERE sellerid = '$sid' AND is_all ='0' and start_time < '$time' and end_time > '$time'  and status='1'");
		$youhui=false;
		if($shangjia){// 如果有店铺非所有商品参加的优惠券
			foreach($shangjia as $value){
				$voucher_id=$value['id'];
				$gvoucher=pdo_fetch("SELECT * FROM `ims_bj_qmxk_voucher_goods` WHERE goodsid = '$gid' AND voucher_id ='$voucher_id'  and sellerid = '$sid'");
				if($gvoucher){
					$youhui=true;
					break;
				}
			}
			if($youhui){
				$result['youhui']=true;
				$result['pingtai']=false;
				return $result;
			}
		}
		return array('youhui'=>false);	
	}

	//订单确认界面
	//必须参数{goods}
	//可选参数：addressId
	public function confirm() {
		global $_W;

		// 判断来源 from = 0 表示普通订单详情页，1 表示中奖订单
        $from = isset($_POST['from']) ? intval($_POST['from']) : 0;
        if ($from == 0) {

            $goods = $_POST['goods'];
            $addressid = intval($_POST['addressId']);
            $pid = intval($_POST['pid']);
            $tid = intval($_POST['tid']);
            $tuan = false;
            if ($pid > 0) {
                $tuan = $this->pintuan($pid, $_W['member_id'], $tid);
                if (!$tuan) {
                    return self::responseError(999, '不符合参团条件');
                }
            }

            if (empty($goods)) return self::responseError(200, 'Parameter [goods] is missing.');
            if (!is_array($goods) || empty($goods[0]['goodsId'])) return self::responseError(200, 'Parameter [goods] is invalid.');

            $allgoods = self::checkGoods(true);


            if ($addressid > 0) {
                $address = pdo_fetch("SELECT id AS addressId,district_id,address,realname,mobile,member_id,deleted FROM `ims_bj_qmxk_address` WHERE id='{$addressid}'");
                if (empty($address) || $address['member_id'] != $_W['member_id'] || $address['deleted']) {
                    //return self::responseError(210, '请选择收货地址！');
                }

                unset($address['member_id']);
                unset($address['deleted']);
            } else {
                $address = pdo_fetch("SELECT id AS addressId,district_id,address,realname,mobile FROM `ims_bj_qmxk_address` WHERE member_id='{$_W['member_id']}' AND deleted='0' AND isdefault='1' LIMIT 1");
                if (empty($address)) {
                    $address = pdo_fetch("SELECT id AS addressId,district_id,address,realname,mobile FROM `ims_bj_qmxk_address` WHERE member_id='{$_W['member_id']}' AND deleted='0' LIMIT 1");
                }
            }

            if (empty($address)) {
                $address = null;
            } else {
                //$address['district_id'] = 371727;
                if (intval($address['district_id']) > 0) {
                    require IA_ROOT . '/source/libs/delivery.class.php';
                    $address_tree = Delivery::fetch_position($address['district_id']);
                    if ($address_tree[0] == '中国大陆') {
                        unset($address_tree[0]);
                    }
                    $address_parent = implode(' ', $address_tree);
                    $address['address'] = $address_parent . ' ' . $address['address'];
                    $address['mobile'] = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1****$3', $address['mobile']);
                    $address_tree = Delivery::fetch_parents($address['district_id']);
                } else {
                    $address = null;
                }


            }

            $seller = array();

            $total_price = 0.00;
            $order_count = 0;//订单数量

            $goods_price = 0.00;//商品总价
            $goods_price1 = 0.00; //参加优惠活动的商品的总价
            $total_freight = 0.00;//总运费
            $total_num = 0; //商品总数量
            $total_num1 = 0;//参加优惠活动的商品的总数量

            foreach ($allgoods as $sellerid => $seller_goods) {//每次循环是一个订单
                $sgoods_price = 0.00;//店铺商品总价
                $sgoods_price1 = 0.00; //店铺参加优惠活动的商品的总价
                $stotal_freight = 0.00;//店铺总运费
                $stotal_num = 0; //店铺商品总数量
                $stotal_num1 = 0;//店铺参加优惠活动的商品的总数量
                foreach ($seller_goods AS $index => $row) {//计算本订单的商品金额、运费
                    if ($tuan) {
                        $row['marketprice'] = $tuan['grouponprice'];
                    } else {
                        //活动价
//					$key="ACTIVITY".$row['goodsId'];
//					if($activity = $_W['mc']->get($key)){//如果商品参加了活动，按活动价格计算
                        if ($row['act_id'] != 0) {//如果商品参加了活动，按活动价格计算
                            $activity = pdo_fetch("SELECT act_start_time,act_end_time FROM " . tablename('bj_qmxk_activity') . " WHERE id = {$row['act_id']}");
                            if (empty($activity['act_start_time']) || empty($activity['act_end_time'])) {
                                $activity_entry = pdo_fetch("SELECT id,goods_id,act_id,actprice,costprice,status,start_time,end_time FROM " . tablename('bj_qmxk_activity_entry') . " WHERE act_id='{$row['act_id']}' AND goods_id='{$row['goodsId']}' AND status=2 ORDER BY id DESC");
                                $activity['act_start_time'] = $activity_entry['start_time'];
                                $activity['act_end_time'] = $activity_entry['end_time'];
                            }
                            if ($activity['act_start_time'] < time() && $activity['act_end_time'] > time()) {
                                $activity_entry = pdo_fetch("SELECT id,goods_id,act_id,actprice,costprice,status FROM " . tablename('bj_qmxk_activity_entry') . " WHERE act_id='{$row['act_id']}' AND goods_id='{$row['goodsId']}' AND status=2 ORDER BY id DESC");
                                $row['marketprice'] = $activity_entry['actprice'];
                                //$row['costprice'] = $activity_entry['costprice'];
                                if ($row['optionid']) {
                                    $option1 = pdo_fetch("SELECT id,goods_id,actprice,costprice FROM " . tablename('bj_qmxk_activity_goods') . " WHERE entry_id='{$activity_entry['id']}' AND goods_id='{$row['goodsId']}' AND spec_id='{$row['optionid']}'");
                                    $row['marketprice'] = $option1['actprice'];
                                    //$row['costprice'] = $option1['costprice'];
                                }
                            }
                        }
                    }
                    $goods_price += $row['marketprice'] * $row['amount'];//商品总价
                    $total_num += $row['amount'];//商品总数量
                    $sgoods_price += $row['marketprice'] * $row['amount'];//店铺商品总价
                    $stotal_num += $row['amount'];//商品总数量
                    $youhui = $this->is_youhui($row['goodsId'], $sellerid);
                    if ($youhui['youhui']) {//如果该商品参加优惠活动
                        if ($youhui['pingtai']) {
                            $goods_price1 += $row['marketprice'] * $row['amount'];//可优惠商品总价
                            $totalnum1 += $row['amount'];//可优惠商品总数量
                            $sgoods_price1 += $row['marketprice'] * $row['amount'];//子订单可优惠商品总价
                            $stotal_num1 += $row['amount'];//子订单可优惠商品总数量
                        } else {
                            $sgoods_price1 += $row['marketprice'] * $row['amount'];//子订单可优惠商品总价
                            $stotal_num1 += $row['amount'];//子订单可优惠商品总数量
                        }
                    }
                    //$row['amount'] = 1;
                    //获取商品物流配置
                    //根据当前地址，计算运费
                    //非免运费地区，按件数配置计算运费
                    //$total_freight += $row['marketprice'];
                    //$row['delivery_id'] = 19;
                    if ($row['delivery_id'] > 1 && $address['district_id'] > 0) {
                        $good_yunfei = 0;
                        //运费模板
                        $_delivery = pdo_fetch("SELECT * FROM `ims_delivery` WHERE id='{$row['delivery_id']}'");

                        if ($_delivery && $_delivery['deleted'] == 0) {
                            if (!$_delivery['issendfree']) {
                                if ($row['amount'] > $_delivery['start_def']) { //大于首件
                                    $good_yunfei += $_delivery['postage_def'];//首件运费
                                    $good_yunfei += floor(($row['amount'] - $_delivery['start_def']) / $_delivery['plus_def']) * $_delivery['postageplus_def'];//续件运费
                                } else {
                                    $good_yunfei += $_delivery['postage_def'];
                                }
                            }

                            //$_delivery['id'] = 28;
                            $_deliverys = pdo_fetchall("SELECT start,postage,plus,postageplus,district_id FROM `ims_delivery_district` WHERE delivery_id='{$_delivery['id']}'");
                            if ($_deliverys) {
                                foreach ($_deliverys AS $_delivery_value) {
                                    if ($_delivery_value['district_id']) {
                                        $_district_ids = explode(',', $_delivery_value['district_id']);
                                        $has_district = false;
                                        foreach ($address_tree AS $address_district) {
                                            if (in_array($address_district['id'], $_district_ids)) {
                                                $has_district = true;
                                                break;
                                            }
                                        }

                                        if ($has_district) {
                                            $good_yunfei = 0;
                                            //初始运费
                                            if ($row['amount'] > $_delivery_value['start']) { //大于首件

                                                $good_yunfei += $_delivery_value['postage'];//首件运费
                                                $good_yunfei += floor(($row['amount'] - $_delivery_value['start']) / $_delivery_value['plus']) * $_delivery_value['postageplus'];//续件运费


                                            } else {
                                                $good_yunfei += $_delivery_value['postage'];
                                                //$row['freight'] = sprintf('%.2f', $_delivery_value['postage']);//商品运费
                                            }

                                            //加件运费
                                        }
                                    }
                                }
                            }
                        } else {
                            $good_yunfei += 0.00;
                        }
                        $total_freight += $good_yunfei;
                        $row['freight'] = sprintf('%.2f', $good_yunfei);//商品运费

                    } else {
                        $total_freight += 0.00;
                        $row['freight'] = sprintf('%.2f', 0);//商品运费
                    }

                    $stotal_freight += $row['freight'];

                    $seller[$sellerid]['sellerid'] = $sellerid;
                    $seller[$sellerid]['seller_name'] = $row['seller_name'];

                    unset($row['delivery_id']);
                    $seller[$sellerid]['goods'][] = $row;

                }
                $seller[$sellerid]['sellerid'] = $sellerid;
                $seller[$sellerid]['seller_name'] = $row['seller_name'];
                $seller[$sellerid]['order_freight'] = sprintf('%.2f', $stotal_freight);
                $seller[$sellerid]['goods_price'] = sprintf('%.2f', $sgoods_price);
                $seller[$sellerid]['order_price'] = sprintf('%.2f', $sgoods_price);
                $seller[$sellerid]['order_total_price'] = sprintf('%.2f', $sgoods_price + $stotal_freight);
                $seller[$sellerid]['total_num'] = $stotal_num;
                $seller[$sellerid]['total_num1'] = $stotal_num1;
                $seller[$sellerid]['goods_price1'] = sprintf('%.2f', $sgoods_price1);

                $order_count += 1;

                $time = TIMESTAMP;
                //优惠券
                $member_id = $_W['member_id'];
                $youhui1 = pdo_fetchall('select vu.* from ims_bj_qmxk_voucher_user as vu left join ims_bj_qmxk_voucher as v on v.id=vu.voucher_id left join ims_bj_qmxk_voucher_seller as vs on vs.voucher_id= vu.voucher_id  where ' . " vu.member_id = '$member_id' and vu.price <= '$sgoods_price1' and v.start_time < '$time' and v.end_time > '$time' and v.status=1 and v.is_top='1' and ( (vs.sellerid='$sellerid' and  vs.status='1') or v.is_all='1')    and vu.status='0'");

                $youhui2 = pdo_fetchall('select vu.* from ims_bj_qmxk_voucher_user as vu left join ims_bj_qmxk_voucher as v on v.id=vu.voucher_id  where ' . " vu.member_id = '$member_id' and vu.price <= '$sgoods_price1' and v.start_time < '$time' and v.end_time > '$time' and v.status=1  and  v.sellerid='$sellerid'  and vu.status='0'");
                $youhui = array_merge($youhui2, $youhui1);

                $seller[$sellerid]['voucher'] = $youhui ? $youhui : array();
            }


            $seller_end = array();
            foreach ($seller AS $sellerid => $row) {
                $seller_end[] = $row;
            }

            $total_price = $goods_price + $total_freight;

            if ($address) {
                unset($address['district_id']);
            }
            //平台优惠券
            $voucher = pdo_fetchall('select vu.* from ims_bj_qmxk_voucher_user as vu left join ims_bj_qmxk_voucher as v on v.id=vu.voucher_id  where ' . " vu.member_id = '$member_id' and vu.price <= '$goods_price1' and vu.end_time > '$time' and  vu.is_top='1' and vu.pay='1' and vu.status='0'");

            $result = array(
                'pid' => $pid,
                'tid' => $tid,
                'tuan' => $tuan ? true : false,
                'seller' => $seller_end, //子订单列表
                'paytype' => array('weixin', 'alipay', 'balance', 'credit2'), //支付方式
                'goods_price' => sprintf('%.2f', $goods_price),//商品总价
                'goods_price1' => sprintf('%.2f', $goods_price1),//可优惠的商品总价商品总价
                'total_freight' => sprintf('%.2f', $total_freight),//总运费
                'total_price' => sprintf('%.2f', $total_price),//订单总价
                'total_num' => $total_num,//商品总数量
                'total_num1' => $total_num1,//可优惠商品的总数量
                'voucher' => $voucher ? $voucher : array(),
                'address' => $address //默认地址信息
            );

            return self::responseOk($result);
        } elseif ($from == 1) {
            return $this->prizeConfirm();
        }
	}

	// 奖品确认订单详情页
    private function prizeConfirm() {
        global $_W;

        $prize_member_id = $_POST['prize_member_id'];
        if (empty($prize_member_id)) {
            return self::responseError(-1, 'Parameter [prize_member_id] is missing.');
        }

        $prize = GamesModel::getInstance()->getPrizeDetail($prize_member_id);
        if (empty($prize) || $prize['type'] != 'prize_goods' || $prize['member_id'] != $_W['member_id']) {
            return self::responseError(-1, 'Parameter [prize_member_id] is invalid.');
        }

        $goods = GamesModel::getInstance()->getGoodsDetail($prize['prize_id']);
        if (empty($goods)) {
            return self::responseError(-1, 'Goods is not exist.');
        }

        $address = pdo_fetch("SELECT id AS addressId,district_id,address,realname,mobile FROM `ims_bj_qmxk_address` WHERE member_id='{$_W['member_id']}' AND deleted='0' AND isdefault='1' LIMIT 1");
        if(empty($address)) {
            $address = pdo_fetch("SELECT id AS addressId,district_id,address,realname,mobile FROM `ims_bj_qmxk_address` WHERE member_id='{$_W['member_id']}' AND deleted='0' LIMIT 1");
        }

        if(empty($address)) {
            $address = null;
        } else {
            //$address['district_id'] = 371727;
            if(intval($address['district_id']) > 0) {
                require IA_ROOT . '/source/libs/delivery.class.php';
                $address_tree = Delivery::fetch_position($address['district_id']);
                if($address_tree[0] == '中国大陆') {
                    unset($address_tree[0]);
                }
                $address_parent = implode(' ', $address_tree);
                $address['address'] = $address_parent. ' '. $address['address'];
                $address['mobile'] = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1****$3', $address['mobile']);
                $address_tree = Delivery::fetch_parents($address['district_id']);
            }else{
                $address = null;
            }
        }

        $sql = "SELECT seller_name FROM ims_members_profile WHERE uid = ?";
        $sellerName = pdo_fetchcolumn($sql, array($goods['sellerid']));

        $result = array(
            "pid" => 0,
            "tid" => 0,
            "tuan" => 3,//0=普通，1=团购，2=活动，3=奖品订单
            'seller' => array(
                0 => array(
                    "sellerid" => $goods['sellerid'], //商家ID
                    "seller_name" => $sellerName,//店铺名称
                    "goods_price" => "0.00",
                    "order_freight" => "0.00",
                    "order_price" => "0.00",
                    "order_total_price" => "0.00",
                    "total_num" => 1,
                    "goods" => array(
                        0 => array(
                            'title' => $goods['title'],
                            'marketprice' => $goods['marketprice'],
                            "thumb" => $goods['thumb'] ? $_W['attachurl'] . $goods['thumb'] : 'http://statics.sldl.fcmsite.com/empty.gif',
                            "optionname" => "", //规格名称1
                            "amount" => 1, //购买数量，客户端需要跟total和maxbuy比较
                            "freight" => "0.00"//商品运费
                        )
                    )
                )
            ),
            'total_freight'	=> "0.00",//总运费
            'total_price'	=> "0.00",//订单总价
            'total_num'	=> 1,//商品总数量
            "total_num1" => 0, //可以使用平台优惠券的商品的总数量
            "voucher" => array(), //平台优惠券列表
            'address'		=> $address //默认地址信息
        );

        return self::responseOk($result);
    }

   	/**
     *
     * 校验偏远地区不发货
     *
     * @param $address_tree
     * @param $allgoods
     * @return stdClass
     */
    private function check_address_can_deliver($address_tree, $allgoods) {
        $result = new stdClass();
        $result->code = FALSE;
        $result->message = '';
        $result->data = null;
        
        if (empty($address_tree) || empty($allgoods) || !is_array($allgoods)) {
            $result->message = '参数错误';
            return $result;
        }

        $seller_ids = array();
        foreach ($allgoods as $_seller_id => $_good) {
            if (!in_array($_seller_id, $seller_ids)) {
                $seller_ids[] = $_seller_id;
            }
        }

        $seller_id_str = $seller_ids ? implode(',', $seller_ids) : 0;
        $sql = "SELECT uid, no_delivery_area FROM ims_members_profile WHERE uid IN($seller_id_str)";
        $member_profiles = pdo_fetchall($sql);        
        $no_delivery_area_arr = array();
        $check_flag = 'province';
        //没有查找到则不做校验
        if (empty($member_profiles)) {
        	$result->code = TRUE;
        	$result->message = '没有配置不发货地区';
        	return $result;
        }
        foreach ($member_profiles as $_profile) {
            if (isset($_profile['no_delivery_area']) && !empty($_profile['no_delivery_area'])) {
                $no_delivery_area = json_decode($_profile['no_delivery_area'], TRUE);
                //json解析错误，则不阻止
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $result->code = TRUE;
                    $result->message = 'json解析错误';
                    return $result;
                }

                foreach ($no_delivery_area as $_area) {
                    $tmp_area_arr = array();
                    if (!empty($_area['province'])) {
                        $tmp_area_arr[] = $_area['province'];
                        $check_flag = 'province';
                    }

                    if (!empty($_area['city'])) {
                        $tmp_area_arr[] = $_area['city'];
                        $check_flag = 'city';
                    }

                    if (!empty($_area['area'])) {
                        $tmp_area_arr[] = $_area['area'];
                        $check_flag = 'area';
                    }

                    if (!empty($tmp_area_arr)) {
                        $tmp_area_str = implode('_', $tmp_area_arr);
                        $no_delivery_area_arr[$tmp_area_str] = $_profile['uid'];
                    }
                }

            }
        }

        $user_address = $this->parse_address_tree($address_tree, $check_flag);
        
        if (isset($no_delivery_area_arr[$user_address])) {
            $good_title = $allgoods[$no_delivery_area_arr[$user_address]][0]['title'];
            $result->code = FALSE;
            $result->message = "您所选择的收货地区 商品:{$good_title} 暂时无货";
            return $result;
        }

        $result->code = TRUE;
        $result->message = 'ok';
        return $result;
    }

    /**
     * 解析用户地址
     * @param  [type] $address [description]
     * @return [type]          [description]
     */
    private function parse_address_tree($address_tree, $check_flag = 'province') {
    	$user_address = '';

    	if (empty($address_tree)) {
    		return $user_address;
    	}
    	$address_province = isset($address_tree[2]['id']) ? $address_tree[2]['id'] : 0;
        $address_city = isset($address_tree[1]['id']) ? $address_tree[1]['id'] : 0;
        $address_area = isset($address_tree[0]['id']) ? $address_tree[0]['id'] : 0;

        if ($check_flag == 'province') {
            $user_address = $address_province;
        } elseif ($check_flag == 'city') {
            $user_address =  $address_province . '_' . $address_city;
        } elseif ($check_flag == 'area') {
            $user_address = $address_province . '_' . $address_city . '_' . $address_area;
        }

        return $user_address;
    }

	//订单结算
	//必须参数：{goods}
	//必须参数：addressId
	//必须参数：paytype
	/*//必须参数：sendtype*/
	//可选参数：remark
	public function checkout() {
		global $_W;
		$pid=intval($_POST['pid']);
		$tid=intval($_POST['tid']);
		$tuan=false;


		if($pid>0){
			$tuan=$this->pintuan($pid,$_W['member_id'],$tid);
			if(!$tuan){
				return self::responseError(999, '不符合参团条件');
			}
		}
		$order_type=0;//是否是团购订单
		$goods		= $_POST['goods'];
		$addressid	= intval($_POST['addressId']);
		$paytype	= trim($_POST['paytype']);
		$sendtype	= trim($_POST['sendtype']);
		$voucher = $_POST['voucher'];
		$remark		= cutstr(htmlspecialchars($_POST['remark']), 200);
		$vouchers=array();
		if(!empty($voucher)){
			foreach($voucher as $v){
				if($v['voucher_user_id'] >0){
					if(in_array($v['voucher_user_id'],$vouchers)){
						return self::responseError(228, '不能同时对两个店铺使用同一张优惠券！');
					}else{
						$vouchers[$v['sellerid']]=$v['voucher_user_id'];
					}
				}
			}
		}
		

		$sendtype = 1;

		if(empty($goods)) return self::responseError(200, 'Parameter [goods] is missing.');
		if(!is_array($goods) || empty($goods[0]['goodsId'])) return self::responseError(200, 'Parameter [goods] is invalid.');
		if(empty($addressid)) return self::responseError(220, 'Parameter [addressId] is missing.');
		if(empty($paytype)) return self::responseError(221, 'Parameter [paytype] is missing.');
		if(empty($sendtype)) return self::responseError(222, 'Parameter [sendtype] is missing.');

		$address = pdo_fetch("SELECT member_id,district_id,deleted FROM `ims_bj_qmxk_address` WHERE id='{$addressid}'");
		if(empty($address) || $address['member_id'] != $_W['member_id'] || $address['deleted']) {
			return self::responseError(223, '请选择收货地址！');
		}
		
		if(empty($address)) {
			$address = null;
		} else {
			require IA_ROOT . '/source/libs/delivery.class.php';
			$delivery=new Delivery();
			$address_tree = $delivery->fetch_parents($address['district_id']);
		}

		if(empty($paytype)) {
			return self::responseError(224, '请选择支付方式！');
		}

		$allgoods = self::checkGoods();
		//print_r($allgoods);
        $check_ret = $this->check_address_can_deliver($address_tree, $allgoods);
        if (!$check_ret->code) {
            return self::responseError(225, $check_ret->message);
        }
		//获取当前用户的上级关系链
		$member_relation = pdo_fetch("SELECT * FROM ims_bj_qmxk_member_shareid WHERE member_id='{$_W['member_id']}'");
		if(empty($member_relation['mid'])) {

		}

		$mids = '';
		if($member_relation['shareid']) $mids .= $member_relation['shareid'].',';
		if($member_relation['shareid2']) $mids .= $member_relation['shareid2'].',';
		if($member_relation['shareid3']) $mids .= $member_relation['shareid3'].',';
		if($member_relation['shareid4']) $mids .= $member_relation['shareid4'].',';
		if($member_relation['shareid5']) $mids .= $member_relation['shareid5'].',';
		if($member_relation['shareid6']) $mids .= $member_relation['shareid6'].',';
		if($member_relation['shareid7']) $mids .= $member_relation['shareid7'].',';
		if($member_relation['shareid8']) $mids .= $member_relation['shareid8'].',';
		if($member_relation['shareid9']) $mids .= $member_relation['shareid9'].',';
		if($member_relation['shareid10']) $mids .= $member_relation['shareid10'].',';
		if($member_relation['shareid11']) $mids .= $member_relation['shareid11'].',';
		if($member_relation['shareid12']) $mids .= $member_relation['shareid12'].',';
		if($member_relation['shareid13']) $mids .= $member_relation['shareid13'].',';
		if($member_relation['shareid14']) $mids .= $member_relation['shareid14'].',';
		if($member_relation['shareid15']) $mids .= $member_relation['shareid15'].',';
		if($member_relation['shareid16']) $mids .= $member_relation['shareid16'].',';
		if($member_relation['shareid17']) $mids .= $member_relation['shareid17'].',';
		if($member_relation['shareid18']) $mids .= $member_relation['shareid18'].',';
		if($member_relation['shareid19']) $mids .= $member_relation['shareid19'].',';
		if($member_relation['shareid20']) $mids .= $member_relation['shareid20'].',';

		if($mids) {
			if(preg_match('/\,$/', $mids)) $mids = substr($mids, 0, - 1);
			//获取关系链上所有用户的级别
			$member_agent_level = pdo_fetchall("SELECT mid,agent_level FROM ims_bj_qmxk_member_selldata WHERE mid IN({$mids})", array(), 'mid');
		}

		$paysn = build_paysn($_W['member_id']);


		$all_price = 0.00;
		$all_order_price=0.00;
		$order_count = 0;//订单数量
		$goods = array();
		$subject = '';
		$shops=array();//记录店铺可优惠的商品的总金额
		$allprice1=0.00;//参加优惠活动的商品的总金额
		
		foreach($allgoods as $sellerid => $seller_goods){//计算可优惠的总金额
			$goodsprice1=0.00;//店铺参加优惠活动的商品总金额
			if($tuan){
				$shops[$sellerid]=0;
				$order_type=1;
			}else{
				foreach($seller_goods AS $index => $row) {
//					$key="ACTIVITY".$row['goodsId'];
//					if($activity = $_W['mc']->get($key)){
					if($row['act_id'] != 0){//如果商品参加了活动，按活动价格计算
//						$activity = unserialize($activity);
						$activity = pdo_fetch("SELECT id,act_start_time,act_end_time FROM ".tablename('bj_qmxk_activity')." WHERE id={$row['act_id']}");
                                                if (empty($activity['act_start_time']) || empty($activity['act_end_time'])){
                                                    $activity_entry = pdo_fetch("SELECT id,goods_id,act_id,actprice,costprice,status,start_time,end_time FROM " . tablename('bj_qmxk_activity_entry') . " WHERE act_id='{$row['act_id']}' AND goods_id='{$row['goodsId']}' AND status=2 ORDER BY id DESC");
                                                    $activity['act_start_time'] = $activity_entry['start_time'];
                                                    $activity['act_end_time'] = $activity_entry['end_time'];
                                                }
							if ($activity['act_start_time'] < time() && $activity['act_end_time'] > time()) {
								$order_type = 2;
								$tid = intval($activity['id']);
								$activity_entry = pdo_fetch("SELECT id,act_id,goods_id,seller_id,actprice,costprice,status FROM " . tablename('bj_qmxk_activity_entry') . " WHERE act_id='{$activity['id']}' AND goods_id='{$row['goodsId']}' ORDER BY id DESC");
								$row['marketprice'] = $activity_entry['actprice'];
								$row['costprice'] = $activity_entry['costprice'];
								if ($row['optionid']) {
									$option1 = pdo_fetch("SELECT id,goods_id,actprice,costprice FROM " . tablename('bj_qmxk_activity_goods') . " WHERE entry_id='{$activity_entry['id']}' AND goods_id='{$row['goodsId']}' AND spec_id='{$row['optionid']}'");
									$row['marketprice'] = $option1['actprice'];
									$row['costprice'] = $option1['costprice'];
								}
								$allgoods[$sellerid][$index] = $row;
							}
					}
					$youhui= $this->is_youhui($row['goodsId'],$sellerid);
					if($youhui['youhui']){//如果该商品参加优惠活动
						if($youhui['pingtai']){
							$allprice1+=$row['marketprice']*$row['amount'];
							$goodsprice1+=$row['marketprice']*$row['amount'];
						}else{
							$goodsprice1+=$row['marketprice']*$row['amount'];
						}
					}
				}
				$shops[$sellerid]=$goodsprice1;
			}
			
		}
		if(intval($vouchers[0]) > 0){//如果对总订单使用了优惠券
			if(count($vouchers)>1){
				return self::responseError(229, '不能同时对总订单和子订单同时使用优惠券!');
			}
			$youhui_id=$vouchers[0];
			$order_quan=pdo_fetch("SELECT * FROM `ims_bj_qmxk_voucher_user` WHERE id = '$youhui_id' and status='0'");		
		}
		foreach($allgoods as $sellerid =>$seller_goods) {//每次循环是一个订单
			$goodsprice = 0.00;
			$price = 0.00;
			$totalcnf=0;
			$dispatchprice = 0.00;//总运费
			$dispatchprice_order[$sellerid] = 0;//订单运费
			$youhui_discount=0;
			if(intval($vouchers[$sellerid]) > 0){//如果对子订单使用了优惠券
				$youhui_id=$vouchers[$sellerid];
				$quan=pdo_fetch("SELECT * FROM `ims_bj_qmxk_voucher_user` WHERE id = '$youhui_id' and status='0'");
				$youhui_discount=sprintf('%.2f',$quan['discount']);				
			}
			if(isset($order_quan) && !empty($order_quan)){//如果对总订单使用了优惠券
				$youhui_discount=sprintf('%.2f',(($shops[$sellerid]*$order_quan['discount'])/$allprice1));		
			}

//		$address['district_id'] = 700000;
			foreach($seller_goods AS $index => $row) {//计算本订单的商品金额、运费
				if($tuan){
					$row['marketprice']=$tuan['grouponprice'];
					$row['costprice']=$tuan['costprice'];
					$seller_goods[$index]=$row;
				}
				
//		$row['amount'] = 1;
				//获取商品物流配置
				//根据当前地址，计算运费
				//非免运费地区，按件数配置计算运费
				//$dispatchprice += $row['marketprice'];
//		$row['delivery_id'] = 19;
				if($row['delivery_id'] > 1) {
					$_delivery = pdo_fetch("SELECT * FROM `ims_delivery` WHERE id='{$row['delivery_id']}'");

					if($_delivery && $_delivery['deleted'] == 0) {
						$good_yunfei=0;
						if(!$_delivery['issendfree']) {
							if($row['amount'] > $_delivery['start_def']) { //大于首件
								$good_yunfei += $_delivery['postage_def'];//首件运费
								$good_yunfei += floor(($row['amount']-$_delivery['start_def'])/$_delivery['plus_def']) * $_delivery['postageplus_def'];//续件运费
							} else {
								$good_yunfei += $_delivery['postage_def'];
							}
						}

						$_deliverys = pdo_fetchall("SELECT start,postage,plus,postageplus,district_id FROM `ims_delivery_district` WHERE delivery_id='{$_delivery['id']}'");
						if($_deliverys) {
							foreach($_deliverys AS $_delivery_value) {
								if($_delivery_value['district_id']) {
									$_district_ids = explode(',', $_delivery_value['district_id']);
									$has_district = false;
									foreach($address_tree AS $address_district) {
										if(in_array($address_district['id'], $_district_ids)) {
												$has_district = true;
												break;
										}
									}
									if($has_district) {
										$good_yunfei=0;
										//初始运费
										if($row['amount'] > $_delivery_value['start']) { //大于首件
												$good_yunfei += $_delivery_value['postage'];//首件运费
												$good_yunfei += floor(($row['amount']-$_delivery_value['start'])/$_delivery_value['plus']) * $_delivery_value['postageplus'];//续件运费

										} else {
											$good_yunfei += $_delivery_value['postage'];
										}
									}
								}
							}
						}
					} else {
						$good_yunfei=0;
					}
					
					$dispatchprice += $good_yunfei;
					$dispatchprice_order[$sellerid] += $good_yunfei;
					$seller_goods[$index]['dispatchprice'] = $good_yunfei;//商品运费
				}
				else {
					$dispatchprice += 0;
					$dispatchprice_order[$sellerid] += 0;
					$seller_goods[$index]['dispatchprice'] = 0;//商品运费
				}
				$totalcnf= $row['totalcnf'];
				$dispatchprice = sprintf('%.2f', $dispatchprice);//总运费
				$dispatchprice_order[$sellerid] = sprintf('%.2f', $dispatchprice_order[$sellerid]);//订单运费
				$seller_goods[$index]['dispatchprice'] = sprintf('%.2f', $seller_goods[$index]['dispatchprice']);//商品运费

				$goodsprice += sprintf('%.2f', $row['marketprice']*$row['amount']);
				$goodstype = $row['type'];
			}
			$price = $goodsprice+$dispatchprice;
			$all_price += $price;
			$all_order_price += $price-($tuan?0:$youhui_discount);

			// 检查是否有重复订单号
			$ordersns = build_ordersn($_W['member_id']);
			$randomorder = pdo_fetchcolumn("SELECT ordersn FROM ims_bj_qmxk_order WHERE ordersn='{$ordersns}'");
			if(!empty($randomorder)) {
				$ordersns = build_ordersn($_W['member_id']);
			}
            if($sellerid == 1344){
		         $order_type = 4;
            }
			$data = array(
				'weid' => 2,
				'sellerid' => $sellerid,
				'member_id' => $_W['member_id'],
				'ordersn' => $ordersns,
				'addressid' => $addressid,
				'remark' => $remark,
				'status' => 0,
				'paytype' => $paytype,//支付方式
				'sendtype' => $sendtype,//发货方式，1快递2自提
				'dispatch' => 1,//配送方式 0自提1快递

				'price' => $price-($tuan?0:$youhui_discount),
				'dispatchprice' => $dispatchprice_order[$sellerid],
				'goodsprice' => $goodsprice,
				'goodstype' => $goodstype,
				'order_type' => $order_type,
				'tid' => $tid,

				'from_user' => '',
				'createtime' => TIMESTAMP,
				'updatetime' => TIMESTAMP,
				'platform'	=> self::$platform,

				'shareid' => $member_relation['shareid'],
				'shareid2' => $member_relation['shareid2'],
				'shareid3' => $member_relation['shareid3'],
				'shareid4' => $member_relation['shareid4'],
				'shareid5' => $member_relation['shareid5'],
				'shareid6' => $member_relation['shareid6'],
				'shareid7' => $member_relation['shareid7'],
				'shareid8' => $member_relation['shareid8'],
				'extra_shareid' => $member_relation['shareid'],
				'extra_shareid2' => $member_relation['shareid2'],
				'extra_shareid3' => $member_relation['shareid3'],
				'extra_shareid4' => $member_relation['shareid4'],
				'extra_shareid5' => $member_relation['shareid5'],
				'extra_shareid6' => $member_relation['shareid6'],
				'extra_shareid7' => $member_relation['shareid7'],
				'extra_shareid8' => $member_relation['shareid8'],
				'extra_shareid9' => $member_relation['shareid9'],
				'extra_shareid10' => $member_relation['shareid10'],
				'extra_shareid11' => $member_relation['shareid11'],
				'extra_shareid12' => $member_relation['shareid12'],
				'extra_shareid13' => $member_relation['shareid13'],
				'extra_shareid14' => $member_relation['shareid14'],
				'extra_shareid15' => $member_relation['shareid15'],
				'extra_shareid16' => $member_relation['shareid16'],
				'extra_shareid17' => $member_relation['shareid17'],
				'extra_shareid18' => $member_relation['shareid18'],
				'extra_shareid19' => $member_relation['shareid19'],
				'extra_shareid20' => $member_relation['shareid20']

			);

			// 不同商家的商品生成不同的订单
			pdo_insert('bj_qmxk_order', $data);
			$orderid = pdo_insertid();

			self::addOrderLog(array(
				'orderid' => $orderid,
				'ordersn' => $ordersns,
				'status' => 'create',
				'action_user' => 'user',
				'member_id' => $_W['member_id'],
				'adminid' => 0,
				'sellerid' => 0,
				'dateline' => TIMESTAMP,
				'remark' => ''
			));

			// 生成支付号，用于多个订单一次支付
			$data = array(
				'paysn' => $paysn,
				'ordersn' => $ordersns,
				'transid' => '',
				'status' => 0
			);
			pdo_insert('bj_qmxk_payinfo', $data);

			foreach($seller_goods AS $row) {
				$subject = $subject ? $row['title']."等商品" : $row['title'];
				$d = array(
					'weid' => 2,
					'goodsid' => $row['goodsId'],
					'orderid' => $orderid,
					'total' => $row['amount'],
					'price' => $row['marketprice'],
					'costprice' => $row['costprice'],
					'createtime' => TIMESTAMP,
					'optionid' => $row['optionid'],
					'act_id' => $row['act_id']
				);

				if(!empty($row['optionname'])) $d['optionname'] = $row['optionname'];
				$d_commission = self::get_commission($row, $member_relation);

				//$d['prize_pool'] = $d_commission['prize_pool'];
				$d['commission'] = $d_commission['commission'];
				$d['commission2'] = $d_commission['commission2'];
				$d['commission3'] = $d_commission['commission3'];
				$d['commission4'] = $d_commission['commission4'];
				$d['commission5'] = $d_commission['commission5'];
				$d['commission6'] = $d_commission['commission6'];
				$d['commission7'] = $d_commission['commission7'];
				$d['commission8'] = $d_commission['commission8'];

				$d['extra_commission'] = $d_commission['extra_commission'];
				$d['extra_commission2'] = $d_commission['extra_commission2'];
				$d['extra_commission3'] = $d_commission['extra_commission3'];
				$d['extra_commission4'] = $d_commission['extra_commission4'];
				$d['extra_commission5'] = $d_commission['extra_commission5'];
				$d['extra_commission6'] = $d_commission['extra_commission6'];
				$d['extra_commission7'] = $d_commission['extra_commission7'];
				$d['extra_commission8'] = $d_commission['extra_commission8'];
				$d['extra_commission9'] = $d_commission['extra_commission9'];
				$d['extra_commission10'] = $d_commission['extra_commission10'];
				$d['extra_commission11'] = $d_commission['extra_commission11'];
				$d['extra_commission12'] = $d_commission['extra_commission12'];
				$d['extra_commission13'] = $d_commission['extra_commission13'];
				$d['extra_commission14'] = $d_commission['extra_commission14'];
				$d['extra_commission15'] = $d_commission['extra_commission15'];
				$d['extra_commission16'] = $d_commission['extra_commission16'];
				$d['extra_commission17'] = $d_commission['extra_commission17'];
				$d['extra_commission18'] = $d_commission['extra_commission18'];
				$d['extra_commission19'] = $d_commission['extra_commission19'];
				$d['extra_commission20'] = $d_commission['extra_commission20'];
				
				if(intval($vouchers[$sellerid])>0 && !$tuan){
					$youhui_id=intval($vouchers[$sellerid]);
					$quan=pdo_fetch("SELECT * FROM `ims_bj_qmxk_voucher_user` WHERE id = '$youhui_id' and status='0'");
					$youhui= $this->is_youhui($d['goodsid'],$sellerid);
					if($youhui['youhui'] && $shops[$sellerid] >= $quan['price']){
						$d['pay'] = $quan['pay'];
						$d['voucher_id']=$quan['id'];//用户拥有的优惠券ID
						$d['offerprice']=sprintf('%.2f', (($d['price']*$d['total'])/$shops[$sellerid])*$quan['discount'] );//优惠金额
					}	
				}
				
				if(isset($order_quan) && !empty($order_quan) && !$tuan){//如果对总订单使用了优惠券
					$d['voucher_id']=$order_quan['id'];//用户拥有的优惠券ID
					$d['pay'] = $order_quan['pay'];
					$d['offerprice']=sprintf('%.2f', (($d['price']*$d['total'])/$allprice1)*$order_quan['discount'] );//优惠金额		
				}

				pdo_insert('bj_qmxk_order_goods', $d);
				
				if($tuan){			    
					if($order_type==1){
						$profile = pdo_fetch("SELECT * FROM `ims_bj_qmxk_member_info` WHERE member_id='{$_W['member_id']}'");
						$groupon_data = array(
							'orderid'=>$orderid,
							'goodsid' => $d['goodsid'],
							'pid'=>$pid,
							'tid'=>$tid,
							'realname'=>$profile['nickname'],
							'avatar'=>$profile['avatar'],
							'member_id'=>$_W['member_id'],
							'isleader'=>$tid == 0 ? 1 : 0
						);
						pdo_insert('bj_qmxk_groupon_member', $groupon_data);
					}
				}
			}
			//库存问题
			if ($totalcnf==0)
			{
				$this->setOrderStock($orderid);
			}

			$order_count += 1;
		}

		// 更新对应代理的订单数量
		$profileids = '';
		$member_relation['shareid'] && $profileids .= $member_relation['shareid'] . ",";
		$member_relation['shareid2'] && $profileids .= $member_relation['shareid2'] . ",";
		$member_relation['shareid3'] && $profileids .= $member_relation['shareid3'] . ",";
		$member_relation['shareid4'] && $profileids .= $member_relation['shareid4'] . ",";
		$member_relation['shareid5'] && $profileids .= $member_relation['shareid5'] . ",";
		$member_relation['shareid6'] && $profileids .= $member_relation['shareid6'] . ",";
		$member_relation['shareid7'] && $profileids .= $member_relation['shareid7'] . ",";
		$member_relation['shareid8'] && $profileids .= $member_relation['shareid8'] . ",";
/*
		if($profileids) {
			$is_dzd = 0;
			if(preg_match('/\,$/', $profileids)) {
				$profileids = substr($profileids, 0, - 1);
			}
			
			$profileids_arr = explode(',', $profileids);

			foreach($profileids_arr as $profile_id) {
				$order_count = pdo_fetch("SELECT m.flag,c.profileid FROM ims_bj_qmxk_member m LEFT JOIN ims_bj_qmxk_order_count c ON m.id=c.profileid WHERE m.id='{$profile_id}'");
				if($order_count['flag'] == 1) {
					if($order_count['profileid']) {
						if($is_dzd) {
							pdo_query("UPDATE " . tablename('bj_qmxk_order_count') . " SET `count_all`=count_all+{$order_count},`count_all_dzd`=count_all_dzd+{$order_count}, `count_today`=count_today+{$order_count} WHERE `profileid`='{$profile_id}'");
						} else {
							pdo_query("UPDATE " . tablename('bj_qmxk_order_count') . " SET `count_all`=count_all+{$order_count},`count_all_goods`=count_all_goods+{$order_count},`count_today`=count_today+{$order_count} WHERE `profileid`='{$profile_id}'");
						}
					} else {
						if($is_dzd) {
							pdo_query("INSERT INTO " . tablename('bj_qmxk_order_count') . " SET `profileid`='{$profile_id}',`count_all`='{$order_count}',`count_all_dzd`='{$order_count}',`count_today`='{$order_count}'");
						} else {
							pdo_query("INSERT INTO " . tablename('bj_qmxk_order_count') . " SET `profileid`='{$profile_id}',`count_all`='{$order_count}',`count_all_goods`='{$order_count}',`count_today`='{$order_count}'");
						}
					}
				}
			}
		}
*/
		if($member_relation['shareid']) {
			$order_count = pdo_fetch("SELECT m.flag,c.profileid FROM ims_bj_qmxk_member m LEFT JOIN ims_bj_qmxk_order_count c ON m.id=c.profileid WHERE m.id='{$member_relation['shareid']}'");
			if($order_count['flag'] == 1) {
				if($order_count['profileid']) {
					if($is_dzd) {
						pdo_query("UPDATE " . tablename('bj_qmxk_order_count') . " SET `count_all`=count_all+1,`count_all_dzd`=count_all_dzd+1, `count_today`=count_today+1 WHERE `profileid`='{$member_relation['shareid']}'");
					} else {
						pdo_query("UPDATE " . tablename('bj_qmxk_order_count') . " SET `count_all`=count_all+1,`count_all_goods`=count_all_goods+1,`count_today`=count_today+1 WHERE `profileid`='{$member_relation['shareid']}'");
					}
				} else {
					if($is_dzd) {
						pdo_query("INSERT INTO " . tablename('bj_qmxk_order_count') . " SET `profileid`='{$member_relation['shareid']}',`member_id`='{$member_id}',`count_all`='1',`count_all_dzd`='1',`count_today`='1'");
					} else {
						pdo_query("INSERT INTO " . tablename('bj_qmxk_order_count') . " SET `profileid`='{$member_relation['shareid']}',`member_id`='{$member_id}',`count_all`='1',`count_all_goods`='1',`count_today`='1'");
					}
				}
			}
		}
		
		if(count($vouchers)>0 && !$tuan){//如果使用了优惠券 把优惠券该为已使用状态
			$vstr=join(',',$vouchers);
			if(!empty($vstr)){
				$time=TIMESTAMP;
				$sql = 'update ' . tablename('bj_qmxk_voucher_user') . " set status='1' , use_time = '$time' where id in ($vstr)";
				pdo_query($sql);
			}
		}

		if($paytype=='credit2'){
			$result = $this->credit2_pay($paysn, $paytype, sprintf('%.2f', $all_order_price),substr($subject, 0, 120));//余额支付
			$result['paysn']=$paysn;
			$result['paytype']=$paytype;
			$result['price']=sprintf('%.2f', $all_order_price);
		}else{
			$result = array('paysn'=>$paysn, 'paytype'=>$paytype, 'price'=>sprintf('%.2f', $all_order_price), 'subject'=>substr($subject, 0, 120));	
			require IA_ROOT.'/source/apis/Pay.php';
		
			$result['payRequest'] = Pay::buildRequest($result);
		}
		return self::responseOk($result);
	}

	//支付订单
	//必须参数: ordersn
	//必须参数: paysn
	//必须参数: paytype
	public function pay() {
		global $_W;

		$ordersn = trim($_GET['ordersn']);
		$paysn = trim($_GET['paysn']);
		$paytype = trim($_GET['paytype']);

		if(empty($ordersn) && empty($paysn)) return self::responseError(280, 'Parameter [paysn] or [ordersn] is missing.');
		if(empty($paytype)) return self::responseError(281, '请选择支付方式！');

		if($ordersn) {
			$order = pdo_fetch("SELECT p.paysn FROM ims_bj_qmxk_order o LEFT JOIN ims_bj_qmxk_payinfo p ON p.ordersn=o.ordersn WHERE o.ordersn='{$ordersn}'");
			$paysn = $order['paysn'];
		}

		if(empty($paysn)) return self::responseError(282, '参数错误');

		if($paysn) {
			if(!preg_match('/^PAY([A-Z0-9]+)$/', $paysn)) return self::responseError(282, '参数错误(2)');

			$params = array();
			$price = 0;

			$payinfo_arr = pdo_fetchall("SELECT * FROM ims_bj_qmxk_payinfo WHERE paysn='{$paysn}'");
			if(empty($payinfo_arr)) return self::responseError(283, '抱歉，本支付号不存在！');

			$subject = '订单号: ';
			foreach($payinfo_arr AS $payinfo) {
				if($payinfo['status'] != '0') return self::responseError(284, '抱歉，本订单已经完成付款。');
				$order = pdo_fetch("SELECT id,status,price,paytype FROM ims_bj_qmxk_order WHERE ordersn ='{$payinfo['ordersn']}'");

				if($order['status'] != '0' && ! ($order['status'] == 1 && $order['paytype'] == 3)) {
					return self::responseError(285, '抱歉，您的订单已经付款或是被关闭，请重新进入付款！');
				}
				$subject .= $payinfo['ordersn']." ";
				$price += $order['price'];
			}
		}
/*
		if($paytype == 'swiftpass') { //针对威富通，重新支付时生成新的支付号
			$new_paysn = 'PAY' . date('Ymd') . random(9, 1);
			pdo_update('bj_qmxk_payinfo', array('paysn' => $new_paysn), array('paysn' => $paysn));
			$paysn = $new_paysn;
		}
*/
			
		if($paytype=='credit2'){
			$result = $this->credit2_pay($paysn, $paytype, sprintf('%.2f', $price),substr($subject, 0, 120));//余额支付
			$result['paysn']=$paysn;
			$result['paytype']=$paytype;
			$result['price']=sprintf('%.2f', $price);
		}else{
			$result = array('paysn'=>$paysn, 'paytype'=>$paytype, 'price'=>sprintf('%.2f', $price), 'subject'=>substr($subject, 0, 120));
			require IA_ROOT.'/source/apis/Pay.php';

			$result['payRequest'] = Pay::buildRequest($result);
		}

		return self::responseOk($result);
		/**
		https://api.mch.weixin.qq.com/pay/unifiedorder
		appid
		mch_id
		nonce_str
		sign
		body
		attach
		out_trade_no
		fee_type=CNY
		total_fee
		spbill_create_ip
		time_start
		time_expire
		notify_url
		trade_type=APP

		*/
	}
	
	private function credit2_pay($paysn,$paytype,$fee,$subject){
		global $_W;
		$result=array();
		$profile = pdo_fetch("SELECT m.*,mi.avatar,mi.credit1,ma.id AS member_id,ma.unionid,ma.password,mi.nickname AS realname,mi.credit2,mi.credit2_freeze,mi.is_agent AS flag,mi.agent_time AS flagtime,mi.commission,mi.zhifu ".
		"FROM ims_bj_qmxk_member m ".
		"LEFT JOIN ims_bj_qmxk_member_auth ma ON ma.mid=m.id ".
		"LEFT JOIN ims_bj_qmxk_member_info mi ON mi.member_id=ma.id ".
		"WHERE ma.id='{$_W['member_id']}'");
		if(($profile['credit2'] - $profile['credit2_freeze']) < $fee) {
			return self::responseError(99, '抱歉，所剩余额不足，无法购买');
		}
		$data = array(
			'credit2' => $profile['credit2'] - $fee,
			'tag' => '余款付款购买商品，订单编号为' . $paysn,
			'type' => 'usegold',
			'fee' => $fee,
			'createtime' => TIMESTAMP,
			'openid' => $profile['from_user'],
			'member_id' => $profile['member_id'],
			'mid' => $profile['id'],
			'weid' => $_W['weid']
		);
		$result['old_credit2']=$profile['credit2'];
		$result['new_credit2']=sprintf('%.2f',$profile['credit2'] - $fee);
		pdo_insert('bj_qmxk_paylog', $data);
		pdo_update('bj_qmxk_member', array('credit2' => $result['new_credit2']), array('id' => $profile['id']));
		pdo_update('bj_qmxk_member_info', array('credit2' => $result['new_credit2']), array('member_id' => $profile['member_id']));
		
		
		$order_send_lock = 0;
		$order_send_lock = $_W['mc']->get(md5('order-send-lock-'.$paysn));
		
		if($order_send_lock) { //判断执行锁
			return self::responseError(99, '请不要重复支付');
		} else {
			$_W['mc']->set(md5('order-send-lock-'.$params['tid']), '1', 0, 60*60);
		}
		
		if(preg_match('/^PAY([A-Z0-9]+)$/', $paysn)) { // 多订单同时支付，使用支付号
			$payinfo_arr = pdo_fetchall("SELECT * FROM ".tablename('bj_qmxk_payinfo')." WHERE paysn = '{$paysn}'");
			if(empty($payinfo_arr)){
				return self::responseError(99, '抱歉，本支付号不存在！');
			}

			foreach($payinfo_arr as $kkey => $payinfo) {
				$payinfo_data = array();
				$payinfo_data['transid'] = '';
				$payinfo_data['status'] = 1;
				pdo_update('bj_qmxk_payinfo', $payinfo_data, array('payinfoid' => $payinfo['payinfoid']));

				$data = array('status' => 1);
				$data['updatetime'] = TIMESTAMP;
				$order = pdo_fetch('SELECT * FROM ' . tablename('bj_qmxk_order') . " WHERE ordersn = '{$payinfo['ordersn']}'");
				if($fee < $order['price']) {
					continue;
				}
				if($order['status'] != 0) {
					if($order['status'] == -1) {
						if(TIMESTAMP - $order['createtime'] > 10*60*60) {//超过10小时再支付
							continue;
						}
					} else {
							continue;
					}
				}
				if($data['status'] == 1) {
					if(!$_W['mcq']->set('order_message', $order['id'], 0, 0)) {
						if(!$_W['mcq']->set('order_message', $order['id'], 0, 0)) {
							$_W['mcq']->set('order_message', $order['id'], 0, 0);
						}
					}
					$this->addOrderLog(array(
						'orderid' => $order['id'],
						'ordersn' => $order['ordersn'],
						'status' => 'pay',
						'action_user' => 'user',
						'member_id' => $order['member_id'],
						'adminid' => 0,
						'sellerid' => 0,
						'dateline' => TIMESTAMP,
						'remark' => ''
					));
					$data['paytype'] = 'credit2';
					pdo_update('bj_qmxk_order', $data, array('id' => $order['id']));
					$this->change_pintuan($order['id']);
				}
			}
		} else {
			return self::responseError(99, '支付号错误！');
		}
		
		return $result;	
	}
	/**
	 * 是拼单的订单
	 * @param integer $orderid
	 */
   private function change_pintuan($orderid){
	    /**
	     * 1、有tid！=0（加入别人的团）更新ims_bj_qmxk_groupon表tid=id,buynum+1;如果拼单成功，则要把status置成成功。
	     * 2、tid==0,ims_bj_qmxk_groupon插入一条新数据，用户自己为群主。buynum=1
	     * 3、ims_bj_qmxk_groupon_member更新状态
	     */
	   global $_W;
	   $key = 'CHANGE-PINTUAN';
	   $_W['mcq']->set($key, $orderid, 0, 0);//统一队列处理
	   return;
	    try {
	        $groupon_member = pdo_fetch("SELECT `id`,`orderid`,`goodsid`,`pid`,`tid`,`member_id`,`isleader`,`realname`,`avatar` FROM `ims_bj_qmxk_groupon_member` WHERE `orderid`='{$orderid}' AND `status`=0 LIMIT 1");
	        if(!empty($groupon_member)){
	            //更新状态
	            pdo_update('bj_qmxk_groupon_member', array('status' => 1), array('id' => $groupon_member['id']));
	            //更新订单状态为拼单中
	            pdo_update('bj_qmxk_order', array('status' => 4,'tid'=>$groupon_member['tid']), array('id' => $groupon_member['orderid']));
	             
	            if($groupon_member['isleader']==1){
	                $tuan = pdo_fetch("SELECT `id`,`goodsid`,`num_limit`,`grouponprice` FROM ims_bj_qmxk_goods_groupon WHERE `id`='{$groupon_member['pid']}'  limit 1");
	                //开新团
	                $data = array(
	                    'groupid'=>$groupon_member['pid'],
	                    'goodsid'=>$groupon_member['goodsid'],
	                    'member_id'=>$groupon_member['member_id'],
	                    'realname'=>$groupon_member['realname'],
	                    'avatar'=>$groupon_member['avatar'],
	                    'buynum'=>1,
	                    'starttime'=>time(),
	                    'num_limit'=>$tuan['num_limit']
	                );
	                pdo_insert('bj_qmxk_groupon', $data);
	                $tid = pdo_insertid();
	                pdo_update('bj_qmxk_groupon_member', array('tid' => $tid), array('id' => $groupon_member['id']));
	                pdo_update('bj_qmxk_order', array('tid' => $tid), array('id' => $groupon_member['orderid']));
	            }else{
	                //加入别人的团
	                $groupon = pdo_fetch("SELECT buynum,`num_limit` FROM ims_bj_qmxk_groupon WHERE `id`='{$groupon_member['tid']}' limit 1");
	                if($groupon['buynum']+1 >= $groupon['num_limit']){
	                    pdo_query("UPDATE `ims_bj_qmxk_groupon` SET buynum=buynum+1,`status`=1 WHERE `id`='{$groupon_member['tid']}'");
	                    pdo_update('bj_qmxk_order', array('status' => 1), array('tid' => $groupon_member['tid'],'status'=>4));
	                }else{
	                    pdo_query("UPDATE `ims_bj_qmxk_groupon` SET buynum=buynum+1 WHERE `id`='{$groupon_member['tid']}'");
	                }
	            }
	        }
	    } catch (Exception $e){}
	}

	//商品检查
	//{"goods":{"0":{"goodsId":"123","optionid":"1234","amount":"1"},"1":{"goodsId":"234","optionid":"2345","amount":"2"}}}
	private function checkGoods($return=false) {
		global $_W;

		//下架/未审核
		//删除
		//限购
		//超过库存
		//时间未开始(秒杀)
		//时间已结束(秒杀)

		//价格标错，金额为0
		$post_goods = $_POST['goods'];

		foreach($post_goods AS $item) {
			$item['goodsId'] = intval($item['goodsId']);
			$item['amount'] = intval($item['amount']);
			$item['amount'] = $item['amount'] ? $item['amount'] : 1;
			$item['optionid'] = intval($item['optionid']);
			$item['act_id'] = empty($item['act_id'])?0:intval($item['act_id']);
			if(empty($item['goodsId'])) continue;
			if($return) {
				$goods = pdo_fetch("SELECT g.id AS goodsId,g.sellerid,mp.seller_name,g.title,g.thumb,g.status,g.type,g.total,g.maxbuy,g.istime,g.timestart,g.totalcnf,".
					"g.timeend,g.deleted,g.checked,g.marketprice,g.productprice,g.costprice,g.hasoption,g.delivery_id ".
					"FROM `ims_bj_qmxk_goods` g ".
					"LEFT JOIN `ims_members_profile` mp ON mp.uid=g.sellerid ".
					"WHERE g.id='{$item['goodsId']}'");
			} else {
				$goods = pdo_fetch("SELECT totalcnf,id AS goodsId,sellerid,title,thumb,status,type,total,maxbuy,istime,timestart,".
					"timeend,deleted,checked,marketprice,productprice,costprice,hasoption,delivery_id,".
					'commission,commission2,commission3,commission4,commission5,commission6,commission7,commission8 '.
					"FROM `ims_bj_qmxk_goods` WHERE id='{$item['goodsId']}'");
			}

			if(empty($goods)) continue;

			$goods['thumb'] = $goods['thumb'] ? $_W['attachurl'].$goods['thumb'] : 'http://statics.sldl.fcmsite.com/empty.gif';
			$goods['productprice'] = '-';

			if($item['optionid'] > 0) {
				$goods_option = pdo_fetch("SELECT goodsid,title,marketprice,productprice,costprice,stock FROM `ims_bj_qmxk_goods_option` WHERE id='{$item['optionid']}'");

				if(!$return) {
					$goods['title'] = $goods_option['title'] ? $goods['title'].'('.$goods_option['title'].')' : $goods['title'];
				}

				$goods['marketprice'] = $goods_option['marketprice'] ? $goods_option['marketprice']: $goods['marketprice'];
				$goods['productprice'] = $goods_option['productprice'] ? $goods_option['productprice']: $goods['productprice'];
				$goods['productprice'] = '-';
				$goods['costprice'] = $goods_option['costprice'] ? $goods_option['costprice'] : $goods['costprice'];
				$goods['total'] = $goods_option['stock'] ? $goods_option['stock'] : $goods['total'];

				$goods['optionname'] = $goods_option['title'];

				if($goods_option['goodsid'] != $goods['goodsId'] && !$return) {
					return self::responseError(200, '“' . $goods['title'] . '”规格与商品不匹配！'.$goods_option['goodsid'].'!='.$goods['goodsId']);
				}
			} else {
				if($goods['hasoption'] > 0 && !$return) {
					return self::responseError(200, '抱歉，“' . $goods['title'] . '”请选择规格！');
				}
				$goods['optionname'] = '';
			}
			
			$allow_pf = false;//是否允许批发
			$allow_pf = in_array($goods['sellerid'],ConfigModel::$WHOLESALE_SHOPS);
			if($allow_pf){//洪伟公司销售价格和结算价格重新计算
				$pf_config = ConfigModel::$WHOLESALE_CONFIG[$goods['sellerid']];
				$goods['costprice'] = $goods['marketprice'] * $pf_config['retail_costprice'];
				if($item['amount'] >= $goods['limit_num']){
					$goods['costprice'] = $goods['marketprice'] * $pf_config['wholesale_costprice'];
					$goods['marketprice'] = $goods['marketprice'] * $pf_config['wholesale_saleprice'];
				}
			}
			if(!$return) {
				if(!$goods['status']) {
					return self::responseError(200, '抱歉，“' . $goods['title'] . '”已下架！');
				}
				if(!$goods['checked'] || $goods['deleted']) {
					return self::responseError(200, '抱歉，“' . $goods['title'] . '”不存在或已下架！');
				}

				if($item['amount'] > $goods['total']) {
					return self::responseError(200, '抱歉，“' . $goods['title'] . '”库存为' . $goods['total'] . '！');
				}
				if($goods['marketprice'] <= 0) {
					return self::responseError(200, '抱歉，“' . $goods['title'] . '”售价错误，暂时无法购买！');
				}

				if($goods['istime'] > 0) {
					if(TIMESTAMP < $goods['starttime']) {
						return self::responseError(200, '抱歉，“'.$goods['title'].'”限购时间已到，无法购买了！');
					}

					if(TIMESTAMP > $goods['endtime']) {
						return self::responseError(200, '抱歉，“'.$goods['title'].'”尚未开始抢购，无法购买！');
					}
				}
			} else {
				unset($goods['costprice']);
				unset($goods['type']);
			}
			$goods['optionid'] = $item['optionid'];
			$pid=intval($_POST['pid']);
			if($item['amount'] < 1 && $pid < 1 ) {
					return self::responseError(200, '抱歉，“' . $goods['title'] . '”购买数量不能小于1');
			}
			$goods['amount'] = $item['amount'];
                        $goods['act_id'] = $item['act_id'];
			unset($goods['status']);
			unset($goods['deleted']);
			unset($goods['checked']);
	
			$allgoods_end[$goods['sellerid']][] = $goods;
		}

		return $allgoods_end;
	}

	//计算佣金
	private function get_commission($goods, $member_relation) {
		global $member_agent_level;

/*
		$commission = $goods['commission'];
		$commission2 = $goods['commission2'];
		$commission3 = $goods['commission3'];
		$commission4 = $goods['commission4'];
		$commission5 = $goods['commission5'];
		$commission6 = $goods['commission6'];
		$commission7 = $goods['commission7'];
		$commission8 = $goods['commission8'];

		if($commission <= 0) $commission = 7.5;
		if($commission2 <= 0) $commission2 = 2.5;
		if($commission3 <= 0) $commission3 = 7.5;
		if($commission4 <= 0) $commission4 = 1.5;
		if($commission5 <= 0) $commission5 = 1.5;
		if($commission6 <= 0) $commission6 = 1.5;
		if($commission7 <= 0) $commission7 = 1.5;
		if($commission8 <= 0) $commission8 = 1.5;

		$commissionTotal = $goods['marketprice'] * $commission / 100;
		$d['commission'] = $commissionTotal;
		$commissionTotal2 = $goods['marketprice'] * $commission2 / 100;
		$d['commission2'] = $commissionTotal2;
		$commissionTotal3 = $goods['marketprice'] * $commission3 / 100;
		$d['commission3'] = $commissionTotal3;
		$commissionTotal4 = $goods['marketprice'] * $commission4 / 100;
		$d['commission4'] = $commissionTotal4;
		$commissionTotal5 = $goods['marketprice'] * $commission5 / 100;
		$d['commission5'] = $commissionTotal5;
		$commissionTotal6 = $goods['marketprice'] * $commission6 / 100;
		$d['commission6'] = $commissionTotal6;
		$commissionTotal7 = $goods['marketprice'] * $commission7 / 100;
		$d['commission7'] = $commissionTotal7;
		$commissionTotal8 = $goods['marketprice'] * $commission8 / 100;
		$d['commission8'] = $commissionTotal8;
*/

		if($goods['costprice'] > 0) {
			$profit = ($goods['marketprice'] - $goods['costprice'])*0.90;
		} else {
			$profit = 0;
		}

		$commission = 0.25;
		$commission2 = 0.25;
		$commission3 = 0.05;
		$commission4 = 0.05;
		$commission5 = 0.05;
		$commission6 = 0.05;
		$commission7 = 0.05;
		$commission8 = 0.05;

		$commissionTotal = $profit * $commission;
		$d['commission'] = $commissionTotal;
		$commissionTotal2 = $profit * $commission2;
		$d['commission2'] = $commissionTotal2;
		$commissionTotal3 = $profit * $commission3;
		$d['commission3'] = $commissionTotal3;
		$commissionTotal4 = $profit * $commission4;
		$d['commission4'] = $commissionTotal4;
		$commissionTotal5 = $profit * $commission5;
		$d['commission5'] = $commissionTotal5;
		$commissionTotal6 = $profit * $commission6;
		$d['commission6'] = $commissionTotal6;
		$commissionTotal7 = $profit * $commission7;
		$d['commission7'] = $commissionTotal7;
		$commissionTotal8 = $profit * $commission8;
		$d['commission8'] = $commissionTotal8;


		$shareId = $member_relation['shareid'];
		$shareId2 = $member_relation['shareid2'];
		$shareId3 = $member_relation['shareid3'];
		$shareId4 = $member_relation['shareid4'];
		$shareId5 = $member_relation['shareid5'];
		$shareId6 = $member_relation['shareid6'];
		$shareId7 = $member_relation['shareid7'];
		$shareId8 = $member_relation['shareid8'];
		$shareId9 = $member_relation['shareid9'];
		$shareId10 = $member_relation['shareid10'];
		$shareId11 = $member_relation['shareid11'];
		$shareId12 = $member_relation['shareid12'];
		$shareId13 = $member_relation['shareid13'];
		$shareId14 = $member_relation['shareid14'];
		$shareId15 = $member_relation['shareid15'];
		$shareId16 = $member_relation['shareid16'];
		$shareId17 = $member_relation['shareid17'];
		$shareId18 = $member_relation['shareid18'];
		$shareId19 = $member_relation['shareid19'];
		$shareId20 = $member_relation['shareid20'];




		if(empty($shareId)) $d['commission'] = 0;
		if(empty($shareId2)) $d['commission2'] = 0;
		if(empty($shareId3)) $d['commission3'] = 0;
		if(empty($shareId4)) $d['commission4'] = 0;
		if(empty($shareId5)) $d['commission5'] = 0;
		if(empty($shareId6)) $d['commission6'] = 0;
		if(empty($shareId7)) $d['commission7'] = 0;
		if(empty($shareId8)) $d['commission8'] = 0;
		
		// 额外利润
	/*
		if($goods['costprice'] > 0) {
			$extra_profits = ($goods['marketprice'] - $goods['costprice']) * 0.15;
		} else {
			$extra_profits = $goods['marketprice'] * 0.04;
		}
	*/

		if($profit > 0) {
			$extra_profits = $profit * 0.20;
		} else {
			$extra_profits = 0;
		}
		$extra_profits = number_format($extra_profits, 2, '.', '');

	/*
		$d['commission'] = 0;
		$d['commission2'] = 0;
		$d['commission3'] = 0;
		$d['commission4'] = 0;
		$d['commission5'] = 0;
		$d['commission6'] = 0;
		$d['commission7'] = 0;
		$d['commission8'] = 0;
		if($shareId) {
			if($goods['costprice'] > 0) {
				$d['commission'] = ($goods['marketprice'] - $goods['costprice']) * 0.5*0.75;
			} else {
				$d['commission'] = $goods['marketprice'] * 0.2*0.5*0.75;
			}

			$d['prize_pool'] = $d['commission'];
		} else {
			if($goods['costprice'] > 0) {
				$d['prize_pool'] = ($goods['marketprice'] - $goods['costprice']) *0.75;
			} else {
				$d['prize_pool'] = $goods['marketprice'] * 0.2*0.75;
			}
		}
		$extra_profits = 0;
	*/



		$level = 0;
		$max_level = 0;

		$extra_commissionTotal_all = 0;
		if($shareId && $extra_profits > 0) {
			$extra_commissionTotal_1 = self::get_extra_commission($shareId, $extra_profits);
			global $level;
			$shareId_level = $level;
			$max_level = $level;

			//判断如果用户是金牌会员，否则extra_shareId=0
			if($shareId_level > 0) {
				$extra_commissionTotal_all += $extra_commissionTotal_1;
				$extra_shareId = $shareId;
			} else {
				$extra_commissionTotal_1 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId = 0;
			}
		}
		
		if($shareId2 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_2 = self::get_extra_commission($shareId2, $extra_profits, $extra_commissionTotal_all);
			$shareId2_level = $level;

			if($shareId2_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_2;
				$extra_shareId2 = $shareId2;
			} else {
				$extra_commissionTotal_2 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId2 = 0;
			}
			$max_level = max($max_level, $level);
		}

		if($shareId3 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_3 = self::get_extra_commission($shareId3, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId3_level = $level;

			if($shareId3_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_3;
				$extra_shareId3 = $shareId3;
			} else {
				$extra_commissionTotal_3 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId3 = 0;
			}
			$max_level = max($max_level, $level);
		}
		
		if($shareId4 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_4 = self::get_extra_commission($shareId4, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId4_level = $level;

			if($shareId4_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_4;
				$extra_shareId4 = $shareId4;
			} else {
				$extra_commissionTotal_4 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId4 = 0;
			}
			$max_level = max($max_level, $level);
		}
		
		if($shareId5 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_5 = self::get_extra_commission($shareId5, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId5_level = $level;

			if($shareId5_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_5;
				$extra_shareId5 = $shareId5;
			} else {
				$extra_commissionTotal_5 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId5 = 0;
			}
			$max_level = max($max_level, $level);
		}
		
		if($shareId6 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_6 = self::get_extra_commission($shareId6, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId6_level = $level;

			if($shareId6_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_6;
				$extra_shareId6 = $shareId6;
			} else {
				$extra_commissionTotal_6 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId6 = 0;
			}
			$max_level = max($max_level, $level);
		}
		
		if($shareId7 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_7 = self::get_extra_commission($shareId7, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId7_level = $level;

			if($shareId7_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_7;
				$extra_shareId7 = $shareId7;
			} else {
				$extra_commissionTotal_7 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId7 = 0;
			}
			$max_level = max($max_level, $level);
		}
		
		if($shareId8 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_8 = self::get_extra_commission($shareId8, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId8_level = $level;

			if($shareId8_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_8;
				$extra_shareId8 = $shareId8;
			} else {
				$extra_commissionTotal_8 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId8 = 0;
			}
			$max_level = max($max_level, $level);
		}
		
		// 如果上8级别仍然没有分完,并且还有上级，就继续往上分
		if($shareId9 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_9 = self::get_extra_commission($shareId9, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId9_level = $level;

			if($shareId9_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_9;
				$extra_shareId9 = $shareId9;
			} else {
				$extra_commissionTotal_9 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId9 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId10 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_10 = self::get_extra_commission($shareId10, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId10_level = $level;

			if($shareId10_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_10;
				$extra_shareId10 = $shareId10;
			} else {
				$extra_commissionTotal_10 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId10 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId11 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_11 = self::get_extra_commission($shareId11, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId11_level = $level;

			if($shareId11_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_11;
				$extra_shareId11 = $shareId11;
			} else {
				$extra_commissionTotal_11 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId11 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId12 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_12 = self::get_extra_commission($shareId12, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId12_level = $level;

			if($shareId12_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_12;
				$extra_shareId12 = $shareId12;
			} else {
				$extra_commissionTotal_12 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId12 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId13 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_13 = self::get_extra_commission($shareId13, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId13_level = $level;

			if($shareId13_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_13;
				$extra_shareId13 = $shareId13;
			} else {
				$extra_commissionTotal_13 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId13 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId14 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_14 = self::get_extra_commission($shareId14, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId14_level = $level;

			if($shareId14_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_14;
				$extra_shareId14 = $shareId14;
			} else {
				$extra_commissionTotal_14 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId14 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId15 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_15 = self::get_extra_commission($shareId15, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId15_level = $level;

			if($shareId15_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_15;
				$extra_shareId15 = $shareId15;
			} else {
				$extra_commissionTotal_15 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId15 = 0;
			}
			$max_level = max($max_level, $level);
		}

		if($shareId16 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_16 = self::get_extra_commission($shareId16, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId16_level = $level;

			if($shareId16_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_16;
				$extra_shareId16 = $shareId16;
			} else {
				$extra_commissionTotal_16 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId16 = 0;
			}
		}
	
		if($shareId17 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_17 = self::get_extra_commission($shareId17, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId17_level = $level;

			if($shareId17_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_17;
				$extra_shareId17 = $shareId17;
			} else {
				$extra_commissionTotal_17 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId17 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId18 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_18 = self::get_extra_commission($shareId18, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId18_level = $level;

			if($shareId18_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_18;
				$extra_shareId18 = $shareId18;
			} else {
				$extra_commissionTotal_18 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId18 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId19 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_19 = self::get_extra_commission($shareId19, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId19_level = $level;

			if($shareId19_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_19;
				$extra_shareId19 = $shareId19;
			} else {
				$extra_commissionTotal_19 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId19 = 0;
			}
			$max_level = max($max_level, $level);
		}
	
		if($shareId20 && $extra_commissionTotal_all < $extra_profits) {
			$extra_commissionTotal_20 = self::get_extra_commission($shareId20, $extra_profits, $extra_commissionTotal_all);
			global $level;
			$shareId20_level = $level;

			if($shareId20_level > $max_level) {
				$extra_commissionTotal_all += $extra_commissionTotal_20;
				$extra_shareId20 = $shareId20;
			} else {
				$extra_commissionTotal_20 = 0;
				$extra_commissionTotal_all += 0;
				$extra_shareId20 = 0;
			}
		}

		$d['extra_commission'] = floatval($extra_commissionTotal_1);
		$d['extra_commission2'] = floatval($extra_commissionTotal_2);
		$d['extra_commission3'] = floatval($extra_commissionTotal_3);
		$d['extra_commission4'] = floatval($extra_commissionTotal_4);
		$d['extra_commission5'] = floatval($extra_commissionTotal_5);
		$d['extra_commission6'] = floatval($extra_commissionTotal_6);
		$d['extra_commission7'] = floatval($extra_commissionTotal_7);
		$d['extra_commission8'] = floatval($extra_commissionTotal_8);
		$d['extra_commission9'] = floatval($extra_commissionTotal_9);
		$d['extra_commission10'] = floatval($extra_commissionTotal_10);
		$d['extra_commission11'] = floatval($extra_commissionTotal_11);
		$d['extra_commission12'] = floatval($extra_commissionTotal_12);
		$d['extra_commission13'] = floatval($extra_commissionTotal_13);
		$d['extra_commission14'] = floatval($extra_commissionTotal_14);
		$d['extra_commission15'] = floatval($extra_commissionTotal_15);
		$d['extra_commission16'] = floatval($extra_commissionTotal_16);
		$d['extra_commission17'] = floatval($extra_commissionTotal_17);
		$d['extra_commission18'] = floatval($extra_commissionTotal_18);
		$d['extra_commission19'] = floatval($extra_commissionTotal_19);
		$d['extra_commission20'] = floatval($extra_commissionTotal_20);

		return $d;
	}

	//计算扩展佣金
 	// 计算额外佣金
	// $profileid 代理的ID
	// $extra_profits 额外利润的金额
	// $extra_profits_ed 已分配出去的利润
	private function get_extra_commission($profileid = 0, $extra_profits = 0, $extra_profits_ed = 0) {
		global $_W, $level, $member_agent_level;

		if(empty($profileid)) return 0;
		if(empty($extra_profits)) return 0;

		$level = intval($member_agent_level[$profileid]['agent_level']);

		if($level > 0) {
			if($level == 1) { // 金牌
				$extra_commissionTotal = $extra_profits * 10 / 100;
			} elseif($level == 2) { // 白金
				$extra_commissionTotal = ($extra_profits - $extra_profits_ed) * 40 / 100;
			} elseif($level == 3) { // 钻石
				$extra_commissionTotal = ($extra_profits - $extra_profits_ed) * 60 / 100;
			} elseif($level == 4) { // 皇冠
				$extra_commissionTotal = ($extra_profits - $extra_profits_ed) * 80 / 100;
			} elseif($level == 5) { // 股东
				$extra_commissionTotal = ($extra_profits - $extra_profits_ed) * 100 / 100;
			}
			$extra_commissionTotal = number_format($extra_commissionTotal, 2, '.', '');
			if(($extra_profits - $extra_profits_ed) < $extra_commissionTotal) { // 如果剩余金额小于应分配金额，应分配金额以剩余金额为准
				$extra_commissionTotal = $extra_profits - $extra_profits_ed;
			}
			return $extra_commissionTotal;
		} else {
			$level = 0;
		}

		return 0;
	}


	//http://www.kdniao.com/
	//快递查询
	//必须参数: ordersn
	public function express() {
		global $_W,$_GET;

		//是否传入订单号
		//订单号是否存在
		$ordersn = trim($_GET['ordersn']);

		if(empty($ordersn)) return self::responseError(270, 'Parameter [ordersn] is missing.');
		if(!preg_match('/^([A-Z0-9]+)$/', $ordersn)) return self::responseError(270, 'Parameter [ordersn] is invalid.');

		$item = pdo_fetch("SELECT o.id AS orderId,o.ordersn,o.member_id,o.paytype,".
			"o.status,o.createtime,o.express,o.expresssn,l.logistic_code,l.shipper_code ".
			"FROM `ims_bj_qmxk_order` o ".
			"LEFT JOIN `ims_logistics` l ON l.ordersn=o.ordersn ".
			"WHERE o.ordersn='{$ordersn}'");
/**/
	if($_W['member_id'] != 18) {
		if(empty($item) || $item['member_id'] != $_W['member_id']) {
			return self::responseError(271, '抱歉，该订单不存在.');
		}
	}
		if($item['status'] == 0 || $item['status'] == 1) {
			return self::responseError(271, '抱歉，该订单尚未发货.');
		}
/*
		if(empty($item['express']) || empty($item['expresssn'])) {
			return self::responseError(271, '暂无该订单的物流状态.');
		}
*/
		require IA_ROOT . '/source/libs/delivery.class.php';
		if($item['shipper_code'] == 'HHTT') {
			if($item['shipper_code'] == 'HHTT') {
				$item['express'] = 'tiantian';
				$item['expresssn'] = $item['logistic_code'];
			}
			$result = Delivery::expressQuery($item['express'], $item['expresssn']);
		} else {
			$result = Delivery::kdniao($item['express'], $item['expresssn'], $item['ordersn']);
		}

		$results = array();
		if($result['data']) {
			foreach($result['data'] AS $item) {
				unset($item['ftime']);
				$data[] = $item;
			}

			$results['state']		= $result['message']; //快递单当前状态
			$results['com']			= $result['com']; //快递公司名称
			$results['nu']			= $result['nu']; //快递单号
			$results['trackList']	= $data; //快递单当前状态
		}

		return self::responseOk($results);
	}


	private function addOrderLog($orderinfo) {

		$data['orderid'] = $orderinfo['orderid'];
		$data['ordersn'] = $orderinfo['ordersn'];
		$data['status'] = $orderinfo['status'];
		$data['action_user'] = $orderinfo['action_user'];
		$data['member_id'] = $orderinfo['member_id'];
		$data['adminid'] = $orderinfo['adminid'];
		$data['sellerid'] = $orderinfo['sellerid'];
		$data['dateline'] = TIMESTAMP;
		$data['remark'] = $orderinfo['remark'];
		$data['platform'] = self::$platform;

		pdo_insert('bj_qmxk_order_log', $data);
	}
	
	//拼单
	private function pintuan($pid,$member_id,$tid=0){
	    if(!empty($pid)){
	        //判断用户有没有拼单订单
	        /*
	        $have = false;
	        $groupon_member = pdo_fetchall("SELECT `tid`,`orderid` FROM `ims_bj_qmxk_groupon_member` WHERE member_id ='{$member_id}' AND pid='{$pid}'");
	        foreach ($groupon_member as $m){
	            if($m['tid']==0){
	                //tid=0说明是开新团的，还没有付款,
	                $order = pdo_fetch('SELECT `status` FROM '.tablename('bj_qmxk_order'). " WHERE `id`='{$m['orderid']}'");
	                if($order['status']>0){
	                    $have = true;
	                    break;
	                }
	            }else{
    	            $tuan_arr = pdo_fetch('SELECT `status` FROM '.tablename('bj_qmxk_groupon'). " WHERE `id`='{$m['tid']}' AND `status`<>2 ");
                    if($tuan_arr){
                        $order = pdo_fetch('SELECT `status` FROM '.tablename('bj_qmxk_order'). " WHERE `id`='{$m['orderid']}'");
        	            if($order['status']>0){
        	                $have = true;
        	                break;
        	            }
                    }
	            }
	        }
	        */
	        $have = false;
	        $orders = pdo_fetchall("SELECT orderid FROM `ims_bj_qmxk_groupon_member` WHERE member_id ='{$member_id}' AND pid='{$pid}'");
	        foreach ($orders as $val){
	            $order = pdo_fetch('SELECT `status` FROM '.tablename('bj_qmxk_order'). " WHERE `id`='{$val['orderid']}'");
	            if($order['status']>=0){
	                $have = true;
	                break;
	            }
	        }
	        if(!$have){
	            $tuan = pdo_fetch('SELECT * FROM '.tablename('bj_qmxk_goods_groupon'). " WHERE endtime>UNIX_TIMESTAMP(NOW()) AND `status`=1 AND `id`='{$pid}' limit 1");
	            if($tuan){
	                if($tid!=0){
	                    $groupon = pdo_fetch('SELECT * FROM '.tablename('bj_qmxk_groupon'). " WHERE `id`='{$tid}' limit 1");
	                    if($groupon['buynum'] < $groupon['num_limit']){
	                        if($groupon['starttime'] + 3600*24 >= time()){
	                            return $tuan;
	                        }else{
	                            return self::responseError(999, '抱歉，该拼单已经过期了');
	                        }
	                    }else{
	                        return self::responseError(999, '抱歉，该拼单已经满员了');
	                    }
	                }
	                return $tuan;
	            }
	        }else{
	            return self::responseError(999, '抱歉，您已经参加过该商品的拼单了');
	        }
	    }
	    return false;
	}

	/*
	$totalcnf 减库存方式 0 拍下减库存 1 付款减库存 2不减库存
	*/
	public function setOrderStock($orderid = '', $minus = true, $totalcnf=0) {
		$goods = pdo_fetchall("SELECT g.id, g.title, g.thumb, g.unit, g.marketprice,g.total AS goodstotal,og.total,og.optionid,g.sales,g.totalcnf ".
			"FROM `ims_bj_qmxk_order_goods` og ".
			"LEFT JOIN `ims_bj_qmxk_goods` g ON og.goodsid=g.id ".
			"WHERE og.orderid='{$orderid}'");

		foreach($goods AS $item) {
			if($minus) {//减库存
				if(!empty($item['optionid'])) {
					pdo_query('UPDATE `ims_bj_qmxk_goods_option` set stock=stock-:stock WHERE id=:id', array(
						':stock' => $item['total'],
						':id' => $item['optionid']
					));
				}
				pdo_query("UPDATE  ims_bj_qmxk_goods SET total=total-{$item['total']},sales=sales+{$item['total']} WHERE id='{$item['id']}'");
			}
			else {
				if($totalcnf != $item['totalcnf']) {
					continue;
				}
				if(!empty($item['optionid'])) {
					pdo_query('UPDATE `ims_bj_qmxk_goods_option` set stock=stock+:stock WHERE id=:id', array(
						':stock' => $item['total'],
						':id' => $item['optionid']
					));
				}
				pdo_query("UPDATE  ims_bj_qmxk_goods SET total=total+{$item['total']},sales=sales-{$item['total']} WHERE id='{$item['id']}'");
			}
		}
	}

}
