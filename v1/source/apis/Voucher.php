<?php

/**
 * Created by PhpStorm.
 * User: huwh
 * Date: 2016/11/2
 * Time: 9:33
 */
class Voucher  extends  Api
{

    //登陆验证
    function __construct() {
        global $_W;
        require IA_ROOT.'/source/apis/User.php';
        if(!User::checklogin()) {
            return self::responseError(1000, '尚未登陆。');
        }

        if(empty($_W['member_id'])) {
            return self::responseError(1001, '尚未登陆。');
        }

    }
    public  function  getVoucher()
    {
        global $_W;
        $result = array('message' => '领取成功');
        //优惠卷ID 会员ID
        $vid=$_POST['vid'];
        $id =$_W['member_id'];

        //判断登陆 有member_id
        if (!$id||!is_numeric($id))
        {
            return self::responseError(1,'尚未登陆');
        }

        //验证优惠卷的有效性
        if (!$vid||!is_numeric($vid))
        {
            return self::responseError(1,'优惠券ID有误');
        }
        $v_entity=pdo_fetch("select `count`,id,title,remark,start_time,end_time,limit_count,price,discount,use_count,type,is_top,pay from ".tablename('bj_qmxk_voucher')." where id={$vid}");
        if (!$v_entity)
        {
           // $result = array('result' => 1,'message' => '此优惠卷不存在');
            return self::responseError(1,'此优惠卷不存在');
        }

        if ($v_entity['end_time']<time())
        {
          //  $result = array('result' => 1,'message' => '此优惠卷已过期');
            return self::responseError(1,'此优惠卷已过期');
        }
        if ($v_entity['count']-$v_entity['use_count']<=0)
        {
            //$result = array('result' => 1,'message' => '此优惠卷已发放完毕');
            return self::responseError(1,'此优惠卷已发放完毕');
        }
        if ($v_entity['limit_count']<=0)
        {
            //$result = array('result' => 1,'message' => '此优惠卷不可领取');
            return self::responseError(1,'此优惠卷不可领取');
            // message('此优惠卷不可领取', referer(), 'error');
        }

        //验证会员是否可领取此优惠卷

        $vu_entity=pdo_fetch("select count(id) as count from ".tablename('bj_qmxk_voucher_user')."  where member_id={$id} and voucher_id={$vid}");
        if ($vu_entity['count']>=$v_entity['limit_count'])
        {
           // $result = array('result' => 1,'message' => '此优惠卷最多可领'.$v_entity['limit_count'].'张');
            return self::responseError(1,'此优惠卷最多可领'.$v_entity['limit_count'].'张');

        }
        //验证通过，生成优惠卷信息并减优惠卷数量  ，返回成功

        $data=array('member_id'=>$id,
            'voucher_id'=>$vid,
            'status'=>0,
            'use_time'=>$v_entity['start_time'],
            'end_time'=>$v_entity['end_time'],
            'price'=>$v_entity['price'],
            'discount'=>$v_entity['discount'],
            'type'=>$v_entity['type'],
            'is_top'=>$v_entity['is_top'],
            'title'=>$v_entity['title'],
            'remark'=>$v_entity['remark'],
            'pay'=>$v_entity['pay'],
            'create_time'=>TIMESTAMP);

        try{
            pdo_begin();
            pdo_insert('bj_qmxk_voucher_user',$data);
            pdo_query("UPDATE `ims_bj_qmxk_voucher` SET `use_count`=`use_count`+1 WHERE `id`={$vid}");
            pdo_commit();
        }catch (Exception $e){
            pdo_rollback();//回滚事务
           // $result = array('result' => 1,'message' => '操作失败，请重试！');
            return self::responseError(1,'操作失败，请重试!');
        }
        return self::responseOk($result);
       // die(json_encode($result));
    }

    //领券中心
    public  function  getVlist()
    {
       // global $_W;

        $count = intval($_GET['count']);
        $page = max(1, intval($_GET['page']));

        $count = ($count && $count <= 40) ? $count : 20;
        $limit = ($page-1) * $count.', '.$count;

        $sql=" FROM ".tablename('bj_qmxk_voucher')." WHERE is_top='1' AND status='1' AND end_time>".time();
        $itemCount=pdo_fetch("SELECT COUNT(ID) AS  itemCount ".$sql);
        $lists=pdo_fetchall("SELECT *".$sql." LIMIT {$limit}");

        foreach ($lists as $key=>$value)
        {
            $lists[$key]['discount']=intval($value['discount']);
            $lists[$key]['price']=intval($value['price']);
            $lists[$key]['start_time']= date('Y-m-d',$value['start_time']) ;
            $lists[$key]['end_time']= date('Y-m-d',$value['end_time']) ;
            $lists[$key]['create_time']= date('Y-m-d H:i',$value['create_time']) ;
            $lists[$key]['flag']=0;
            if ((time()+3600*24)>$value['end_time'])
            {
                $lists[$key]['flag']=1;
            }
        }

        $result['itemCount'] = intval($itemCount['itemCount']);//总数
        $result['allPage'] = ceil($itemCount['itemCount']/$count);////总页数
        $result['page'] = $page;//当前页数
        $result['count'] = $count;//每页数量
        $result['items']		= $lists;

        return self::responseOk($result);
    }
}