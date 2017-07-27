<?php

class Category extends Api {

	//获取所有分类信息
	//可选参数:containTop=1,子分类包含顶级分类
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

		$shareInfo = array(
			'title' => '顺联动力',
			'desc' => '分享描述',
			'link' => $_W['share_domain'].'list?u='.$member_id_public,
			'img' => $category_end[0]['thumb']
		);

		$results = array();
		$results['categoryList'] = $category_end;
		$results['shareInfo'] = $shareInfo;

		return self::responseOk($results);
	}

	//获取所有顶级分类
    public function top() {
		global $_W;

		$category = array();
		$category_arr = pdo_fetchall("SELECT id AS categoryId,name,thumb,isrecommand FROM ims_bj_qmxk_category WHERE enabled='1' AND parentid='0' ORDER BY displayorder DESC", array(), '', true);
		foreach($category_arr as $row) {
			$row['thumb'] = $row['thumb'] ? $_W['attachurl'].$row['thumb'] : 'http://statics.sldl.fcmsite.com/resource/image/category-thumb.png';

			$category[] = $row;
		}

		return self::responseOk($category);
	}

	//根据分类ID获取子分类
	//必须参数: categoryId=分类ID
	public function childrenList() {
		global $_W;

		$id = intval($_GET['categoryId']);
		if(empty($id)) {
			return self::responseOk(array());
		}

		$category = array();
		$category_arr = pdo_fetchall("SELECT id AS categoryId,name,thumb,isrecommand FROM ims_bj_qmxk_category WHERE parentid='{$id}' ORDER BY displayorder DESC", array(), '', true);
		foreach($category_arr as $row) {
			$row['thumb'] = $row['thumb'] ? $_W['attachurl'].$row['thumb'] : 'http://statics.sldl.fcmsite.com/resource/image/category-thumb.png';

			$category[] = $row;
		}

		return self::responseOk($category);
	}

	//根据分类ID获取分类详情
	//必须参数: categoryId=分类ID
	public function item() {
		global $_W;

		$id = intval($_GET['categoryId']);
		if(empty($id)) {
			return self::responseOk(array());
		}

		$category = array();
		$category = pdo_fetch("SELECT id AS categoryId,name,thumb,isrecommand FROM ims_bj_qmxk_category WHERE id='{$id}' ORDER BY displayorder DESC", array(), '', true);
		$category['thumb'] = $category['thumb'] ? $_W['attachurl'].$category['thumb'] : 'http://statics.sldl.fcmsite.com/resource/image/category-thumb.png';

		return self::responseOk($category);
	}

	//根据分类ID获取商品列表
	//必须参数: categoryId=直接分类ID
	//必须参数: PcategoryId=顶级分类ID
	//可选参数: count=获取数量，最大40，默认10
	//可选参数: sort=排序规则,默认|销量|时间|人气|价格
	//可选参数: sortby=ASC|DESC
	//可选参数: usePage=yes|no，是否需要分页，不传输默认否
	//可选参数: page=分页
	public function goodsList() {
		global $_W;
        require_once IA_ROOT.'/source/apis/User.php';
		if(User::checklogin()){}
		$ccate = intval($_GET['categoryId']);
		$pcate = intval($_GET['PcategoryId']);
		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$sort = trim($_GET['sort']);
		$sort = $sort ? $sort : 'default';
		
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

		$where = ' 1 ';
		$limit = 'LIMIT ';
/*
		if(empty($ccate) && empty($pcate)) {
			return self::responseError(400, 'Parameter is missing.');
		}
*/
		if($ccate) {
			$where .= " AND g.ccate='{$ccate}'";
			if($sort == 'sales') {
				$force_index = 'FORCE INDEX (`c_d_s_s_u`) ';
			} elseif ($sort == 'default') {
                $force_index = 'FORCE INDEX (`c_d_s_u_c`) ';
            } elseif ($sort == 'dateline') {
                $force_index = 'FORCE INDEX (`c_d_s_c_u`) ';
            } elseif ($sort == 'hot') {
                $force_index = 'FORCE INDEX (`c_d_s_v_u`) ';
            } elseif ($sort == 'price') {
                if ($sortby == 'DESC') {
                    $force_index = 'FORCE INDEX (`c_d_s_m_u`) ';
                } else {
                    $force_index = 'FORCE INDEX (`c_d_s_m_d`) ';
                }
            }
		} elseif($pcate) {
			$where .= " AND g.pcate='{$pcate}'";
			if($sort == 'sales') {
				$force_index = 'FORCE INDEX (`p_d_s_s_u`) ';
			} elseif ($sort == 'default') {
                $force_index = 'FORCE INDEX (`p_d_s_u_c`) ';
            } elseif ($sort == 'dateline') {
                $force_index = 'FORCE INDEX (`p_d_s_c_u`) ';
            } elseif ($sort == 'hot') {
                $force_index = 'FORCE INDEX (`p_d_s_v_u`) ';
            } elseif ($sort == 'price') {
                if ($sortby == 'DESC') {
                    $force_index = 'FORCE INDEX (`p_d_s_m_u`) ';
                } else {
                    $force_index = 'FORCE INDEX (`p_d_s_m_d`) ';
                }
            }
		}

		$where .= " AND g.deleted='0' AND g.status='1'";
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

//				$items[$key]['thumb'] = $item['thumb'] ?  $_W['attachurl'].$item['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
                                
                                //活动
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


		$searchKwd_arr = pdo_fetchall("SELECT word FROM `ims_app_hotsearch` ORDER BY displayorder ASC, id DESC");
		shuffle($searchKwd_arr);
		$results['searchKwd'] = $searchKwd_arr[0]['word'] ? $searchKwd_arr[0]['word'] : '';

		$results['items'] = $items;

        //分享链接
        if($ccate==0) {
            $weixinLink = $_W['shunlian_domain'].'list/p'.$pcate;
        }elseif($ccate==0 && $pcate==0){
            $weixinLink = $_W['shunlian_domain'].'list/p0?sort=0&sortb0=desc';
        }else{
            $weixinLink = $_W['shunlian_domain'].'list/c'.$ccate;
        }

        
		if(isset($_W['mid'])){
            if($ccate==0){
                $weixinLink = $_W['shunlian_domain'].'list/p'.$pcate.'?mid='.$_W['mid'];
            }elseif($ccate==0 && $pcate==0){
                 $weixinLink = $_W['shunlian_domain'].'list/p0?sort=0&sortb0=desc&mid='.$_W['mid'];
            }else{
                $weixinLink = $_W['shunlian_domain'].'list/c'.$ccate.'?mid='.$_W['mid'];
            }
		}

		$results['shareLink'] = $_W['share_domain'].'list?cid='.$ccate.'&pid='.$pcate.'&u='.$member_id_public;
        $results['weixinLink'] = $weixinLink;
		$shareInfo = array(
			'title' => $item['title'],
			'desc' => '分享描述',
			'link' => $_W['share_domain'].'list?cid='.$ccate.'&pid='.$pcate.'&u='.$member_id_public,
            'weixinLink' =>$weixinLink,
			'img' => $items[0]['thumb']
		);

		$results['shareInfo'] = $shareInfo;

		return self::responseOk($results);
	}
}
