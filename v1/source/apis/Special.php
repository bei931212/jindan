<?php
class Special extends Api {
	function __construct() {}
	
	public function index(){
	    
	    $id = intval($_GET['id']);
	    if(empty($id)) {
	        return self::responseError('非法请求。');
	    }
	    $special = pdo_fetch('select * from ' . tablename('special') . " where id= '{$id}'");
	    if(empty($special)){
	        return self::responseError('不存在该专题。');
	    }
	    unset($special['weid']);
	    return self::responseOk($special);
	}
        
        public function hot(){
	    global $_W;
	    $yesterday_begin = strtotime('yesterday');
	    $yesterday_end = $yesterday_begin + 86400000;
	    
	    $list_key = md5('list-hot');
	    $list = $_W['mc']->get($list_key);
	    
	    if($list) {
	        $list = unserialize($list);
	    } else {
	        $list = pdo_fetchall("SELECT g.id,g.thumb,g.istime,g.title,g.marketprice,g.productprice ".
	            "FROM `ims_bj_qmxk_order` o ".
	            "LEFT JOIN `ims_bj_qmxk_order_goods` og ON og.orderid=o.id ".
	            "LEFT JOIN `ims_bj_qmxk_goods` g ON g.id=og.goodsid ".
	            "WHERE g.deleted=0 AND g.status=1 AND o.createtime>='{$yesterday_begin}' AND o.createtime<'{$yesterday_end}' AND o.status>'0' GROUP BY g.id ORDER BY COUNT(o.id) DESC LIMIT 100");
	        //$list = pdo_fetchall('SELECT id,thumb,istime,title,marketprice,productprice FROM ims_bj_qmxk_goods WHERE deleted=0 AND `status`=1 ORDER BY createtime DESC LIMIT 3000,100');
	        
	        $_W['mc']->set($list_key, serialize($list), MEMCACHE_COMPRESSED, 1800);
	    }
	    //return self::responseOk($list);
	    header('Content-type: text/html; charset=utf-8');
	    include IA_ROOT . '/source/template/hot.php';
	}
        
        //活动列表
        public function actList(){
	    global $_W;

	    $time = TIMESTAMP;

            $list = pdo_fetchall("SELECT a.id AS act_id,a.act_title AS act_title FROM `ims_bj_qmxk_activity` a".
                    " RIGHT JOIN `ims_bj_qmxk_activity_entry` ae ON ae.act_id = a.id ".
                    " WHERE ae.status = 2 AND ae.end_time > {$time} AND ae.start_time <= {$time}");
//	        $list = pdo_fetchall("SELECT a.id AS act_id,a.act_title AS act_title,ae.status,ae.end_time,ae.start_time FROM `ims_bj_qmxk_activity` a".
//                        " RIGHT JOIN `ims_bj_qmxk_activity_entry` ae ON ae.act_id = a.id ".
//                        " WHERE ae.status = 2 AND ae.end_time > {$time} AND ae.start_time <= {$time}");

            if($list) {
                return self::responseOk($list);
            }else{
                return self::responseError('不存在进行中的活动。');
            }
	    
//	    header('Content-type: text/html; charset=utf-8');
//	    include IA_ROOT . '/source/template/hot.php';
	}
	public function activity(){
            $id = intval($_GET['id']);
	    global $_W;
	    $time = TIMESTAMP;
	    $list_key = md5('act_goods_list'.$id);
	    $list = $_W['mc']->get($list_key);
            $title = pdo_fetch("SELECT act_title,detail_pic FROM `ims_bj_qmxk_activity` WHERE id = {$id}");
            $activity_adv = pdo_fetch('select * from ' . tablename('bj_qmxk_activity_adv') . " where cid= '{$id}' ORDER BY id DESC");
	    if($list) {
	        $list = unserialize($list);
	    } else {

                $list = pdo_fetchall("SELECT ae.goods_id AS id,ae.actprice AS marketprice,ae.goods_pic AS thumb,g.title AS title FROM `ims_bj_qmxk_activity_entry` ae".
                        " LEFT JOIN `ims_bj_qmxk_goods` g ON g.id = ae.goods_id ".
                        "WHERE act_id = {$id} AND ae.status = 2 AND ae.end_time >{$time} AND ae.start_time <= {$time}");
              $time2 = pdo_fetchcolumn("SELECT MIN(end_time) FROM `ims_bj_qmxk_activity_entry` WHERE act_id = {$id} AND status = 2 AND end_time >{$time} AND start_time <= {$time}");
              $timez = $time2-$time;
	        $_W['mc']->set($list_key, serialize($list), MEMCACHE_COMPRESSED, $timez);
	    }
	    //return self::responseOk($list);
	    header('Content-type: text/html; charset=utf-8');
	    include IA_ROOT . '/source/template/activity.php';
	}

	public function show(){
		$id = intval($_GET['id']);
	    if(empty($id)) {
	        return self::responseError('非法请求。');
	    }
	    $special = pdo_fetch('select * from ' . tablename('special') . " where id= '{$id}'");
	    if(empty($special)){
	        return self::responseError('不存在该专题。');
	    }
	    unset($special['weid']);

	    $preg='/<a .*?href=["|\'](.*?)["|\'](.*?)>/is';

		preg_match_all($preg,$special['content'],$match);//在$str中搜索匹配所有符合$preg加入$match中

		foreach ($match[1] as $value) {
		  //匹配special
		  if(mb_strpos($value, '/special/') !== false){
		    preg_match('/\/\d+/',$value,$arr);  
		    $url = 'https://h5.api.shunliandongli.com/v1/special/show/'.str_replace('/', '', $arr[0]).'.html';
		    $special['content'] = str_replace($value, $url, $special['content']);
		    continue;
		  }
		  if(mb_strpos($value, 'do=special') !== false){
		    preg_match('/id=\d+/',$value,$arr);
		    $url = 'https://h5.api.shunliandongli.com/v1/special/show/'.str_replace('id=', '', $arr[0]).'.html';
		    $special['content'] = str_replace($value, $url, $special['content']);
		    continue;
		  }

		  if (true || !strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {

		  		//搜索
			    if(mb_strpos($value, 'keyword=') !== false){
			      preg_match('/keyword=(.*?)&/',$value,$arr);
			      $url = 'slmall://search/goods.json?keyword='.$arr[1];
			      $special['content'] = str_replace($value, $url, $special['content']);
			      continue;
			    }
		  		//小类
		  	    if(mb_strpos($value, '/list/c') !== false){
			      preg_match('/\/c\d+/',$value,$arr);
			      $url = 'slmall://clist/item.json?id='.str_replace('/c', '', $arr[0]);
			      $special['content'] = str_replace($value, $url, $special['content']);
			      continue;
			    }
			    //大类
			    if(mb_strpos($value, '/list/p') !== false){
			      preg_match('/\/p\d+/',$value,$arr);
			      $url = 'slmall://plist/item.json?id='.str_replace('/p', '', $arr[0]);
			      $special['content'] = str_replace($value, $url, $special['content']);
			      continue;
			    }
			    

			  //匹配商品
			  if(mb_strpos($value, '/goods/') !== false){
			    preg_match('/\/\d+/',$value,$arr);  
			    $url = 'slmall://goods/item.json?goodsId='.str_replace('/', '', $arr[0]);
			    $special['content'] = str_replace($value, $url, $special['content']);
			    continue;
			  }
			  if(mb_strpos($value, 'do=detail') !== false){
			    preg_match('/id=\d+/',$value,$arr);
			    $url = 'slmall://goods/item.json?goodsId='.str_replace('id=', '', $arr[0]);
			    $special['content'] = str_replace($value, $url, $special['content']);
			    continue;
			  }

			  //匹配店铺
			  if(mb_strpos($value, '/shop/') !== false){
			    preg_match('/\/\d+/',$value,$arr);  
			    $url = 'slmall://shop/home.json?shopId='.str_replace('/', '', $arr[0]);
			    $special['content'] = str_replace($value, $url, $special['content']);
			    continue;
			  }
			  ////wx.shunliandongli.com/mobile.php?act=module&sellerid=94&dzdid=0&name=bj_qmxk&do=shop&weid=2">
			  if(mb_strpos($value, 'do=shop') !== false){
			    preg_match('/sellerid=\d+/',$value,$arr);
	      		$url = 'slmall://shop/home.json?shopId='.str_replace('sellerid=', '', $arr[0]);
			    $special['content'] = str_replace($value, $url, $special['content']);
			    continue;
			  }

			  if(mb_strpos($value, 'mobile.php?act=entry') !== false){
			  	$url = 'slmall://home/all';
			  	$special['content'] = str_replace($value, $url, $special['content']);
			  	continue;
			  }
			  if(mb_strpos($value, 'myshop') !== false){
			  	$url = 'slmall://home/all';
			  	$special['content'] = str_replace($value, $url, $special['content']);
			  	continue;
			  }
			}else{
				//匹配商品
			  if(mb_strpos($value, '/goods/') !== false){
			    preg_match('/\/\d+/',$value,$arr);  
			    $url = 'https://wx.shunliandongli.com/goods/'.str_replace('/', '', $arr[0]);
			    $special['content'] = str_replace($value, $url, $special['content']);
			    continue;
			  }
			  if(mb_strpos($value, 'do=detail') !== false){
			    preg_match('/id=\d+/',$value,$arr);
			    $url = 'https://wx.shunliandongli.com/goods/'.str_replace('id=', '', $arr[0]);
			    $special['content'] = str_replace($value, $url, $special['content']);
			    continue;
			  }

			  //匹配店铺
			  if(mb_strpos($value, '/shop/') !== false){
			    preg_match('/\/\d+/',$value,$arr);  
			    $url = 'https://wx.shunliandongli.com/shop/'.str_replace('/', '', $arr[0]);
			    $special['content'] = str_replace($value, $url, $special['content']);
			    continue;
			  }
			  ////wx.shunliandongli.com/mobile.php?act=module&sellerid=94&dzdid=0&name=bj_qmxk&do=shop&weid=2">
			  if(mb_strpos($value, 'do=shop') !== false){
			    preg_match('/sellerid=\d+/',$value,$arr);
	      		$url = 'https://wx.shunliandongli.com/shop/'.str_replace('sellerid=', '', $arr[0]);
			    $special['content'] = str_replace($value, $url, $special['content']);
			    continue;
			  }

			  if(mb_strpos($value, 'mobile.php?act=entry') !== false){
			  	$url = 'https://wx.shunliandongli.com/myshop';
			  	$special['content'] = str_replace($value, $url, $special['content']);
			  	continue;
			  }
			}
		}

        // 替换http到https
        if (!empty($special['content'])) {
            $special['content'] = str_replace('http://', 'https://', $special['content']);
        }

		header('Content-type: text/html; charset=utf-8');
	    include IA_ROOT . '/source/template/special.php';
	}
}