<?php
class CommentModel{
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
    * 获取一条评价
    */
    public function getOne($cmt_id){
        $cmt_id  = intval($cmt_id);
        $cmt_row = pdo_fetch("SELECT c.*, cb.* ".
                "FROM `ims_bj_qmxk_goods_comment` AS c ".
                "LEFT JOIN `ims_bj_qmxk_goods_comment_body` AS cb ON cb.comment_id=c.id ".
                "WHERE c.id=:id", array(':id'=>$cmt_id));

        return $cmt_row;
    }
    
    /**
    * 根据评价更新评价统计表 goods_id, shop_id, star_level
    */
    public function increaseGoodsCommentData($goods_id, $shop_id, $star_level){
        if($goods_id>0){
            $goods_id           = intval($goods_id);
            $shop_id            = intval($shop_id);
            $star_level         = intval($star_level);
            $star_key           = 'star_'. $star_level .'_num';

            $result = pdo_query("UPDATE ".tablename('bj_qmxk_goods_comment_data')." SET {$star_key}={$star_key}+1  WHERE goods_id = :goods_id", array(':goods_id' => $goods_id));
            if (empty($result)) {
                $data = array();
                $data['goods_id']   = $goods_id;
                $data['shop_id']    = $shop_id;
                $data[$star_key]    = 1;

                pdo_insert('bj_qmxk_goods_comment_data', $data);
            }
            return true;
        }
        return false;
    }

    /*
    * 获取商品统计数据
    */
    public function getGoodsCommentData($goods_id){
        $result_end                     = array();
        $result_end['all_cmt_num']      = '0';
        $result_end['good_cmt_num']     = '0';
        $result_end['normal_cmt_num']   = '0';
        $result_end['bad_cmt_num']      = '0';
        $result_end['good_cmt_rate']    = '';

        $goods_id = intval($goods_id);
        if($goods_id > 0){
            $tb_cd = tablename('bj_qmxk_goods_comment_data');
            $row = pdo_fetch("SELECT * FROM {$tb_cd} WHERE goods_id=:goods_id", array(':goods_id' => $goods_id));
            if(!empty($row)){
                $all_num = $row['star_5_num']+$row['star_3_num']+$row['star_1_num'];
                if($all_num > 0){
                    $rate = floor(1000*$row['star_5_num']/$all_num)/10;
                    $rate = $rate.'%';
                }else{
                    $rate = '';
                }

                $result_end['all_cmt_num']      = $all_num.'';
                $result_end['good_cmt_num']     = $row['star_5_num'].'';
                $result_end['normal_cmt_num']   = $row['star_3_num'].'';
                $result_end['bad_cmt_num']      = $row['star_1_num'].'';
                $result_end['good_cmt_rate']    = $rate;
            }
        }

        return $result_end;
    }

    /**
    * 收到的评价和商品评价数
    */
    public function getCommentCounts($member_id, $for='comment'){
        $result_end                 = array();
        $result_end['from_seller']  = 0;
        $result_end['to_goods']     = 0;

        $tb_rc       = tablename('bj_qmxk_member_receive_comment');
        $tb_gc       = tablename('bj_qmxk_goods_comment');
        $tb_o        = tablename('bj_qmxk_order');
        if($member_id){
            $from_seller = pdo_fetchcolumn("SELECT COUNT(*) FROM {$tb_rc} WHERE member_id='{$member_id}'");
            $from_seller = intval($from_seller);

            $to_goods    = pdo_fetchcolumn("SELECT COUNT(*) FROM {$tb_gc} WHERE member_id='{$member_id}'");
            $to_goods    = intval($to_goods);

            $result_end['from_seller'] = $from_seller;
            $result_end['to_goods']    = $to_goods;
        }
        if($for == 'home'){ //增加返回未获得评价时间
            $result_end['unreceive_day'] = 0;

            $last_receive_time = 0;
            $last_receive_cmt = pdo_fetch("SELECT addtime FROM {$tb_rc} WHERE member_id='{$member_id}' ORDER BY addtime DESC LIMIT 1");
            if(empty($last_receive_cmt)){
                //有完成订单的
                $last_ok_order = pdo_fetch("SELECT createtime FROM {$tb_o} WHERE member_id='{$member_id}' AND status='3' ORDER BY createtime DESC LIMIT 1");
                if(!empty($last_ok_order) && $last_ok_order['createtime']){
                    $last_receive_time = $last_ok_order['createtime'];
                }
            }else{
                $last_receive_time = $last_receive_cmt['addtime'];
            }

            $un_cmt_days = ceil((TIMESTAMP-$last_receive_time)/86400);
            if($last_receive_time > 0 && $un_cmt_days > ConfigModel::UN_RECEIVE_CMT_TIP_MIN_DAYS){
                $result_end['unreceive_day'] = $un_cmt_days;
            }
        }

        return $result_end;
    }

    /**
    * 是否允许添加评论
    */
    public function isEnableAdd($member_id, $ordersn){
        //判断是否允许评价
        $en_cmt = pdo_fetch("SELECT * ".
                "FROM `ims_bj_qmxk_order_enable_cmt` oec ".
                "WHERE ordersn=:ordersn", array(':ordersn'=>$ordersn));
        if(empty($en_cmt)){
            return array('status'=>22151, 'msg'=>'当前不允许评论。');
        }

        if($en_cmt['member_id']!=$member_id){
            return array('status'=>22152, 'msg'=>'抱歉，无权评价该订单。');
        }

        if($en_cmt['enable_cmt']==2){
            return array('status'=>22153, 'msg'=>'已经评论过了。');
        }else if($en_cmt['enable_cmt']==-1){
            return array('status'=>22154, 'msg'=>'评论时效已过。');
        }

        return array('status'=>0, 'msg'=>'');
    }

    /**
    * 是否允许修改
    */
    public function isEnableEdit($star_level, $addtime){
        if($star_level < 5 && TIMESTAMP-$addtime < ConfigModel::BAD_COMMENT_EDIT_HOURS*3600){
            return true;
        }else{
            return false;
        }
    }

    /**
    * 检查输入是否可以加积分 
    */
    public function checkCommentCredit($content, $pics, $charset='utf-8'){
        if(mb_strlen($content, $charset)>=ConfigModel::CREDIT_COMMENT_MIN_LEN || !empty($pics)){
            return true;
        }
        return false;
    }
    
}