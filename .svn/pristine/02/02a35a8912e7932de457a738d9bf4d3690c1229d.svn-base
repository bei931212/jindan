<?php

class AttractInvestment extends My {
    
	public static $code_key = '';
        public static $attract_key = '';
        public static $codes_key = '';
        
	function __construct() {
		global $_W;
                self::$attract_key = 'attract_'.$_W['member_id'];
                self::$code_key = 'attract_code_'.$_W['member_id'];
                self::$codes_key = 'attract_codes_'.$_W['member_id'];
	}

        //上传营业执照
        public function permit() {
                global $_W;
                $permit_pic = $_FILES['permit_pic'];

		if(empty($_FILES['permit_pic']['tmp_name'])) {
			return self::responseError(11301, '请上传营业执照照片.');
		}

		$permit_pic = self::file_upload($permit_pic);
		if(is_error($permit_pic)) {
			return self::responseError(11302, '营业执照上传失败，请重试.');
		}

		$data = array();
		$data['permit_pic'] = $permit_pic['path'];
                $data['msg'] = '执照上传成功';
                $dataz = $_W['mc']->get(self::$attract_key);
                if($dataz) $dataz = unserialize($dataz);
                $dataz['permit_pic'] = $permit_pic['path'];
		$_W['mc']->set(self::$attract_key,serialize($dataz),0,86400);
		return self::responseOk($data);
        }
        //上传证件资料
        public function permitdata() {
                global $_W;
                if(empty($_POST['company_name'])) {
                        return self::responseError(11303, '公司名称不能为空.');
		}
                if(empty($_POST['permit_code'])) {
                        return self::responseError(11304, '执照代码不能为空.');
		}
                $data = $_W['mc']->get(self::$attract_key);
                if($data) $data = unserialize($data);
                
                $data['company_name'] = trim($_POST['company_name']);
		$data['permit_code'] = trim($_POST['permit_code']);
                
                $_W['mc']->set(self::$attract_key,serialize($data),0,86400);               
                $return = array('msg'=>'信息提交成功，下一步.');
                return self::responseOk($return);
        }
        //类目
        public function category() {
            $product_cates = pdo_fetchall('SELECT id AS cate_id,name AS cate_name FROM ' .tablename('bj_qmxk_category') .' WHERE parentid = 0 AND enabled=1 ORDER BY  displayorder DESC');
            if(empty($product_cates)){
                return self::responseError(11312, '类目不存在.');
            }
            $return = array('msg'=>'获取类目成功','product_cates'=>$product_cates);
            return self::responseOk($return);
        }
        //上传相关文字信息
        public function wordata() {
                global $_W;
                
                if(empty($_POST['product_cate'])) {
			return self::responseError(11305, '产品类目不能为空.');
		}
                if(empty($_POST['linkman_name'])) {
			return self::responseError(11306, '联系人姓名不能为空.');
		}
                if(empty($_POST['linkman_phone'])) {
			return self::responseError(11307, '联系人电话不能为空.');
		}
                if(!empty($_POST['referrer_id'])) {
                        $referrer_id = intval(trim($_POST['referrer_id']));
		}
                if(empty($_POST['mobile_code'])) {
			return self::responseError(11308, '验证码不能为空.');
		}
                $code_mobile = $_W['mc']->get(self::$code_key);
                $code_mobile = unserialize($code_mobile);
                if(trim($_POST['linkman_phone']) != $code_mobile['mobile']){
                        return self::responseError(11311, '您需要先验证手机号.');
                }
                if(trim($_POST['mobile_code']) != $code_mobile['smscode']){
                        return self::responseError(11311, '验证码不正确.');
                }
                
                $data = $_W['mc']->get(self::$attract_key);
                if(!$data) {
                        return self::responseError(11309, '请勿重复提交.');
                }
                $data = unserialize($data);
                $company_name = empty($data['company_name'])?$_POST['company_name']:$data['company_name'];
                $permit_code = empty($data['permit_code'])?$_POST['permit_code']:$data['permit_code'];
                $permit_pic = empty($data['permit_pic'])?$_POST['permit_pic']:$data['permit_pic'];
                $service = pdo_fetch('SELECT service_id FROM '.tablename('category_to_service').' WHERE cate_id = '.$_POST["product_cate"]);
                $member_pics = pdo_fetch('SELECT avatar FROM '.tablename('bj_qmxk_member_info').' WHERE member_id = '.$_W["member_id"]);
                $arrs = array(
                        'referrer_id'=> $referrer_id,
                        'product_cate'=> $_POST['product_cate'],
                        'physical_store'=>empty($_POST['physical_store'])?'0':$_POST['physical_store'],
                        'linkman_name'=>trim($_POST['linkman_name']),
                        'linkman_phone'=>trim($_POST['linkman_phone']),
                        'qq_number'=>trim($_POST['qq_number']),
                        'wx_number'=>trim($_POST['wx_number']),
                        'other_intro'=>trim($_POST['other_intro']),
                        'company_name'=>$company_name,
                        'permit_code'=>$permit_code,
                        'permit_pic'=>$permit_pic,
                        'audit_status'=>0,
                        'member_id'=>$_W['member_id'],
                        'member_pic'=>$member_pics['avatar']? $member_pics['avatar']: 'http://statics.shunliandongli.com/resource/image/avatar.png',
                        'send_people_id'=>empty($service)?'':$service['service_id'],
                        'send_orders_time'=>TIMESTAMP,
                        'declare_time'=>TIMESTAMP
		);
                if(pdo_insert('merchant_enter',$arrs)){
                    $return = array('msg'=>'预计初审将在48小时内完成,请通过站内消息、短信查看审核结果.');
                    $data['permit_pic'] = $_W['mc']->set(self::$attract_key,false,0,86400);
                    return self::responseOk($return);
                }else{
                    return self::responseError(11310, '申报失败请重来一次，谢谢配合.');
                }
                    
        }
        

        //获取手机验证码
	public function mobileCode() {
		global $_W;

		if(empty($_POST['linkman_phone'])) return self::responseError(50, '请输入手机号码！');
                $mobile = $_POST['linkman_phone'];
                $pattern = '/^1\d{10}$/';

                if(!preg_match( $pattern, $mobile )) return self::responseError(50, '您输入的手机号码格式错误！');

                //图片验证
                $captcha = trim($_POST['captcha']);
                $hash = md5($captcha . $_W['config']['setting']['authkey']);
                if($_COOKIE['captcha'] != $hash) {
                        isetcookie('captcha', '');
                        return self::responseError(50, '你输入的图片验证码不正确, 请重新输入.');
                }
                isetcookie('captcha', '');
//                if($_POST['captcha'] != $_W['mc']->get(self::$codes_key)) {
//                        return self::responseError(50, '你输入的图片验证码不正确, 请重新输入.');
//                }
		//60秒获取1次，5分钟最多三次，一天最多60次
		//查询上次发送时间，间隔60s
		$item = pdo_fetch("SELECT dateline FROM ims_sms_log WHERE mobile='{$mobile}' ORDER BY id DESC LIMIT 1");
		if(TIMESTAMP - 60 < $item['dateline']) {
			return self::responseError(50, '请'.(60-(TIMESTAMP-$item['dateline'])).'秒后再试。');
		}

		//查询5分钟发送次数，最多3次
		$time_begin = TIMESTAMP - 5*60;
		$item = pdo_fetch("SELECT COUNT(*) AS count FROM ims_sms_log WHERE mobile='{$mobile}' AND dateline>'{$time_begin}'");
		if($item['count'] >= 3) {
			return self::responseError(50, '5分钟内最多发送三次，请稍后重试。');
		}

		//查询一天发送次数，最多60次
		$time_begin = strtotime('today');
		$item = pdo_fetch("SELECT COUNT(*) AS count FROM ims_sms_log WHERE mobile='{$mobile}' AND dateline>'{$time_begin}'");
		if($item['count'] >= 60) {
			return self::responseError(50, '运营商限制一天最多发送60条短信，请明天再试。');
		}

		$smscode = random(6, 1);

		$SMS_obj = new SMS();

		$SMS_obj->setParameter('mobile', $mobile);
		$SMS_obj->setParameter('text', $smscode);

		$result = $SMS_obj->send_code();
		if($result['code'] == 0) {
			$sms_log['member_id'] = $_W['member_id'];
			$sms_log['dateline'] = TIMESTAMP;
			$sms_log['mobile'] = $mobile;
			$sms_log['code'] = $smscode;
			$sms_log['sid'] = $result['result']['sid'];
			pdo_insert('sms_log', $sms_log);
                        $data['smscode'] = $smscode;
                        $data['mobile'] = $mobile;
			$_W['mc']->set(self::$code_key,serialize($data),0,600);
                        return self::responseOk('OK');
			
		} else {			
                        return self::responseError(50, '发送失败，请稍后重试。');		
		}
	}
	
        //获取统计结果
        public function attractCount() {
            global $_W;
//            $total = pdo_fetch('SELECT audit_status,count(*) as count FROM ' . tablename('merchant_enter').' WHERE member_id = '.$_W['member_id'].' ORDER BY audit_status');
//           
//            if($total){
//                foreach($total as $v){
//                    if($v['audit_status'] == -1) $no_count = $v['count'];
//                    if($v['audit_status'] == 0) $has_count = $v['count'];
//                    if($v['audit_status'] == 1) $already_count = $v['count'];
//                }
//            }
            $no_count = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('merchant_enter').' WHERE member_id = '.$_W['member_id'].' AND audit_status=-1');
            $has_count = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('merchant_enter').' WHERE member_id = '.$_W['member_id'].' AND audit_status=0');
            $already_count = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('merchant_enter').' WHERE member_id = '.$_W['member_id'].' AND audit_status=1');
//            var_dump($no_count);var_dump($has_count);var_dump($already_count);
            $return['msg'] = '获取数量成功.';
            $return['no_count'] = isset($no_count)?$no_count:'0';
            $return['has_count'] = isset($has_count)?$has_count:'0';
            $return['already_count'] = isset($already_count)?$already_count:'0';
            
            return self::responseOk($return); 
        }
	private function file_upload($file) {
		if(empty($file)) {return error(- 1, '没有上传图片');}

		$extention = pathinfo($file['name'], PATHINFO_EXTENSION);
		if(!in_array(strtolower($extention), array('jpg', 'png', 'gif'))) {return error(- 1, '不允许上传此类文件');}
		if(1024 * 1024 * 5 < filesize($file['tmp_name'])) {return error(- 1, "上传的文件超过大小限制，单个文件大小不能超过5M");}
		$result = array();
		$path = '/resource/attachment/';

		$result['path'] = "permit/" . date('Y/m/');

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
