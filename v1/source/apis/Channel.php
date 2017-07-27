<?php
class Channel extends Api {
    
        public static $channel_key = 'channel_list';
        public static $content_key = 'channel_content';

        //清除缓存
        public function clear(){
            global $_W;
            if(empty($_GET['channel_id'])){
                return self::responseError(400, '缺少频道ID');
            }
            $_W['mc']->delete(self::$content_key.'_'.$_GET['channel_id']);
            return self::responseOk('清除成功');
        }
        //滑块频道列表
        public function lists(){
            global $_W;
            $data = $_W['mc']->get(self::$channel_key);
            if($data) {
                $data = unserialize($data);
            }else{
                $data = ChannelFrontModel::getInstance()->getChannelList(false);
                $_W['mc']->set(self::$channel_key,serialize($data),0,300);
            }

            if($data){
                return self::responseOk($data);
            }else{
                return self::responseError(400, '没有频道');
            }
        }
        //频道对应的内容
        public function content(){
            global $_W;
            if(empty($_GET['channel_id'])){
                return self::responseError(400, '缺少频道ID');
            }
            $channel_id = intval($_GET['channel_id']);
            
            $data = $_W['mc']->get(self::$content_key.'_'.$channel_id);
            if($data) {
                $data = unserialize($data);
            }else{
                $channel = ChannelFrontModel::getInstance()->getChannelOne($channel_id); 
                $modules = ChannelFrontModel::getInstance()->getModuleList($channel_id,false); 
                foreach($modules as $k=>$v){
                    $data[$k]['title'] = $v['module_title'];
                    $data[$k]['module'] = $v['op'];
                    $dataz = ChannelFrontModel::getInstance()->getContent($v['id'],false);
                    if(empty($dataz) && $v['op'] != 'roll_tuan'){
                        $data[$k]['items'] = array();
                    }
                    switch ($v['op']){ 
                        case 'roll_pic':
                            foreach($dataz as $zk=>$zv){
                                $data[$k]['items'][$zk]['img'] = $_W['attachurl'].$zv['pics'];
                                if($zv['link_type'] == 'link'){
                                    $data[$k]['items'][$zk]['type'] = 'url';
                                    $data[$k]['items'][$zk]['title'] = $zv['title'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }else{
                                    $data[$k]['items'][$zk]['type'] = $zv['link_type'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }     
                            }
                        break; 
                        case 'act_ad':           
                            foreach($dataz as $zk=>$zv){
                                $data[$k]['items'][$zk]['img'] = $_W['attachurl'].$zv['pics'];
                                
                                if($zv['link_type'] == 'link'){
                                    $data[$k]['items'][$zk]['type'] = 'url';
                                    $data[$k]['items'][$zk]['title'] = $zv['title'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }else{
                                    $data[$k]['items'][$zk]['type'] = $zv['link_type'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }      
                            } 
                        break; 
                        case 'cha_cate':
                            $count = count($dataz);
                            if($count > 4 && $count <8){
                                for($i=0;$i<4;$i++){
                                    $data[$k]['items'][$i]['title'] = $dataz[$i]['title'];  
                                    $data[$k]['items'][$i]['img'] = $_W['attachurl'].$dataz[$i]['pics'];   
                                    $data[$k]['items'][$i]['type'] = $dataz[$i]['link_type'];
                                    if($dataz[$i]['link_type'] == 'link'){
                                        $data[$k]['items'][$i]['type'] = 'url';
                                        $data[$k]['items'][$i]['title'] = $dataz[$i]['title'];
                                        $data[$k]['items'][$i]['itemId'] = trim($dataz[$i]['link_url']);
                                    }else{
                                        $data[$k]['items'][$i]['type'] = $dataz[$i]['link_type'];
                                        $data[$k]['items'][$i]['itemId'] = trim($dataz[$i]['link_url']);
                                    }  
                                }
                            }else{
                                foreach($dataz as $zk=>$zv){
                                    $data[$k]['items'][$zk]['title'] = $zv['title'];  
                                    $data[$k]['items'][$zk]['img'] = $_W['attachurl'].$zv['pics'];   
                                    
                                    if($zv['link_type'] == 'link'){
                                        $data[$k]['items'][$zk]['type'] = 'url';
                                        $data[$k]['items'][$zk]['title'] = $zv['title'];
                                        $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                    }else{
                                        $data[$k]['items'][$zk]['type'] = $zv['link_type'];
                                        $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                    }  
                                }
                            }
                                
                        break; 
                        case 'roll_tuan':
                            $data[$k]['items'] = $this->tuan();
                        break; 
                        case 'roll_goods':
                            foreach($dataz as $ogv){
                                $goods_ids = explode(',',$ogv['goods_id']);
                                foreach($goods_ids as $gk=>$gv){
                                    if(!empty($gv)){
                                        $goods_data = $this->goods($gv);
                                        if($goods_data){
                                            $data[$k]['items'][] = $goods_data;
                                        }
                                    }    
                                } 
                            }
                        break; 
                        case 'display_list':
                            foreach($dataz as $zk=>$zv){
                                $data[$k]['items'][$zk]['img'] = $_W['attachurl'].$zv['pics'];
                                
                                if($zv['link_type'] == 'link'){
                                    $data[$k]['items'][$zk]['type'] = 'url';
                                    $data[$k]['items'][$zk]['title'] = $zv['title'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }else{
                                    $data[$k]['items'][$zk]['type'] = $zv['link_type'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }     
                            }
                        break;
                        case 'roll_brand':
                            foreach($dataz as $zk=>$zv){
                                $data[$k]['items'][$zk]['img'] = $_W['attachurl'].$zv['pics'];
                                $data[$k]['items'][$zk]['type'] = $zv['link_type'];
                                if($zv['link_type'] == 'link'){
                                    $data[$k]['items'][$zk]['type'] = 'url';
                                    $data[$k]['items'][$zk]['title'] = $zv['title'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }else{
                                    $data[$k]['items'][$zk]['type'] = $zv['link_type'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }     
                            }
                        break;
                        case 'order_list':
                            foreach($dataz as $ogv){
                                $goods_ids = explode(',',$ogv['goods_id']);
                                foreach($goods_ids as $gk=>$gv){
                                    if(!empty($gv)){
                                        $goods_data = $this->goods($gv);
                                        if($goods_data){
                                            $data[$k]['items'][] = $goods_data;
                                        }
                                    }    
                                } 
                            }
                        break;
                        case 'specialty':
                            foreach($dataz as $zk=>$zv){
                                $data[$k]['items'][$zk]['img'] = $_W['attachurl'].$zv['pics'];
                                $data[$k]['items'][$zk]['type'] = $zv['link_type'];
                                if($zv['link_type'] == 'link'){
                                    $data[$k]['items'][$zk]['type'] = 'url';
                                    $data[$k]['items'][$zk]['title'] = $zv['title'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }else{
                                    $data[$k]['items'][$zk]['type'] = $zv['link_type'];
                                    $data[$k]['items'][$zk]['itemId'] = trim($zv['link_url']);
                                }     
                            }
                        break;
                    }
                }
                $data[] = array(
                    'title'=>'精选推荐',
                    'module'=>'categoryList',
                    'items'=>array(array('cate_id'=>$channel['cid'])),
                );
                $_W['mc']->set(self::$content_key.'_'.$channel_id,serialize($data),0,300);
            }
 
            if($data){
                return self::responseOk($data);
            }else{
                return self::responseError(400, '没有内容');
            }
        }
	//根据商品ID获取商品详情
	protected function goods($goods_id) {
		global $_W;
                if(empty($goods_id)){
                    return array();
                }
                //查商品
		$item = pdo_fetch("/*liangxiang*/SELECT id,title,thumb,marketprice,productprice,issendfree,activity_type,checked,status,deleted FROM ims_bj_qmxk_goods WHERE id='{$goods_id}'");
                if(empty($item['checked']) || empty($item['status']) || $item['deleted']) {
			return false;
		}
                //查活动
                
                $act_list = pdo_fetch("/*liangxiang*/SELECT a.little_word AS little_word,e.actprice AS actprice,e.goods_pic AS goods_pic ".
                    "FROM `ims_bj_qmxk_activity_entry` e ".
                    "LEFT JOIN `ims_bj_qmxk_activity` a ON a.id=e.act_id ".
                    "WHERE e.goods_id = {$goods_id} AND e.start_time<UNIX_TIMESTAMP(NOW()) AND e.status = 2 AND e.end_time>UNIX_TIMESTAMP(NOW())");
                     
//                    $productprice = $item['marketprice'];
//                    $marketprice = $item['productprice'];
//                    unset($item['productprice']);
//                    unset($item['marketprice']);
//                    $item['marketprice'] = $marketprice;
//                    $item['productprice'] = $productprice;
                    $item['type'] = 'goods';
                    $item['itemId'] = $item['id'];
                if($act_list){
                    $item['little_word'] = $act_list['little_word'];
                    $item['thumb'] = isset($act_list['goods_pic'])?$act_list['goods_pic']:$item['thumb'];
                    $item['marketprice'] = isset($act_list['actprice'])?$act_list['actprice']:$item['marketprice'];
                }else{
                    $item['little_word'] = '';
                }
                 
                $item['thumb'] = $item['thumb'] ? $_W['attachurl'].$item['thumb'].'_500x500.jpg' : '//statics.sldl.fcmsite.com/empty.gif';
                
                return $item;

	}
        
        public function keyword(){
            $searchKwd_arr = pdo_fetchall("SELECT word FROM `ims_app_hotsearch` ORDER BY displayorder ASC, id DESC");
            shuffle($searchKwd_arr);
            $data['searchKwd'] = $searchKwd_arr[0]['word'] ? $searchKwd_arr[0]['word'] : '';
            return self::responseOk($data);
        }
        
        //弹出消息
        public function message(){
            global $_W;
            $tuan = pdo_fetchall("/*liangxiang*/SELECT g.realname,g.starttime,g.goodsid,gs.thumb,a.province,a.mobile,g.groupid ".
                    "FROM `ims_bj_qmxk_groupon` g ".
                    "LEFT JOIN `ims_bj_qmxk_goods` gs ON gs.id=g.goodsid ".
                    "LEFT JOIN `ims_bj_qmxk_address` a ON a.member_id=g.member_id ".
                    "WHERE g.status = 0 AND a.isdefault = 1 ORDER BY g.id DESC limit 10");
            if($tuan){
                foreach($tuan as $k=>$v){
                    $data[$k]['tid'] = $v['groupid'];
                    $data[$k]['realname'] = $this->maskName($v['realname']);
                    $data[$k]['mobile'] = $this->hidtel($v['mobile']);
                    $data[$k]['goods_id'] = $v['goodsid'];
                    $data[$k]['thumb'] = $_W['attachurl'].$v['thumb'];
                    $data[$k]['from_province'] = $v['province'];
                    $data[$k]['starttime'] = $v['starttime'];
                }
            }   
            if(!empty($data)){
                return self::responseOk($data);
            }else{
                return self::responseError(400, '没有内容');
            }
            
        }
        
        //电话隐藏中间
        protected function hidtel($phone){
            $IsWhat = preg_match('/(0[0-9]{2,3}[\-]?[2-9][0-9]{6,7}[\-]?[0-9]?)/i',$phone); //固定电话
            if($IsWhat == 1){
                return preg_replace('/(0[0-9]{2,3}[\-]?[2-9])[0-9]{3,4}([0-9]{3}[\-]?[0-9]?)/i','$1****$2',$phone);
            }else{
                return  preg_replace('/(1[3578]{1}[0-9])[0-9]{4}([0-9]{4})/i','$1****$2',$phone);
            }
        }
        //昵称隐藏中间
        protected function maskName($str, $msask_len=2, $encode='utf-8'){
            $l = mb_strlen($str, $encode);
            if($l==0){
                return $str;
            }else if($l<=2){
                return mb_substr($str, 0, 1, $encode) . str_repeat('*', $msask_len);
            }else if($l==3){
                return mb_substr($str, 0, 1, $encode) . str_repeat('*', $msask_len) . mb_substr($str, -1, 1, $encode);
            }else{
                return mb_substr($str, 0, 2, $encode) . str_repeat('*', $msask_len) . mb_substr($str, -1, 1, $encode);
            }
        }
        
        //根据商品ID获取商品详情
	protected function tuan() {
            global $_W;
		$now=TIMESTAMP;

		$list = pdo_fetchall("/*liangxiang*/SELECT gg.*,g.title,g.thumb,g.ptthumb,g.productprice FROM ims_bj_qmxk_goods_groupon as gg "
                        . "left join ims_bj_qmxk_goods  as g on g.id=gg.goodsid "
                        . "WHERE gg.starttime < $now and gg.endtime > $now AND gg.status = '1' and g.deleted=0 and g.status='1' AND g.activity_type=1  ORDER BY gg.updatetime desc limit 10");
		foreach($list as $key=>$value){
			unset($list[$key]['ptthumb']);
			unset($list[$key]['updatetime']);
			unset($list[$key]['createtime']);
                        $lists[$key]['itemId'] = $value['goodsid'];
                        $lists[$key]['tid'] = $value['id'];
//                        $lists[$key]['goods_id'] = $value['goodsid'];
                        $lists[$key]['marketprice'] = (string)(float)$value['productprice'];
                        $lists[$key]['costprice'] = (string)(float)$value['marketprice'];
                        $lists[$key]['grouponprice'] = (string)(float)$value['grouponprice'];
			$lists[$key]['title'] = $value['title'];
                        $lists[$key]['type'] = 'goods';
                        $thumb = $value['thumb'] ?  $_W['attachurl'].$value['thumb'].'_640x360.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
			$lists[$key]['thumb'] = !empty($value['ptthumb']) ? $_W['attachurl'].$value['ptthumb'].'_640x360.jpg' : $thumb;
		}
		
                return $lists;
        }
        
        
        public function goodsList(){
            global $_W;

            $pcate = intval($_GET['cate_id']);
            $count = intval($_GET['count']);
            $page = max(1, intval($_GET['page']));

            $sort = 'sales'; //设置排序规则
            $sortby =  'ASC'; //设置正序倒叙

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

            $limit = 'LIMIT ';
            if($pcate != 0) {
                $where = " g.pcate='{$pcate}'";
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
            }else{
                $where = ' 1 ';
            }

            $where .= " AND g.deleted='0' AND g.status='1' AND g.checked='1'";
            $count = ($count && $count <= 40) ? $count : 20;

            $limit .= ($page-1)*$count.','.$count;

            $items = pdo_fetchall("SELECT g.id AS id, g.title, g.thumb, g.marketprice, g.productprice, g.issendfree, g.checked,g.status,g.deleted ".
			"FROM `ims_bj_qmxk_goods` g {$force_index}".
			"LEFT JOIN ims_members_profile mp ON g.sellerid=mp.uid ".
			"WHERE {$where} {$orderby} {$limit}");

		if(!empty($items) && is_array($items)) {
			foreach ($items AS $key=>$item) {
				unset($items[$key]['checked']);
				unset($items[$key]['status']);
				unset($items[$key]['deleted']);

                                //活动
                                $time = time();
                                $activity = pdo_fetch("/*liangxiang*/SELECT a.little_word AS little_word,a.id,a.act_start_time,a.act_end_time,e.act_id,e.id AS eid,e.actprice AS actprice,".
                                        "e.goods_pic AS goods_pic,a.if_act_price AS if_act_price,e.status,e.start_time,e.end_time ".
                                        "FROM `ims_bj_qmxk_activity` a,`ims_bj_qmxk_activity_entry` e WHERE e.goods_id = {$item['id']} AND a.id=e.act_id AND ".
                                        "e.status = 2 AND e.end_time>{$time}");
                                if(empty($activity)){
                                    $activity = pdo_fetch("/*liangxiang*/SELECT a.little_word AS little_word,a.id,a.act_start_time,a.act_end_time,e.act_id,e.id AS eid,".
                                        "e.actprice AS actprice,e.goods_pic AS goods_pic,a.if_act_price AS if_act_price,e.status,e.start_time,e.end_time ".
                                        "FROM `ims_bj_qmxk_activity` a,`ims_bj_qmxk_activity_entry` e WHERE e.goods_id = {$item['id']} AND a.id=e.act_id AND ".
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
                                
			}
		} else {
			$items = array();
		}

		$results = array();

                $results['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_goods` g WHERE {$where}");
                $results['itemCount'] = intval($results['itemCount']);
                $results['allPage'] = ceil($results['itemCount']/$count);
                $results['page'] = $page;
                $results['count'] = $count;
		$results['items'] = $items;

		return self::responseOk($results);
        }
   
}
