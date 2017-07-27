<?php
class MemberModel{
    private static $_instance = NULL;
    
    /**
     * @return MemberModel
     */
    final public static function getInstance()
    {
        if (!isset(self::$_instance) || !self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }
    
    /**
    * 检查sellData是否存在，不存在则创建
    */
    public function getSellData($member_id, $field='*'){
        $sql = "SELECT {$field} FROM ". tablename('bj_qmxk_member_selldata') ." WHERE `member_id`=:member_id";
        $row = pdo_fetch($sql, array(':member_id'=>$member_id));
        if(empty($row)){
            $mid = pdo_fetchcolumn("SELECT id FROM ".tablename('bj_qmxk_member')." WHERE member_id={$member_id}");

            $data = array();
            $data['member_id']  = $member_id;
			$data['mid']  = $mid;
            pdo_insert('bj_qmxk_member_selldata', $data);

            $row = pdo_fetch($sql, array(':member_id'=>$member_id));
        }

        return $row;
    }

    /**
    * 获取小店VIP会员
    */
    public function getShopVipUsers($member_id, $mi_fields="mi.member_id"){
        $min_level_credit = ConfigModel::getInstance()->getMinVipLevelCredit(1);
        $rows = array();
        if($min_level_credit!==false){
            $member_ids = $this->getShopMemberIds($member_id);
            if(!empty($member_ids)){
                $rows = pdo_fetchall("/*weixiaogui*/ SELECT {$mi_fields} FROM ims_bj_qmxk_member_info mi WHERE mi.member_id IN(". implode(',', $member_ids) .") AND mi.order_credit_count >= {$min_level_credit}");
            }
            
        }

        return $rows;
    }

    /**
    * 获取小店member_ids
    */
    public function getShopMemberIds($member_id){
        $member_ids = array();
        $m_rows = pdo_fetchall("/*weixiaogui*/ SELECT id FROM ims_bj_qmxk_member_auth ma WHERE ma.sharemaid = '{$member_id}'");
        foreach ($m_rows as $key => $val) {
            $member_ids[] = $val['id'];
        }

        return $member_ids;
    }

    /**
    * 获取小店VIP会员数量
    */
    public function getShopVipUsersNum($member_id){
        $rows = $this->getShopVipUsers($member_id, 'COUNT(mi.member_id) AS num');
        if(!empty($rows)){
            return $rows[0]['num'];
        }
        return 0;
    }

    /**
    * 获取推荐人member_id
    */
    public function getParentMemberId($member_id){
        $sharemaid = pdo_fetchcolumn("SELECT sharemaid FROM ".tablename('bj_qmxk_member_auth')." WHERE id=:id", array(':id'=>intval($member_id) ));
        return intval($sharemaid);
    }

}