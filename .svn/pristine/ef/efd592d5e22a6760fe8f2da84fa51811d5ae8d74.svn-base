<?php
class OrderModel{
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
    
    /**
    * 确认收货后触发
    */
    public function onFinish($member_id, $shop_id, $ordersn){
        if(empty($ordersn)){
            return false;
        }

        $member_id = intval($member_id);
        $shop_id   = intval($shop_id);

        $row_en_cmg = pdo_fetch("SELECT ordersn FROM " . tablename('bj_qmxk_order_enable_cmt') . " WHERE `ordersn` = :ordersn", array(':ordersn' => $ordersn ));

        if(empty($row_en_cmg)){// 允许用户评价
            //插入允许评价记录
            $data = array();
            $data['member_id']     = $member_id;
            $data['ordersn']       = $ordersn;
            $data['enable_cmt']    = 1;
            $data['addtime']       = TIMESTAMP;

            pdo_insert('bj_qmxk_order_enable_cmt', $data);
            unset($data);

            
        }

        //商家默认评价用户
        $row_rc = pdo_fetch("SELECT ordersn FROM " . tablename('bj_qmxk_member_receive_comment') . " WHERE `ordersn` = :ordersn", array(':ordersn' => $ordersn ));
        if(empty($row_rc)){
            //商家默认评价用户
            $data2 = array();
            $data2['member_id']     = $member_id;
            $data2['shop_id']       = $shop_id;
            $data2['ordersn']       = $ordersn;
            $data2['star_level']    = 5; //默认好评
            $data2['addtime']       = TIMESTAMP;

            pdo_insert('bj_qmxk_member_receive_comment', $data2);
        }
    }
}