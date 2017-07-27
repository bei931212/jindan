<?php

class GoodsModel
{

    private static $_instance = NULL;

    /**
     *
     * @return GoodsModel
     */
    final public static function getInstance()
    {
        if (! isset(self::$_instance) || ! self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 获取商家可用的优惠券
     * @param int $goodsid 商品ID
     * @param int $sellerid 商家ID
     */
    public function getGoodsVoucher($sellerid)
    {
        $result = array();
        $vlists = pdo_fetchall("SELECT `id`,is_all,sellerid,title,price,discount,remark,start_time,end_time FROM " . tablename('bj_qmxk_voucher') . " WHERE sellerid='{$sellerid}' AND status=1 and end_time>" . time() . " ORDER BY id DESC LIMIT 20 ");
        foreach ($vlists as $key => $value) {
            $value['start_time'] =  date("Y.m.d",$value['start_time']);
            $value['end_time'] = date("Y.m.d",$value['end_time']);
            $result[] = $value;
        }
        return $result;
    }

    /**
    * 根据商家ID获取店铺统计数据
    * @param $sellerid 商家ID
    */
    public function getShopCount($sellerid) {
        global $_W;

		$count_arr = pdo_fetch("/*axpwx*/ SELECT * FROM `ims_shop_count` WHERE sellerid='{$sellerid}'");

		if(empty($count_arr['goods'])) $count_arr['goods'] = '...';
		if(empty($count_arr['sales'])) $count_arr['sales'] = '...';
		if(empty($count_arr['favorites'])) $count_arr['favorites'] = '...';

        return $count_arr;
    }
}