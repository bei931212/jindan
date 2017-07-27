<?php
require IA_ROOT . '/source/libs/delivery.class.php';
class Fight extends Api {

    private $vip_level = 0;//V1及以上才能报名 
	//登陆验证
	function __construct() {}
	
	private function login(){
	    global $_W;
	    require_once IA_ROOT.'/source/apis/User.php';
	    if(!User::checklogin()) {
	        return self::responseError(1000, '尚未登陆。');
	    }
	    
	    if(empty($_W['member_id'])) {
	        return self::responseError(1001, '尚未登陆。');
	    }
	}
	
	/**
	 * 广告TOP
	 */
	public function top(){
	    $ad_list = $this->getAd();
	    return self::responseOk($ad_list['top']);
	}
	/**
	 * 大赛播报
	 */
    public function report(){
        $area = FightModel::getInstance()->get_fight_area();
        
        global $_W;
        $user['area_id'] = $area[0]['id'];
        $user['area_name'] = $area[0]['area_name'];
        if(isset($_W['member_id'])){
            $member_id = $_W['member_id'];
            $user = FightModel::getInstance()->get_user($member_id);
            if(empty($user)){
                $user['area_id'] = $area[0]['id'];
                $user['area_name'] = $area[0]['area_name'];
            }
        }
        
        foreach ($area as $k=>$v){
            if($v['id']==$user['area_id']){
                $v['select'] = 1;
            }else{
                $v['select'] = 0;
            }
            $area[$k] = $v;
        }
        $data = array();
        $data = $this->get_datalist($user['area_id'],1,20);
        $fight_name = $user['area_name'];
        $ad_list = $this->getAd();
        $data['fight_name'] = $fight_name;
        $data['pic'] = $ad_list['pic'];
        $data['pic2'] = array();//$ad_list['pic2'];
        $data['area'] = $area;
        return self::responseOk($data);
    }
    private function version($Android_Version){
        if(self::$platform == 'Android') {
            return version_compare(self::$client_version, $Android_Version, 'le');
        }
        return false;
    }

    private function get_datalist($area_id,$pageIndex=1,$pageSize=20){
    	$result = array();
    	global $_W;
    	$result_key = 'fight_list'.$area_id.$pageIndex.$pageSize;
		$ret = $_W['mc']->get($result_key);
		if(!$ret){
			$return = FightModel::getInstance()->datalist($area_id,$pageIndex,$pageSize);
			$_W['mc']->set($result_key, serialize($return), MEMCACHE_COMPRESSED, 600);
    		$result = $return;
		}else{
		    $result = unserialize($ret);
		}

		return $result;
    }
    
    /**
     * 我的赛况
     */
    public function my(){
        $this->login();
        global $_W;
        $member_id = $_W['member_id'];
        $user = FightModel::getInstance()->get_user($member_id);
        if(!$user){
            return self::responseError(700, '您还没有参加大赛。');
        }
        $data = FightModel::getInstance()->get_user_data($member_id);
        if(!$data){
            $data['member_id'] = $member_id;
            $data['fight_id'] = '1';
            $data['area_id'] = $user['area_id'];
            $data['rank'] = '0';
            $data['sales_amount'] = '0';
            $data['direct_amount'] = '0';
            $data['branch_amount'] = '0';
            $data['return_amount'] = '0';
            $data['trend'] = '0';
            $data['next_money'] = '0';
            $data['front_money'] = '0';
        }
        $data['area'] = $user['area_name'];
        $data['fight_content'] = $user['fight_content'];
        $data['next_money'] = $data['next_money'] + 0.01;
         
        if($data['front_money']==0){
            $data['front_money'] = $data['front_money'] + 0.01;
        }
        
        $version = $this->version('1.3.7');
        if($version){
            $next = $data['next_money'];
            $data['next_money'] = $data['front_money'];
            $data['front_money'] = $next;
        }
        
        $ad_list = $this->getAd();
        $data['pic'] = $ad_list['pic'];
        $data['pic2'] = array();//$ad_list['pic2'];
        $data['character'] = $ad_list['character'];
        return self::responseOk($data);
    }
	
	
	/**
	 * 首页
	 */
	public function index(){
	    $this->login();
	    global $_W;
	    
	    $ad_list = $this->getAd();
	
	    $member_id = $_W['member_id'];
	    $user_level =  FightModel::getInstance()->get_user_level($member_id);
	    
	    $fight_user_info = FightModel::getInstance()->get_user($member_id);
	
	    $fight_info = FightModel::getInstance()->get_fight_info();
	
	    $is_sign = false;
	    if($fight_user_info){
	        $is_sign = true;
	    }
	    $can_sign = false;
	    if($user_level >= $this->vip_level && $fight_info['sign_start_time'] >= time() && $fight_info['sign_end_time'] < time() ){
	        $can_sign = true;
	    }
	    $is_close = true;
	
	    if($fight_info['state']==1 && $fight_info['end_time']>time()){
	        $is_close = false;
	    }
	
	    $result = array('ad'=>$ad_list,'is_sign'=>$is_sign,'can_sign'=>$can_sign,'is_close'=>$is_close,'user_level'=>$user_level);
	    return self::responseOk($result);
	}
	
	/**
	 * 广告
	 */
	public function ad(){
	    $ad_list = $this->getAd();
	    return self::responseOk($ad_list);
	}
	/**
	 * 注册信息
	 */
	public function info(){
	    
	    $this->login();
	    global $_W;
	    $key = 'fight_info';
	    $result = array();
	    $ret = $_W['mc']->get($key);
	    if(!$ret){
	        $area = FightModel::getInstance()->get_fight_area();
	        $fight_info = FightModel::getInstance()->get_fight_info();
	        $result = array('area'=>$area,'fight'=>$fight_info);
	        $_W['mc']->set($key, serialize($result), MEMCACHE_COMPRESSED, 10);
	    }else{
	        $result = unserialize($ret);
	    }

	    //大赛数据
	    $member_id = $_W['member_id'];
	    $user_level =  FightModel::getInstance()->get_user_level($member_id);//会员等级
	    $fight_user_info = FightModel::getInstance()->get_user($member_id);
	    
	    //是否注册过大赛
	    $is_fight_sign = 0;
	    if($fight_user_info){
	        $is_fight_sign = 1;
	    }
	    
	    $time = time();
	    $fight_status = 0;//0报名前，1报名中，2，比赛中，3，比赛结束。
	    if($time >= $result['fight']['sign_start_time'] && $time <= $result['fight']['sign_end_time']){
	        $fight_status = 1;
	    }
	    if($time >= $result['fight']['start_time'] && $time <= $result['fight']['end_time']){
	        $fight_status = 2;
	    }
	    if($time > $result['fight']['end_time']){
	        $fight_status = 3;
	    }
	    $info = array();
	    $info['start_time'] = date('Y-m-d H:i:s',$result['fight']['start_time']);
	    $info['end_time'] = date('Y-m-d H:i:s',$result['fight']['end_time']);
	    $info['sign_start_time'] = date('Y-m-d H:i:s',$result['fight']['sign_start_time']);
	    $info['sign_end_time'] = date('Y-m-d H:i:s',$result['fight']['sign_end_time']);
	    
	    $result['fight']['start_time'] = date('Y-m-d H:i:s',$result['fight']['start_time']);
	    $result['fight']['end_time'] = date('Y-m-d H:i:s',$result['fight']['end_time']);
	    $result['fight']['sign_start_time'] = date('Y-m-d H:i:s',$result['fight']['sign_start_time']);
	    $result['fight']['sign_end_time'] = date('Y-m-d H:i:s',$result['fight']['sign_end_time']);
	    
	    $result['fight_status'] = $fight_status;
	    $result['user_level'] = $user_level;
	    $result['is_fight_sign'] = $is_fight_sign;
	    $result['state'] = $result['fight']['state'];
	    $result['can_reg_level'] = $this->vip_level;
	    
	    $result['info'] = $info;
	    
	    return self::responseOk($result);
	}
	
	/**
	 * 大赛区域
	 */
	public function area(){
	    $area = FightModel::getInstance()->get_fight_area();
	    return self::responseOk($area);
	}
	
	/**
	 * 大赛数据
	 */
	public function data(){
	    $this->login();
	    global $_W;
	    $member_id = $_W['member_id'];
	    $user = FightModel::getInstance()->get_user($member_id);
	    $data = FightModel::getInstance()->get_user_data($member_id);
	    if(!$data){
	        $data['member_id'] = $member_id;
	        $data['fight_id'] = '1';
	        $data['area_id'] = $user['area_id'];
	        $data['rank'] = '0';
	        $data['sales_amount'] = '0';
	        $data['direct_amount'] = '0';
	        $data['branch_amount'] = '0';
	        $data['return_amount'] = '0';
	        $data['trend'] = '0';
	        $data['next_money'] = '0';
	        $data['front_money'] = '0';
	    }
	    $data['area'] = $user['area_name'];
	    $data['fight_content'] = $user['fight_content'];
	    $data['next_money'] = $data['next_money'] + 0.01;
	    if($data['front_money']==0){
	        $data['front_money'] = $data['front_money'] + 0.01;
	    }
	    return self::responseOk($data);
	}
	
	/**
	 * 报名
	 */
	public function sign(){
	    $this->login();
	    global $_W;
	    $member_id = $_W['member_id'];
	    $user_level =  FightModel::getInstance()->get_user_level($member_id);
	    if($user_level < $this->vip_level){
	        return self::responseError(701, '比须是V1等级以上的才可以报名。');
	    }
	    
	    $fight_info = FightModel::getInstance()->get_fight_info();
	    $time = time();
	    if($time<$fight_info['sign_start_time']){
	        return self::responseError(701, '报名还没有开始。');
	    }
	    if($time>$fight_info['sign_end_time']){
	        return self::responseError(702, '报名已经结束了。');
	    }
	    
	    $data = array(
	        'member_id'		=> $_W['member_id'],
	        'district_id'	=> intval($_POST['district_id']),
	        'user_name'		=> $_POST['user_name'],
	        'mobile'		=> $_POST['mobile'],
	        'address'		=> $_POST['address'],
	        'fight_id'	    => 1,
	        'area_id'	    => intval($_POST['area_id']),
	        'fight_content'	=> $_POST['content']
	    );
	    
	    $username = trim($_POST['user_name']);
	    
	    if(empty($username)) return self::responseError(9101, '请输入用户名.');
	    if(strlen($username) < 4) return self::responseError(9102, '用户名不能小于4个字符(2个汉字).');
	    if(strlen($username) > 20) return self::responseError(9103, '用户名不能大于20个字符(10个汉字).');
	    if(preg_match('/^[\d]+$/', $username)) return self::responseError(9104, '用户名不能为纯数字.');
	    
	    
	    //如果有中文，则须匹配阿凡提/买买提.阿凡提
	    if (preg_match("/[\x7f-\xff]/", $username)) {
	        if(!preg_match('/^[\x{4e00}-\x{9fa5}]{2,10}+(\.[\x{4e00}-\x{9fa5}]{2,10})?$/u', $username)) {
	            return self::responseError(9106, '用户名格式不正确.');
	        }
	    }else{
	        if (!preg_match("/^[a-zA-Z.]+$/", $username)) {
	            return self::responseError(9106, '用户名格式不正确.');
	        }
	    }

	    $data['user_name'] = $username;
	    
	    $fight_user_info = FightModel::getInstance()->get_user($data['member_id']);
	    if($fight_user_info){
	        return self::responseError(710, '您已经报名过了。');
	    }
	    
	    if(empty($data['district_id'])) {
	        return self::responseError(714, '请选择地区');
	    }
	    if(empty($data['user_name'])) return self::responseError(711, '请输入姓名');
	    if(empty($data['mobile'])) return self::responseError(712, '请输入手机号');
	    if(!is_mobile($data['mobile'])) return self::responseError(713, '手机号码不正确');
	    //判断district_id的级别
	    $district_level = pdo_fetchcolumn("SELECT level FROM `ims_district` WHERE id='{$data['district_id']}'");
	    if($district_level < 3) {
	        if($data['district_id'] != '820100' && $data['district_id'] != '820200') {
	            return self::responseError(717, '请选择完整的地区');
	        }
	    }
	    $address_tree = Delivery::fetch_position($data['district_id']);
	    unset($address_tree[0]);
	    if($address_tree[1]) $data['province'] = $address_tree[1];
	    if($address_tree[2]) $data['city'] = $address_tree[2];
	    if($address_tree[3]) $data['area'] = $address_tree[3];
	    
	    if(empty($data['address'])) return self::responseError(715, '请输入详细地址');
	    unset($data['district_id']);
	    $data['reg_time'] = time();
	    pdo_insert('bj_qmxk_fight_member', $data);
	    $Id = pdo_insertid();
	    if(empty($Id)) {
	        pdo_insert('bj_qmxk_fight_member', $data);
	        $Id = pdo_insertid();
	        if(empty($Id)) {
	            return self::responseError(716, '注册失败，请重试。');
	        }
	    }
	    pdo_insert('bj_qmxk_fight_data', array('fight_id'=>1,'member_id'=>$_W['member_id'],'area_id'=>$data['area_id']));
	    pdo_insert('bj_qmxk_fight_data_calc', array('fight_id'=>1,'member_id'=>$_W['member_id'],'area_id'=>$data['area_id']));
	    
	    return self::responseOk(array('Id'=>$Id));
	}
	/**
	 * 修改感言
	 */
	public function changecontent(){
	    $this->login();
	    global $_W;
	    $member_id = $_W['member_id'];
	    $fight_id = 1;
	    $content = $_POST['content'];
	    pdo_update('bj_qmxk_fight_member', array('fight_content' => $content), array('member_id' => $member_id,'fight_id'=>$fight_id));
	    return self::responseOk(array('member_id'=>$member_id));
	}
	
	/**
	 * 大赛数据
	 */
	public function datalist(){
	    $pageSize = isset($_GET['pagesize']) ? intval($_GET['pagesize']) : 20;
	    $pageIndex = max(1, intval($_GET['page']));
	    $area_id = intval($_GET['area_id']);
	    if(!$area_id){
	        return self::responseError(700, '请选择区域ID。');
	    }
	    $data = $this->get_datalist($area_id,$pageIndex,$pageSize);
	    if(empty($data)){
	        $data = array();
	    }
	    return self::responseOk($data);
	}
	
	private function getAd(){
	    global $_W;
	    
	    $ad_key = 'fight_ad_1';
	    $ad_list = array();
	    $ret = $_W['mc']->get($ad_key);
	    if(!$ret){
	        $ad_list = FightModel::getInstance()->get_ad($_W['attachurl']);
	        $_W['mc']->set($ad_key, serialize($ad_list), MEMCACHE_COMPRESSED, 10);
	    }else{
	        $ad_list = unserialize($ret);
	    }
	    
	    $pic = $ad_list['pic'];
	    shuffle($pic);
	    $pic2 = array();//$ad_list['pic2'];
	    //shuffle($pic2);
	    $top = $ad_list['top'];
	    shuffle($top);
	    $character = $ad_list['character'];
	    shuffle($character);
	    
	    unset($ad_list);
	    return array('pic'=>$pic,'pic2'=>$pic2,'top'=>$top,'character'=>$character);
	}
}