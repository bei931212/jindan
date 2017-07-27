<?php

class Credit extends My {

	//积分
	public function index() {
		global $_W;
		$pageSize = isset($_GET['pagesize']) ? intval($_GET['pagesize']) : 50;
		$time = strtotime("-2 months");
		$member_id = $_W['member_id'];
		$data = CreditModel::getInstance()->getListLog($member_id,$time,0,1,$pageSize);
		$jifen = CreditModel::getInstance()->getJiFen($member_id);
		$jifen['data'] = $data;
		return self::responseOk($jifen);
	}
	//好评
	public function haoping() {
		global $_W;
		$pageSize = isset($_GET['pagesize']) ? intval($_GET['pagesize']) : 50;
		$time = strtotime("-2 months");
		$member_id = $_W['member_id'];
		$data = CreditModel::getInstance()->getListLog($member_id,$time,1,1,$pageSize);
		$haoping = CreditModel::getInstance()->getHaoPing($member_id);
		$res['haoping'] = $haoping;
		$res['data'] = $data;
		return self::responseOk($res);
	}
	public function loglist(){
		global $_W;
		$time = strtotime("-2 months");
		$member_id = $_W['member_id'];
		$pageSize = isset($_GET['pagesize']) ? intval($_GET['pagesize']) : 50;
		$type = isset($_GET['type']) ? intval($_GET['type']) : 0;
	    $pageIndex = max(1, intval($_GET['page']));
	    $data = CreditModel::getInstance()->getListLog($member_id,$time,$type,$pageIndex,$pageSize);
	    if(empty($data)){
	        $data = array();
	    }
	    return self::responseOk($data);
	}


	/**
	* 领取好评分
	*/
	public function receiveCredit(){
		$cobj = CreditRandomModel::getInstance();
		$rt_credit = $cobj->getNewCredit();

		$result = array('status'=>175033, 'error'=>'还没有积分可以领取');
		if($rt_credit['credit']>0){//自动领取
			$result = $cobj->storeCredit();
		}

		if($result['status']===0){
			return self::responseOk(array('credit'=>intval($rt_credit['credit'])));
		}else{
			if($_POST['testMode']=='YES'){
				$debug_data = array('credit_data'=>$cobj->getData(), 'chk_credit'=>$rt_credit);
				return self::responseError($result['status'], $result['error'], $debug_data);
			}else{
				return self::responseError($result['status'], $result['error']);
			}
		}
	}
}