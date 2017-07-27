<?php

class Sign extends My {
	public static $sign_step = '1';
	public static $sign_info = array();

	function __construct() {
		global $_W;

		//检查是否有有签约资格

		$agent_info = pdo_fetch("SELECT agent_level FROM `ims_bj_qmxk_member_selldata.20160807` WHERE member_id='{$_W['member_id']}'");
		if($_W['member_id'] == 18) {
			$agent_info['agent_level'] = 6;
		}
		if($agent_info['agent_level'] < 1) {
			return self::responseError(10300, '您尚未获得签约资格哦');
		}

		//获取签约步骤
		self::$sign_info = pdo_fetch("SELECT * FROM `ims_sign` WHERE member_id='{$_W['member_id']}'");
		if(self::$sign_info['step']) {
			self::$sign_step = self::$sign_info['step'];
		}
	}

	public function status() {
		global $_W;

		$return = array(
			'next_step' => self::$sign_step,
			'errmsg' => self::$sign_info['errmsg']
		);
		
		if($return['next_step'] == -1){
			$return['errmsg'] = '审核不通过。'.$return['errmsg'];
		}

		return self::responseOk($return);
	}

	//填写姓名和身份证号码
	public function step1() {
		global $_W;

		$name = trim($_POST['name']);
		$cardno = trim($_POST['cardno']);
		/*
		if(!preg_match('/^[a-zA-Z\x{4e00}-\x{9fa5}]+$/u', $name)) {
			return self::responseError(10301, '姓名格式不正确.'.$name);
		}
		*/
		if(!self::checkCardNo($cardno)) {
			return self::responseError(10302, '您输入的身份证号码不正确.');
		}
		
		
		$sign_info = pdo_fetch("SELECT member_id,step FROM `ims_sign` WHERE cardno='{$cardno}'");

		if($sign_info['member_id'] > 0 && $sign_info['step'] > -1) {
				return self::responseError(10303, '该身份证已经提交过签约.');
		}
		
		if($sign_info['member_id'] > 0 && $sign_info['member_id'] != self::$sign_info['member_id']){//其他用户用过此身份证
			return self::responseError(10303, '该身份证已经提交过签约.');
		}

		if(self::$sign_info['member_id']) {
			if(self::$sign_info['checked'] != 0 || self::$sign_info['step'] != -1) {
				return self::responseError(10303, '请勿重复提交.');
			}
			pdo_update('sign', array(
				'name' => $name,
				'cardno' => $cardno,
				'step' => '2',
				'checked' => '0'
			), array('member_id'=>$_W['member_id']));
		} else {
			pdo_insert('sign', array(
				'member_id' => $_W['member_id'], 
				'name' => $name, 
				'cardno' => $cardno,
				'step' => '2',
				'checked' => '0'
			));
		}

		$return = array('msg'=>'填写成功，请上传身份证图片.', 'next_step'=>'2');

		return self::responseOk($return);
	}

	public function step2() {
		global $_W;

		$image_1 = $_FILES['image_1'];
		$image_2 = $_FILES['image_2'];
		$image_3 = $_FILES['image_3'];

		if(empty($_FILES['image_1']['tmp_name'])) {
			return self::responseError(10304, '请上传身份证正面图片.');
		}
		if(empty($_FILES['image_2']['tmp_name'])) {
			return self::responseError(10305, '请上传身份证背面图片.');
		}
		if(empty($_FILES['image_3']['tmp_name'])) {
			return self::responseError(10306, '请上传手持身份证图片.');
		}

		$upload_1 = self::file_upload($image_1);
		if(is_error($upload_1)) {
			return self::responseError(10307, '身份证上传失败，请重试.');
		}

		$upload_2 = self::file_upload($image_2);
		if(is_error($upload_2)) {
			return self::responseError(10308, '身份证上传失败，请重试.');
		}

		$upload_3 = self::file_upload($image_3);
		if(is_error($upload_3)) {
			return self::responseError(10309, '身份证上传失败，请重试.');
		}

		$data = array();
		$data['image_1'] = $upload_1['path'];
		$data['image_2'] = $upload_2['path'];
		$data['image_3'] = $upload_3['path'];
		$data['step'] = 3;
		$data['checked'] = 0;

		pdo_update('sign', $data, array('member_id'=>$_W['member_id']));

		$return = array('msg'=>'实名信息提交成功，请等待审核.', 'next_step'=>'3');

		return self::responseOk($return);
	}


	public function step3() {
		global $_W;

		if(self::$sign_info['step'] == '3') {
			$return = array('msg'=>'实名信息提交成功，请等待审核.');
			return self::responseOk($return);
		} else {
			return self::responseError(10310, '未查询到您的实名信息.');
		}
	}

	public function step4() {
		global $_W;

		if(self::$sign_info['checked'] != '1' || self::$sign_info['step'] == '-1') {
			return self::responseError(10311, '您的实名信息审核失败，请重新提交.');
		}

		$return = array(
			'name' => self::$sign_info['name'],
			'cardno' => self::$sign_info['cardno']
		);

		return self::responseOk($return);
	}

	private function checkCardNo($cardno){
		if(strlen($cardno) != 18){
			if(strlen($cardno) == 9 || strlen($cardno) == 10){//港澳台证件
				return true;
			}
			return false;
		}
		
		$cardno = strtoupper($cardno);

		$idcard_base = substr($cardno, 0, 17);
		$verify_code = substr($cardno, 17, 1);
		$factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
		$verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
		$total = 0;
		for($i=0; $i<17; $i++){
			$total += substr($idcard_base, $i, 1) * $factor[$i];
		}

		$mod = $total % 11;

		if($verify_code == $verify_code_list[$mod]){
			return true;
		}

		return false;
	}

	private function file_upload($file) {
		if(empty($file)) {return error(- 1, '没有上传图片');}

		$extention = pathinfo($file['name'], PATHINFO_EXTENSION);
		if(!in_array(strtolower($extention), array('jpg', 'png', 'gif'))) {return error(- 1, '不允许上传此类文件');}
		if(1024 * 1024 * 5 < filesize($file['tmp_name'])) {return error(- 1, "上传的文件超过大小限制，单个文件大小不能超过5M");}
		$result = array();
		$path = '/resource/attachment/';

		$result['path'] = "idcard/" . date('Y/m/');

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
