<?php
class ChannelFrontModel{
    private static $_instance = NULL;

    /**
     * @return ChannelFrontModel
     */
    final public static function getInstance()
    {
        if (!isset(self::$_instance) || !self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /*获取所用的频道
     * @params $ifall true|false  是否获取全部的频道
     * @return $data array    
     */
    public function getChannelList($isall)
    {
        if($isall){
            $sql = "/*liangxiang*/SELECT * FROM `ims_bj_qmxk_channel_list` ORDER BY channel_order ASC";
        }else{
            $sql = "/*liangxiang*/SELECT id,channel_name,cid FROM `ims_bj_qmxk_channel_list` WHERE `status`= 1 ORDER BY channel_order ASC";
        } 
        return pdo_fetchall($sql);
    }
    
    public function getChannelOne($channel_id)
    {
        if($channel_id){
            $sql = "/*liangxiang*/SELECT id,channel_name,cid FROM `ims_bj_qmxk_channel_list` WHERE `id`= {$channel_id}";
        }
        return pdo_fetch($sql);
    }

    /*
     * 获取所有组件
     * @params $channel_id 频道ID
     * @params $ifall bool
     * @return $data array    
     */
    public function getModuleList($channel_id,$ifall)
    {
        //$param = func_get_args();
        if($ifall){
            $sql = "/*liangxiang*/SELECT * FROM `ims_bj_qmxk_channel_module` WHERE channel_id = {$channel_id} ORDER BY id ASC";
        }else{
            $sql = "/*liangxiang*/SELECT id,module_title,op FROM `ims_bj_qmxk_channel_module` WHERE channel_id = {$channel_id} AND status = 1 ORDER BY id ASC";
        }
        return pdo_fetchall($sql);    
    }

    /*
     * 获取内容
     * @params $channel_id 频道ID
     * @params $ifall bool
     * @return $data array    
     */
    public function getContent($module_id,$ifall)
    {
        if($ifall){
            $sql = "/*liangxiang*/SELECT * FROM  `ims_bj_qmxk_channel_module_content`  WHERE module_id = {$module_id} ORDER BY c_order ASC";
        }else{
            $sql = "/*liangxiang*/SELECT * FROM  `ims_bj_qmxk_channel_module_content`  WHERE module_id = {$module_id} AND status = 1 ORDER BY c_order ASC";
        }
  
        return pdo_fetchall($sql);
    }

}
/*---频道拉列表
 * ChannelFrontModel::getInstance()->getChannelList(true); true查询全部  false查询 id和channel_name
 * ---组件列表
 * ChannelFrontModel::getInstance()->getModuleList($channel_id,$ifall); 频道ID  $ifall true 查询全部 false查询 id和module_title
 * ----获取内容
 * ChannelFrontModel::getInstance()->getContent($module_id,$ifall); 组件ID   $ifall true 查全部
 *  
 */
