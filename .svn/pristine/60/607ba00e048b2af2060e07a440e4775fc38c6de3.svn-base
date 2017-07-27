<?php

/**
 * Created by PhpStorm.
 * User: huwh
 * Date: 2016/10/31
 * Time: 14:41
 * 优惠券相关
 */
class Voucher extends My
{
    //我的优惠券列表
    public  function  vouList()
    {
        global $_W;
        $count = intval($_GET['count']);
        $page = max(1, intval($_GET['page']));

        $count = ($count && $count <= 40) ? $count : 20;
        $limit = ($page-1) * $count.', '.$count;


        $voucher_state=intval($_GET['vstatus']);//用户优惠券列表状态，0代表未使用，1代表已使用，2代表已过期

        $where='';
        $sql='SELECT gg.is_top,gg.id,gg.status,gg.use_time,gg.end_time,gg.price,gg.discount,gg.type,gg.title,gg.remark,g.start_time,g.sellerid FROM ' . tablename('bj_qmxk_voucher_user') . " AS gg LEFT JOIN " . tablename('bj_qmxk_voucher') . " AS g ON g.id=gg.voucher_id WHERE  gg.member_id='{$_W['member_id']}'";

        if($voucher_state==2){
            $where.=' AND gg.status = 2 AND gg.end_time < '.time().' ORDER BY gg.id DESC LIMIT 30';
        }else if($voucher_state==0){
            $where.=' AND gg.status = 0 AND gg.end_time > '.time().' ORDER BY gg.id DESC';
        }
        else{
            $where.=' AND gg.status = '.$voucher_state.' ORDER BY gg.id DESC LIMIT 30';
        }
        $sql.=$where;
        $lists=pdo_fetchall($sql);

        $rlist_key="VOUCHER_COUNT_".$_W['member_id'];
        $rlist = $_W['mc']->get($rlist_key);

        if($rlist) {
            $result = unserialize($rlist);
        } else {
            $count=pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('bj_qmxk_voucher_user') ."  WHERE  member_id='{$_W['member_id']}' AND status = 0 AND  end_time > ".time()." LIMIT 30 " );
            $count1=pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('bj_qmxk_voucher_user') ."  WHERE  member_id='{$_W['member_id']}' AND status = 1" );
            $count2=pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename('bj_qmxk_voucher_user') ."  WHERE  member_id='{$_W['member_id']}' AND status = 2 AND  end_time < ".time()." LIMIT 30");
            $result = array('unused'=>$count,'used'=>$count1,'overdue'=>$count2);
            $_W['mc']->set($rlist_key, serialize($result), MEMCACHE_COMPRESSED, 600);

        }
      //  $result['itemCount']	= intval($total);
      //  $result['allPage']		= ceil($total/$count);
      //  $result['page']			= $page;
      //  $result['count']		= $count;
       foreach ($lists as $key=>$value)
       {
           if ($value['end_time']<time())//过期了 更新状态
           {
               pdo_update('bj_qmxk_voucher_user',array('status'=>2),array('id'=>$value['id']));
            //   unset($lists[$key]);
           }
           $lists[$key]['discount']=intval($value['discount']);
           $lists[$key]['price']=intval($value['price']);
           $lists[$key]['use_time']= date('Y-m-d',$value['use_time']) ;
           $lists[$key]['end_time']= date('Y-m-d',$value['end_time']) ;
           $lists[$key]['create_time']= date('Y-m-d H:i',$value['create_time']) ;
           $lists[$key]['flag']=0;
		   if($value['status']==0){
	           if ((time()+3600*24)>$value['end_time'])
	           {
	               $lists[$key]['flag']=1;
	           }
           }
           $lists[$key]['remark']=utf_substr(20,$value['remark']) ;

           if($value['is_top']==1){
               $lists[$key]['sellername']='顺联动力';
           }else{
               $seller=pdo_fetch("SELECT seller_name FROM ims_members_profile where uid='{$value['sellerid']}'");
               if($seller){
                   $lists[$key]['sellername']=isset($seller['seller_name']) ? utf_substr( 8,$seller['seller_name']):"顺联动力";
               }else{
                   $lists[$key]['sellername']='顺联动力';
               }
           }
       }
        $result['items']		= $lists;
        return self::responseOk($result);
    }


}