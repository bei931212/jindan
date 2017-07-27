<?php

class Search extends Api {



	//搜索商品
	//根据分类ID获取商品列表
	//必须参数: categoryId=直接分类ID
	//必须参数: PcategoryId=顶级分类ID
	//可选参数: count=获取数量，最大40，默认10
	//可选参数: sort=排序规则,默认|销量|时间|人气|价格
	//可选参数: sortby=ASC|DESC
	//可选参数: usePage=yes|no，是否需要分页，不传输默认否
	//可选参数: page=分页
	public function goods() {
		global $_W;

		$keyword = trim($_GET['keyword']);
		$ccate = intval($_GET['categoryId']);
		$pcate = intval($_GET['PcategoryId']);
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
			// $orderby = 'ORDER BY g.updatetime DESC, g.createtime DESC';
			$orderby = 'ORDER BY g.sales DESC, g.updatetime DESC';
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

		if(empty($keyword)) {
			return self::responseError(400, 'Parameter [keyword] is missing.');
		}

		if($ccate) {
			$where .= "g.ccate='{$ccate}'";
			if($sort == 'sales') {
				$force_index = 'FORCE INDEX (`c_d_s_s_u`) ';
			}
		} else {
			if($pcate) {
				$where .= "g.pcate='{$pcate}'";
				if($sort == 'sales') {
					$force_index = 'FORCE INDEX (`p_d_s_s_u`) ';
				}
			}
		}

		$where .= $where ? " AND title LIKE '%{$keyword}%'" : "title LIKE '%{$keyword}%'";

		$where .= " AND g.deleted='0' AND g.status='1'";
		$count = ($count && $count <= 40) ? $count : 20;

		if($_GET['usePage'] == 'yes') {
			$limit .= ($page-1)*$count.','.$count;
		} else {
			$limit .= ($count && $count <= 40) ? $count : 20;
		}

		$list_key = md5('list-app-'.$where.'-'.$orderby.'-'.$limit);
		$list = $_W['mc']->get($list_key);

		if($list) {
			$items = unserialize($list);
		} else {
			$items = pdo_fetchall("SELECT g.id AS goodsId, g.title, g.thumb, g.sellerid, mp.seller_name, g.pcate, g.ccate, g.type, g.goodssn, ".
				"g.marketprice, g.productprice, g.isnew, g.ishot, g.issendfree, ".
				"g.isrecommand,g.istime,g.timestart,g.timeend,g.checked,g.status,g.deleted ".
				"FROM `ims_bj_qmxk_goods` g {$force_index}".
				"LEFT JOIN ims_members_profile mp ON g.sellerid=mp.uid ".
				"WHERE {$where} {$orderby} {$limit}");
			$_W['mc']->set($list_key, serialize($items), MEMCACHE_COMPRESSED, 1800);
		}

		if(!empty($items) && is_array($items)) {
			foreach ($items AS $key=>$item) {
				unset($items[$key]['checked']);
				unset($items[$key]['status']);
				unset($items[$key]['deleted']);

                                //$items[$key]['thumb'] = $item['thumb'] ?  $_W['attachurl'].$item['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';                                              //活动是否参与
                                $time = time();
                                $activity = pdo_fetch("SELECT a.little_word AS little_word,a.id,a.act_start_time,a.act_end_time,e.act_id,e.id AS eid,e.actprice AS actprice,".
                                        "e.goods_pic AS goods_pic,a.if_act_price AS if_act_price,e.status,e.start_time,e.end_time ".
                                        "FROM `ims_bj_qmxk_activity` a,`ims_bj_qmxk_activity_entry` e WHERE e.goods_id = {$item['goodsId']} AND a.id=e.act_id AND ".
                                        "e.status = 2 AND e.end_time>{$time}");
                                if(empty($activity)){
                                    $activity = pdo_fetch("SELECT a.little_word AS little_word,a.id,a.act_start_time,a.act_end_time,e.act_id,e.id AS eid,".
                                        "e.actprice AS actprice,e.goods_pic AS goods_pic,a.if_act_price AS if_act_price,e.status,e.start_time,e.end_time ".
                                        "FROM `ims_bj_qmxk_activity` a,`ims_bj_qmxk_activity_entry` e WHERE e.goods_id = {$item['goodsId']} AND a.id=e.act_id AND ".
                                        "e.status = 2 AND a.act_end_time>{$time}");
                                }
                                if(!empty($activity) && ($activity['act_start_time']<$time && $activity['act_end_time']>$time) || ($activity['start_time']<$time && $activity['end_time']>$time)){
                                    $items[$key]['activity_status'] = '1';
                                    
                                    if($activity['actprice'] !=0){
                                        $items[$key]['marketprice'] = $activity['actprice'];
                                    }
                                    
                                    $items[$key]['little_word'] = empty($activity['little_word'])?'':$activity['little_word'];
                                    $items[$key]['thumb'] = $activity['goods_pic'] ?  $_W['attachurl'].$activity['goods_pic'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
                                }else{
                                    $items[$key]['activity_status'] = '0';
                                    $items[$key]['little_word'] = '';
                                    $items[$key]['thumb'] = $item['thumb'] ?  $_W['attachurl'].$item['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
                                }
								// $items[$key]['productprice'] = '-';
                                
			}
		} else {
			$items = array();
		}

		$results = array();

		if($_GET['usePage'] == 'yes') {
			$results['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_goods` g WHERE {$where}");
			$results['itemCount'] = intval($results['itemCount']);
			$results['allPage'] = ceil($results['itemCount']/$count);
			$results['page'] = $page;
			$results['count'] = $count;
		}

		$results['items'] = $items;

		$shareInfo = array(
			'title' => $keyword,
			'desc' => '分享描述',
			'link' => 'https://m.shunliandongli.com/',
			'img' => $items[0]['thumb']
		);

		$results['shareInfo'] = $shareInfo;


		//返回随机积分相关cookie
		require_once IA_ROOT.'/source/apis/User.php';
		if(User::checklogin()) {
			CreditRandomModel::getInstance()->addSearchNum($keyword);

			if($_POST['testMode']=='YES'){
				$results['debug_credit'] = CreditRandomModel::getInstance()->getData();
			}
		}

		return self::responseOk($results);
	}

	//随机热销商品
	public function goodshot() {
		global $_W;
		$now=TIMESTAMP;
		$limit=intval($_GET['limit']);
		if($limit==0){
			$limit=6;
		}
		$start=0;
		$sellerid=intval($_GET['sellerid']);
		$total_num=pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_goods` g WHERE sellerid='{$sellerid}' AND status='1'");
		if($total_num <= $limit){
			$start=0;
		}else{
			$start=rand(0,($total_num-$limit));
		}
		$items = pdo_fetchall("SELECT g.id AS goodsId, g.title, g.thumb, g.sellerid,g.pcate, g.ccate, g.type, g.goodssn, ".
			"g.marketprice, g.productprice, g.isnew, g.ishot, g.issendfree, ".
			"g.isrecommand,g.istime,g.timestart,g.timeend FROM `ims_bj_qmxk_goods`  as g WHERE g.sellerid='{$sellerid}' AND g.status='1' limit $start, $limit");
		if(!empty($items) && is_array($items)) {
			foreach ($items AS $key=>$item) {
				$items[$key]['thumb'] = $item['thumb'] ?  $_W['attachurl'].$item['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
				// $items[$key]['productprice'] = '-';
			}
		} else {
			$items = array();
		}		
		$results['items'] = $items;
		return self::responseOk($results);

	}
	

	//搜索店铺
	public function shop() {

	}
	
	//拼单商品列表
	public function groupon(){
		global $_W;
		$now=TIMESTAMP;
		$count=intval($_GET['count']);
		$page=intval($_GET['page'])-1;
		if($count==0){
			$count=10;
		}
		if($page<0){
			$page=0;
		}
		$start=$count*$page;
		$list = pdo_fetchall("SELECT gg.*,g.title,g.thumb,g.ptthumb FROM ims_bj_qmxk_goods_groupon as gg left join ims_bj_qmxk_goods  as g on g.id=gg.goodsid WHERE gg.starttime < $now and gg.endtime > $now AND gg.status = '1' and g.deleted=0 and g.status='1' AND g.activity_type=1  ORDER BY gg.updatetime desc limit $start , $count");
		foreach($list as $key=>$value){
			$thumb = $value['thumb'] ?  $_W['attachurl'].$value['thumb'].'_640x360.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
			$list[$key]['thumb'] = !empty($value['ptthumb']) ? $_W['attachurl'].$value['ptthumb'].'_640x360.jpg' : $thumb;
			unset($list[$key]['ptthumb']);
			unset($list[$key]['updatetime']);
			unset($list[$key]['createtime']);
			//$list[$key]['marketprice'] = '-';
		}
		$results = array();
		$results['group_list']=$list?$list:array();
		$pageInfo=array();
		$pageInfo['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM ims_bj_qmxk_goods_groupon as gg left join ims_bj_qmxk_goods  as g on g.id=gg.goodsid WHERE gg.starttime < $now and gg.endtime > $now AND gg.status = '1' and g.deleted=0 and g.status='1' AND g.activity_type=1");
		$pageInfo['itemCount'] = intval($pageInfo['itemCount']);
		$pageInfo['allPage'] = ceil($pageInfo['itemCount']/$count);
		$pageInfo['page'] = $page+1;
		$pageInfo['count'] = $count;
		$results['pageInfo']=$pageInfo;
		return self::responseOk($results);
	}

	//热搜
	public function hot() {
		global $_W;

		$items_arr = pdo_fetchall("SELECT word FROM `ims_app_hotsearch` ORDER BY displayorder ASC, id DESC");

		$items = array();
		foreach($items_arr AS $item) {
			$items[] = $item['word'];
		}

	//	$hot = '618|风扇|粽子';
	//	$items = explode('|', $hot);

		return self::responseOk($items);
	}

}