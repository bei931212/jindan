<?php
class CreditRandomModel{
    private static $_instance = NULL;
    private $sign_mode        = 0;  //0 cookie端保存, 1 memcache保存
    private $data             = null;
    private $config           = null;
    //随机赠送积分规则设定 >=min
    public static $CREDIT_RULE  = array(
            array('idx'=>1, 'min'=>5,   'num'=>1, 'random'=>2),
            array('idx'=>2, 'min'=>15,  'num'=>3, 'random'=>2),
            array('idx'=>3, 'min'=>30,  'num'=>5, 'random'=>3),
        );

    const COOKIE_NAME       = 'utj'; //保存cookie key
    const COOKIE_SIGN_NAME  = 'utjs'; //cookie验证签名 
    const COOKIE_SIGN_KEY   = 'Gf[h2!ke31$7mRR#h8O=vpqc(IdZL*K.'; //cookie验证 key
    const COOKIE_EXPIRE     = 864000; //cookie过期时间

    const DATE_ID           = 'a';
    const SEARCH_COUNT      = 'b'; //搜索次数
    const SEARCH_SIGN       = 'c'; //上一次搜索签名，防单页刷

    const HOME_TO_DETAIL    = 'd'; //首页-详情次数
    const LIST_TO_DETAIL    = 'e'; //列表-详情次数
    const CHANNEL_TO_DETAIL = 'f'; //频道-详情次数
    const SEARCH_TO_DETAIL  = 'g'; //频道-详情次数

    const DETAIL_SIGN       = 'h'; //详情签名，防单页刷

    const CREDIT_LOGS       = 'i'; //获奖记录，逗号间隔
    const GET_CREDIT_TIMES  = 'j'; //领奖次数

    //memcache键值
    const MCK_CREDIT_CONFIG           = 'row_bj_qmxk_integral_rule'; //后台积分规则设置
    const MCK_HAVE_GIVE_RANDOM_CREDIT = 'int_have_give_radom_credit'; //后台积分规则设置


    /*
    * 禁止外部new实例化
    */
    private function __construct(){

    }
    private function __clone(){
        
    }


    /**
     * @return Model
     */
    final public static function getInstance()
    {
        if (!isset(self::$_instance) || !self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
    * 编码
    */
    private function encode($vidata){
        $str = array();
        foreach ($vidata as $key => $val) {
            if(!empty($val)){
                $val = str_replace(array('|', ':'), '', $val);
                $str[] = "$key:$val";
            }
        }

        return implode('|', $str);
    }

    /**
    * 解码
    */
    private function decode($encode){
        if(empty($encode)){
            return array();
        }
        $items = explode('|', $encode);

        $rt = array();
        foreach ($items as $key => $item) {
            $item = !empty($item)? explode(':', $item) : array();
            if(!empty($item)){
                $rt[array_shift($item)] = array_shift($item);
            }
        }
        $items = null;
        unset($items);

        return $rt;
    }

    /**
    * 签名
    */
    private function sign($str){
        global $_W;

        $key = self::COOKIE_SIGN_KEY;
        $sign = md5( $_W['member_id'] . substr($key, 3, 8) . $str . substr($str, 2, 8) . $key  );
        return $sign;
    }

    /**
    * 获取签名
    */
    private function getSign($member_id){
        if($this->sign_mode==1){
            return CacheModel::getInstance()->get('sign_utj_' . $member_id);
        }else{
            return isset($_COOKIE[self::COOKIE_SIGN_NAME]) ? $_COOKIE[self::COOKIE_SIGN_NAME] : '';
        }
    }
    /**
    * 设置签名
    */
    private function setSign($member_id, $sign){
        if($this->sign_mode==1){
            CacheModel::getInstance()->set('sign_utj_' . $member_id, $sign, self::COOKIE_EXPIRE);
        }else{
            isetcookie(self::COOKIE_SIGN_NAME, $sign, self::COOKIE_EXPIRE);
        }
    }


    /**
    * 获取系统设置
    */
    private function getConfig(){
        if($this->config===null){
            //缓存获取
            $cach_config = CacheModel::getInstance()->get(self::MCK_CREDIT_CONFIG);

            if(empty($cach_config)){
                $config = pdo_fetch("/*weixiaogui*/ SELECT `ratio`, `all_upper_limit`, `one_upper_limit` FROM " . tablename('bj_qmxk_integral_rule') . " WHERE for_day<". TIMESTAMP . " ORDER BY id DESC");
                if(!empty($config)){
                    CacheModel::getInstance()->set(self::MCK_CREDIT_CONFIG, json_encode($config), 86400);
                }
            }else{
                $config = json_decode($cach_config, true);
            }

            $this->config = $config;
        }
        return $this->config;
        
    }

    /**
    * 是否有奖没领
    */
    private function haveNewCredit(&$ck_data){

        if(count($ck_data[self::CREDIT_LOGS]) > $ck_data[self::GET_CREDIT_TIMES]){
            return true;
        }

        return false;
    }

    /**
    * 保存数据到cookie
    */
    private function saveToCookie($member_id){
        $ck_data = $this->getData();
        $ck_data[self::CREDIT_LOGS] = !empty($ck_data[self::CREDIT_LOGS])? implode(',', $ck_data[self::CREDIT_LOGS]) : '';

        $ck_data = $this->encode($ck_data);
        isetcookie(self::COOKIE_NAME, $ck_data, self::COOKIE_EXPIRE);

        $ck_sign = $this->sign($ck_data);
        $this->setSign($member_id, $ck_sign);
    }

    /**
    * 根据详情页浏览数，计算积分等级
    */
    private function getCreditRuleByDv($view_num){
        $rule = self::$CREDIT_RULE;
        $rt = array();
        foreach ($rule as $key => $val) {
            if($view_num >= $val['min']){
                $rt = $val;
            }
        }
        return $rt;
    }


    /**
    * 增加总发送记录
    */
    private function addHaveGiveCredit($credit){
        $ck_data = &$this->getData();

        return CacheModel::getInstance()->increment(self::MCK_HAVE_GIVE_RANDOM_CREDIT . $ck_data[self::DATE_ID], $credit, 86400*3); //保存3天
    }

    /**
    * 随机提示领取
    */
    private function getTipCredit($ratio, $clogs){
        $rd = mt_rand(1, 100);
        if($rd <= $ratio){//随机提示领取
            return array_pop($clogs);
        }

        return 0;
    }

    /**
    * 通过cookie解码数据
    */
    public function &getData(){
        global $_W;
        if(!empty($_W['member_id']) && $this->data===null){
            $ck_val  = isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : '';
            $ck_sign = $this->getSign($_W['member_id']);
            $date_id = strtotime(date('Y-m-d', TIMESTAMP));
            $data = array();
            if(!empty($ck_val) && !empty($ck_sign) && $this->sign($ck_val)==$ck_sign){//验签通过
                $data = $this->decode($ck_val);
                if($data[self::DATE_ID]!=$date_id){//日期不正确，重置
                    $data = array();
                    $data[self::DATE_ID] = $date_id;
                }

                $data[self::CREDIT_LOGS] = !empty($data[self::CREDIT_LOGS])? explode(',', $data[self::CREDIT_LOGS]) : array();
                $this->data = $data;
            }else{
                $data[self::DATE_ID] = $date_id;
                $data[self::CREDIT_LOGS] = !empty($data[self::CREDIT_LOGS])? explode(',', $data[self::CREDIT_LOGS]) : array();
                $this->data = $data;
            }
        }
        return $this->data;
    }

    /**
    * 增加
    * @param $kw 搜索关键字
    */
    public function addSearchNum($kw){
        global $_W;
        if(empty($_W['member_id'])){
            return false;
        }

        if(empty($kw)){
            return false;
        }

        $ck_data = &$this->getData(); //增加&， 获取到 $this->data 的引用

        //获奖了，但还没领取，数据暂停更新
        if($this->haveNewCredit($ck_data)){
            return false;
        }

        $search_sign = substr(md5($kw), 7, 8);

        if($ck_data[self::SEARCH_SIGN]!=$search_sign ){//需要与上一次不一样
            $ck_data[self::SEARCH_COUNT] = intval($ck_data[self::SEARCH_COUNT]) + 1;
            $ck_data[self::SEARCH_SIGN] = $search_sign;

            $this->saveToCookie($_W['member_id']);

            return true;
        }

        return false;
    }

    /**
    * 判断是否详情页统计
    */
    public function isDetailType($type){
        $typ_arr   = array();
        $typ_arr[] = self::HOME_TO_DETAIL;
        $typ_arr[] = self::LIST_TO_DETAIL;
        $typ_arr[] = self::CHANNEL_TO_DETAIL;
        $typ_arr[] = self::SEARCH_TO_DETAIL;

        if(in_array($type, $typ_arr)){
            return true;
        }

        return false;
    }

    /**
    * 增加详情浏览次数
    */
    public function addDetailViewNum($goodsid, $type=''){
        global $_W;
        if(empty($_W['member_id'])){
            return false;
        }

        if(empty($goodsid)){
            return false;
        }

        if(!$this->isDetailType($type)){
            return false;
        }

        $ck_data = &$this->getData(); //增加&， 获取到 $this->data 的引用

        //获奖了，但还没领取，数据暂停更新
        if($this->haveNewCredit($ck_data)){
            return false;
        }
        
        $detail_sign = substr(md5($goodsid), 9, 8);

        if($ck_data[self::DETAIL_SIGN]!=$detail_sign ){//需要与上一次不一样
            $ck_data[$type] = intval($ck_data[$type]) + 1;
            $ck_data[self::DETAIL_SIGN] = $detail_sign;

            $this->saveToCookie($_W['member_id']);

            return true;
        }

        return false;
    }

    /**
    * 增加统计
    */
    public function addNum($type, $val=''){
        if($type==self::SEARCH_COUNT){
            return $this->addSearchNum($val);
        }else{
            return $this->addDetailViewNum($val, $type);
        }
    }

    /**
    * 获取已经发放积分记录
    */
    public function getHaveGiveCredit($date_id=0){
        if($date_id==0){
            $ck_data = &$this->getData();
            $date_id = $ck_data[self::DATE_ID];
        }

        $have_give = CacheModel::getInstance()->get(self::MCK_HAVE_GIVE_RANDOM_CREDIT . $date_id);
        return $have_give;
    }
    

    /**
    * 是否可以获得积分
    */
    public function getNewCredit(){
        global $_W;
        if(empty($_W['member_id'])){
            return array('status'=>175100, 'msg'=>'请先登录后领取');
        }

        $config = $this->getConfig();
        $ratio = $config['ratio']; //可以领奖的几率， 0~100%
        $ratio = $ratio*1;

        $ck_data = &$this->getData(); //增加&， 获取到 $this->data 的引用

        //检查是否有奖没领
        if($this->haveNewCredit($ck_data)){
            return array('status'=>0, 'credit'=>$this->getTipCredit($ratio, $ck_data[self::CREDIT_LOGS]), 'msg'=>'随机提示');
        }

        //获取当天总发放积分
        $have_give = $this->getHaveGiveCredit();
        if($have_give >= $config['all_upper_limit']){//达到当天限额，不再继续发送
            return array('status'=>175101, 'msg'=>'当天限额已经达到，限额：' . $config['all_upper_limit'] . ", 已发：". $have_give);
        }

        //检查是否达成领奖条件
        $date_id = $ck_data[self::DATE_ID];
        $next_date_id = $date_id + 86400;
        $view_num = $ck_data[self::HOME_TO_DETAIL] + $ck_data[self::LIST_TO_DETAIL] + $ck_data[self::CHANNEL_TO_DETAIL] + $ck_data[self::SEARCH_TO_DETAIL];
        $rule = $this->getCreditRuleByDv($view_num);
        if(empty($rule)){
            return array('status'=>0, 'credit'=>0, 'msg'=>'没达成领奖条件');
        }

        if($rule['idx'] <= $ck_data[self::GET_CREDIT_TIMES]){
            return array('status'=>0, 'credit'=>0, 'msg'=>'已经领过了');
        }

        //单用户是否达到限额
        $actid = ConfigModel::CREDIT_VIEW_ANY_GOODS_ACTID;
        $u_credit = pdo_fetchcolumn("/*weixiaogui*/ SELECT SUM(`change`) FROM ims_bj_qmxk_member_credit_log WHERE member_id='{$_W['member_id']}' AND type=0 AND addtime>='{$date_id}' AND addtime<'{$next_date_id}' AND actid='{$actid}'");
        if($u_credit >= $config['one_upper_limit']){
            return array('status'=>175102, 'credit'=>0, 'msg'=>'已达到每人当日限额，限额：' . $config['one_upper_limit'] . ", 已发：". $u_credit);
        }

        //随机积分
        $credit = mt_rand(0, $rule['random']);

        if($credit < $rule['random'] && $ck_data[self::SEARCH_COUNT] >= $rule['idx']){//有搜索，增加获得几率
            $ratio2 = $ratio + 0.1*$rule['idx']*100;
            $rd = mt_rand(1, 100);
            if($rd <= $ratio2){
                $credit = $credit + 1;
            }
        }

        //最终积分
        $credit = $credit + $rule['num'];
        $ck_data[self::CREDIT_LOGS][] = $credit;
        $this->saveToCookie($_W['member_id']);

        //更新总发放积分
        $this->addHaveGiveCredit($credit);

        //随机提示领取
        return array('status'=>0, 'credit'=>$this->getTipCredit($ratio, $ck_data[self::CREDIT_LOGS]), 'msg'=>'随机提示2');
    }
    

    /**
    * 领取随机积分
    */
    public function storeCredit(){
        global $_W;
        if(empty($_W['member_id'])){
            return array('status'=>175000, 'error'=>'请先登录后领取');
        }

        $ck_data = &$this->getData();
        $clogs = $ck_data[self::CREDIT_LOGS];

        //检查是否有奖没领
        if(!$this->haveNewCredit($ck_data)){
            return array('status'=>175001, 'error'=>'当前没有积分可以领取');
        }

        $credit = array_pop($clogs);
        if($credit <= 0){
            return array('status'=>175002, 'error'=>'积分异常，领取失败');
        }

        //# 并发控制
        $do_token_key   = "do_store_credit_{$_W['member_id']}";
        if(CacheModel::getInstance()->doBegin($do_token_key)==false){
            return array('status'=>175003, 'error'=>'操作太频繁了，请稍后再试');
        }

        CreditModel::getInstance()->addLog($_W['member_id'], ConfigModel::CREDIT_VIEW_ANY_GOODS_ACTID, '', $credit);

        $ck_data[self::GET_CREDIT_TIMES] = intval($ck_data[self::GET_CREDIT_TIMES]) + 1;
        $this->saveToCookie($_W['member_id']);

        //# 释放并发控制
        CacheModel::getInstance()->doEnd($do_token_key);

        return array('status'=>0);
    }

    /**
    * 生成统计script代码
    */
    public function genScriptCode($type, $val=''){
        return '<script src="/mobile.php?act=visitcredit&amp;t='. $type .'&amp;v='. urlencode($val) .'"></script>';
    }
    
}