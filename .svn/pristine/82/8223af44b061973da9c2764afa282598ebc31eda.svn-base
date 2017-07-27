<?php

class Comment extends My {

	/**
	* 对商品评价
	*/
	public function goodsCmts(){
		global $_W;

		$count 			 = intval(input('get.count'));
		$page 			 = max(1, intval(input('get.page')));
		$usePage 		 = input('get.usePage');
		$useCountdata 	 = input('get.useCountdata');

		$count = ($count && $count <= 40) ? $count : 10;

		$where = array();
		$where[] = "gc.member_id='{$_W['member_id']}'";
		// $where[] = "gc.status>=0"; 用户的评价不用隐藏
		$where = implode(' AND ', $where);

		$limit = ' LIMIT ';
		if($usePage == 'yes') {
			$limit .= ($page-1)*$count.','.$count;
		} else {
			$limit .= $count;
		}
		
        $tb_gc    = tablename('bj_qmxk_goods_comment');
		$tb_gcb   = tablename('bj_qmxk_goods_comment_body');
		$tb_g 	  = tablename('bj_qmxk_goods');
        $tb_o     = tablename('bj_qmxk_order');
        $tb_og    = tablename('bj_qmxk_order_goods');
		$comments = pdo_fetchall("SELECT tp_gc.id, tp_gc.goods_id AS goodsId, tp_gc.addtime, tp_gc.star_level, tp_gc.ordersn, tp_gc.status, tp_gc.buytime, ".
            "gcb.content, gcb.reply, gcb.reply_time, gcb.pics, ".
            "g.title, g.thumb ,".
            "og.price ".
            "FROM (SELECT * ".
			"FROM {$tb_gc} AS gc WHERE {$where} ORDER BY gc.addtime DESC {$limit}) AS tp_gc ".
            "LEFT JOIN {$tb_gcb} AS gcb ON gcb.comment_id=tp_gc.id ".
			"LEFT JOIN {$tb_g} AS g ON g.id=tp_gc.goods_id ".
            "LEFT JOIN {$tb_o} AS o ON o.ordersn=tp_gc.ordersn ".
            "LEFT JOIN {$tb_og} AS og ON og.orderid=o.id AND og.goodsid=tp_gc.goods_id AND og.optionid=tp_gc.optionid".
			"");

        // 需要对分页再排序
        function sort_addtime($a, $b){
            if($a['addtime']==$b['addtime']){
                return 0;
            }
            return ($a['addtime'] < $b['addtime']) ? 1 : -1;
        }
        usort($comments, 'sort_addtime');

		$comments_end = array();
		foreach ($comments as $key => $val) {
			$val['enable_edit'] = CommentModel::getInstance()->isEnableEdit($val['star_level'], $val['addtime'])? 'yes' : 'no';

			$val['addtime'] = time_tran($val['addtime']);
			$val['buytime'] = date('Y-m-d', $val['buytime']);
			$val['thumb'] 	= $val['thumb'] ? $_W['attachurl'].$val['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';

            if($val['reply'] && $val['reply_time']>0){
                $val['reply_time'] = time_tran($val['reply_time']);
            }else{
                $val['reply_time'] = '';
            }

            $pics = !empty($val['pics'])? explode(',', $val['pics']) : array();
            foreach ($pics as $pk => $pv){
                if($pv){
                    $pics[$pk] = $_W['attachurl'].$pv.'_500x500.jpg';
                }
            }
            $val['pics'] = $pics;
			
			$comments_end[] = $val;
		}


		$results = array();
		if($useCountdata == 'yes'){
			//统计数据
			$count_data = CommentModel::getInstance()->getCommentCounts($_W['member_id']);
			$results['count_data'] = $count_data;
		}

		if($usePage == 'yes') {
			$itemCount 				= pdo_fetchcolumn("SELECT COUNT(*) FROM {$tb_gc} AS gc WHERE {$where}");
			$itemCount 				= intval($itemCount);
			$results['itemCount'] 	= $itemCount;
			$results['allPage'] 	= ceil($results['itemCount']/$count);
			$results['page'] 		= $page;
			$results['count'] 		= $count;
		}
		$results['comments'] = $comments_end;

		return self::responseOk($results);
	}

	/**
	* 收到的评价
	*/
	public function receiveCmts(){
		global $_W;

		$count 			 = intval(input('get.count'));
		$page 			 = max(1, intval(input('get.page')));
		$usePage 		 = input('get.usePage');
		$useCountdata 	 = input('get.useCountdata');

		$count = ($count && $count <= 40) ? $count : 10;

		$where = array();
		$where[] = "member_id='{$_W['member_id']}'";
		$where = implode(' AND ', $where);

		$limit = ' LIMIT ';
		if($usePage == 'yes') {
			$limit .= ($page-1)*$count.','.$count;
		} else {
			$limit .= $count;
		}
		
		$tb_rc 	  = tablename('bj_qmxk_member_receive_comment');
		$tb_o 	  = tablename('bj_qmxk_order');
		$tb_og 	  = tablename('bj_qmxk_order_goods');
		$tb_g 	  = tablename('bj_qmxk_goods');
		$comments = pdo_fetchall("SELECT tp_rc.*, ".
					"o.id AS order_id, o.createtime, ".
					"og.optionname, og.price, ".
					"g.id AS goodsId, g.title, g.thumb FROM ".
					"(SELECT ordersn, star_level, addtime FROM {$tb_rc} WHERE {$where} ORDER BY addtime DESC {$limit}) AS tp_rc ".
					"LEFT JOIN {$tb_o} AS o ON o.ordersn=tp_rc.ordersn ".
					"LEFT JOIN {$tb_og} AS og ON og.orderid=o.id ".
					"LEFT JOIN {$tb_g} AS g ON g.id=og.goodsid");

        // 需要对分页再排序
        function sort_addtime($a, $b){
            if($a['addtime']==$b['addtime']){
                return 0;
            }
            return ($a['addtime'] < $b['addtime']) ? 1 : -1;
        }
        usort($comments, 'sort_addtime');

		$comments_end = array();
        $order_ids = array();
		foreach ($comments as $key => $val) {
			if(in_array($val['order_id'], $order_ids)) continue; //只取一条
            $val['addtime'] = time_tran($val['addtime']);
			$val['buytime'] = date('Y-m-d', $val['createtime']);
			$val['thumb'] 	= $val['thumb'] ? $_W['attachurl'].$val['thumb'].'_300x300.jpg' : 'http://statics.sldl.fcmsite.com/empty.gif';
			
			unset($val['createtime']);
			$comments_end[] = $val;
            $order_ids[]    = $val['order_id'];
		}

		$results = array();
		if($useCountdata == 'yes'){
			//统计数据
			$count_data = CommentModel::getInstance()->getCommentCounts($_W['member_id']);
			$results['count_data'] = $count_data;
		}

		if($usePage == 'yes') {
			$itemCount 				= pdo_fetchcolumn("SELECT COUNT(*) FROM {$tb_rc} WHERE {$where}");
			$itemCount 				= intval($itemCount);
			$results['itemCount'] 	= $itemCount;
			$results['allPage'] 	= ceil($results['itemCount']/$count);
			$results['page'] 		= $page;
			$results['count'] 		= $count;
		}
		$results['comments'] = $comments_end;

		return self::responseOk($results);
	}

	/**
	* 上传评论图片
	*/
	public function uploadCmtPic(){
        global $_W;

        // 订单判断
        $ordersn 	= input('get.ordersn');
        $member_id	= $_W['member_id'];


        $row_order = array();
		if(empty($ordersn)){
            return self::responseError(22101, '抱歉，订单编号不能为空。');
        }

        $row_order = pdo_fetch("SELECT o.id AS orderId, o.member_id, o.sellerid, o.createtime ".
                "FROM `ims_bj_qmxk_order` o ".
                "WHERE ordersn=:ordersn", array(':ordersn'=>$ordersn));

        if(empty($row_order) || $row_order['member_id'] != $member_id) {
            return self::responseError(22140, '抱歉，该订单不存在。');
        }

        //图片上传保存
        $cmt_pic = $_FILES['cmt_pic'];

        if(empty($_FILES['cmt_pic']['tmp_name'])) {
            return self::responseError(22107, '请上传评论照片.');
        }

        $cmt_pic = self::file_upload($cmt_pic);
        if(is_error($cmt_pic)) {
            return self::responseError(22108, '评论照片上传失败，请重试.');
        }

        $data = array();
        $data['cmt_pic'] = $_W['attachurl'].$cmt_pic['path'].'_500x500.jpg';
        $data['msg']     = '评论照片上传成功';

        //保存到缓存
        $attract_key    = 'comment_upics_'. $ordersn;
        $data_mc        = $_W['mc']->get($attract_key);
        $data_mc        = json_decode($data_mc, true);
        $data_mc        = is_array($data_mc)? $data_mc : array();
        $data_mc[]      = $cmt_pic['path'];

        $_W['mc']->set($attract_key, json_encode($data_mc), 0, 86400);

        return self::responseOk($data);
	}

	/**
	* 评论订单 
	*/
	public function add(){
		global $_W;
		$arr_star_level      = array(1, 3, 5);
		$ordersn             = isset($_POST['ordersn'])? $_POST['ordersn'] : input('get.ordersn');
		$member_id			 = $_W['member_id'];

		$u_data = array();
		$row_order = array();
		if(empty($ordersn)){
            return self::responseError(22101, '抱歉，订单编号不能为空。');
        }

        $row_order = pdo_fetch("SELECT o.id AS orderId, o.member_id, o.sellerid, o.createtime ".
                "FROM `ims_bj_qmxk_order` o ".
                "WHERE ordersn=:ordersn", array(':ordersn'=>$ordersn));

        if(empty($row_order) || $row_order['member_id'] != $member_id) {
            return self::responseError(22140, '抱歉，该订单不存在。');
        }

        //判断是否允许评价
		$pre_check = CommentModel::getInstance()->isEnableAdd($member_id, $ordersn);
        if($pre_check['status']!=0){
        	return self::responseError($pre_check['status'], $pre_check['msg']);
        }

		$u_data['member_id'] 	= $member_id;
        $u_data['shop_id']      = $row_order['sellerid'];
        $u_data['addtime']      = TIMESTAMP;
        $u_data['ordersn']      = $ordersn;
		$u_data['buytime']      = $row_order['createtime'];

		//订单商品
		$goods_all = pdo_fetchall("SELECT og.goodsid AS goodsId, og.optionid, og.optionname ".
			"FROM `ims_bj_qmxk_order_goods` og ".
			"WHERE og.orderid=:orderid", array(':orderid'=>$row_order['orderId']));

        if(empty($goods_all)){
            return self::responseError(22141, '抱歉，该订单商品不存在。');
        }
		
		//校验评价
        $attract_key    = 'comment_upics_'. $ordersn;
        $data_mc        = $_W['mc']->get($attract_key); //短路径
        $data_mc        = json_decode($data_mc, true);
        $data_mc        = is_array($data_mc)? $data_mc : array();

        $p_comments     = input('post.comments');
        $p_comments     = empty($p_comments)? array() : $p_comments;
        $p_cmt_end      = array();
        //提交数据整理     
        foreach ($p_comments as $key => $val) {
            $optionid = intval($val['optionid']);
            $p_key = $val['goodsId'].'_optid_'.$optionid;

            if(!isset($p_cmt_end[$p_key])){
                $p_cmt_end[$p_key] = array();
            }
            $p_cmt_end[$p_key][] = $val;
        }
        unset($p_comments);

		$last_p_key0 = '';
        $p_key0_idx = 0;
        foreach ($goods_all as $key => $val) {
            $gid        = $val['goodsId'];
			$optionid   = intval($val['optionid']);
            $p_key      = $gid.'_optid_'.$optionid;
            $p_key0     = $gid.'_optid_0';

            if($p_key0==$last_p_key0){
                $p_key0_idx++;
            }else{
                $p_key0_idx = 0;
            }

            if( isset($p_cmt_end[$p_key]) ){//接口增加了optionid参数
                $item = $p_cmt_end[$p_key][0];
            }else if( isset($p_cmt_end[$p_key0]) ){//兼容接口还未增加optionid参数，一个商品多个规格
                $item = $p_cmt_end[$p_key0][$p_key0_idx];
            }else{
                $item = array();
            }

            $star_level = isset($item['star_level'])? intval($item['star_level']) : 0;
            $content    = isset($item['content'])?    trim($item['content'])      : '';
			$pics	    = trim( isset($item['pics'])? trim($item['pics']) : '', ',');

			$star_level = intval($star_level);
			if(in_array($star_level, $arr_star_level)){
				$goods_all[$key]['star_level'] = $star_level;
                $goods_all[$key]['content']    = $content;

                //图片检查
                $pics_end = array();
                $pics     = $pics? explode(',', $pics) : array();
                foreach ($pics as $pk => $pv) {
                    foreach ($data_mc as $mv) {
                        if(strpos($pv, $mv)!==false){
                            $pics_end[] = $mv;
                        }
                    }
                }
				$goods_all[$key]['pics'] = !empty($pics_end)? implode(',', $pics_end) : '';
                
			}else{
				return self::responseError(22102, '请给商品评价');
			}

            $last_p_key0 = $p_key0;
		}

        //对商家评价参数
        $data_shop_cmt       = array();
        $data_shop_cmt['desc_star_level']     = intval(input('post.desc_star_level'));
        $data_shop_cmt['service_star_level']  = intval(input('post.service_star_level'));
        $data_shop_cmt['ship_star_level']     = intval(input('post.ship_star_level'));

        foreach ($data_shop_cmt as $key => $val) {
            $val = $val>5? 5 : $val;
            $data_shop_cmt[$key] = $val*2;
        }

        if($data_shop_cmt['desc_star_level'] ==0 || $data_shop_cmt['service_star_level']==0 || $data_shop_cmt['ship_star_level']==0){
            return self::responseError(22110, '请对店铺评价');
        }


        //==保存==
        pdo_begin();		
        $cmt_credit_num = 0;
        foreach ($goods_all as $key => $val) {
            $u_data['goods_id']     = $val['goodsId'];
            $u_data['optionid']     = intval($val['optionid']);
            $u_data['star_level']   = $val['star_level'];
            if($val['star_level']==5){ //好评直接发布
                $u_data['status']   = 1;
            }else{
                $u_data['status']   = -1; //临时状态
            }

            //积分标记
            if( CommentModel::getInstance()->checkCommentCredit($val['content'], $val['pics']) ){
            	$u_data['credit_flag'] = 1;
            	$cmt_credit_num++;
                //增加积分
                $add_credit += CreditModel::getInstance()->addLog($u_data['member_id'], ConfigModel::CREDIT_EFFECT_COMMENT_ACTID, '', 0, $ordersn, $u_data['goods_id']);
            }else{
            	$u_data['credit_flag'] = 0;
            }

            $result = pdo_insert('bj_qmxk_goods_comment', $u_data);
            if(!empty($result)){
                $comment_id = pdo_insertid();
                if($u_data['status']==1){ //更新统计表
                    CommentModel::getInstance()->increaseGoodsCommentData($u_data['goods_id'], $u_data['shop_id'], $u_data['star_level']);
                }

                //保存图片、评论
                $u_data2 = array();
                $u_data2['comment_id']      = $comment_id;
                $u_data2['goods_option']    = $val['optionname'].'';
                $u_data2['content']         = $val['content'].'';
                $u_data2['pics']            = $val['pics'];

                pdo_insert('bj_qmxk_goods_comment_body', $u_data2);
            }
        }

        //更新允许评论记录表
        pdo_update('bj_qmxk_order_enable_cmt', array('enable_cmt'=>2), array('ordersn'=>$ordersn));


        //=对商家评价=
        $data_shop_cmt['shop_id']   = $row_order['sellerid'];
        $data_shop_cmt['member_id'] = $member_id;
        $data_shop_cmt['ordersn']   = $ordersn;
        $data_shop_cmt['addtime']   = TIMESTAMP;

        pdo_insert('bj_qmxk_shop_comment', $data_shop_cmt);
        //=对商家评价 end=


        //事务处理判断
        try {
        	pdo_commit();
        } catch (Exception $e) {
        	pdo_rollback();//回滚事务
			return self::responseError(22150, '发表失败，请稍后再试');
        }
        //==保存 end==

        //释放缓存
        $_W['mc']->delete($attract_key);

        $result = array();
        $result['cmt_credit_num']		= $cmt_credit_num;
        $result['add_credit']			= $add_credit;
        $result['punish_credit_set']	= abs( ConfigModel::getInstance()->getCreditSetArr(ConfigModel::CREDIT_HIDDEN_COMMENT_ACTID, 'num') );
        if($result['cmt_credit_num']>0){
        	$result['ok_title']			= "谢谢您的参与！";
        	$result['ok_desc']			= "您写的文字评论或图片如果违规，将会被删除，同时扣除一条评论{$result['punish_credit_set']}个积分";
        }else{
        	$result['ok_title']			= "谢谢您的参与！";
        	$result['ok_desc']			= '';
        }

        return self::responseOk($result);
	}

	/**
	* 获取商品评价信息
	*/
	public function detail(){
		global $_W;
		$arr_star_level      = array(1, 3, 5);
		$cmt_id              = input('get.id');
		
		$cmt_row = CommentModel::getInstance()->getOne($cmt_id);

		if(empty($cmt_row)){
			return self::responseError(22301, '抱歉，评论记录不存在。');
		}

		if($cmt_row['member_id'] != $_W['member_id']) {
			return self::responseError(22340, '抱歉，无权评论。');
		}

		$cmt_row_end = array();

		$cmt_row_end['id'] 	 		 = $cmt_row['id'];
		$cmt_row_end['goods_id'] 	 = $cmt_row['goods_id'];
		$cmt_row_end['shop_id'] 	 = $cmt_row['shop_id'];
		$cmt_row_end['star_level'] 	 = $cmt_row['star_level'];
		$cmt_row_end['ordersn'] 	 = $cmt_row['ordersn'];
		//$cmt_row_end['goods_option'] = $cmt_row['goods_option'];
		$cmt_row_end['content'] 	 = $cmt_row['content'];
		$cmt_row_end['reply'] 		 = $cmt_row['reply'];

		$cmt_row_end['addtime'] = time_tran($cmt_row['addtime']);
		$cmt_row_end['buytime'] = time_tran($cmt_row['buytime']);
		if($cmt_row['reply'] && $cmt_row['reply_time']>0){
			$cmt_row_end['reply_time'] = time_tran($cmt_row['reply_time']);
		}else{
			$cmt_row_end['reply_time'] = '';
		}

		$pics = !empty($cmt_row['pics'])? explode(',', $cmt_row['pics']) : array();
		foreach ($pics as $pk => $pv){
			if($pv){
				$pics[$pk] = $_W['attachurl'].$pv.'_500x500.jpg';
			}
		}
		$cmt_row_end['pics'] = $pics;

		$result = array();
		$result['detail'] = $cmt_row_end;

		return self::responseOk($result);
	}

	/**
	* 修改评价
	*/
	public function edit(){
		global $_W;
		$arr_star_level      = array(1, 3, 5);
		$cmt_id              = isset($_POST['id'])? $_POST['id'] : input('get.id');
		
		$cmt_row = CommentModel::getInstance()->getOne($cmt_id);

		if(empty($cmt_row)){
			return self::responseError(22301, '抱歉，评论记录不存在。');
		}

		if($cmt_row['member_id'] != $_W['member_id']) {
			return self::responseError(22340, '抱歉，无权评论。');
		}

		//检查是否允许修改
		if(CommentModel::getInstance()->isEnableEdit($cmt_row['star_level'], $cmt_row['addtime']) == false){
			return self::responseError(22341, '对不起，修改评价时效已过');
		}

		// 表单
		$star_level   = input('post.star_level');
		$content   	  = input('post.content');
		$pics   	  = input('post.pics');
		$status 	  = $cmt_row['status']==1? 2 : $cmt_row['status'];

		if($star_level != 5){
			return self::responseError(22302, '修改评价，只能修改为好评');
		}

		//图片检查
		$cmt_pics 		= trim($cmt_row['pics'], ',');
		$cmt_pics 		= $cmt_pics? explode(',', $cmt_pics) : array();

		$attract_key    = 'comment_upics_'. $cmt_row['ordersn'];
        $data_mc        = $_W['mc']->get($attract_key); //短路径
        $data_mc        = json_decode($data_mc, true);
        $data_mc        = is_array($data_mc)? $data_mc : array();

        if($cmt_pics){
        	$data_mc = array_merge($data_mc, $cmt_pics);
        }

        $pics_end = array();
        $pics     = $pics? explode(',', $pics) : array();
        foreach ($pics as $pk => $pv) {
            foreach ($data_mc as $mv) {
                if(strpos($pv, $mv)!==false){
                    $pics_end[] = $mv;
                }
            }
        }
        $pics = $pics_end? implode(',', $pics_end) : '';


        //更新
        pdo_begin();

        $u_data2 = array();
        $u_data2['content']         = $content;
        $u_data2['change_log']      = $cmt_row['change_log'].($cmt_row['change_log']? ';' : '').date('Y-m-d H:i:s', TIMESTAMP).'用户修改为好评';
        $u_data2['change_time']     = TIMESTAMP;

        if( isset($_POST['pics']) ){ //不传该参数，不更新图片
            $u_data2['pics']            = $pics;
        }

        //增加积分 判断
        $u_data = array();
        $u_data['star_level'] = $star_level;
        $u_data['status']     = 2;
        $cmt_credit_num = 0;
        if( $cmt_row['credit_flag']<=0 && CommentModel::getInstance()->checkCommentCredit($u_data2['content'], $u_data2['pics']) ){
            $u_data['credit_flag'] = 1;
            $cmt_credit_num++;

            //增加积分
            $add_credit += CreditModel::getInstance()->addLog($cmt_row['member_id'], ConfigModel::CREDIT_EFFECT_COMMENT_ACTID, '', 0, $cmt_row['ordersn'], $cmt_row['goods_id']);
        }

        pdo_update('bj_qmxk_goods_comment', $u_data, array('id'=>$cmt_id));
        pdo_update('bj_qmxk_goods_comment_body', $u_data2, array('comment_id'=>$cmt_id));

		//更新统计表
        CommentModel::getInstance()->increaseGoodsCommentData($cmt_row['goods_id'], $cmt_row['shop_id'], $star_level);


        //事务处理判断
        try {
            pdo_commit();
        } catch (Exception $e) {
            pdo_rollback();//回滚事务
            return self::responseError(22350, '修改失败，请稍后再试');
        }

        //释放缓存
        $_W['mc']->delete($attract_key);

        $result = array();
        $result['cmt_credit_num']       = $cmt_credit_num;
        $result['add_credit']           = $add_credit;
        $result['punish_credit_set']    = abs( ConfigModel::getInstance()->getCreditSetArr(ConfigModel::CREDIT_HIDDEN_COMMENT_ACTID, 'num') );
        if($result['cmt_credit_num']>0){
            $result['ok_title']         = "谢谢您的参与！";
            $result['ok_desc']          = "您写的文字评论或图片如果违规，将会被删除，同时扣除一条评论{$result['punish_credit_set']}个积分";
        }else{
            $result['ok_title']         = "谢谢您的参与！";
            $result['ok_desc']          = '';
        }

        return self::responseOk($result);
	}


	/**
	* 评论图片上传
	*/
	private function file_upload($file) {
		if(empty($file)) {return error(- 1, '没有上传图片');}

		$extention = pathinfo($file['name'], PATHINFO_EXTENSION);
		if(!in_array(strtolower($extention), array('jpeg', 'jpg', 'png', 'gif'))) {return error(- 1, '不允许上传此类文件');}
		if(1024 * 1024 * 5 < filesize($file['tmp_name'])) {return error(- 1, "上传的文件超过大小限制，单个文件大小不能超过5M");}
		$result = array();
		$path = '/resource/attachment/';

		$result['path'] = "comment/" . date('Y/m/');

		define('PIC_ROOT', '/webdata/htdocs/m.shunliandongli.com');

		@mkdir(PIC_ROOT . $path . $result['path'], 0755, true);
		do {
			$filename = random(30) . ".{$extention}";
		} while(file_exists(PIC_ROOT . $path . $filename));
		$result['path'] .= $filename;

		$filename = PIC_ROOT . $path . $result['path'];

		if(is_uploaded_file($file['tmp_name'])) {
			if(!move_uploaded_file($file['tmp_name'], $filename)) {
				return error(- 1, '保存上传文件失败');
			}
		} else {
			return error(- 1, '文件上传失败');
		}

		$result['success'] = true;
		return $result;
	}

}
?>
