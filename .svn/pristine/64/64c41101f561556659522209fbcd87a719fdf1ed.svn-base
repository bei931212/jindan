<?php

class Favorite extends Api {

	//登陆验证
	function __construct() {
		global $_W;

		if(empty($_W['member_id'])) {
			require IA_ROOT.'/source/apis/User.php';
			if(!User::checklogin()) {
				return self::responseError(1000, '尚未登陆。');
			}

			if(empty($_W['member_id'])) {
				return self::responseError(1001, '尚未登陆。');
			}
		}

		//$_W['member_id'] = 18;
	}

	//获取收藏夹中的所有商品
	public function allGoods() {
		global $_W;

		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$limit = 'LIMIT ';

		$count = ($count && $count <= 40) ? $count : 20;

		if($_GET['usePage'] == 'yes') {
			$limit .= ($page-1)*$count.','.$count;
		} else {
			$limit .= ($count && $count <= 40) ? $count : 20;
		}

		$items = pdo_fetchall("SELECT f.id AS favoriteId,f.goodsid AS goodsId,f.optionid,f.marketprice,f.dateline, ".
			"g.title,g.marketprice AS g_marketprice,g.thumb,g.sellerid,mp.seller_name,go.marketprice AS go_marketprice,go.title AS go_title,go.stock,g.total,g.checked,g.status,g.deleted ".
			"FROM `ims_bj_qmxk_favorites` f ".
			"LEFT JOIN ims_bj_qmxk_goods g ON g.id=f.goodsid ".
			"LEFT JOIN ims_bj_qmxk_goods_option go ON go.id=f.optionid ".
			"LEFT JOIN ims_members_profile mp ON g.sellerid=mp.uid ".
			"WHERE f.member_id='{$_W['member_id']}' AND f.type='goods' {$limit}");

		$items_end = array();
		if(!empty($items) && is_array($items)) {
			foreach ($items AS $key=>$item) {
				$item['goods_marketprice'] = $item['go_marketprice'] ? $item['go_marketprice'] : $item['g_marketprice'];
				if($item['marketprice'] > $item['goods_marketprice']) {
					$item['cheap'] = number_format($item['marketprice'] - $item['goods_marketprice'], 2, '.', '');//比加入购物车时优惠多少
				} else {
					$item['cheap'] = "0.00";
				}
				$item['title'] = $item['go_title'] ? $item['title'].'('.$item['go_title'].')' : $item['title'];
				$item['thumb'] = $item['thumb'] ?  $_W['attachurl'].$item['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
				$item['total'] = $item['stock'] ? $item['stock'] : $item['total'];//库存
				$item['status'] = ($item['checked'] == 1 && $item['status'] == 1 && $item['deleted'] == 0) ? 1 : 0;//上架状态，1上架 0下架

				$item['datetime'] = date('Y-m-d', $item['dateline']);

				unset($item['checked']);
				//unset($item['status']);
				unset($item['deleted']);
				unset($item['g_marketprice']);
				unset($item['go_marketprice']);
				unset($item['go_title']);
				unset($item['stock']);
				$items_end[] = $item;
			}
		}

		$results = array();

		if($_GET['usePage'] == 'yes') {
			$results['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_favorites` f WHERE f.member_id='{$_W['member_id']}' AND f.type='goods'");
			$results['itemCount'] = intval($results['itemCount']);
			$results['allPage'] = ceil($results['itemCount']/$count);
			$results['page'] = $page;
			$results['count'] = $count;
		}

		$results['items'] = $items_end;

		if(!empty($results['items'])) {
			return self::responseOk($results);
		}

		return self::responseError(810, '您的收藏夹为空');
	}

	//获取收藏夹中的所有店铺
	public function allShop() {
		global $_W;

		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$limit = 'LIMIT ';

		$count = ($count && $count <= 40) ? $count : 20;

		if($_GET['usePage'] == 'yes') {
			$limit .= ($page-1)*$count.','.$count;
		} else {
			$limit .= ($count && $count <= 40) ? $count : 20;
		}

		$list = pdo_fetchall("SELECT f.id AS favoriteId,f.shopid,f.dateline,mp.seller_name ".
			"FROM `ims_bj_qmxk_favorites` f ".
			"LEFT JOIN ims_members_profile mp ON f.shopid=mp.uid ".
			"WHERE f.member_id='{$_W['member_id']}' AND f.type='shop' {$limit}");

		$items_end = array();
		if(!empty($list)) {
			foreach($list AS $item) {
				$item['datetime'] = date('Y-m-d', $item['dateline']);
				$items_end[] = $item;
			}
		}

		$results = array();

		if($_GET['usePage'] == 'yes') {
			$results['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_favorites` f WHERE f.member_id='{$_W['member_id']}' AND f.type='shop'");
			$results['itemCount'] = intval($results['itemCount']);
			$results['allPage'] = ceil($results['itemCount']/$count);
			$results['page'] = $page;
			$results['count'] = $count;
		}

		$results['items'] = $items_end;

		if(!empty($results['items'])) {
			return self::responseOk($results);
		}

		return self::responseError(810, '您的收藏夹为空');
	}

	//数量统计
	//必选参数: type
	public function count($internalCall = false) {
		global $_W;

		if($internalCall) {
			$goods = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND type='goods'");
			$shop = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND type='shop'");

			$result = array(
				'goods' => intval($goods),
				'shop' => intval($shop)
			);

			return $result;
		}

		$type = trim($_GET['type']);
		if(empty($type)) return self::responseError(850, 'Parameter [type] is missing.');
		if($type != 'goods' && $type != 'shop') return self::responseError(851, 'Parameter [type] is invalid.');

		$itemCount = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND type='{$type}'");

		return self::responseOk(intval($itemCount));
	}

	//把商品加入收藏夹
	//必须参数goodsId
	//可选参数optionid
	//可选参数
	public function addGoods() {
		global $_W;

		$post_goods	= $_POST['goods'];
		$multi	= $_POST['multi'];

		if($multi == 'yes') {
			if(empty($post_goods)) return self::responseError(824, 'Parameter [goods] is missing.');
			if(!is_array($post_goods) || empty($post_goods[0]['goodsId'])) return self::responseError(825, 'Parameter [goods] is invalid.');

			//检查收藏的商品数量
			$goodsTotal = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND type='goods'");
			if($goodsTotal >= 100) {
				return self::responseError(820, '最多可以收藏100个商品');
			}

			$favoriteId_arr = array();
			foreach($post_goods AS $item) {
				$item['goodsId'] = intval($item['goodsId']);
				$item['optionid'] = intval($item['optionid']);

				if(empty($item['goodsId'])) continue;

				$goods = pdo_fetch("SELECT g.id AS goodsId,g.status,g.deleted,g.checked,g.marketprice,g.hasoption ".
					"FROM `ims_bj_qmxk_goods` g WHERE g.id='{$item['goodsId']}'");

				if(empty($goods['goodsId'])) continue;

				if($item['optionid'] > 0) {
					$goods_option = pdo_fetch("SELECT goodsid,marketprice FROM `ims_bj_qmxk_goods_option` WHERE id='{$item['optionid']}'");

					$goods['marketprice'] = $goods_option['marketprice'] ? $goods_option['marketprice']: $goods['marketprice'];

					if($goods_option['goodsid'] != $goods['goodsId']) {
						continue;
					}
				} else {
					if($goods['hasoption'] > 0) {
						continue;
					}
				}

				//检查是否已收藏
				if($item['optionid'] > 0) {
					$favorite = pdo_fetch("SELECT id AS favoriteId,optionid FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND goodsid='{$item['goodsId']}' AND optionid='{$item['optionid']}'");
				} else {
					$favorite = pdo_fetch("SELECT id AS favoriteId,optionid FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND goodsid='{$item['goodsId']}'");
				}

				if($favorite['favoriteId']) {
					$favoriteId = $favorite['favoriteId'];
				} else {
					$data = array(
						'member_id' => $_W['member_id'],
						'type' => 'goods',
						'goodsid' => intval($goods['goodsId']),
						'optionid' => intval($item['optionid']),
						'shopid' => 0,
						'marketprice' => $goods['marketprice'],
						'dateline' => TIMESTAMP
					);

					pdo_insert('bj_qmxk_favorites', $data);
					$favoriteId = pdo_insertid();
				}
				$favoriteId_arr[] = $favoriteId;
			}

			return self::responseOk(array('favoriteId'=>$favoriteId_arr));
		} else {
			$data = array(
				'member_id' => $_W['member_id'],
				'type' => 'goods',
				'goodsid' => intval($_POST['goodsId']),
				'optionid' => intval($_POST['optionid']),
				'shopid' => 0,
				'marketprice' => 0.00,
				'dateline' => TIMESTAMP
			);
			if(empty($data['goodsid'])) return self::responseError(821, 'Parameter [goodsId] is missing.');

			//检查收藏的商品数量
			$goodsTotal = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND type='goods'");
			if($goodsTotal >= 100) {
				return self::responseError(820, '最多可以收藏100个商品');
			}

			//检查是否已收藏
			if($data['optionid'] > 0) {
				$favorite = pdo_fetch("SELECT id AS favoriteId,optionid FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND goodsid='{$data['goodsid']}' AND optionid='{$data['optionid']}'");
			} else {
				$favorite = pdo_fetch("SELECT id AS favoriteId,optionid FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND goodsid='{$data['goodsid']}'");
			}

			if($favorite['favoriteId']) {
				return self::responseOk(array('favoriteId'=>$favorite['favoriteId']));
			}

			//获取当前商品价格
			if($data['optionid']) {
				$goods = pdo_fetch("SELECT g.id,go.id AS optionid,g.hasoption,go.marketprice AS go_marketprice,g.marketprice ".
					"FROM `ims_bj_qmxk_goods_option` go ".
					"LEFT JOIN `ims_bj_qmxk_goods` g ON g.id=go.goodsid ".
					"WHERE go.id='{$data['optionid']}'");
				if($goods['optionid'] != $data['optionid']) {
					return self::responseError(826, '规格与商品不匹配');
				}
			} else {
				$goods = pdo_fetch("SELECT id,marketprice,hasoption FROM `ims_bj_qmxk_goods` WHERE id='{$data['goodsid']}'");
				if($goods['hasoption']) {
					return self::responseError(827, '请选择规格');
				}
			}
			if(empty($goods['id'])) return self::responseError(822, '收藏失败，无此商品。');

			$data['marketprice'] = $goods['go_marketprice'] ? $goods['go_marketprice'] : $goods['marketprice'];

			pdo_insert('bj_qmxk_favorites', $data);
			$favoriteId = pdo_insertid();

			if(empty($favoriteId)) {
				pdo_insert('bj_qmxk_favorites', $data);
				$favoriteId = pdo_insertid();
				if(empty($favoriteId)) {
					return self::responseError(823, '收藏失败，请重试。');
				}
			}
		}

		return self::responseOk(array('favoriteId'=>$favoriteId));
	}

	//把店铺加入收藏夹
	//必须参数shopId
	public function addShop() {
		global $_W;

		$data = array(
			'member_id' => $_W['member_id'],
			'type' => 'shop',
			'goodsid' => 0,
			'optionid' => 0,
			'shopid' => intval($_POST['shopId']),
			'marketprice' => 0.00,
			'dateline' => TIMESTAMP
		);

		//检查收藏的店铺数量
		$goodsTotal = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND type='shop'");

		if($goodsTotal >= 50) {
			return self::responseError(830, '最多可以收藏50个店铺');
		}

		if(empty($data['shopid'])) return self::responseError(831, 'Parameter [shopId] is missing.');

		//检查是否已收藏
		$favorite = pdo_fetch("SELECT id AS favoriteId FROM `ims_bj_qmxk_favorites` WHERE member_id='{$_W['member_id']}' AND shopid='{$data['shopid']}'");
		if($favorite['favoriteId']) {
			return self::responseOk(array('favoriteId'=>$favorite['favoriteId']));
		}
		$shop = pdo_fetch("SELECT uid FROM ims_members_profile WHERE uid='{$data['shopid']}'");
		if(empty($shop['uid'])) return self::responseError(832, '收藏失败，无此店铺。');

		pdo_insert('bj_qmxk_favorites', $data);
		$favoriteId = pdo_insertid();

		if(empty($favoriteId)) {
			pdo_insert('bj_qmxk_favorites', $data);
			$favoriteId = pdo_insertid();
			if(empty($favoriteId)) {
				return self::responseError(833, '收藏失败，请重试。');
			}
		}

		return self::responseOk(array('favoriteId'=>$favoriteId));
	}

	//把商品移出收藏夹
	//必须参数favoriteId
	public function removeGoods() {
		global $_W;

		$ids = trim($_GET['favoriteId']);
		if(empty($ids)) return self::responseError(840, 'Parameter [favoriteId] is missing.');
		
		$ids_arr = explode(',', $ids);
		$comm = '';
		$count = 0;

		foreach($ids_arr AS $id) {
			$id = intval($id);
			if($id) {
				$ids_del .= $comm.$id;
				$comm = ',';
				$count += 1;
			}
		}

		if(empty($ids_del)) return self::responseError(842, 'Parameter [favoriteId] is invalid.');
		if($count > 100) return self::responseError(843, 'Parameter [favoriteId] is too much.');

		$favorites = pdo_fetchall("SELECT id,member_id FROM `ims_bj_qmxk_favorites` WHERE id IN ({$ids_del})");
		if($favorites[0]['id']) {
			$id_sql = '';
			$comm = '';
			foreach($favorites AS $favorite) {
				if($favorite['member_id'] == $_W['member_id']) {
					$id_sql .= $comm.$favorite['id'];
					$comm = ',';
				}
			}

			if($id_sql) {
				pdo_query("DELETE FROM `ims_bj_qmxk_favorites` WHERE id IN ({$id_sql})");
			}
		}

		return self::responseOk('移除成功');
	}

	//把店铺移出收藏夹
	//必须参数favoriteId
	public function removeShop() {
		global $_W;

		self::removeGoods();
	}
}