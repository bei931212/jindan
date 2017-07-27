<?php
//地区、物流管理

class Delivery {

	public function __construct() {


	}

    /**
     * 获取所有地区信息
     * @param string $fields 字段名
     * @return array
     */
    public function fetch_all() {
        return $this->model->getField('id,parent_id,name',TRUE);
    }

    /**
     * 获取所有地区信息
     * @return array
     */
    public function fetch_all_by_tree($root = 0, $level = 0) {
    		$_map = array();
		if($level > 0) {
			$_map['level'] = array("LT", $level);
		}
        $result = $this->model->where($_map)->select();
        return list_to_tree($result, 'id', 'parent_id', '_child', $root);
    }

    /**
     * 获取指定地区信息
     * @param int $id
     * @return array
     */
    public function fetch_by_district_id($id) {
        return pdo_fetch("SELECT * FROM `ims_district` WHERE id='{$id}'");
    }

    /**
     * 获取指定地区的所有上级地区数组
     * @param int $id 地区主键ID
     * @return array
     */
    public function fetch_parents($id, $isclear = true) {
        static $position;
        if($isclear === true) $position = array();
        $r = self::fetch_by_district_id($id);
        if($r && $r['parent_id'] > 0) {
            $position[] = $r;
            self::fetch_parents($r['parent_id'], FALSE);
        }
        if($r['parent_id'] == 0){
            $position[] = $r;
        }
        return $position;
    }

    /**
     * 获取指定地区完整路径
     * @param int $id 地区ID
     * @param string $filed 字段
     * @return array
     */
    public function fetch_position($id, $filed = 'name') {
        $position = self::fetch_parents($id);
        krsort($position);
        $result = array();
        foreach($position AS $pos) {
            $result[] = $pos[$filed];
        }
        return $result;
    }

    /**
     * 返回指定地区下级地区
     * @param int $parent_id
     * @return array
     */
    public function get_children($parent_id = 0 ,$order = '`sort` ASC,`id` ASC') {
		return pdo_fetchall("SELECT * FROM `ims_district` WHERE `parent_id`='{$parent_id}' ORDER BY `sort` ASC");
    }
    
    /**
     * 获取指定地区所有下级地区
     * @param int $id 地区ID
     */
    public function fetch_all_childrens_by_id($id = 0, $isself = 1) {
        static $ids = array();
        if($isself == 1) {
            $ids[] = $id;
        }
        $rs = $this->model->where(array('parent_id' => $id))->getField('id', TRUE);
        if($rs) {
            $ids = array_merge($ids, $rs);
            foreach($rs AS $id) {
                self::fetch_all_childrens_by_id($id, 0);
            }
        }
        return $ids;
    }

	/**
	 * 根据物流ID获取物流信息
	 * @param int $id 物流主键ID
	 * @return [result]
	 */
	public function get_by_id($id = 0) {
		$id = (int) $id;
		if ($id == 0) {
			$this->error = '物流ID必须为正整数';
			return FALSE;
		}
		$result = $this->table->find($id);
		// 获取物流地区配置
		$result['_districts'] = $this->table_district->where(array('delivery_id' => $id))->order("`id` ASC")->select();
		return $result;
	}

	/**
	 * 根据地区ID&skuids获取商家物流信息
	 * @param int	 $district_id 地区表主键id (必传)
	 * @param string $skuids 	  商品skuids (必传，格式 ：skuid1[,数量1];[skuid2[,数量2];]...)
	 * @return [result]
	 */
	public function get_deliverys($district_id = 0 ,$skuids = '') {
		$district_id = (int) $district_id;
		// 获取商家id
		$sku_arr = array_filter(explode(';', $skuids));
		$sku_ids = $nums = $arr = array();
		foreach ($sku_arr as $k => $val) {
			$arr = explode(',', $val);
			$sku_ids[] = $arr[0];
			$nums[$arr[0]] = abs((int) $arr[1]);
		}
		$sellerids = array();
		foreach ($sku_ids as $k => $skuid) {
			$sellerids[$k] = (int) model('goods/goods_sku')->where(array('sku_id' =>$skuid))->getField('seller_id');
		}
		$sellerids = array_unique($sellerids);
		if (empty($sellerids)) {
			$this->error = '商家ID不能为空';
			return FALSE;
		}
		// 当前地区id的所有父级地区
		$districtids = model('admin/district','service')->fetch_position($district_id ,'id');
		if (!$districtids) {
			$this->error = "地区不存在";
			return FALSE;
		}
		$deliverys = $sqlmap = $infos = $arr = array();
		foreach ($sellerids as $k => $sellerid) {
			foreach ($districtids as $key => $id) {
				$sqlmap['_string'] = "FIND_IN_SET($id, `district_id`)";
				$infos[] = $this->table_district->where($sqlmap)->getField('delivery_id ,id ,price', TRUE);
			}
			foreach ($infos as $val) {
				foreach ($val as $k => $v) {
					$map = array();
					$map['enabled'] = 1;
					$map['id'] = $v['delivery_id'];
					$v['_delivery'] = $this->table->where($map)->find();
					if ($v['_delivery'] == false) {
						unset($val[$k]);
						continue;
					}
					$arr[$k] = $v;
				}
			}
			$deliverys[$sellerid] = $arr;
		}
		return $deliverys;
	}
	/**
	 * 根据快递单号查询快递100获取快递信息
	 * @param  string 	$com  	快递代码 (必传)
	 * @param  string 	$nu  	快递单号 (必传)
	 * @return [result]
	 */
	public function kuaidi100($com , $nu) {
		if(empty($com) || empty($nu)) {
			return -1;
			return FALSE;
		}

		$url = 'http://www.kuaidi100.com/query?';
		$par = array();
		$par['id']     = 1;		// id
		$par['type']   = $com;	// 物流代码
		$par['postid'] = $nu;	// 快递单号
		$result = curl_get($url.http_build_query($par), false, 5);
		if($result) {
			$result =  json_decode($result, TRUE);
			if ($result['status'] == 200) {
				unset($result['status']);
				switch ($result['state']) {
					case '0':
						$result['message'] = '在途';
						break;
					case '1':
						$result['message'] = '揽件';
						break;
					case '2':
						$result['message'] = '疑难';
						break;
					case '3':
						$result['message'] = '签收';
						break;
					case '4':
						$result['message'] = '退签';
						break;
					case '5':
						$result['message'] = '派件';
						break;
					case '6':
						$result['message'] = '退回';
						break;
					default:
						$result['message'] = '其他';
						break;
				}
				return $result;
			} else if($result['status'] == 201) {
			//	$this->error = $result['message'];
			return 201;
				return FALSE;
			} else if($result['status'] == 2) {
			//	$this->error = '接口出现异常';
			return 2;
				return FALSE;
			} else {
			//	$this->error = '物流单暂无结果';
			return 100;
				return FALSE;
			}
		} else {
			return 111;
		//	$this->error = '查询失败，请稍候重试';
			return FALSE;
		}
	}

	public function kdniao($com, $nu='', $ordersn) {

		$logistics = pdo_fetch("SELECT * FROM `ims_logistics` WHERE ordersn='{$ordersn}'");

		if(empty($logistics) || ($logistics['state'] < 3 && (time() - $logistics['lastupdate'] < 3600 * 10))) {
			curl_get('http://logistics-receiver.shunliandongli.com/kdniao/subscribe?ordersn='.$ordersn, false, 2);
			$logistics = pdo_fetch("SELECT * FROM `ims_logistics` WHERE ordersn='{$ordersn}'");
		}

		if(empty($logistics)) {
			$result = array(
				'nu' => $nu,
				'com' => '-',
				'message' => '无轨迹',
				'data' => array(
					array(
						'time' => date('Y-m-d H:i:s'),
						'context' => '暂无物流信息，可能是卖家发货，快递公司尚未录入系统。'
					)
				)
			);

			return $result;
		}

		$result = array(
			'nu' => $logistics['logistic_code'],
			'com' => $logistics['shipper_name'],
		);

		switch ($logistics['state']) {
			case '0':
				$result['message'] = '无轨迹';
				break;
			case '1':
				$result['message'] = '已揽收';
				break;
			case '2':
				$result['message'] = '在途';
				break;
			case '201':
				$result['message'] = '到达派件城市';
				break;
			case '202':
				$result['message'] = '派件中';
				break;
			case '211':
				$result['message'] = '已放入快递柜或驿站';
				break;
			case '3':
				$result['message'] = '已签收';
				break;
			case '311':
				$result['message'] = '已取出快递柜或驿站';
				break;
			case '4':
				$result['message'] = '问题件';
				break;
			case '401':
				$result['message'] = '发货无信息';
				break;
			case '402':
				$result['message'] = '超时未签收';
				break;
			case '403':
				$result['message'] = '超时未更新';
				break;
			case '404':
				$result['message'] = '拒收（退件）';
				break;
			case '412':
				$result['message'] = '快递柜或驿站超时未取';
				break;
			default:
				$result['message'] = '其他';
				break;
		}

		$data = $logistics['traces'];
		$data = json_decode($data, true);
		@krsort($data);
		if(is_array($data) && $data[0]['AcceptTime']) {
			foreach($data AS $key=>$item) {
				$result['data'][$key]['time'] = $item['AcceptTime'];
				$result['data'][$key]['context'] = $item['AcceptStation'];
			}
		} else {
			$result['data'][0]['time'] = date('Y-m-d H:i:s');
			$result['data'][0]['context'] = '暂无物流信息，可能是卖家发货，快递公司尚未录入系统，请耐心等待。';
		}

		return $result;
	}

	public function expressQuery($com , $nu) {

		return self::kuaidi100($com, $nu);
	}

}
