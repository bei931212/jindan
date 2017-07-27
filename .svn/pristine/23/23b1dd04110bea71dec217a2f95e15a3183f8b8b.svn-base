<?php
class CreditModel{
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
    * 添加积分记录
    *  @param $change 变化量，正为加积分，负数为扣积分
    *  @return 返回变化的积分值
    */
    public function addLog($member_id, $actid=0, $min_memo='', $change=0, $ordersn='', $goods_id=0){
        //检查配置
        $set_arr = ConfigModel::getInstance()->getCreditSetArr($actid);
        if(empty($set_arr)){//配置异常
            $this->addErrorLog("member_id: {$member_id}, ordersn: {$ordersn},  set_arr: {$actid} empty, add credit log abort. \n");
            return false;
        }
        $change = $set_arr['num']==0? $change : $set_arr['num'];

        if($change==0){//加分异常
            $this->addErrorLog("member_id: {$member_id}, ordersn: {$ordersn},  set_arr: {$actid}, change=0, add credit log abort. \n");
            return false;
        }

        $result = pdo_query("UPDATE ".tablename('bj_qmxk_member_info')." SET credit1=credit1+{$change}  WHERE member_id = :member_id", array(':member_id' => $member_id));
        if (!empty($result)) {
            $data = array();
            $data['member_id']  = $member_id;
            $data['ordersn']    = $ordersn;
            $data['goods_id']   = $goods_id;
            $data['type']       = 0;
            $data['actid']      = $actid;
            $data['change']     = $change;
            $data['min_memo']   = $min_memo;
            $data['addtime']    = time();

            pdo_insert('bj_qmxk_member_credit_log', $data);
        }

        return $change;

    }

    /**
    * 添加好评分记录
    *  @param $actid 操作ID
    *  @return 返回变化的好评分值
    */
    public function addExpLogByOrdersn($member_id, $actid, $ordersn, $pactid){
		
		//2017-05-08前不可加好评分
        if (time()<strtotime('2017-05-08'))
        {
            return 0;
        }

		//8号之前的订单不能获得好评分
		$date = substr($ordersn, 0, 6);
		if($date < 170508) {
			return 0;
		}

        //检查配置
        $set_arr = ConfigModel::getInstance()->getCreditSetArr($actid);
        if(empty($set_arr)){//配置异常
            $this->addErrorLog("member_id: {$member_id}, ordersn: {$ordersn},  set_arr: {$actid} empty, add explog abort. \n");
            return false;
        }

        $parent_member_id = MemberModel::getInstance()->getParentMemberId($member_id); //推荐人member_id   

        if($set_arr['flag']>0){//加分，按商品算
            $rt_call = $this->addCreditByOrderGoods($member_id, $actid, $ordersn, $pactid, $parent_member_id);
        }else{//扣分，按加分记录算
            $rt_call = $this->subCreditByOrderGoods($member_id, $actid, $ordersn, $pactid, $parent_member_id);            
        }

        if($rt_call===false){
            return false;
        }
        $credit_count = $rt_call;
        

        //保存好评分、v等级
        if($credit_count!=0){
            //======================最终方案
            pdo_query("UPDATE ".tablename('bj_qmxk_member_info')." SET credit1=credit1+{$credit_count}, order_credit_count=order_credit_count+{$credit_count}  WHERE member_id = :member_id", array(':member_id' => $member_id));
            if($parent_member_id > 0){
                pdo_query("UPDATE ".tablename('bj_qmxk_member_info')." SET credit1=credit1+{$credit_count}, order_credit_count=order_credit_count+{$credit_count}  WHERE member_id = :member_id", array(':member_id' => $parent_member_id));
            }

            //#规0操作
            if($credit_count<0){
                pdo_query("UPDATE ".tablename('bj_qmxk_member_info')." SET credit1=0  WHERE member_id = :member_id AND credit1<0", array(':member_id' => $member_id));
                pdo_query("UPDATE ".tablename('bj_qmxk_member_info')." SET order_credit_count=0  WHERE member_id = :member_id AND order_credit_count<0", array(':member_id' => $member_id));

                if($parent_member_id > 0){
                    pdo_query("UPDATE ".tablename('bj_qmxk_member_info')." SET credit1=0  WHERE member_id = :member_id AND credit1<0", array(':member_id' => $parent_member_id));
                    pdo_query("UPDATE ".tablename('bj_qmxk_member_info')." SET order_credit_count=0  WHERE member_id = :member_id AND order_credit_count<0", array(':member_id' => $parent_member_id));
                }
            }
            
        }

        return $credit_count;
    }
    /**
    * 根据订单商品加分
    */
    private function addCreditByOrderGoods($member_id, $actid, $ordersn, $pactid, $parent_member_id){
        $credit_count = 0;
        $tmp_cal_price = 0;
        $tmp_count = 0;
        $match_num = 0;
        $enable_num = 0;
        $log_data = array(
            'member_id' => $member_id,
            'ordersn'   => $ordersn,
            'type'      => 1,
            'actid'     => $actid,
            'addtime'   => TIMESTAMP,
        );
        $log_data2 = array(
            'member_id' => $parent_member_id,
            'ordersn'   => $ordersn,
            'type'      => 1,
            'actid'     => $pactid,
            'addtime'   => TIMESTAMP,
        );

        //检查规则
        $exp_rule = ConfigModel::$CREDIT_BUY_RULE;
        if(empty($exp_rule)){
            $this->addErrorLog("member_id: {$member_id}, ordersn: {$ordersn},  actid: {$actid}, CREDIT_BUY_RULE empty,  add explog abort. \n");
            return false;
        }

        //查找订单商品
        $sql = "/*weixiaogui*/ SELECT og.goodsid, og.price, og.total, og.offerprice 
                FROM ims_bj_qmxk_order_goods og 
                INNER JOIN 
                  (SELECT id AS orderid FROM ims_bj_qmxk_order WHERE ordersn='{$ordersn}') tm_o 
                USING(orderid)";
        $g_rows = pdo_fetchall($sql);

        if(empty($g_rows)){//订单异常
            $this->addErrorLog("member_id: {$member_id}, ordersn: {$ordersn},  actid: {$actid}, order goods empty,  add explog abort. \n");
            return false;
        }

        //多个规格商品，合并记录
        $g_rows_end = array();
        foreach ($g_rows as $key => $val) {
            $g_rows_end[$val['goodsid']][] = $val;
        }

        $goodsid = '';
        $o_total = 0; //同goods_id 商品数量
        $all_cal_price = 0;
        foreach ($g_rows_end as $key => $val) {
            $goodsid = $key;
            $o_total = 0;
            $all_cal_price = 0;

            foreach ($val as $key2 => $val2) {
                $o_total += $val2['total']; 

                $tmp_cal_price = $val2['price'] - $val2['offerprice']/$val2['total'];
                if($tmp_cal_price>0){
                    $all_cal_price += $tmp_cal_price*$val2['total'];
                }
            }

            if($all_cal_price<=0) continue;

            $tmp_count = $this->calOrderCreditByPrice($all_cal_price, $exp_rule);
            

            if($tmp_count>0 && $o_total>0){
                $enable_num = $this->checkGoodsCreditEnableNum($member_id, $goodsid);

                if($enable_num<=0){//当月达到限额

                    //$credit_count += 0;

                    $log_data['goods_id'] = $goodsid;
                    $log_data['qty']      = $o_total;
                    $log_data['change']   = 0;
                    $log_data['min_memo'] = '月单品达限额，免记数值:'.$tmp_count;
                    pdo_insert('bj_qmxk_member_credit_log', $log_data);

                    //上级就不记录通知了

                }else{
                    $credit_count += $tmp_count;

                    $log_data['goods_id'] = $goodsid;
                    $log_data['qty']      = $o_total;
                    $log_data['change']   = $tmp_count;
                    pdo_insert('bj_qmxk_member_credit_log', $log_data);

                    if($parent_member_id > 0){
                        $log_data2['goods_id'] = $goodsid;
                        $log_data2['qty']      = $o_total;
                        $log_data2['change']   = $tmp_count;
                        $log_data2['min_memo'] = '';
                        pdo_insert('bj_qmxk_member_credit_log', $log_data2);
                    }
                }
                $match_num++;
            }
        }

        if($match_num==0){//没有匹配
            $this->addErrorLog("member_id: {$member_id}, ordersn: {$ordersn},  actid: {$actid}, order goods match num 0,  add explog abort. \n");
            return false;
        }

        $log_data  = null;
        $log_data2 = null;
        unset($log_data);
        unset($log_data2);

        return $credit_count;
    }
    /**
    * 根据上个加分记录扣分
    */
    private function subCreditByOrderGoods($member_id, $actid, $ordersn, $pactid, $parent_member_id){
        $credit_count = 0;
        $tmp_count = 0;
        $log_data = array(
            'member_id' => $member_id,
            'ordersn'   => $ordersn,
            'type'      => 1,
            'actid'     => $actid,
            'addtime'   => TIMESTAMP,
        );
        $log_data2 = array(
            'member_id' => $parent_member_id,
            'ordersn'   => $ordersn,
            'type'      => 1,
            'actid'     => $pactid,
            'addtime'   => TIMESTAMP,
        );

        $map[':ordersn']   = $ordersn;
        $map[':member_id'] = $member_id;
        $map[':actid']     = ConfigModel::CREDIT_BUY_ACTID;
        $sql = "/*weixiaogui*/ SELECT cl.id, cl.ordersn, cl.goods_id, cl.qty, cl.change, cl.addtime   
                FROM ims_bj_qmxk_member_credit_log cl FORCE INDEX(ordersn)
                WHERE ordersn=:ordersn AND member_id=:member_id AND type=1 AND actid=:actid ORDER BY addtime DESC";
        $log_rows = pdo_fetchall($sql, $map);

        $group_flag = ''; //批次标识，排除多次加分情况
        foreach ($log_rows as $key => $row) {
            if($key==0){
                $group_flag = $row['addtime'];
            }
            if($row['change']>0 && $group_flag==$row['addtime']){
                $tmp_count = $row['change']*-1;

                $log_data['goods_id'] = $row['goods_id'];
                $log_data['qty']      = $row['qty'];
                $log_data['change']   = $tmp_count;
                $log_data['min_memo'] = '上次加分id:'. $row['id'];
                pdo_insert('bj_qmxk_member_credit_log', $log_data);

                if($parent_member_id > 0){
                    $log_data2['goods_id'] = $row['goods_id'];
                    $log_data2['qty']      = $row['qty'];
                    $log_data2['change']   = $tmp_count;
                    $log_data2['min_memo'] = '';
                    pdo_insert('bj_qmxk_member_credit_log', $log_data2);
                }

                $credit_count += $tmp_count;
            }
        }

        return $credit_count;

    }

    /**
    * 获取某个用户某个商品加好评分的剩余次数
    */
    public function checkGoodsCreditEnableNum($member_id, $goodsid){
        list($y, $m) = explode('-', date('Y-m', TIMESTAMP));
        $month_start = mktime(0, 0, 0, $m, 1, $y);
        $month_end   = mktime(0, 0, 0, $m+1, 1, $y);

        $map = array();

        //#判断当月是否达到限额
        $map[':goodsid']    = $goodsid;
        $map[':member_id']  = $member_id;
        $map[':actid']      = ConfigModel::CREDIT_BUY_ACTID;
        $map[':m_start']    = $month_start;
        $map[':m_end']      = $month_end;
        $sql = "/*weixiaogui*/ SELECT COUNT(`qty`) FROM ims_bj_qmxk_member_credit_log WHERE goods_id=:goodsid AND member_id=:member_id AND type=1 AND actid=:actid AND addtime>=:m_start AND addtime<:m_end"; //不再乘以数量 SUM(`qty`)
        $total = pdo_fetchcolumn($sql, $map);

        return ConfigModel::CREDIT_GOODS_PER_MONTH - $total;
    }

    /**
    * 获取某个用户某个商品的总好评分
    */
    public function getUserGoodsCredit($member_id, $goodsid){
        $map = array();

        $map[':goodsid']    = $goodsid;
        $map[':member_id']  = $member_id;
        $sql = "/*weixiaogui*/ SELECT SUM(`change`) FROM ims_bj_qmxk_member_credit_log WHERE goods_id=:goodsid AND member_id=:member_id AND type=1";
        $total = pdo_fetchcolumn($sql, $map);

        return $total;
    }

    /**
    * 根据好评分计算v等级
    */
    public function calVipLevelByCredit($credit){
        $rule = ConfigModel::$CREDIT_LEVEL_RULE;
        foreach ($rule as $key => $val) {
            if($credit >= $val['min'] && $credit < $val['max']){
                return $val['level'];
                break;
            }
        }
        return 0;
    }

    /**
    * 根据金额计算好评分
    */
    private function calOrderCreditByPrice($price, $rule=null){
        $rule = $rule==null? ConfigModel::$CREDIT_BUY_RULE : $rule;
        foreach ($rule as $key => $val) {
            if($price >= $val['min'] && $price < $val['max']){
                return $val['num'];
                break;
            }
        }
        return 0;
    }

    /**
    * 记录异常日志
    */
    private function addErrorLog($msg){
        $dir = 'data/logs/';
        if(!is_dir($dir)){
            mkdir($dir, 0644, true);
        }
        error_log(date('Y-m-d H:i:s == ', TIMESTAMP) . $msg, 3, $dir.date('Ymd').'_credit.log'); 
    }

    /**
     * 取积分日志记录
     * @param  [type]  $member_id [description]
     * @param  [type]  $time      [description]
     * @param  integer $type      [description] 0积分 ，1分评分
     * @param  integer $pageIndex [description]
     * @param  integer $pageSize  [description]
     * @return [type]             [description]
     */
    public function getListLog($member_id,$time,$type = 0,$pageIndex=1,$pageSize=50){
        $count = $this->data_count($member_id,$time,$type);
        $page= 1;
        if($count>0){
            $page = ceil($count/$pageSize);
        }

        $data = array();
        if($pageIndex <= $page){
            $sql = '/*wangdingyi*/SELECT `id`,`member_id`,`actid`,`change`,`addtime`,`ordersn`, `min_memo` FROM ims_bj_qmxk_member_credit_log WHERE member_id=:member_id AND addtime>:addtime AND `type`=:type  ORDER BY `addtime` DESC ';
            if($type==0){
                $sql = '/*wangdingyi*/SELECT `id`,`member_id`,`actid`,`change`,`addtime`,`ordersn`, `min_memo` FROM ims_bj_qmxk_member_credit_log WHERE member_id=:member_id AND addtime>:addtime ORDER BY `addtime` DESC ';
            }
            $sql .= ' LIMIT ' .($pageIndex-1)*$pageSize.','.$pageSize;
            if($type==0){
                $data = pdo_fetchall($sql,array('member_id'=>$member_id,'addtime'=>$time));
            }else{
                $data = pdo_fetchall($sql,array('member_id'=>$member_id,'addtime'=>$time,'type'=>$type));
            }
            
            if(empty($data)){
                $data = array();
            }else{
                foreach ($data as $k=>$v){
                    $data[$k]['addtime'] = date('Y-m-d H:i',$v['addtime']);
                    $actData = ConfigModel::getInstance()->getCreditSetArr($v['actid']);
                    if($actData){
                         $data[$k]['name'] = $actData['name'];
                    }else{
                        $data[$k]['name'] = '';
                    }

                    if($v['change']==0){
                        if(strpos($v['min_memo'], '月单品达限额')!==false) $data[$k]['name'] = '月单品达限额';
                    }

                    unset($data[$k]['member_id']);
                    unset($data[$k]['actid']);
                    unset($data[$k]['min_memo']);
                }
            }
        }
        $results = array();
        $results['count'] = $count;
        $results['page'] = $page;
        $results['pageindex'] = $pageIndex;
        $results['pagesize'] = $pageSize;
        $results['data'] = $data;
        return $results;
    }

    /**
     * 获取总条数
     * @param int $member_id
     */
    private function data_count($member_id,$time,$type){
        if($type==0){
            $sql = '/*wangdingyi*/SELECT count(*) FROM ims_bj_qmxk_member_credit_log WHERE member_id=:member_id AND addtime>:addtime';
            return pdo_fetchcolumn($sql,array('member_id'=>$member_id,'addtime'=>$time));
        }else{
            $sql = '/*wangdingyi*/SELECT count(*) FROM ims_bj_qmxk_member_credit_log WHERE member_id=:member_id AND addtime>:addtime AND `type`=:type';
            return pdo_fetchcolumn($sql,array('member_id'=>$member_id,'addtime'=>$time,'type'=>$type));
        }
    }
    public function getHaoPing($member_id){
        $sql = 'SELECT `order_credit_count` FROM ims_bj_qmxk_member_info WHERE member_id=:member_id limit 1';
        $res = pdo_fetchcolumn($sql,array('member_id'=>$member_id));
        return $res ? $res : 0;
    }
    public function getJiFen($member_id) {
        $sql = '/*wangdingyi*/SELECT `credit1`, `credit_used` FROM ims_bj_qmxk_member_info where member_id=:member_id LIMIT 1';
        $jifen = pdo_fetch($sql,array('member_id'=>$member_id));

        $date=date('Y-m-d');//当前日期
        $first=1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $w=date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $nowstart = strtotime("$date -".($w ? $w - $first : 6).' days');
        $now_start  =date('Y-m-d',strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $now_end    =strtotime("$now_start +7 days");  //本周结束日期
        $last_start =strtotime("$now_start - 7 days"); //上周开始日期
        $last_end   =$nowstart; //上周结束日期=本周开始时间-1

        $sql = '/*wangdingyi*/SELECT SUM(`change`) FROM ims_bj_qmxk_member_credit_log WHERE member_id=:member_id AND addtime>=:start_time AND addtime<:end_time AND actid <> 614';

        $benzhou = pdo_fetchcolumn($sql,array('member_id'=>$member_id,'start_time'=>$nowstart,'end_time'=>$now_end));
        $shangzhou = pdo_fetchcolumn($sql,array('member_id'=>$member_id,'start_time'=>$last_start,'end_time'=>$last_end));

        $benzhou = $benzhou?$benzhou:0;
        $shangzhou = $shangzhou?$shangzhou:0;

        if ($jifen['credit_used'] != 0) {
            $credit_used = 0 - $jifen['credit_used'];
        } else {
            $credit_used = 0;
        }

        return array('jifen'=>$jifen['credit1'] + $credit_used,'benzhou'=>$benzhou,'shangzhou'=>$shangzhou, 'used_points' => $credit_used);
    }
}