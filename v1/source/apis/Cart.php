<?php

class Cart extends Api {

	//登陆验证
	function __construct() {
		global $_W;

		require IA_ROOT.'/source/apis/User.php';
		if(!User::checklogin()) {
			return self::responseError(1000, '尚未登陆。');
		}

		if(empty($_W['member_id'])) {
			return self::responseError(1001, '尚未登陆。');
		}
	}

	//把商品加入购物车
	public function add() {
		global $_W;

		$goodsid = intval($_POST['goodsId']);
		$total = intval($_POST['total']);
		$total = empty($total) ? 1 : $total;
		$optionid = intval($_POST['optionid']);

		if(empty($goodsid)) return self::responseError(600, 'Parameter is missing.');


		$goods = pdo_fetch("SELECT id,type,total,marketprice,maxbuy,status,deleted,checked,hasoption FROM `ims_bj_qmxk_goods` WHERE id ='{$goodsid}'");

		if(empty($goods) || empty($goods['checked']) || $goods['deleted'] || empty($goods['status'])) {
			return self::responseError(601, '抱歉，该商品不存在或已下架！');
		}
		if($goods['hasoption'] > 0 && empty($optionid)) {
			return self::responseError(603, '抱歉，请选择规格。');
		}

		$marketprice = $goods['marketprice'];
		$goodsOptionStock = 0;
		$goodsOptionStock = $goods['total'];

		if(!empty($optionid)) {
			$option = pdo_fetch("select marketprice,stock from `ims_bj_qmxk_goods_option` where id='{$optionid}'");
			if(!empty($option)) {
				$marketprice = $option['marketprice'];
				$goodsOptionStock = $option['stock'];
			}
		}

		if($goodsOptionStock <= $total && $goodsOptionStock != - 1) {
			$result = array(
				'result' => 0,
				'maxbuy' => $goodsOptionStock
			);
			return self::responseError(602, '抱歉，该商品库存为:'.$goodsOptionStock.',请修改数量。');
		}

		$cart_all = pdo_fetchall("SELECT id,total,goodsid,optionid FROM `ims_bj_qmxk_cart` WHERE member_id='{$_W['member_id']}'");

		$have_this_goods = 0;
		$item_id = 0;
		$item_total = 0;
		foreach($cart_all AS $item) {
			if($item['goodsid'] == $goodsid && $item['optionid'] == $optionid) {
				$have_this_goods = 1;
				$item_id = $item['id'];
				$item_total = $item['total'];
				break;
			}
		}
		$member = pdo_fetch("SELECT from_user FROM `ims_bj_qmxk_member` WHERE member_id='{$_W['member_id']}'");
		
		$from_user = empty($member['from_user']) ? '' : $member['from_user'];

		if(!$have_this_goods) {
			$data = array(
				'weid' => 2,
				'goodsid' => $goodsid,
				'goodstype' => $goods['type'],
				'marketprice' => $marketprice,
				'member_id' => $_W['member_id'],
				'total' => $total,
				'from_user' => $from_user,//解决APP端加入购物车商品，微信端没有的问题。
				'optionid' => $optionid
			);
			pdo_insert('bj_qmxk_cart', $data);
		} else {
			$t = $total + $item_total;
			if(! empty($goods['maxbuy'])) {
				if($t > $goods['maxbuy']) {
					$t = $goods['maxbuy'];
				}
			}
			$data = array(
				'marketprice' => $marketprice,
				'total' => $t,
				'optionid' => $optionid
			);
			pdo_update('bj_qmxk_cart', $data, array('id' => $item_id));
		}

		return self::responseOk('成功加入购物车。');
	}


	//把商品移除购物车
	public function remove() {
		global $_W;

		$ids = trim($_GET['cartId']);
		if(empty($ids)) return self::responseError(620, 'Parameter [cartId] is missing.');
		
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

		if(empty($ids_del)) return self::responseError(621, 'Parameter [cartId] is invalid.');
		if($count > 100) return self::responseError(622, 'Parameter [cartId] is too much.');

		$carts = pdo_fetchall("SELECT id,member_id FROM `ims_bj_qmxk_cart` WHERE id IN ({$ids_del})");
		if($carts[0]['id']) {
			$id_sql = '';
			$comm = '';
			foreach($carts AS $cart) {
				if($cart['member_id'] == $_W['member_id']) {
					$id_sql .= $comm.$cart['id'];
					$comm = ',';
				}
			}

			if($id_sql) {
				pdo_query("DELETE FROM ims_bj_qmxk_cart WHERE id IN ({$id_sql})");
			}
		}

		return self::responseOk('所选商品已移出购物车。');
	}

	//修改购物车中的商品数量
	//必须参数cartId=购物车项目ID
	//必须参数type=set|add|minus
	//type=set时必须参数total=购买数量
	public function modify() {
		global $_W;

		$cartid = intval($_POST['cartId']);
		$total = intval($_POST['total']);
		$type = trim($_POST['type']);

		$set_sql = '';
		if($type == 'set') {
			$set_sql = "SET total='{$total}'";
		} elseif($type == 'add') {
			$set_sql = "SET total=total+1";
		} elseif($type == 'minus') {
			$set_sql = "SET total=total-1";
		}

		//判断是否自己的购物车
		//查询库存
		//查询限购
		//查询商品状态
		$cart_item = pdo_fetch("SELECT c.total,c.member_id,g.total AS g_total,g.status,g.deleted,g.checked,g.maxbuy,go.stock AS go_stock ".
			"FROM ims_bj_qmxk_cart c ".
			"LEFT JOIN ims_bj_qmxk_goods g ON g.id=c.goodsid ".
			"LEFT JOIN ims_bj_qmxk_goods_option go ON go.id=c.optionid ".
			"WHERE c.id='{$cartid}'");

		if(empty($cart_item) || $cart_item['member_id'] != $_W['member_id']) {
			return self::responseError(630, '您购物车中不存在该商品!');
		}

		if(empty($cart_item['status']) || $cart_item['deleted'] || empty($cart_item['checked'])) {
			return self::responseError(631, '该商品已下架!');
		}

		$cart_item['stock'] = $cart_item['go_stock'] ? $cart_item['go_stock'] : $cart_item['g_total'];

		if($type == 'set' && $total > $cart_item['stock']) {
			return self::responseError(632, '该商品库存为:'.$cart_item['stock']);
		} elseif($type == 'set' && $total <=0) {
			return self::responseError(634, '商品数量不能为0');
		} elseif($type == 'add' && $cart_item['total']+1 > $cart_item['stock']) {
			return self::responseError(633, '该商品库存为:'.$cart_item['stock']);
		} elseif($type == 'minus' && $cart_item['total']-1 <=0) {
			return self::responseError(634, '商品数量不能为0');
		}

		if($cart_item['maxbuy'] > 0) {
			if($type == 'set' && $total > $cart_item['maxbuy']) {
				return self::responseError(635, '该商品限购'.$cart_item['stock'].'件');
			} elseif($type == 'add' && $cart_item['total']+1 > $cart_item['maxbuy']) {
				return self::responseError(636, '该商品限购'.$cart_item['stock'].'件');
			}
		}

		pdo_query("UPDATE `ims_bj_qmxk_cart` {$set_sql} WHERE id='{$cartid}'");

		return self::responseOk('数量修改成功。');
	}

	//清空购物车
	public function clear() {
		global $_W;

		pdo_delete('bj_qmxk_cart', array('member_id' => $_W['member_id']));

		return self::responseOk('购物车已清空。');
	}

	//返回购物车商品列表
	public function goodsList() {
		global $_W;

	//	$goodsList = pdo_fetchall("SELECT c.goodsid,c.optionid FROM ims_bj_qmxk_cart WHERE member_id='{$_W['member_id']}'");

		$list = pdo_fetchall("SELECT c.id AS cartId, c.goodsid,c.optionid,c.total,g.sellerid,mp.seller_name,g.title, g.thumb, g.marketprice, g.unit, g.total AS g_total,g.maxbuy, ".
					"go.title AS go_title,go.marketprice AS go_marketprice,go.stock AS go_stock ".
					"FROM ims_bj_qmxk_cart c ".
					"LEFT JOIN ims_bj_qmxk_goods g ON g.id=c.goodsid ".
					"LEFT JOIN ims_bj_qmxk_goods_option go ON go.id=c.optionid ".
					"LEFT JOIN ims_members_profile mp ON g.sellerid=mp.uid ".
					"WHERE c.member_id='{$_W['member_id']}' ORDER BY c.id DESC");
				//	"WHERE c.from_user='ok0PkslAFVirfmB4IyAbM08iRolw'");

		if(empty($list)) {
			return self::responseError(610, '购物车中没有商品！');
		}

		$totalPrice = 0;
		$totalCount = 0;

		$lists = $lists_end = array();

		foreach($list AS $item) {
			$totalCount += 1;
			$item['optionname'] = $item['go_title'] ? $item['go_title'] : '';
			$item['marketprice'] = $item['go_marketprice'] ? $item['go_marketprice'] : $item['marketprice'];
		//	$item['total'] = $item['go_stock'] ? $item['go_stock'] : $item['total'];


			$item['totalprice'] = floatval($item['marketprice']) * intval($item['total']);
			$totalPrice += $item['totalprice'];

			$item['thumb'] = $item['thumb'] ? $_W['attachurl'].$item['thumb'].'_300x300.jpg' : 'http://statics.shunliandongli.com/empty.gif';
                        //活动相关
                        $time = time(); 
                        $act_entry = pdo_fetchall("SELECT * FROM ".tablename('bj_qmxk_activity_entry')." WHERE goods_id = {$item['goodsid']} AND status=2 AND end_time>{$time}");
                        if(empty($act_entry)){
                            $act_entry = pdo_fetchall("SELECT * FROM ".tablename('bj_qmxk_activity_entry')." WHERE goods_id = {$item['goodsid']} AND status=2 AND end_time=0");
                        }
                        if((empty($act_entry['start_time']) && empty($act_entry['end_time'])) || ($act_entry['start_time'] <$time && $act_entry['end_time'] >$time)){
                            foreach($act_entry as $entry_v){

                                $activity_id = pdo_fetch("SELECT * FROM ".tablename('bj_qmxk_activity')." WHERE id = {$entry_v['act_id']} AND status > -1");
                                if((empty($activity_id['act_start_time']) && empty($activity_id['act_end_time'])) || ($activity_id['act_start_time'] < $time && $activity_id['act_end_time'] >$time)){
                                    $item['act_id'] = $activity_id['id'];
                                    break;
                                }else{
                                    $item['act_id'] = '0';
                                }
                            }
                            $activity_entry = pdo_fetch("SELECT id,act_id,goods_id,seller_id,actprice,costprice,status FROM " . tablename('bj_qmxk_activity_entry') . " WHERE act_id='{$item['act_id']}' AND goods_id='{$item['goodsid']}' AND status = 2");
                            if($activity_entry){

                                if (!empty($item['optionid'])) {
                                    $option3 = pdo_fetch("SELECT id,goods_id,actprice,costprice FROM " . tablename('bj_qmxk_activity_goods') . " WHERE entry_id='{$activity_entry['id']}' AND goods_id='{$item['goodsid']}' AND spec_id='{$item['optionid']}'");
                                    $item['marketprice'] = $option3['actprice'];
                                }else{
                                    $item['marketprice'] = $activity_entry['actprice'];
                                }
                            }
                        }
                            

                            


			$lists[$item['sellerid']]['sellerid'] = $item['sellerid'];
			$lists[$item['sellerid']]['seller_name'] = $item['seller_name'];
			$lists[$item['sellerid']]['goods'][] = $item;
		}

		if($lists) {
			foreach($lists AS $item) {
				$lists_end[] = $item;
			}
		}

		$result = array(
			'totalCount' => $totalCount,
			'totalPrice' => number_format($totalPrice , 2, '.', ''),
			'goodsList' => $lists_end
		);

		return self::responseOk($result);
	}

	//判断商品是否存在
    public function hasItem($id) {
		global $_W;

        return array_key_exists($id,$this->items);
    }

	//返回购物车商品数量
	public function totalCount() {
		global $_W;

		$cartotal = pdo_fetchcolumn("SELECT SUM(total) FROM ims_bj_qmxk_cart WHERE member_id='{$_W['member_id']}'");

		$cartotal = empty($cartotal) ? 0 : $cartotal;
		return self::responseOk($cartotal);
	}

	//返回购物车商品价格
	public function totalPrices() {
		global $_W;

		$totalPrices = pdo_fetchcolumn("SELECT SUM(marketprice) FROM ims_bj_qmxk_cart WHERE member_id='{$_W['member_id']}'");

		$totalPrices = empty($totalPrices) ? '0.00' : number_format($totalPrices , 2, '.', '');
		return self::responseOk($totalPrices);
	}
}
