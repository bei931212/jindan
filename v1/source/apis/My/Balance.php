<?php

class Balance extends My {

	//充值
	public function recharge() {
		global $_W;

		$amount = round($_POST['amount'], 2);
		$amount = number_format($amount, 2, '.', '');

		return self::responseError(10000, '当前未开启充值功能.');
	}

	//提现
	public function withdraw() {
		global $_W;

		$amount = round($_POST['amount'], 2);
		$amount = number_format($amount, 2, '.', '');

		$profile = pdo_fetch("SELECT * FROM `ims_bj_qmxk_member_info` WHERE member_id='{$_W['member_id']}'");

		if(empty($amount) || $amount <= 0) return self::responseError(10100, '请输入要提现的金额.');
		if($amount < 1) return self::responseError(10101, '提现金额不能小于1元.');
		if($amount > 20000) return self::responseError(10101, '提现金额不能大于20000元.');
                if($profile['credit2']<0 || $profile['credit2_freeze']<0)//负数不能提
                    return self::responseError(10101, '提现余额不能小于0元.');
		if($amount > ($profile['credit2'] - $profile['credit2_freeze'])) return self::responseError(10102, '提现金额不能大于您的可用余额.');

		// 同一个用户，一小时只允许申请一次
		//$withdraw_applyed_key = md5('api-withdraw-applyed-'.$_W['member_id'].'-2');
		$withdraw_applyed_key = md5('withdraw-applyed-' . $_W['member_id'].'-2');

		if(!$_W['mc']){return self::responseError(10101, '请刷新重试！');}

		if($_W['mc']->get($withdraw_applyed_key)) {
			return self::responseError(10103, '已有您的申请在处理中，请稍后再申请');
		} else {
			$_W['mc']->set($withdraw_applyed_key, $_W['member_id'], 0, 600);
		}

		//@sime限制如果有正在处理中的订单就不让提现
		if(pdo_fetch("/**liuchunlin**/SELECT * FROM " . tablename('bj_qmxk_withdraw') . " WHERE status = 0 AND member_id = '".$_W['member_id']."'")){
			return self::responseError(10103, '已有您的申请在处理中，请稍后再申请');
		}

		// @sime 每天 提现额度大于 500 的部分收取0.8%手续费
		$wAppKey = md5('withdraw-applyed-'.$_W['member_id'].date('Y-m-d'));
		$quo     = $_W['mc']->get($wAppKey);
		$_W['mc']->set($wAppKey, ($quo+$amount), 0, 86400);
		$quoVal  = $_W['mc']->get($wAppKey);

		if($quoVal > 500){

			if($quo > 500){
				$sfee = $amount * 0.008;
			}else{
				$sfee = ($quoVal - 500)* 0.008;
			}
			$sfee = substr($sfee,0,strrpos($sfee, '.')+3);
		}

		//实际提现金额
		$credit2 = $amount - $sfee;

		pdo_insert('bj_qmxk_withdraw', array(
			'member_id' => $_W['member_id'],
			'credit2'    => $credit2,	//实际提现金额
			'act_mon'	 => $amount,	//申请金额
			'sfee'	     => $sfee,		//手续费
			'createtime' => TIMESTAMP,
			'status' => 0
		));

		$wdid = pdo_insertid();
		if(empty($wdid)) return self::responseError(10104, '申请失败，请稍后重试.');

		pdo_query("UPDATE `ims_bj_qmxk_member` SET credit2_freeze=credit2_freeze+'{$amount}' WHERE id='{$_W['mid']}'");
		pdo_query("UPDATE `ims_bj_qmxk_member_info` SET credit2_freeze=credit2_freeze+'{$amount}' WHERE member_id='{$_W['member_id']}'");

		return self::responseOk('提现申请成功，请等待审核.');
	}

	//取消提现功能
    public function withdrawCancel(){
        global $_W;
        $txid   = $_POST['wdid'];
        if(empty($txid)){
            return self::responseError(10100, 'ID不能为空');
        }

        if(!$_W['mc']){
        	return self::responseError(10100, '请刷新重试！');
        }

        $where  = "member_id='{$_W['member_id']}' and wdid='{$txid}' and (status='0' or status='1')";
        $wdinfo = pdo_fetch("SELECT * FROM " . tablename('bj_qmxk_withdraw') . " WHERE {$where}");

        if(empty($wdinfo)) {
            return self::responseError(10101, 'Sorry,数据不存在');
        }
        $where1 = "member_id='{$_W['member_id']}'";
        $mm     = pdo_fetch("SELECT * FROM " . tablename('bj_qmxk_member') . " WHERE {$where1}");
        $minfo  = pdo_fetch("SELECT * FROM " . tablename('bj_qmxk_member_info') . " WHERE {$where1}");
        $mm['credit2_freeze']=sprintf("%0.2f", $mm['credit2_freeze']);
        $minfo['credit2_freeze']=sprintf("%0.2f", $minfo['credit2_freeze']);
        $wdinfo['credit2']=sprintf("%0.2f", $wdinfo['credit2']);
        // @sime
        $actVal = $wdinfo['act_mon'] == 0 ? $wdinfo['credit2'] : $wdinfo['act_mon'] ;
        $djyue  = $mm['credit2_freeze']-$actVal;
        $djiyue = $minfo['credit2_freeze']-$actVal;
        if($djyue < 0 || $djiyue < 0 ) {
            return self::responseError(10102, '账号冻结金额异常，请联系客服');
        }else{
            $result1=pdo_update('bj_qmxk_withdraw', array('status' => 4,'checktime' => TIMESTAMP), array('wdid' => $txid));
            if(empty($result1)) {
                return self::responseError(10103, '更新申请取消失败');
            }
            $result2= pdo_query("UPDATE `ims_bj_qmxk_member` SET credit2_freeze='{$djyue}' WHERE member_id='{$_W['member_id']}'");
            if(empty($result2)) {
                return self::responseError(10104, '扣减冻结金失败');
            }
            $result3=pdo_query("UPDATE `ims_bj_qmxk_member_info` SET credit2_freeze='{$djiyue}' WHERE member_id='{$_W['member_id']}'");
            if(empty($result3)) {
                return self::responseError(10104, '扣减冻结金失败');
            }

            // @sime 减去memcache
            $wAppKey = md5('withdraw-applyed-'.$wdinfo['member_id'].date('Y-m-d'));
            $mRe     = $_W['mc']->get($wAppKey);
            if($mRe){
                $memVal = $wdinfo['act_mon'] == 0 ? $wdinfo['credit2'] : $wdinfo['act_mon'] ;
                $_W['mc']->set($wAppKey, ($mRe-$memVal), 0, 86400);
            }

            return self::responseOk('申请取消成功');
        }
    }

	//余额明细
	//可选参数: count=获取数量，最大40，默认10
	//可选参数: page=分页
	public function record() {
		global $_W;

		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$count = ($count && $count <= 40) ? $count : 20;

		$where = "member_id='{$_W['member_id']}'";
		//$where = "member_id=''";
		$limit = ($page-1)*$count.','.$count;

		$items_end = array();
		$items = pdo_fetchall("SELECT createtime,type,fee,tag FROM `ims_bj_qmxk_paylog` WHERE {$where} ORDER BY plid DESC LIMIT {$limit}");

		foreach($items AS $item) {
			$item['fee'] = sprintf('%.2f', $item['fee']);
			$item['createtime'] = date('Y-m-d H:i', $item['createtime']);

			if($item['type'] == 'addgold') {
				$item['type'] = '增加';
			}
			if($item['type'] == 'usegold') {
				$item['type'] = '减少';
			}

			$items_end[] = $item;
		}

		$results['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_paylog` WHERE {$where}");
		$results['itemCount'] = intval($results['itemCount']);
		$results['allPage'] = ceil($results['itemCount']/$count);
		$results['page'] = $page;
		$results['count'] = $count;
		$results['items'] = $items_end;

		return self::responseOk($results);
	}

	//充值记录
	//可选参数: count=获取数量，最大40，默认10
	//可选参数: page=分页
	public function withdrawRecord() {
		global $_W;

		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$count = ($count && $count <= 40) ? $count : 20;

		$where = "member_id='{$_W['member_id']}'";
		$limit = ($page-1)*$count.','.$count;

		$items_end = array();
		$items = pdo_fetchall("SELECT wdid,credit2,createtime,status,act_mon,sfee FROM `ims_bj_qmxk_withdraw` WHERE {$where} ORDER BY wdid DESC LIMIT {$limit}");

		foreach($items AS $item) {
            $item['credit2'] = floatval($item['credit2']);
			$item['createtime'] = date('Y-m-d H:i', $item['createtime']);

            $item['act_mon'] = $item['act_mon'] == 0 ? floatval($item['credit2']) : $item['act_mon'];
			$item['sfee'] 	 = $item['sfee'] ? floatval($item['sfee']) : 0 ;
			
			$verStr = self::$client_version;
			if(self::$platform == 'Android'){
				if(substr($verStr, 0,3) == '1.5' && substr($verStr, 4,1) <= 2){
					if($item['status'] == 0) {
						$item['status'] = '待审核';
					} elseif($item['status'] == 1) {
						$item['status'] = '待打款';
					} elseif($item['status'] == 2) {
						$item['status'] = '审核拒绝';
					} elseif($item['status'] == 3) {
						$item['status'] = '已打款';
					} elseif($item['status'] == 4) {
						$item['status'] = '已取消';
					}
				}
			}

			if(self::$platform == 'IOS') {
			    if(substr($verStr, 0,3) == '1.3' && substr($verStr, 4,1) <= 5){
			    	if($item['status'] == 0) {
			    		$item['status'] = '待审核';
			    	} elseif($item['status'] == 1) {
			    		$item['status'] = '待打款';
			    	} elseif($item['status'] == 2) {
			    		$item['status'] = '审核拒绝';
			    	} elseif($item['status'] == 3) {
			    		$item['status'] = '已打款';
			    	} elseif($item['status'] == 4) {
			    		$item['status'] = '已取消';
			    	}
			    }
			}

			$items_end[] = $item;
		}


		$results['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_withdraw` WHERE {$where}");
		$results['itemCount'] = intval($results['itemCount']);
		$results['allPage'] = ceil($results['itemCount']/$count);
		$results['page'] = $page;
		$results['count'] = $count;
		$results['items'] = $items_end;

		return self::responseOk($results);
	}
}
?>