<?php

class Share extends Api {

    public function url(){
        global $_W;
        if($_W['config']['setting']['share_close_status'] == 2){
            return self::responseError(400,'截图分享已关闭');
        }

        if($_W['config']['setting']['share_status'] == 2){
            $url = $_W['config']['download_url'];
            return self::responseOk(array('url' => $url));
        }

        if($_W['config']['cookie']['domain'] == '.api-test.shunliandongli.com'){
            $host = 'http://wx-test.shunliandongli.com';
        }else{
            $host = 'https://wx.shunliandongli.com';
        }

        $type_url = array(
            '1' => array('url' => '/mobile.php?act=module&name=bj_qmxk&do=list'),  //首页
            '2' => array('url' => '/special/[special_id]',
                    'param' => array('field'=>'special_id','reg'=>'\d+'),
                    'table'=>'special','where'=>'id'),   //专题

            '3' => array('url' => '/list/p[pid]',
                    'param' => array( 'field'=>'pid','reg'=>'\d+'),
                     'table'=>''),   //频道页

            '4' => array('url' => '/list/c[cid]',
                    'param' => array( 'field'=>'cid','reg'=> '\d+'),
                     'table'=>'bj_qmxk_category','where'=>'id'),   //分类

            '5' => array('url' => '/goods/[goods_id]',
                    'param' => array('field'=>'goods_id' ,'reg'=> '\d+'),
                    'table'=>'bj_qmxk_goods','where'=>'id'), //商品

            '6' => array('url' => '/mobile.php?act=module&name=bj_qmxk&do=list_tuan'),      //拼团

            '7' => array('url' => '/shop/[shop_id]',
                    'param' => array( 'field'=>'shop_id', 'reg'=>'\d+'),
                    'table'=>''),  //商家页面

            '8' => array(
                'url' => '/list',   //分类首页
            ),
            '9' => array(
                'url' => '/myshop?chn_id=[chn_id]',   //主频道页
                'param' => array( 'field'=>'chn_id', 'reg'=>'\d+'),
                'table'=>''
            )
        );

        $member_id = isset($_COOKIE['pin']) ? compute_id($_COOKIE['pin']) :10;
        if(!$member_id){
            return self::responseError(400, 'Parameter [member_id] is missing.');
        }
        //$mid = $member_id;
        $user = pdo_fetch("SELECT mid FROM `ims_bj_qmxk_member_auth` WHERE id='{$member_id}'");
        if(!$user){
            return self::responseError(400, '用户不存在.');
        }
        $mid = $user['mid'];
        $type_id = intval($_GET['type']);
        if(!$type_id || !$type_url[$type_id]){
            return self::responseOk(array('url' => $host.$type_url[1]['url']));
        }
        $url = $type_url[$type_id]['url'];
        $field = $type_url[$type_id]['param']['field'];
        $reg = $type_url[$type_id]['param']['reg'];
        if(!empty($field) && !$_GET[$field]){
            return self::responseOk(array('url' => $host.$type_url[1]['url']));
        }
        //判断参数是否正确
        preg_match('/'.$reg.'/', $_GET[$field], $matches);

        if(!$matches){
            return self::responseOk(array('url' => $host.$type_url[1]['url']));
        }

        /**
         * 验证数据是否正确
         */
        if($type_url[$type_id]['table']){
            $where = $type_url[$type_id]['where'];
            $data = pdo_fetch('SELECT * FROM ' . tablename($type_url[$type_id]['table']) . ' WHERE  '.$where.' = :'.$field, array(':'.$field => $_GET[$field]));
            if(!$data){
                return self::responseOk(array('url' => $host.$type_url[1]['url']));
            }

            if($field == 'cid'){
                if($data['parentid'] == 0){
                    $url = $type_url[3]['url'];
                    $_GET[$type_url[3]['param']['field']] = $_GET[$field];
                    $field = $type_url[3]['param']['field'];
                }
            }
        }
        $url = preg_replace('/\['.$field.'\]/',$_GET[$field],$url);

        if(strpos($url,'?') !== false){
            $url .= '&mid='.$mid;
        }else{
            $url .= '?mid='.$mid;
        }
        $url = $host.$url;

        return self::responseOk(array('url' => $url));
    }
}