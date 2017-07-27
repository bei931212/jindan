<?php
/*
namespace SldlRestApi;
*/
//use RestService\Client;
//print_r('aaa');
//		print_r(get_included_files());

class Home extends Api {
	
	//获取静态URL
	public function getUrl(){
		$type = trim($_GET['type']);
		$data['url'] = 'https://h5.api.shunliandongli.com/v1/html/'.$type.'.html';
		return self::responseOk($data);
	}

	/*
	一次获取首页所有数据
	*/
    public function all() {
        $is_show_new = 0;//是否启用双12首页
        
        $version = $this->version('1.2.2', '1.3.1');//ios1.2.2，Android1.3.1后新增大类type=pcategory
        
		global $_W;
		$result = array();
		$result_key = 'home-app-list'.self::$client_version;
		$ret = $_W['mc']->get($result_key);
		if(!$ret){
    		//获取首页分类
    		$category_arr = pdo_fetchall("SELECT itemId AS id,img AS icon, title AS name FROM `ims_app_home` WHERE area='cate' AND status='1' ORDER BY displayorder ASC, id DESC LIMIT 9");
    
    		foreach($category_arr AS $row) {
    			$row['icon'] = $_W['attachurl'].$row['icon'];
    			$category[] = $row;
    		}
    
    		//获取首页分类，新，去掉最后的固定图标
    		$category_arr_2 = pdo_fetchall("SELECT itemId AS id,img AS icon, title AS name FROM `ims_app_home` WHERE area='cate' AND status='1' ORDER BY displayorder ASC, id DESC LIMIT 10");
    
    		foreach($category_arr_2 AS $row) {
    			$row['icon'] = $_W['attachurl'].$row['icon'];
    			$category_new[] = $row;
    		}
    		
    		//新分类，有-1优惠券
    		$category_arr_3 = pdo_fetchall("SELECT itemId AS id,img AS icon, title AS name FROM `ims_app_home` WHERE area='newcate' AND status='1' ORDER BY displayorder ASC, id DESC LIMIT 10");
    		
    		$cat_version = $this->version('1.2.9', '1.4.3');
    		foreach($category_arr_3 AS $row) {
    		    $row['icon'] = $_W['attachurl'].$row['icon'];
    		    if($cat_version){
    		        if($row['id']<0){
    		            $row['icon'] = $_W['attachurl'].'images/2017/03/YgcKcLZ9E3p38e2kL52g918U5Ocg5L.png';
    		            $row['id'] = '-1';
    		            $row['name'] = '领券';
    		        }
    		    }
    		    $newcate[] = $row;
    		}
    		
    
    		//获取顶部轮询banner
    		$banner_arr = pdo_fetchall("SELECT img,type,itemId FROM `ims_app_home` WHERE area='banner' AND status='1' ORDER BY displayorder ASC,id DESC LIMIT 5");
    		foreach($banner_arr AS $v) {
    		    if($version){
    		        if($v['type']=='pcategory'){
    		            continue;
    		        }
    		    }
    			$v['img'] = $_W['attachurl'].$v['img'].'_750x10000.jpg?75';
    			$banner[] = $v;
    		}
    		//新品前线
    		$picList_arr = pdo_fetchall("SELECT img,type,itemId,title,sub_title FROM `ims_app_home` WHERE area='piclist' AND status='1' ORDER BY displayorder ASC,id DESC LIMIT 100");
    		foreach($picList_arr AS $v) {
    		    if($version){
    		        if($v['type']=='pcategory'){
    		            continue;
    		        }
    		    }
    		    $v['img'] = $_W['attachurl'].$v['img'].'_750x10000.jpg?75';
    		    $picList[] = $v;
    		}
    		//榜单,天天特惠，今日上新，清仓区等
    		$bangdanList = array();
    		$bangdan_arr = pdo_fetchall("SELECT img,type,itemId,title,sub_title FROM `ims_app_home` WHERE area='list12' AND status='1' AND column_num='榜单' ORDER BY displayorder ASC,id DESC LIMIT 4");
    		foreach($bangdan_arr AS $v) {
    		    $v['img'] = $_W['attachurl'].$v['img'].'_750x10000.jpg?75';
    		    $bangdanList[] = $v;
    		}
    		//秒杀榜
    		$miaoshaList = array();
    		$miaosha_arr = pdo_fetchall("SELECT img,type,itemId,title,sub_title FROM `ims_app_home` WHERE area='list12' AND status='1' AND column_num='秒杀' ORDER BY displayorder ASC,id DESC LIMIT 4 ");
    		foreach($miaosha_arr AS $v) {
    		    $v['img'] = $_W['attachurl'].$v['img'].'_750x10000.jpg?75';
    		    $miaoshaList[] = $v;
    		}
    		
    		
    		//双12
    		$picList_arr12 = pdo_fetchall("SELECT img,type,itemId,title,sub_title,column_num,displayorder FROM `ims_app_home` WHERE area='list12' AND status='1' ORDER BY displayorder ASC,id DESC LIMIT 100");
    		$newList = array('one1'=>array(),'one2'=>array(),'two'=>array(),'three1'=>array(),'three2'=>array(),'three3'=>array());
    		
    		foreach($picList_arr12 AS $v) {
    		    if($version){
    		        if($v['type']=='pcategory'){
    		            continue;
    		        }
    		    }
    		    $column = $v['column_num'];
    		    unset($v['column_num']);
    		    switch ($column){
    		        case '一栏上部':
    		            $v['img'] = $_W['attachurl'].$v['img'].'?75';
    		            $newList['one1'][] = $v;
    		            break;
    	            case '一栏下部':
    	                $v['img'] = $_W['attachurl'].$v['img'].'?75';
    	                $newList['one2'][] = $v;
    	                break;
                    case '二栏':
                        $v['img'] = $_W['attachurl'].$v['img'].'?75';
                        $newList['two'][] = $v;
                        break;
                    case '三栏品类':
                        $v['img'] = $_W['attachurl'].$v['img'].'?75';
                        $newList['three1'][] = $v;
                        break;
                    case '三栏好货':
                        $v['img'] = $_W['attachurl'].$v['img'].'?75';
                        $newList['three2'][] = $v;
                        break;
                    case '三栏好店':
                        $v['img'] = $_W['attachurl'].$v['img'].'?75';
                        $newList['three3'][] = $v;
                        break;
    		    }
    		}
    		
    		foreach ($newList as $key=>$val){
    		    if(is_array($val)){
        		    $sort = $this->my_sort($val, 'displayorder');
        		    foreach ($sort as $k=>$new){
        		        unset($new['displayorder']);
        		        $sort[$k] = $new;
        		    }
        		    $newList[$key] = $sort;
        		    unset($sort);
    		    }
    		}
    
    		$news = pdo_fetchall("SELECT title,type,itemId FROM `ims_app_home` WHERE area='news' AND status='1' ORDER BY displayorder ASC,id DESC LIMIT 3");
    		$news_end = array();
    		foreach($news AS $v) {
    			$v['type'] = 'none';
    			$news_end[] = $v;
    		}
    
    		$searchKwd_arr = pdo_fetchall("SELECT word FROM `ims_app_hotsearch` ORDER BY displayorder ASC, id DESC");
    		shuffle($searchKwd_arr);
    		$searchKwd = $searchKwd_arr[0]['word'] ? $searchKwd_arr[0]['word'] : '';
    
    		$shareInfo = array(
    			'title' => '顺联动力',
    			'desc' => '分享描述',
    			'link' => 'https://m.shunliandongli.com/',
    			'img' => 'https://statics.shunliandongli.com/resource/image/logo/system.png'
    		);
    
    		$return = array(
    			'searchKwd'		=> $searchKwd,
    			'news'			=> $news_end,
    			'banner'		=> $banner,
    			'category'		=> $category,
    			'category_new'	=> $category_new,
    			'picList'		=> $picList,
    			'shareInfo'		=> $shareInfo,
    		    'newList'       => $newList,
    		    'newcate'       => $newcate,
    		    'is_show_new'   => $is_show_new,
    		    'bangdanList'   =>$bangdanList,
    		    'miaoshaList'   =>$miaoshaList
    		);
    		$_W['mc']->set($result_key, serialize($return), MEMCACHE_COMPRESSED, 60);
    		$result = $return;
		}else{
		    $result = unserialize($ret);
		}
		return self::responseOk($result);
	}
	public function set(){
	    $tabbar = 1;//tabbar自定义
	    $flash = 0;//第一次打开图片
	    $result = array('tabbar'=>$tabbar,'flash'=>$flash);
	    return self::responseOk($result);
	}
	/*
	public function tabbar() {
	    global $_W;
	    $result = array('version'=>'12',
	        'data'=> array(
    	        array('normal'=>array('img'=>$_W['attachurl'].'images/2016/12/TrLLbi6L3GOLGWOOORlQIz3YqwIIlw.png','color'=>'#aaaaaa'),'selected'=>array('img'=>$_W['attachurl'].'images/2016/12/TrLLbi6L3GOLGWOOORlQIz3YqwIIlw.png','color'=>'#f81c05'),'text'=>'首页'),
    	        array('normal'=>array('img'=>$_W['attachurl'].'images/2016/12/lQQ4jDLqe145AJn2YB15yyJytbLkld.png','color'=>'#aaaaaa'),'selected'=>array('img'=>$_W['attachurl'].'images/2016/12/lQQ4jDLqe145AJn2YB15yyJytbLkld.png','color'=>'#f81c05'),'text'=>'分类'),
    	        array('normal'=>array('img'=>$_W['attachurl'].'images/2016/12/DLwiW8bIcVpkN8ze803llZ3Y8yj83e.png','color'=>'#aaaaaa'),'selected'=>array('img'=>$_W['attachurl'].'images/2016/12/DLwiW8bIcVpkN8ze803llZ3Y8yj83e.png','color'=>'#f81c05'),'text'=>'购物车'),
    	        array('normal'=>array('img'=>$_W['attachurl'].'images/2016/12/x83xxzgve7H0535QZm5rZEu8eRbcGG.png','color'=>'#aaaaaa'),'selected'=>array('img'=>$_W['attachurl'].'images/2016/12/x83xxzgve7H0535QZm5rZEu8eRbcGG.png','color'=>'#f81c05'),'text'=>'我的')
    	    )
	    );
	    return self::responseOk($result);
	}
    */
	public function flash() {
	    global $_W;
	    $result_key = 'home-app-flash';
	    $ret = $_W['mc']->get($result_key);
	    $result = array('version'=>1,'data'=>array());
	    if(!$ret){
	        $ret = pdo_fetchall("SELECT img,title AS ad,type,itemId,dateline FROM `ims_app_home` WHERE area='flash' AND status='1' ORDER BY displayorder ASC,id DESC ");
	        foreach($ret AS $row) {
	            $row['img'] = $_W['attachurl'].$row['img'].'_750x10000.jpg?75';
	            $result['version'] = $row['dateline']>$result['version'] ? $row['dateline'] : $result['version'];
	            unset($row['dateline']);
	            $result['data'][] = $row;
	        }
	        $_W['mc']->set($result_key, serialize($result), MEMCACHE_COMPRESSED, 60);
	    }else{
	        $result = unserialize($ret);
	    }
	    return self::responseOk($result);
	}
	private function version($iPhone_Version,$Android_Version){
	    if(self::$platform == 'Android') {
	        return version_compare(self::$client_version, $Android_Version, '<');
	    }
	    if(self::$platform == 'IOS') {
	        return version_compare(self::$client_version, $iPhone_Version, '<');
	    }
	    return false;
	}
	private function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
	    if(count($arrays)>1){
            if(is_array($arrays)){
                foreach ($arrays as $array){
                    if(is_array($array)){
                        $key_arrays[] = $array[$sort_key];
                    }else{
                        return false;
                    }
                }
            }else{
                return false;
            }
            array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
	    }
	    return $arrays;
    }
}