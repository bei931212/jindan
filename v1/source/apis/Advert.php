<?php

class Advert extends Api
{

    /**
     * sign
     *  个人中心=GRZX 我的订单=MYORDER 动力指数=DLZS1 优惠券=YHJ 推荐订单=TJDD 小店订单上=XDDD1 小店订单中=XDDD2  小店订单下=XDDD3 签到头部QD1 签到中部QD2
     */
    public function index()
    {
        global $_W;
        $sign = $_GET['sign'];
        if (empty($sign))
        {
            return self::responseError(-1,'缺少标记');
        }
        $status=2;
        $enry=pdo_fetch("SELECT adv_id,adv_w,adv_h FROM ".tablename('bj_qmxk_advert_container')." WHERE  adv_sign='{$sign}' AND adv_status&$status ");

        if (empty($enry))
        {
            return self::responseError(-1,'无此广告位');
        }
        $list= pdo_fetchall("SELECT adv_cimg as img,adv_ctype as type, adv_citemId as itemId  FROM ".tablename('bj_qmxk_advert_container_content').  " WHERE  adv_id={$enry['adv_id']} AND adv_cstatus=1");

        if (empty($list))
        {
             return self::responseError(-1,'此广告位还未添加广告');
        }
        //$w_h="_".$enry['adv_w']."x".$enry['adv_h'].".jpg";
        foreach ($list as  $key=>$entity)
        {
            $entity['img']=$_W['attachurl'].$entity['img'];
            $list[$key]=$entity;
        }

        return self::responseOk($list);

    }


}
