<?php
/**
 * 大赛
 * @author wander
 *
 */
class FightModel
{
    private static $_instance = NULL;

    /**
     * 单例
     * @return FightModel
     */
    final public static function getInstance()
    {
        if (! isset(self::$_instance) || ! self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * 获取用户比赛数据
     * @param integer $member_id
     * @param number $fight_id
     * @return mixed
     */
    public function get_user_data($member_id,$fight_id=1){
        return pdo_fetch("SELECT * FROM ims_bj_qmxk_fight_data WHERE member_id=:member_id AND fight_id=:fight_id",array('member_id'=>$member_id,'fight_id'=>$fight_id));
    }
    
    /**
     * 获取用户信息
     * @param int $member_id
     * @param number $fight_id
     */
    public function get_user($member_id,$fight_id=1){
        $userRow = pdo_fetch("SELECT m.* FROM `ims_bj_qmxk_fight_member` m WHERE m.member_id=:member_id AND m.fight_id=:fight_id", array('member_id'=>$member_id,'fight_id'=>$fight_id));
        if (!empty($userRow)) {
            $areaName = pdo_fetchcolumn('SELECT area_name from ims_bj_qmxk_fight_area WHERE `id`=:area_id AND `fight_id`=:fight_id', array('area_id' => $userRow['area_id'], 'fight_id' =>$userRow['fight_id']));
            $userRow['area_name'] = $areaName;
        }

        return $userRow;
    }
    
    /**
     * 获取用户VIP_LEVEL
     * @param integer $member_id
     */
    public function get_user_level($member_id){
        $info = pdo_fetch("SELECT * FROM ims_bj_qmxk_member_selldata WHERE member_id=:member_id",array('member_id'=>$member_id));
        if(!empty($info)){
            return $info['vip_level'];
        }
        return 0;
    }
    
    /**
     * 获取大赛设置信息
     * @param number $fight_id
     * @return mixed
     */
    public function get_fight_info($fight_id=1){
       return pdo_fetch("SELECT * FROM ims_bj_qmxk_fight WHERE `id`='{$fight_id}' LIMIT 1");
    }
    
    /**
     * 获取大赛区域信息
     * @param number $fight_id
     */
    public function get_fight_area($fight_id=1){
        return pdo_fetchall("SELECT * FROM ims_bj_qmxk_fight_area WHERE `fight_id`='{$fight_id}'");
    }
    
    /**
     * 获取大赛广告
     * @param int $fight_id 大赛ID
     */
    public function get_ad($attach_url,$fight_id=1)
    {
        $data = pdo_fetchall("SELECT area,img,type,itemId,title,sub_title,displayorder FROM `ims_bj_qmxk_fight_ad` WHERE status='1' AND fight_id='{$fight_id}' ORDER BY displayorder ASC,id DESC");
        $list = array('pic'=>array(),'pic2'=>array(),'top'=>array(),'character'=>array());
        foreach ($data as $value) {
            $value['img'] = $attach_url.$value['img'].'?75';
            
            switch ($value['area']){
                case 'pic':
                    $list['pic'][] = $value;
                    break;
                case 'pic2':
                    $list['pic2'][] = $value;
                    break;
                case 'top':
                    $list['top'][] = $value;
                    break;
                case 'character':
                    $list['character'][] = $value;
                    break;
            }
        }
        foreach ($list as $key=>$val){
            if(is_array($val)){
                $sort = $this->my_sort($val, 'displayorder');
                foreach ($sort as $k=>$new){
                    unset($new['displayorder']);
                    $sort[$k] = $new;
                }
                $list[$key] = $sort;
                unset($sort);
            }
        }
        return $list;
    }
    
    public function datalist($area_id,$pageIndex=1,$pageSize=20){

        $count = $this->data_count($area_id);
        if($count>100){
            $count = 100;
        }
        $page= 1;
        if($count>0){
            $page = ceil($count/$pageSize);
        }
        $data = array();
        if($pageIndex <= $page){
            $sql = 'SELECT
                    	m.id,m.user_name,d.fight_id,d.member_id,m.area_id,i.avatar,i.nickname,d.rank,m.fight_content
                    FROM
                    	ims_bj_qmxk_fight_data d
                    INNER JOIN ims_bj_qmxk_fight_member m ON d.member_id = m.member_id
                    LEFT JOIN ims_bj_qmxk_member_info i ON m.member_id=i.member_id
                    WHERE d.area_id=:area_id AND d.rank>0
                    ORDER BY d.rank ASC LIMIT ';
            $sql .= ($pageIndex-1)*$pageSize.','.$pageSize;
            $data = pdo_fetchall($sql,array('area_id'=>$area_id));
            if(empty($data)){
                $data = array();
            }else{
                foreach ($data as $k=>$v){
                    $data[$k]['nickname'] = $v['user_name'];
                }
            }
        }
        $results = array();
        $area_name = pdo_fetchcolumn('SELECT area_name from ims_bj_qmxk_fight_area WHERE `id`=:area_id',array('area_id'=>$area_id));
        $results['area_name'] = $area_name;
        $results['count'] = $count;
        $results['page'] = $page;
        $results['pageindex'] = $pageIndex;
        $results['pagesize'] = $pageSize;
        $results['data'] = $data;
        return $results;
    }
    /**
     * 获取总条数
     * @param int $area_id
     */
    private function data_count($area_id){
        $sql = "SELECT COUNT(1) FROM ims_bj_qmxk_fight_member WHERE area_id=:area_id AND fight_id=1";
        return pdo_fetchcolumn($sql,array('area_id'=>$area_id));
    }
    
    
    /**
     * 数组排序
     * @param unknown $arrays
     * @param unknown $sort_key
     * @param string $sort_order
     * @param string $sort_type
     */
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