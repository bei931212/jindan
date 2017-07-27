<?php
require IA_ROOT . '/source/libs/delivery.class.php';
class Address extends Api {
	//登陆验证
	function __construct() {
		global $_W;
/**/
		require IA_ROOT.'/source/apis/User.php';
		if(!User::checklogin()) {
			return self::responseError(1000, '尚未登陆。');
		}

		if(empty($_W['member_id'])) {
			return self::responseError(1001, '尚未登陆。');
		}

	}

	//获取所有地址
	public function all() {
		global $_W;

		$address = pdo_fetchall("SELECT id AS addressId,district_id,realname,mobile,district_addr,address,isdefault FROM `ims_bj_qmxk_address` WHERE deleted='0' AND member_id='{$_W['member_id']}' AND isnew='1'");

		$default = array();

		if(!empty($address)) {
			$address_end = array();
			foreach($address AS $addr) {
				$addr['address'] = $addr['district_addr'].' '.$addr['address'];
				unset($addr['district_addr']);
				//$addr['mobile'] = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1****$3', $addr['mobile']);
				if($addr['isdefault'] == 1) {
					$default = $addr;
					continue;
				}
				$address_end[] = $addr;
			}

			if(!empty($default)) {
				array_unshift($address_end, $default);
			}

			return self::responseOk($address_end);
		}

		return self::responseError(700, '您还没有设置收货地址');
	}

	//获取默认地址
	public function defaults() {
		global $_W;

		$address = pdo_fetch("SELECT id AS addressId,district_id,realname,mobile,district_addr,address,isdefault FROM `ims_bj_qmxk_address` WHERE member_id='{$_W['member_id']}' AND deleted='0' AND isdefault='1' AND isnew='1' LIMIT 1");

		if(!empty($address)) {
			$address['address'] = $address['district_addr'].' '.$address['address'];
			unset($address['district_addr']);
			//$address['mobile'] = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1****$3', $address['mobile']);
			return self::responseOk($address);
		}

		return self::responseError(700, '您还没有设置收货地址');
	}

	//根据地址ID获取单个收货地址信息
	public function item() {
		global $_W;

		$id = intval($_GET['addressId']);

		if(empty($id)) return self::responseError(700, 'Parameter [addressId] is missing.');

		$address = pdo_fetch("SELECT id AS addressId,district_id,member_id,realname,mobile,district_addr,address,isdefault,deleted FROM `ims_bj_qmxk_address` WHERE id='{$id}'");

		if(empty($address) || $address['member_id'] != $_W['member_id'] || $address['deleted']) {
			return self::responseError(701, '不存在该地址');
		}

		$address['address'] = $address['district_addr'].' '.$address['address'];
		unset($address['district_addr']);
		unset($address['member_id']);
		unset($address['deleted']);

		//$address['mobile'] = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1****$3', $address['mobile']);

		return self::responseOk($address);
	}
//{"goods":[{"goodsId":"20585","optionid":"139176"},{"goodsId":"20528","optionid":"138780"},{"goodsId":"20582","optionid":"139122"}],"addressId":"0","paytype":"alipay","testMode":"YES"}
	//新增收货地址
	public function add() {
		global $_W;

		$data = array(
			'weid'			=> 2,
			'member_id'		=> $_W['member_id'],
			'district_id'	=> intval($_POST['district_id']),
			'realname'		=> $_POST['realname'],
			'mobile'		=> $_POST['mobile'],
			'address'		=> $_POST['address'],
			'isdefault'		=> 1,
			'isnew'			=> 1
		);

		if(empty($data['district_id'])) {
			return self::responseError(714, '请选择地区');
		}

		if(empty($data['realname'])) return self::responseError(711, '请输入收货人姓名');
		if(empty($data['mobile'])) return self::responseError(712, '请输入收货人手机号');
		if(!is_mobile($data['mobile'])) return self::responseError(713, '手机号码不正确');

		//检查收货地址数量
		$addressTotal = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_bj_qmxk_address` WHERE member_id='{$_W['member_id']}' AND deleted='0'");

		if($addressTotal >= 20) {
			return self::responseError(710, '收货地址最多允许设置20个');
		}

		//判断district_id的级别
		$district_level = pdo_fetchcolumn("SELECT level FROM `ims_district` WHERE id='{$data['district_id']}'");
		if($district_level < 3) {
			if($data['district_id'] != '820100' && $data['district_id'] != '820200') {
				return self::responseError(717, '请选择完整的地区');
			}
		}
		$address_tree = Delivery::fetch_position($data['district_id']);
		unset($address_tree[0]);
		if($address_tree[1]) $data['province'] = $address_tree[1];
		if($address_tree[2]) $data['city'] = $address_tree[2];
		if($address_tree[3]) $data['area'] = $address_tree[3];

		$data['district_addr'] = implode(' ', $address_tree);

		if(empty($data['address'])) return self::responseError(715, '请输入详细地址');

		pdo_update('bj_qmxk_address', array('isdefault' => 0), array('member_id' => $_W['member_id']));
		pdo_insert('bj_qmxk_address', $data);
		$addressId = pdo_insertid();

		if(empty($addressId)) {
			pdo_insert('bj_qmxk_address', $data);
			$addressId = pdo_insertid();
			if(empty($addressId)) {
				return self::responseError(716, '新增地址失败，请重试。');
			}
		}

		return self::responseOk(array('addressId'=>$addressId));
	}

	//修改收货地址
	public function modify() {
		global $_W;

		$id = intval($_POST['addressId']);
		$data = array(
			'weid'			=> 2,
			'member_id'		=> $_W['member_id'],
			'district_id'	=> intval($_POST['district_id']),
			'realname'		=> $_POST['realname'],
			'mobile'		=> $_POST['mobile'],
			'address'		=> $_POST['address'],
			'isnew'			=> 1
		);

		if(empty($id)) return self::responseError(720, 'Parameter [addressId] is missing.');

		$address = pdo_fetch("SELECT member_id,isdefault FROM `ims_bj_qmxk_address` WHERE id='{$id}'");

		if(empty($address) || $address['member_id'] != $_W['member_id']) {
			return self::responseError(721, '不存在该地址');
		}
		if($address['isdefault']) {
			$data['isdefault'] = 1;
		}

		if(empty($data['realname'])) return self::responseError(722, '请输入收货人姓名');
		if(empty($data['mobile'])) return self::responseError(723, '请输入收货人手机号');
		if(empty($data['address'])) return self::responseError(726, '请输入详细地址');
		if(!is_mobile($data['mobile'])) return self::responseError(724, '手机号码不正确');

		//判断district_id的级别
		$district_level = pdo_fetchcolumn("SELECT level FROM `ims_district` WHERE id='{$data['district_id']}'");
		if($district_level < 3) {
			if($data['district_id'] != '820100' && $data['district_id'] != '820200') {
				return self::responseError(728, '请选择完整的地区');
			}
		}
		$address_tree = Delivery::fetch_position($data['district_id']);
		unset($address_tree[0]);
		if($address_tree[1]) $data['province'] = $address_tree[1];
		if($address_tree[2]) $data['city'] = $address_tree[2];
		if($address_tree[3]) $data['area'] = $address_tree[3];

		$data['district_addr'] = implode(' ', $address_tree);

		if(empty($data['address'])) return self::responseError(729, '请输入详细地址');

		//把原来的设置为删除，新增一个
		pdo_insert('bj_qmxk_address', $data);
		$addressId = pdo_insertid();
		if(empty($addressId)) {
			pdo_insert('bj_qmxk_address', $data);
			$addressId = pdo_insertid();
			if(empty($addressId)) {
				return self::responseError(727, '新增地址失败，请重试。');
			}
		}
		pdo_update('bj_qmxk_address', array('deleted'=>1), array('id'=>$id));

		return self::responseOk(array('addressId'=>$addressId));
	}

	//删除收货地址,多个地址用逗号分隔,最多20个
	public function remove() {
		global $_W;

		$ids = trim($_GET['addressId']);
		if(empty($ids)) return self::responseError(730, 'Parameter [addressId] is missing.');
		
		$ids_arr = explode(',', $ids);
		$comm = '';
		$count = 0;

		foreach($ids_arr AS $id) {
			$id = intval($id);
			if($id) {
				$ids_del .= $comm.$id;
				$comm = ',';
				$count += 1;
			}
		}

		if(empty($ids_del)) return self::responseError(732, 'Parameter [addressId] is invalid.');
		if($count > 20) return self::responseError(733, 'Parameter [addressId] is too much.');

		$address = pdo_fetchall("SELECT id,member_id FROM `ims_bj_qmxk_address` WHERE id IN ({$ids_del})");
		if($address[0]['id']) {
			foreach($address AS $addr) {
				if($addr['member_id'] == $_W['member_id']) {
					pdo_update('bj_qmxk_address', array('deleted'=>1,'isdefault'=>0), array('id'=>$addr['id']));
				}
			}
		}

		return self::responseOk('删除成功');
	}

	//设置为默认地址
	public function setDefault() {
		global $_W;

		$id = intval($_GET['addressId']);
		if(empty($id)) return self::responseError(740, 'Parameter [addressId] is missing.');

		$address = pdo_fetch("SELECT member_id,isdefault FROM `ims_bj_qmxk_address` WHERE id='{$id}'");

		if(empty($address) || $address['member_id'] != $_W['member_id']) {
			return self::responseError(741, '不存在该地址');
		}

		if(!$address['isdefault']) {
			pdo_update('bj_qmxk_address', array('isdefault'=>0), array('member_id' =>$_W['member_id']));
			pdo_update('bj_qmxk_address', array('isdefault'=>1), array('id'=>$id));
		}

		return self::responseOk('设置成功');
	}

	//可选参数: type=default|app
	public function getDistrict() {
		global $_W;

		$type = trim($_GET['type']);

		if($type == 'app') {
			$file = IA_ROOT.'/district.json';
			$content = json_decode(file_get_contents($file), true);
			$content = array_values($content);

			foreach($content AS $c) {
				foreach($c['_child'] AS $k=> $cc) {
					if($cc['_child']) $c['_child'][$k]['_child'] = array_values($cc['_child']);
				}
				if($c['_child']) $c['_child'] = array_values($c['_child']);

				$result[] = $c;
			}

			return self::responseOk(array('districts'=>$result));
		} else {
			$root = 0;
			$level = 3;
			$result = pdo_fetchall("SELECT name,id,parent_id FROM ims_district WHERE `level`<'4'");
			$districts = list_to_tree($result, 'id', 'parent_id', '_child', $root);

			return self::responseOk(array('districts'=>$districts));
		}
	}
}