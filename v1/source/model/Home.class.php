<?php
class HomeModel{
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
    
    public function get($id){
        $sql = "SELECT * FROM `ims_bj_qmxk_order` WHERE `id`=:id";
        return pdo_fetch($sql,array(':id'=>$id));
    }
}