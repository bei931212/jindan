<?php
class ConfigModel{
    private static $_instance = NULL;
    
    /**
     * @return OrderModel
     */
    final public static function getInstance()
    {
        if (!isset(self::$_instance) || !self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    //评论内容最少加分长度
    const CREDIT_COMMENT_MIN_LEN = 10;


    //中评差评提交后，多长时间允许修改(小时)
    const BAD_COMMENT_EDIT_HOURS = 48;

    //多少天未收获评价，则提示
    const UN_RECEIVE_CMT_TIP_MIN_DAYS = 10;

    //默认头像
    const IMG_DEF_HEADFACE = 'http://statics.shunliandongli.com/resource/image/avatar.png';


    //每个用户单品加好评分，每月限额
    const CREDIT_GOODS_PER_MONTH = 20;


    //积分数组配置
    const CREDIT_SYS_OPT_ACTID         = '0';
    const CREDIT_APP_SIGNUP_ACTID      = '1';
    const CREDIT_WX_SIGNUP_ACTID       = '2';
    const CREDIT_LOCK_FANS_ACTID       = '3';
    const CREDIT_EFFECT_COMMENT_ACTID  = '4';
    const CREDIT_HIDDEN_COMMENT_ACTID  = '5';
    const CREDIT_VIEW_ANY_GOODS_ACTID  = '6';


    const CREDIT_OLD_TO_NEW_ACTID      = '508';
 

    //好评分ID设定
    const CREDIT_BUY_ACTID             = '1000';
    const CREDIT_SON_BUY_ACTID         = '1001';
    const CREDIT_RETURN_ACTID          = '1002';
    const CREDIT_SON_RETURN_ACTID      = '1003';
    const CREDIT_BAD_BY_SHOP_ACTID     = '1004';
    const CREDIT_SON_BAD_BY_SHOP_ACTID = '1005';

    private $credit_set_arr = array(
        '0' => array('id'=>0, 'num'=>0, 'name'=>'市场奖惩或修复增减'),
        '1' => array('id'=>1, 'num'=>2, 'name'=>'APP签到'),
        '2' => array('id'=>2, 'num'=>2, 'name'=>'微商城签到'),
        '3' => array('id'=>3, 'num'=>1, 'name'=>'锁粉积分'),
        '4' => array('id'=>4, 'num'=>2, 'name'=>'发表商品评论'),
        '5' => array('id'=>5, 'num'=>-5, 'name'=>'隐藏评论-扣除积分'),
        '6' => array('id'=>6, 'min'=>0, 'max'=>10, 'name'=>'浏览商城随机奖励'),

        '508' => array('id'=>508, 'name'=>'积分系统升级，一次性发放'),

        '1000' => array('id'=>1000, 'flag'=>1, 'name'=>'购物成功-获得好评分'),//flag符号，扣分是-1
        '1001' => array('id'=>1001, 'flag'=>1, 'name'=>'小店销售成功-获得好评分'),
        '1002' => array('id'=>1002, 'flag'=>-1, 'name'=>'购物退单-扣除好评分'),
        '1003' => array('id'=>1003, 'flag'=>-1, 'name'=>'小店销售退单-扣除好评分'),
        '1004' => array('id'=>1004, 'flag'=>-1, 'name'=>'商家修改评价-扣除好评分'),
        '1005' => array('id'=>1005, 'flag'=>-1, 'name'=>'小店被商家修改评价-扣除好评分'),
        '614' => array('id'=>614, 'flag'=>-1, 'name'=>'抽奖消耗积分'),
    );

    //好评分设定 >=min && <max
    public static $CREDIT_BUY_RULE  = array(
            array('min'=>0.01, 'max'=>20,      'num'=>1),
            array('min'=>20,   'max'=>100,     'num'=>3),
            array('min'=>100,  'max'=>300,     'num'=>5),
            array('min'=>300,  'max'=>600,     'num'=>7),
            array('min'=>600,  'max'=>9999999, 'num'=>9),
        );

    //好评分级别判断规则 >=min && <max
    public static $CREDIT_LEVEL_RULE  = array(
            array('min'=>0,    'max'=>10,      'level'=>0),
            array('min'=>10,   'max'=>50,      'level'=>1),
            array('min'=>50,   'max'=>200,     'level'=>2),
            array('min'=>200,  'max'=>500,     'level'=>3),
            array('min'=>500,  'max'=>1000,    'level'=>4),
            array('min'=>1000, 'max'=>2000,    'level'=>5),
            array('min'=>2000, 'max'=>9999999, 'level'=>6),
        );

    /**
    * 根据actid获取积分操作信息
    */
    public function getCreditSetArr($actid, $key=null){
        if(isset($this->credit_set_arr[$actid])){
            $arr = $this->credit_set_arr[$actid];
            if (!is_null($key)) {
                return isset($arr[$key])? $arr[$key] : array();
            }
            return $arr;
        }

        return array();
        
    }

    /**
    * 获取v1最小好评分条件
    */
    public function getMinVipLevelCredit($level=1){
        $crules = self::$CREDIT_LEVEL_RULE;
        foreach ($crules as $key => $val) {
            if($val['level']==$level){
                return $val['min'];
            }
        }
        return false;
    }
	
	/*
	支持批发模式的相关商家
	*/
	public static $WHOLESALE_SHOPS = array('1345');
	public static $SECRET_KEY = 'a152uiuysdgdogasdgag141241';
	public static $WHOLESALE_CONFIG = array(
		'1345' => array(
			'data_url'=>'http://im.api-test.shunliandongli.com/push/test', //数据同步url
			'secret_key'=>'r22tt3rggsdg4t43423446rwtjhk', //签名秘钥
			'retail_costprice'=>0.8,//零售结算价比率
			'wholesale_saleprice'=>0.7,//批发销售价比率
			'wholesale_costprice'=>0.609,//批发结算价比率
			
			'retail_province_rate'=>0.008,//零售省级代理分润比率
			'retail_city_rate'=>0.012,//零售市级代理分润比率
			'retail_dist_rate'=>0.015,//零售县级代理分润比率
			
			'wholesale_province_rate'=>0.006,//批发省级代理分润比率
			'wholesale_city_rate'=>0.008,//批发市级代理分润比率
			'wholesale_dist_rate'=>0.012,//批发县级代理分润比率
		),	
	);
    

}
