<?php

class Shop extends Api {


	//获取店铺列表
    public function all() {
		global $_W;

		$category = array();
		$category_arr = pdo_fetchall("SELECT id AS categoryId,name,thumb,parentid,enabled,isrecommand FROM ims_bj_qmxk_category WHERE enabled='1' ORDER BY parentid ASC, displayorder DESC", array(), '', true);

		foreach($category_arr as $index => $row) {
			$row['thumb'] = $row['thumb'] ? $_W['attachurl'].$row['thumb'] : 'http://statics.sldl.fcmsite.com/resource/image/category-thumb.png';
			if(!empty($row['parentid']) && $category[$row['parentid']]['enabled']) {
				//$children[$row['parentid']][$row['id']] = $row;
				unset($category[$row['categoryId']]);

				$category[$row['parentid']]['children'][] = $row;
			} else {
				$category[$row['categoryId']] = $row;
			}
		}

		$category_end = array();
		foreach($category AS $key => $category_item) {
			if($_GET['containTop'] == 1 && $category_item['children']) {
				$topCategory['categoryId'] = $category[$key]['categoryId'];
				$topCategory['name'] = '全部';
				$topCategory['thumb'] = $category[$key]['thumb'];
				$topCategory['parentid'] = $category[$key]['parentid'];
				$topCategory['enabled'] = $category[$key]['enabled'];
				$topCategory['isrecommand'] = $category[$key]['isrecommand'];

				array_unshift($category_item['children'], $topCategory);
			}
			$category_item['name'] = str_replace('&lt;', '', $category_item['name']);
			$category_end[] = $category_item;
		}

		return self::responseOk($category_end);
	}

	//根据店铺ID获取店铺首页
	//必须参数shopId
    public function home() {
		global $_W;
		require_once IA_ROOT.'/source/apis/User.php';
		if(User::checklogin()){}
		//return self::responseOk($_W);
		$shopId = intval($_GET['shopId']);
		if(empty($shopId)) {
			return self::responseError(400, 'Parameter [shopId] is missing.');
		}

		$member_id = isset($_COOKIE['pin']) ? compute_id($_COOKIE['pin']) : 0;
		$member_id_public = isset($_COOKIE['pin']) ? intval($_COOKIE['pin']) : 0;

		$shopInfo = pdo_fetch("SELECT mp.uid AS shopId, mp.seller_name AS shop_name, mp.shop_banner AS banner ".
			", mp.seller_tel AS service_tel, mp.seller_qq AS service_qq, mp.seller_weixin AS service_weixin ".
			"FROM `ims_members_profile` mp ".
			"WHERE mp.uid='{$shopId}'");
		
		if(empty($shopInfo)) {
			return self::responseError(400, '不存在该店铺.');
		}

		$shopInfo['banner'] = $shopInfo['banner'] ? $_W['attachurl'].$shopInfo['banner'] : 'https://statics.shunliandongli.com/resource/image/shop-banner.png';

		$results = array();

		//分享链接
		$shareLink = $_W['share_domain'].'shop/'.$shopInfo['shopId'].'?u='.$member_id_public;
        $weixinLink = $_W['shunlian_domain'].'shop/'.$shopInfo['shopId'];
        if(isset($_W['mid'])){
			$weixinLink = $_W['shunlian_domain'].'shop/'.$shopInfo['shopId'].'?mid='.$_W['mid'];
		}
		$shareInfo = array(
			'title' =>   $shopInfo['shop_name'],
			'desc' => '分享描述',
			'link' => $shareLink,
            'weixinLink' => $weixinLink,
			'img' => $shopInfo['banner'].'_300x300.jpg'
		);

		if($member_id >0) {
			$favorite = pdo_fetch("SELECT id AS favoriteId,optionid FROM `ims_bj_qmxk_favorites` WHERE member_id='{$member_id}' AND shopid='{$shopId}'");
		} else {
			$favorite['favoriteId'] = 0;
		}

		$goodsList = self::goodsList($shopId);

		//优惠券列表，最多10个
		$vlist = pdo_fetchall('SELECT id,price,discount FROM ' . tablename('bj_qmxk_voucher') . " WHERE sellerid='{$shopId}' AND status=1 and end_time>".time()." ORDER BY id DESC  LIMIT 10");
		//聊天功能是否开启
		$seller_chat = pdo_fetch("SELECT * FROM " . tablename('members_chat') . " WHERE `uid` = '{$shopId}'");
		if($seller_chat && $seller_chat['state'] == 1){
			$chat=1;
		}else{
			$chat=0;
		}

		if($_GET['usePage'] == 'yes') {
			return self::responseOk(array('shopInfo'=>$shopInfo, 'chat'=>$chat, 'favoriteId'=>intval($favorite['favoriteId']), 'shareLink'=>$weixinLink, 'weixinLink'=>$weixinLink, 'shareInfo'=>$shareInfo, 'pageInfo'=>$goodsList['pageInfo'], 'goodsList'=>$goodsList['items'],'voucherList'=>$vlist));
		} else {
			return self::responseOk(array('shopInfo'=>$shopInfo, 'chat'=>$chat, 'favoriteId'=>intval($favorite['favoriteId']), 'shareLink'=>$weixinLink, 'weixinLink'=>$weixinLink, 'shareInfo'=>$shareInfo, 'goodsList'=>$goodsList,'voucherList'=>$vlist));
		}
	}

	//根据店铺ID获取店铺详细信息
	//必须参数shopId
    public function item() {
		global $_W;

		$shopId = intval($_GET['shopId']);
		if(empty($shopId)) {
			return self::responseError(400, 'Parameter [shopId] is missing.');
		}

		$shopInfo = pdo_fetch("SELECT mp.uid AS shopId, mp.seller_name AS shop_name, mp.shop_banner AS banner ".
			", mp.seller_tel AS service_tel, mp.seller_qq AS service_qq, mp.seller_weixin AS service_weixin ".
			"FROM `ims_members_profile` mp ".
			"WHERE mp.uid='{$shopId}'");

		if(empty($shopInfo)) {
			return self::responseError(400, '不存在该店铺.');
		}
		$shopInfo['banner'] = $shopInfo['banner'] ? $_W['attachurl'].$shopInfo['banner'] : 'https://statics.shunliandongli.com/resource/image/shop-banner.png';

		return self::responseOk($shopInfo);

	}

    private function goodsList($shopId) {
		global $_W;

		$sellerid = $shopId;

		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$sort = trim($_GET['sort']);
		$sortby = trim($_GET['sortby']);

		$sortby = $sortby == 'DESC' ? 'DESC' : 'ASC';

		$sort_arr = array('default', 'sales', 'dateline', 'hot', 'price');
		$sort = in_array($sort, $sort_arr) ? $sort : 'default';

		$force_index = '';
		$orderby = '';
		if($sort == 'default') {
			$orderby = 'ORDER BY g.updatetime DESC, g.createtime DESC';
		} elseif($sort == 'sales') {
			$orderby = 'ORDER BY g.sales DESC, g.updatetime DESC';
		} elseif($sort == 'dateline') {
			$orderby = 'ORDER BY g.createtime DESC, g.updatetime DESC';
		} elseif($sort == 'hot') {
			$orderby = 'ORDER BY g.viewcount DESC, g.updatetime DESC';
		} elseif($sort == 'price') {
			if($sortby == 'DESC') {
				$orderby = "ORDER BY g.marketprice {$sortby}, g.updatetime DESC";
			} else {
				$orderby = "ORDER BY g.marketprice {$sortby}, g.displayorder ASC";
			}
		}

		$where = '';
		$limit = 'LIMIT ';

		$where .= "g.sellerid='".$sellerid."' AND g.deleted='0' AND g.status='1'";
		$count = ($count && $count <= 40) ? $count : 20;

		if($_GET['usePage'] == 'yes') {
			$limit .= ($page-1)*$count.','.$count;
		} else {
			$limit .= ($count && $count <= 40) ? $count : 20;
		}

		$items = pdo_fetchall("SELECT g.id AS goodsId, g.title, g.thumb, g.sellerid, mp.seller_name, g.pcate, g.ccate, g.type, g.goodssn, ".
			"g.marketprice, g.productprice, g.isnew, g.ishot, g.issendfree, ".
			"g.isrecommand,g.istime,g.timestart,g.timeend,g.checked,g.status,g.deleted ".
			"FROM `ims_bj_qmxk_goods` g {$force_index}".
			"LEFT JOIN ims_members_profile mp ON g.sellerid=mp.uid ".
			"WHERE {$where} {$orderby} {$limit}");

		if(!empty($items) && is_array($items)) {
			foreach ($items AS $key=>$item) {
				unset($items[$key]['checked']);
				unset($items[$key]['status']);
				unset($items[$key]['deleted']);

				$items[$key]['thumb'] = $item['thumb'] ?  $_W['attachurl'].$item['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
				// $items[$key]['productprice'] = '-';
			}
		} else {
			$items = array();
		}

		$results = array();
		$pageInfo = array();

		if($_GET['usePage'] == 'yes') {
			$pageInfo['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_goods` g WHERE {$where}");
			$pageInfo['itemCount'] = intval($pageInfo['itemCount']);
			$pageInfo['allPage'] = ceil($pageInfo['itemCount']/$count);
			$pageInfo['page'] = $page;
			$pageInfo['count'] = $count;
		}

		$results['pageInfo'] = $pageInfo;
		$results['items'] = $items;

		return $results;
	}
}
