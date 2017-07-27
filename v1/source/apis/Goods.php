<?php
class Goods extends Api {
    
	//根据商品ID获取商品详情
	//必须参数: goodsId=商品ID
	//返回是否已收藏
	public function item() {
		global $_W;
		require_once IA_ROOT.'/source/apis/User.php';
		if(User::checklogin()){}

		$id = intval($_GET['goodsId']);
		$tid = intval($_GET['tid']);
		$useCmt = input('get.useCmt');
		$useCmt = empty($useCmt)? 'yes' : '';

		if(empty($id)) {
			return self::responseError(400, 'Parameter [goodsId] is missing.');
		}

		$member_id = isset($_COOKIE['pin']) ? compute_id($_COOKIE['pin']) : 0;
		$member_id_public = isset($_COOKIE['pin']) ? intval($_COOKIE['pin']) : 0;

		$sql_ext = '';
		if(self::$platform == 'Touch') { //移动端描述直接输出
			$sql_ext = 'g.content,';
		}

		$item = array();
		$item = pdo_fetch("SELECT g.id AS goodsId,g.limit_num, g.title, g.thumb, g.sellerid, mp.seller_name,mp.paush,mp.paush_reason, g.pcate, g.ccate, g.type, g.goodssn, ".
			"g.marketprice, g.productprice, g.total, g.sales, g.thumb_url, g.viewcount, g.maxbuy, g.isnew, g.ishot, g.issendfree,{$sql_ext} ".
			"g.issendfree,g.isrecommand,g.istime,g.timestart,g.timeend,g.checked,g.status,g.deleted,g.hasoption,g.activity_type ".
			"FROM ims_bj_qmxk_goods g ".
			"LEFT JOIN ims_members_profile mp ON g.sellerid=mp.uid ".
			"WHERE g.id='{$id}'");
		$allow_pf = false;//是否允许批发
		$allow_pf = in_array($item['sellerid'],ConfigModel::$WHOLESALE_SHOPS);
		if(!$allow_pf){
			$item['limit_num'] = '0';//非洪伟公司，批发数量不存在。
		}

		if(empty($item['checked']) || empty($item['status']) || $item['deleted']) {
			return self::responseError(404, '此商品已售謦或已下架！');
		}
                
		$item['stock'] = $item['total'];
		$item['stock_item'] = array('item_left'=>'剩余：','item_num'=>(string)$item['total'],'item_right'=>'');
		unset($item['total']);
		unset($item['checked']);
		unset($item['status']);
		unset($item['deleted']);
		// $item['productprice'] = '-';

		$item['thumb_url'] = unserialize($item['thumb_url']);
		if($item['thumb_url']) {
			foreach ($item['thumb_url'] AS $key=>$thumb_url) {
				$item['thumb_alisa'][$key] = $thumb_url['attachment'] ?  $_W['attachurl'].$thumb_url['attachment'].'_500x500.jpg' : '';
			}
		} else {
			$item['thumb_alisa'] = array();
		}
		unset($item['thumb_url']);

		$item['thumb_source'] = $item['thumb'] ? $_W['attachurl'].$item['thumb'] : 'http://statics.sldl.fcmsite.com/empty.gif';
		$item['thumb'] = $item['thumb'] ? $_W['attachurl'].$item['thumb'].'_500x500.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
		$item['detail_url'] = 'https://h5.api.shunliandongli.com/v1/detail/'.$item['goodsId'].'.html';

		$allspecs = pdo_fetchall("SELECT id AS specid,title,goodsid FROM ims_bj_qmxk_spec WHERE goodsid='{$item['goodsId']}' ORDER BY displayorder ASC", array(), 'specid');

		foreach($allspecs as &$s) {
			$spec_item_arr = pdo_fetchall("SELECT id AS spec_itemId,specid,title,thumb FROM ims_bj_qmxk_spec_item WHERE `show`=1 and specid='{$s['specid']}' ORDER BY displayorder ASC");
			foreach($spec_item_arr AS $key=> $spec_item) {
				$spec_item_arr[$key]['thumb'] = $spec_item_arr[$key]['thumb'] ? $_W['attachurl'].$spec_item_arr[$key]['thumb'] : '';
			}
			$s['items'] = $spec_item_arr;
		}

		unset($s);

		$options_arr = pdo_fetchall("SELECT id AS optionId,title,thumb,marketprice,productprice,stock,weight,specs FROM ims_bj_qmxk_goods_option WHERE goodsid='{$item['goodsId']}' ORDER BY id ASC");
		foreach($options_arr AS $key=>$option) {
			$specitemids = explode('_', $option['specs']);
			$option['specid'] = $specitemids[0];
			$option['spec_itemId'] = $specitemids[1];
			$option['thumb'] = $option['thumb'] ? $_W['attachurl'].$option['thumb'].'_300x300.jpg' : '';
			asort($specitemids);
			$comma = '';
			foreach ($specitemids AS $val) {
				$option['specs_id'] .= $comma.$val;
				$comma = '_';
			}
			unset($option['specs']);
			// $option['productprice'] = '-';
			$options[] = $option;
		}

		$specs = array();
		if(count($options) > 0) {
			foreach($allspecs as $ss) {
				$items = $ss['items'];
				foreach($items as $it) {
					if($it['specid'] == $allspecs[$it['specid']]['specid']) {
						$specs[] = $ss;
						break;
					}
				}
			}
		}
		$is_pintuan = 0;
		$pintuan = $this->pintuan($item,$tid);
		if($tid!=0){
		    $is_pintuan = 1;
		    $item['grouponprice'] = $pintuan['grouponprice'];
		    if($options){
		        foreach ($options as $key=>$option){
		            $option['grouponprice'] = $pintuan['grouponprice'];
		            $options[$key] = $option;
		        }
		    }
		}else{
		    if(!empty($pintuan)){
		        $is_pintuan = 1;
		        $item['grouponprice'] = $pintuan['grouponprice'];
		        if($options){
		            foreach ($options as $key=>$option){
		                $option['grouponprice'] = $pintuan['grouponprice'];
		                $options[$key] = $option;
		            }
		        }
		    }
		}
		
                //活动
                $items = $this->activity($item['goodsId'],$_W['attachurl']);
                if($options){
                    foreach ($options as $key=>$option){
                        $option['actprice'] = isset($items['act_option'][$option['optionId']])?$items['act_option'][$option['optionId']]:'';
                        $options[$key] = $option;
                    }
                }
                $item['act_id'] = $items['act_id'];
                $item['actprice'] = $items['actprice'];
                $item['activity_status'] = $items['activity_status']; 
                $item['little_word'] = $items['little_word'];
                $item['act_pic'] = $items['act_pic'];
                $item['actstilltime'] = $items['actstilltime'];
                $item['thumb'] = isset($items['thumb'])?$items['thumb']:$item['thumb'];
                $item['marketprice'] = isset($items['marketprice'])?$items['marketprice']:$item['marketprice'];
                unset($items['act_option']);
                
                if($item['paush'] == 1){
                    $item['activity_status'] = '3'; 
                }   
                $item['paush_reason'] = empty($item['paush_reason'])?'':$item['paush_reason'];
               
		//分享链接
		$shareLink = $_W['share_domain'].'goods/'.$item['goodsId'].'?u='.$member_id_public;
		$weixinLink = $_W['shunlian_domain'].'goods/'.$item['goodsId'];
		if(isset($_W['mid'])){
			$weixinLink = $_W['shunlian_domain'].'goods/'.$item['goodsId'].'?mid='.$_W['mid'];
		}
		

		$desc_ext = $item['issendfree'] ? '（还包邮哦）' : '';
		$goods_price = isset($item['grouponprice']) ? $item['grouponprice'] : $item['marketprice'];
                
		$shareInfo = array(
			'title' => $item['title'],
			'desc' => $item['title'].'今日特价：￥'.$goods_price.$desc_ext,
			'link' => $shareLink,//$_W['share_domain'].'goods/'.$item['goodsId'].'?u='.$member_id_public,
            'weixinLink' => $weixinLink,
			'img' => $item['thumb_source'].'_100x100.jpg'
		);

		$favorite = pdo_fetch("SELECT id AS favoriteId,optionid FROM `ims_bj_qmxk_favorites` WHERE member_id='{$member_id}' AND goodsid={$item['goodsId']}");

		$shopCount = array();
		$shopCount = GoodsModel::getInstance()->getShopCount($item['sellerid']);

		$voucher = GoodsModel::getInstance()->getGoodsVoucher($item['sellerid']);
		foreach($voucher AS $k=>$v){
           $voucher[$k]['price'] = intval($v['price']);
           $voucher[$k]['discount'] = intval($v['discount']);
        }
		//聊天功能是否开启
		$seller_chat = pdo_fetch("SELECT * FROM " . tablename('members_chat') . " WHERE `uid` = '{$item['sellerid']}'");
		if($seller_chat && $seller_chat['state'] == 1){
			$chat=1;
		}else{
			$chat=0;
		}
		
		$seller_phone = pdo_fetch("SELECT seller_tel , telephone FROM ".tablename('members_profile')." WHERE uid='{$item['sellerid']}'");
		$seller_tel = '';
		if(!empty($seller_phone['seller_tel'])){
			$seller_tel = $seller_phone['seller_tel'];
		}else if(!empty($seller_phone['telephone'])){
			$seller_tel = $seller_phone['telephone'];
		}
		$item['seller_tel'] = $seller_tel;
		
		$options = ($item['hasoption'] == 1) ? $options : array();
		
		$specs = ($item['hasoption'] == 1) ? $specs : array();

		$results = array('item'=>$item,'chat'=>$chat,'is_pintuan'=>$is_pintuan, 'pintuan'=>$pintuan,'voucher'=>$voucher,'specs'=>$specs, 'options'=>$options, 'favoriteId'=>intval($favorite['favoriteId']), 'shareLink'=>$weixinLink, 'weixinLink'=>$weixinLink,'shareInfo'=>$shareInfo, 'shopCount'=>$shopCount);

		//评论
		if($useCmt=='yes'){
			$paras['goods_id'] 		 = $id;
			$paras['count'] 		 = 2;
			$paras['page'] 			 = 1;
			$paras['star_level']     = 0;
			$paras['usePage'] 		 = 'yes';
			$paras['useCmtdata'] 	 = 'yes';

			$cmts = $this->_comments($paras);
			if(empty($cmts) || $cmts['itemCount']==0){
				$results['commentInfo'] = null;
			}else{
				$results['commentInfo'] = $cmts;
			}
			
		}

		//返回随机积分相关cookie
		if(User::checklogin()) {
			$trackInfo = $_GET['trackInfo'];
			$track_arr = array();
			$track_arr['HOME_TO_DETAIL'] 	= CreditRandomModel::HOME_TO_DETAIL;
			$track_arr['LIST_TO_DETAIL'] 	= CreditRandomModel::LIST_TO_DETAIL;
			$track_arr['CHANNEL_TO_DETAIL'] = CreditRandomModel::CHANNEL_TO_DETAIL;
			$track_arr['SEARCH_TO_DETAIL']  = CreditRandomModel::SEARCH_TO_DETAIL;
			CreditRandomModel::getInstance()->addDetailViewNum($id, $track_arr[$trackInfo]);

			if($_POST['testMode']=='YES'){
				$results['debug_credit'] = CreditRandomModel::getInstance()->getData();
			}
		}
		

		return self::responseOk($results);
	}

	//根据商品ID获取规则收藏信息
	public function favorite() {
		global $_W;

		$goodsId = intval($_GET['goodsId']);
		if(empty($goodsId)) {
			return self::responseError(400, 'Parameter [goodsId] is missing.');
		}
		$member_id = isset($_COOKIE['pin']) ? compute_id($_COOKIE['pin']) : 0;
		$member_id_public = isset($_COOKIE['pin']) ? intval($_COOKIE['pin']) : 0;

		$options_arr = pdo_fetchall("SELECT go.id AS optionId,go.title,go.thumb,go.marketprice,go.productprice,".
			"go.stock,go.weight,go.specs FROM ims_bj_qmxk_goods_option go ".
			"LEFT JOIN `ims_bj_qmxk_favorites` f ON f.member_id='{$member_id}' AND f.optionid=go.id ".	
			"WHERE go.goodsid='{$goodsId}' ORDER BY go.id ASC");

		foreach($options_arr AS $key=>$option) {
			$specitemids = explode('_', $option['specs']);
			$option['specid'] = $specitemids[0];
			$option['spec_itemId'] = $specitemids[1];
			$option['thumb'] = $option['thumb'] ? $_W['attachurl'].$option['thumb'].'_300x300.jpg' : '';
			$option['favoriteId'] = intval($option['favoriteId']);
			unset($option['specs']);
			// $option['productprice'] = '-';
			$options[] = $option;
		}

		return self::responseOk(array('options'=>$options));
	}

	public function detail() {
		global $_W;

		$id = intval($_GET['goodsId']);
		if(empty($id)) {
			return self::responseError('');
		}

		$item = array();
		$item = pdo_fetch("SELECT content,checked,status,deleted,sellerid FROM ims_bj_qmxk_goods WHERE id='{$id}'");

		if(empty($item['checked']) || empty($item['status']) || $item['deleted']) {
			return self::responseError('');
		}
		unset($item['checked']);
		unset($item['status']);
		unset($item['deleted']);

		$item['content'] = preg_replace(array(
			'/max-width:([^>]+);/i',
			'/data-mce-style="([^>]+)"/i',
			'/<table([^>]+)width\:([^>]+);([^>]*)>/i',
			'/\.\/resource\/attachment\/images\//i'
		), array(
			'',
			'',
			"<table $1 width:100%;$3>",
			$_W['attachurl'].'images/'
		), $item['content']);

		$seller = pdo_fetch("SELECT paush,paush_reason FROM ims_members_profile WHERE uid='{$item['sellerid']}'");
		if($seller['paush']==1){
		    $item['content'] = '<h2 style="background:#c00;color:#fff;font-size:22px;width:100%;padding:10px;text-align:left;">'.$seller['paush_reason'].'</h2>'.$item['content'];
		}
		unset($item['sellerid']);
		
		return self::responseOk($item['content']);

	}
	/**
	 * 拼单商品信息
	 * @param array $goods 商品信息
	 */
	private function pintuan($goods,$tid=0){
	    $pintuan = null;
	    $PIN_TUAN_TIME = 24;//拼购过期时间(小时)
	    if($tid!=0){
	        $list_tuan_arr = pdo_fetchall('SELECT member_id,realname,avatar,isleader,tid,pid,status,orderid,goodsid,createtime FROM '.tablename('bj_qmxk_groupon_member'). " WHERE `tid`='{$tid}' AND `status`>0 ORDER BY isleader DESC");
	        if($list_tuan_arr){
	            $group = pdo_fetch('SELECT `id` as tid,groupid as pid,goodsid,member_id,`status`,num_limit,buynum,starttime FROM '.tablename('bj_qmxk_groupon'). ' WHERE `id`='.$tid .' LIMIT 1');
	            if(!empty($group)){
	                $tuan = pdo_fetch('SELECT `id` as pid,sellerid,num_limit,marketprice,costprice,grouponprice,status,starttime,endtime FROM '.tablename('bj_qmxk_goods_groupon'). " WHERE `id`='{$group['pid']}'  limit 1");
	                $pintuan = $tuan;
	                $pintuan['list'] = null;
	                $pintuan['tuan_list']['left_num'] = $group['num_limit']- $group['buynum'];
	                $pintuan['tuan_list']['left_time'] = $group['starttime']+(3600*$PIN_TUAN_TIME)-time();
	                $pintuan['tuan_list']['status'] = $group['status'];
	                $pintuan['tuan_list']['isleader'] = 0;
	                $pintuan['tuan_list']['list'] = $list_tuan_arr;
	            }
	        }
	    }else{
	        if($goods['activity_type']==1){//拼单活动
	            //获取当前拼单的信息grouponprice团购价，num_limit几个成团
	            $tuan = pdo_fetch('SELECT `id` as pid,sellerid,num_limit,marketprice,costprice,grouponprice,status,starttime,endtime FROM '.tablename('bj_qmxk_goods_groupon'). " WHERE endtime>UNIX_TIMESTAMP(NOW()) AND starttime<UNIX_TIMESTAMP(NOW()) AND `status`=1 AND `goodsid`='{$goods['goodsId']}'  limit 1");
	            if(!empty($tuan)){
	                $pintuan = $tuan;
	                $list_tuan_arr = pdo_fetchall('SELECT `id` as tid,groupid as pid,member_id,realname,avatar,num_limit,buynum,starttime,status,goodsid FROM '.tablename('bj_qmxk_groupon'). " WHERE `groupid`='{$tuan['pid']}' AND `status`=0");
	                $tuan_list = null;
	                $total_num = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_groupon_member WHERE `pid`='{$tuan['pid']}' AND `status`=1");
	                $tuan_num = 0;
	                if(!empty($list_tuan_arr)){
	                    foreach ($list_tuan_arr as $val){
	                        $val['left_num'] = $tuan['num_limit'] - $val['buynum'];
	                        $val['left_time'] = $val['starttime']+(3600*$PIN_TUAN_TIME)-time();
	                        if($val['left_num']>0){
    	                        if($val['left_time']>0 || $val['status']==0 ){
    	                            $tuan_list[] = $val;
    	                        }
	                        }
// 	                        $total_num += $val['buynum'];
	                        $tuan_num++;
	                    }
	                }
	                $pintuan['tuan_num'] = $tuan_num;
                        
	                $pintuan['total_num'] = $total_num;
                        
                        if(self::$platform == 'IOS') {
                            $pintuan['total_num'] = $total_num ? $total_num.'...' : '...';
                        }
                        else if(self::$platform == 'Android')
                        {
                            $pintuan['total_num'] = $total_num ;
                        }
                        
	                $pintuan['list'] = $tuan_list;
	                $pintuan['tuan_list'] = null;
	            }
	        }
	    }
	    return $pintuan;
	}

	/**
	* 获取商品评价
	*/
	private function _comments($paras){
		global $_W;

		$arr_star_level  = array(1, 3, 5);
		$goods_id 		 = intval($paras['goods_id']);
		$count 			 = intval($paras['count']);
		$page 			 = max(1, intval($paras['page']));
		$star_level      = intval($paras['star_level']);
		$usePage 		 = $paras['usePage'];
		$useCmtdata 	 = $paras['useCmtdata'];

		$count = ($count && $count <= 40) ? $count : 20;
		if(empty($goods_id)) {
			return array();
		}
		if(!in_array($star_level, $arr_star_level)){
			$star_level = 0;
		}

		$where = array();
		$where[] = "goods_id='{$goods_id}'";
		$where[] = "status>=0";
		if($star_level>0){
			$where[] = "star_level='{$star_level}'";
		}
		$where = implode(' AND ', $where);

		$limit = ' LIMIT ';
		if($usePage == 'yes') {
			$limit .= ($page-1)*$count.','.$count;
		} else {
			$limit .= $count;
		}
		
		$tb_c 	  = tablename('bj_qmxk_goods_comment');
		$tb_bd 	  = tablename('bj_qmxk_goods_comment_body');
		$tb_mi 	  = tablename('bj_qmxk_member_info');
		$comments = pdo_fetchall("SELECT tp_c.id, tp_c.member_id, tp_c.star_level, tp_c.addtime, tp_c.buytime, tp_c.status, ".
			"cb.goods_option, cb.content, cb.reply, cb.reply_time, cb.pics, ".
			"mi.nickname, mi.avatar, mi.order_credit_count FROM ".
			"(SELECT * FROM {$tb_c} WHERE {$where} ORDER BY addtime DESC {$limit}) AS tp_c ".
			"LEFT JOIN {$tb_bd} AS cb ON cb.comment_id=tp_c.id ".
			"LEFT JOIN {$tb_mi} AS mi ON mi.member_id=tp_c.member_id ".
			"");

		// 需要对分页再排序
        function sort_addtime($a, $b){
            if($a['addtime']==$b['addtime']){
                return 0;
            }
            return ($a['addtime'] < $b['addtime']) ? 1 : -1;
        }
        usort($comments, 'sort_addtime');

		$comments_end = array();
		foreach ($comments as $key => $val) {
			$val['addtime'] = time_tran($val['addtime']);
			$val['buytime'] = time_tran($val['buytime'], 'Y-m-d');
			if($val['reply'] && $val['reply_time']>0){
				$val['reply_time'] = time_tran($val['reply_time']);
			}else{
				$val['reply_time'] = '';
			}
			$pics = !empty($val['pics'])? explode(',', $val['pics']) : array();
			foreach ($pics as $pk => $pv){
				if($pv){
					$pics[$pk] = $_W['attachurl'].$pv.'_500x500.jpg';
				}
			}
			$val['pics'] = $pics;

			$val['avatar'] 	 = $val['avatar']? $val['avatar'] : ConfigModel::IMG_DEF_HEADFACE;
			$val['nickname'] = $val['nickname']? maskName($val['nickname']) : '顺联会员' . maskName($val['member_id']);

			if($val['status']==0){//评论隐藏
				$val['pics'] 	= array();
				$val['content'] = '**评论违规被隐藏（广告，无关此商品，违反用户手册……）**';
			}

			$val['vip_level'] = CreditModel::getInstance()->calVipLevelByCredit($val['order_credit_count']);
			
			$comments_end[] = $val;
		}

		$results = array();
		if($useCmtdata == 'yes'){
			//统计数据
			$cmt_data = CommentModel::getInstance()->getGoodsCommentData($goods_id);
			$results['cmt_data'] = $cmt_data;
		}

		if($usePage == 'yes') {
			$itemCount 				= pdo_fetchcolumn("SELECT COUNT(*) FROM {$tb_c} WHERE {$where}");
			$itemCount 				= intval($itemCount);
			$results['itemCount'] 	= $itemCount;
			$results['allPage'] 	= ceil($results['itemCount']/$count);
			$results['page'] 		= $page;
			$results['count'] 		= $count;
		}
		$results['comments'] = $comments_end;

		return $results;
	}

	/**
	* 获取商品评价
	*/
	public function comments(){
		$paras['goods_id'] 		 = intval(input('get.goodsId'));
		$paras['count'] 		 = intval(input('get.count'));
		$paras['page'] 			 = max(1, intval(input('get.page')));
		$paras['star_level']     = intval(input('get.starLevel'));
		$paras['usePage'] 		 = input('get.usePage');
		$paras['useCmtdata'] 	 = input('get.useCmtdata');

		$paras['useCmtdata']	 = empty($paras['useCmtdata'])? 'yes' : $paras['useCmtdata'];

		if(empty($paras['goods_id'])) {
			return self::responseError(400, 'Parameter [goodsId] is missing.');
		}

		$results = $this->_comments($paras);

		return self::responseOk($results);
	}
        
        /**
	 * 活动商品信息
	 * @param array $goods_id 商品id
	 */
	private function activity($goods_id,$urls){
            //查询报名商品
            $time = time();
            $act_list = pdo_fetch("SELECT a.little_word AS little_word,a.id AS aid,a.act_start_time AS act_start_time,a.detail_pic AS detail_pic,".
                    "a.if_act_price AS if_act_price,a.if_time AS if_time,a.act_end_time,e.act_id,e.id AS eid,e.actprice AS actprice,e.start_time,".
                    "e.goods_pic AS goods_pic,e.status,a.status AS isstatus,e.end_time FROM `ims_bj_qmxk_activity` a,`ims_bj_qmxk_activity_entry` e ".
                    "WHERE e.goods_id = {$goods_id} AND a.id=e.act_id AND e.status = 2 AND e.end_time>{$time}");
            if(empty($act_list)){
                $act_list = pdo_fetch("SELECT a.little_word AS little_word,a.id AS aid,a.act_start_time AS act_start_time,a.detail_pic AS detail_pic,".
                    "a.if_act_price AS if_act_price,a.if_time AS if_time,a.act_end_time,e.act_id,e.id AS eid,e.actprice AS actprice,e.start_time,".
                    "e.goods_pic AS goods_pic,e.status,a.status AS isstatus,e.end_time FROM `ims_bj_qmxk_activity` a,`ims_bj_qmxk_activity_entry` e ".
                    "WHERE e.goods_id = {$goods_id} AND a.id=e.act_id AND e.status = 2 AND a.act_end_time>{$time}");
            }
                
            if((!empty($act_list) && $act_list['act_end_time'] >$time) || (empty($act_list['act_end_time']) && $act_list['end_time']>$time)){
                if($act_list['act_start_time'] > $time || $act_list['start_time'] > $time){  //活动未开始
                    $item['act_id'] = 0;
                    $item['activity_status'] = '2';
                    $item['little_word'] = empty($act_list['little_word'])?'':$act_list['little_word'];
                    $item['act_pic'] = '';
                    if($act_list['isstatus'] == 1){
                        $act_time = abs($act_list['act_start_time'] - time());
                    }elseif($act_list['isstatus'] == 2){
                        $act_time = abs($act_list['start_time'] - time());
                    }
                    
                    if($act_time < $act_list['if_time']*86400){
                        $item['actstilltime'] = (string)$act_time;
                    }else{
                        $item['actstilltime'] = '';
                    }
                    if($act_list['if_act_price'] == 1){
                        $item['actprice'] = $act_list['actprice'];
                    }else{
                        $item['actprice'] = '';
                    }
                }else{
                    $item['activity_status'] = '1'; 
                    $item['act_id'] = $act_list['aid'];
                    $item['little_word'] = empty($act_list['little_word'])?'':$act_list['little_word'];
                    $item['act_pic'] = !empty($act_list['detail_pic']) ?  $urls.$act_list['detail_pic'] : 'http://statics.sldl.fcmsite.com/empty.gif';
                    $item['actstilltime'] = '';
                    $item['actprice'] = '';
                    $item['thumb'] = $item['thumb'] ?  $urls.$item['goods_pic'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
                    
                    $act_op = pdo_fetchall("SELECT actprice,spec_id FROM `ims_bj_qmxk_activity_goods` WHERE entry_id = {$act_list['eid']}");
                    $item['marketprice'] = empty($act_list['actprice'])?$act_op[0]['actprice']:$act_list['actprice'];  
                    if(!empty($act_op)){
                        foreach($act_op as $v){
                            $act_option[$v['spec_id']] = $v['actprice'];
                        }
                    }
                }

            }else{
                $item['act_id'] = 0;
                $item['activity_status'] = '0';
                $item['little_word'] = '';
                $item['act_pic'] = '';
                $item['actstilltime'] = '';
                $item['actprice'] = '';
                $act_option = array();
            }
            $item['act_option'] = $act_option;
            return $item;
        }
}
