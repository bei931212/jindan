<?php

class Activity extends Api {

    public function item() {
		global $_W;

		$activityId = intval($_GET['activityId']);

		if(empty($activityId)) {
			return self::responseError(400, 'Parameter [activityId] is missing.');
		}

		$item = pdo_fetch("SELECT * FROM `ims_app_activity` WHERE id='{$activityId}'");

		if(empty($item)) {
			return self::responseError(400, '不存在该条目或已下线.');
		}

		if(TIMESTAMP < $item['time_start']) {
			$item['rest_time'] = -1;
		} else {
			$item['rest_time'] = $item['time_end'] - TIMESTAMP;
			$item['rest_time'] = $item['rest_time'] > 0 ? $item['rest_time'] : 0;
		}

		$searchKwd = '618';
		$type = $item['type']; //限时促销
                $time = TIMESTAMP;
		$item['banner'] = $item['banner'] ? $_W['attachurl'].$item['banner'] : 'http://statics.sldl.fcmsite.com/resource/image/category-thumb.png';

		$goodsList = array();
		if($item['goodsids']) {
			$goodsList_arr = pdo_fetchall("SELECT g.id AS goodsId, g.title, g.thumb, g.sellerid, mp.seller_name, g.pcate, g.ccate, g.type, g.goodssn, ".
				"g.marketprice, g.productprice, g.isnew, g.ishot, g.issendfree, ".
				"g.isrecommand,g.istime,g.timestart,g.timeend,g.checked,g.status,g.deleted ".
				"FROM `ims_bj_qmxk_goods` g ".
				"LEFT JOIN ims_members_profile mp ON g.sellerid=mp.uid ".
				"WHERE g.id IN ({$item['goodsids']}) ORDER BY field(g.id, {$item['goodsids']})");

			if(!empty($goodsList_arr) && is_array($goodsList_arr)) {
				foreach ($goodsList_arr AS $key=>$value) {
					if(!$value['checked']) unset($value);
					if(!$value['status']) unset($value);
					if($value['deleted']) unset($value);
					unset($value['checked']);
					unset($value['status']);
					unset($value['deleted']);

					if($value) {
//                                            echo "SELECT ae.actprice AS marketprice,ae.goods_pic AS thumb FROM `ims_bj_qmxk_activity_entry` ae".
//                                            " LEFT JOIN `ims_bj_qmxk_goods` g ON g.id = ae.goods_id ".
//                                            "WHERE ae.goods_id = {$value['goodsId']} AND ae.status = 2 AND ae.end_time >{$time} AND ae.start_time <= {$time}";
                                            $item_activity = pdo_fetch("SELECT ae.actprice AS marketprice,ae.goods_pic AS thumb FROM `ims_bj_qmxk_activity_entry` ae".
                                            " LEFT JOIN `ims_bj_qmxk_goods` g ON g.id = ae.goods_id ".
                                            "WHERE ae.goods_id = {$value['goodsId']} AND ae.status = 2 AND ae.end_time >{$time} AND ae.start_time <= {$time}");
//						$item_activity = $this->activitys($item['goodsId'],$_W['attachurl']);
						$value['marketprice'] = empty($item_activity['marketprice']) ? $value['marketprice'] : $item_activity['marketprice'];
						$value['thumb'] = $value['thumb'] ? $_W['attachurl'].$value['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
						$value['productprice'] = '-';
						$goodsList[] = $value;
					}
				}
			}
		}

		unset($item['id'],$item['displayorder'],$item['status'],$item['type'],$item['goodsids']);

		$shareInfo = array(
			'title' => $item['title'],
			'desc' => '分享描述',
			'link' => $_W['share_domain'].'activity/'.$item['id'].'?u='.$member_id_public,
			'img' => $item['banner']
		);

		$results = array(
			'searchKwd'	=> $searchKwd,
			'type'		=> $type,//活动类型
			'item'		=> $item,
			'goodsList'	=> $goodsList,
			'shareInfo'	=> $shareInfo
		);

		return self::responseOk($results);
	}


}