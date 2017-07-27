<?php

class Pay extends Api {

	//生成支付请求数据

	//必须参数: ordersn
	//必须参数: paysn
	//必须参数: payment
	public function buildRequest($order_info) {
		global $_W;

		//插入支付记录
		if(self::insert_paylog($order_info) === true) {
			if($order_info['paytype'] == 'alipay') {
				return self::buildRequest_Alipay($order_info);
			} elseif($order_info['paytype'] == 'weixin') {
				return self::buildRequest_Weixin($order_info);
			} elseif($order_info['paytype'] == 'credit2') {
				return self::buildRequest_credit2($order_info);
			} elseif($order_info['paytype'] == 'swiftpass') {
				return self::buildRequest_Swiftpass($order_info);
			} elseif($order_info['paytype'] == 'jdpay') {
				return self::buildRequest_jdpay($order_info);
			} elseif($order_info['paytype'] == 'kuaiqian') {
				return self::buildRequest_kuaiqian($order_info);
			} elseif($order_info['paytype'] == 'unionpay') {
				return self::buildRequest_unionpay($order_info);
			} elseif($order_info['paytype'] == 'xiaoxiaopay-app') {
				return self::buildRequest_Xiaoxiaopay_app($order_info);
			}
		} else {
			return self::responseError(292, '系统错误, 请稍后重试(292).');
		}
	}

	public function insert_paylog($order_info) {
		global $_W;

		$log = pdo_fetch("SELECT * FROM `ims_paylog` WHERE `tid`='{$order_info['paysn']}'");
		if(!empty($log) && $log['status'] != '0') {
			return self::responseError(290, '这个订单已经支付成功, 不需要重复支付');
		}

		if($log['fee'] != $order_info['price']) {
			pdo_delete('paylog', array('plid' => $log['plid']));
			$log = null;
		}

		if(empty($log)) {
			$record = array();
			$record['weid'] = 2;
			$record['openid'] = '';
			$record['member_id'] = $_W['member_id'];
			$record['module'] = 'app';
			$record['type'] = $order_info['paytype'];
			$record['tid'] = $order_info['paysn'];
			$record['fee'] = $order_info['price'];
			$record['status'] = '0';

			if(!pdo_insert('paylog', $record)) {
				return self::responseError(291, '系统错误, 请稍后重试.');
			}
		} else {
			if($log['type'] != $order_info['paytype']) {
				pdo_update('paylog', array('type'=>$order_info['paytype']), array('plid' => $log['plid']));
			}
		}

		return true;
	}

	public function buildRequest_Alipay($order_info) {
		global $_W;

        $subject = $order_info['subject'] ? mb_substr($order_info['subject'],0,20,'utf-8') : '顺联动力';
		$url = 'http://pay.shunliandongli.com/request/alipay?tid='.$order_info['paysn'].'&subject='.rawurlencode($subject);
		$string = curl_get($url, true, 5);
		if(empty($string)) {
			$string = curl_get($url, true, 5);
		}
		if(empty($string)) {
			$string = curl_get($url, true, 5);
		}
		if(empty($string)) {
			$string = 'null';
		}

		return $string;
	}

	public function buildRequest_Weixin($order_info) {
		return null;

		/**
		https://api.mch.weixin.qq.com/pay/unifiedorder
		appid
		mch_id
		nonce_str
		sign
		body
		attach
		out_trade_no
		fee_type=CNY
		total_fee
		spbill_create_ip
		time_start
		time_expire
		notify_url
		trade_type=APP

		*/
	}

	public function buildRequest_Swiftpass($order_info) {
		global $_W;

        $subject = $order_info['subject'] ? $order_info['subject'] : '顺联动力';
		$url = 'http://pay.shunliandongli.com/request/swiftpass?tid='.$order_info['paysn'].'&subject='.rawurlencode($subject).'&device_info='.self::$platform;
		$string = curl_get($url, true, 5);
		if(empty($string)) {
			$string = curl_get($url, true, 5);
		}
		if(empty($string)) {
			$string = curl_get($url, true, 5);
		}
		if(empty($string)) {
			$string = 'null';
		}

		return $string;
	}

	public function buildRequest_jdpay($order_info) {
		global $_W;

        $subject = $order_info['subject'] ? $order_info['subject'] : '顺联动力';
		return 'https://pay.shunliandongli.com/request/jdpay?tid='.$order_info['paysn'].'&subject='.rawurlencode($subject).'&member_id='.compute_id($_W['member_id'], 'ENCODE');
	}

	public function buildRequest_unionpay($order_info) {
		global $_W;

        $subject = $order_info['subject'] ? $order_info['subject'] : '顺联动力';
		return 'https://pay.shunliandongli.com/request/unionpay?tid='.$order_info['paysn'].'&subject='.rawurlencode($subject);
	}

	public function buildRequest_kuaiqian($order_info) {
		global $_W;

        $subject = $order_info['subject'] ? $order_info['subject'] : '顺联动力';
		return 'https://pay.shunliandongli.com/request/kuaiqian?tid='.$order_info['paysn'].'&subject='.rawurlencode($subject).'&member_id='.compute_id($_W['member_id'], 'ENCODE');
	}


	public function buildRequest_credit2($order_info) {

		return 'https://pay.shunliandongli.com/request/credit2?tid='.$order_info['paysn'];
	}

	public function buildRequest_Xiaoxiaopay_app($order_info) {
		global $_W;

        $subject = $order_info['subject'] ? $order_info['subject'] : '顺联动力';
		$url = 'http://pay.shunliandongli.com/request/xiaoxiaopay-app?tid='.$order_info['paysn'].'&subject='.rawurlencode($subject).'&ip='.CLIENT_IP;

		$result = curl_get($url, true, 5);
		if(empty($result)) {
			$result = curl_get($url, true, 5);
		}
		if(empty($result)) {
			$result = curl_get($url, true, 5);
		}
		if(empty($result)) {
			return (object)NULL;
		}

		$callback = json_decode($result);
		if($callback->code == 0 && is_object($callback->data)) {
			return $callback->data;
		}

		return (object)NULL; //强制输出为对象
	}

	//获取支付方式列表
	public function payment() {

		$payments = array(
			/*
			array(
				'paytype' => 'weixin',
				'payname' => '微信支付',
				'call_mode' => 'app',
				'icon'	=> 'weixin',
				'default'	=> 0
			),
			*/

			array(
				'paytype' => 'alipay',
				'payname' => '支付宝【推荐】',
				'call_mode' => 'app',
				'icon'	=> 'alipay',
				'default'	=> 1
			),

			array(
				'paytype' => 'jdpay',
				'payname' => '京东支付',
				'call_mode' => 'h5',
				'icon'	=> 'jdpay',
				'default'	=> 0
			),

			array(
				'paytype' => 'unionpay',
				'payname' => '银联支付',
				'call_mode' => 'h5',
				'icon'	=> 'unionpay',
				'default'	=> 0
			),
/*
			array(
				'paytype' => 'swiftpass',
				'payname' => '微信支付',
				'call_mode' => 'app',
				'icon'	=> 'swiftpass',
				'default'	=> 0
			),
*/
/*
			array(
				'paytype' => 'kuaiqian',
				'payname' => '快钱支付',
				'call_mode' => 'h5',
				'icon'	=> 'kuaiqian',
				'default'	=> 0
			),
*/


			array(
				'paytype' => 'credit2',
				'payname' => '余额支付',
				'call_mode' => 'h5',
				'icon'	=> 'credit2',
				'default'	=> 0
			),
			/*
			array(
				'paytype' => 'xiaoxiaopay',
				'payname' => '微信支付',
				'call_mode' => 'app',
				'icon'	=> 'weixin',
				'default'	=> 0
			)
			

			array(
				'paytype' => 'xiaoxiaopay-app',
				'payname' => '微信支付',
				'call_mode' => 'app',
				'icon'	=> 'weixin',
				'default'	=> 0
			)*/
		);



		if((self::$platform == 'Android' && version_compare(self::$client_version, '1.3.6', '>')) || (self::$platform == 'IOS' AND version_compare(self::$client_version, '1.2.5', '>'))) {
			$payments[] = array(
				'paytype' => 'xiaoxiaopay-app',
				'payname' => '微信支付',
				'call_mode' => 'app',
				'icon'	=> 'weixin',
				'default'	=> 0
			);
		}

		if(self::$platform == 'IOSs') {
			foreach($payments AS $key => $val) {
				if($val['paytype'] == 'alipay') {
					unset($payments[$key]);
				}
				/**/
				if($val['paytype'] == 'jdpay') {
					$payments[$key]['default'] = 1;
				}
			}

			$payments2 = array();
			foreach($payments AS $val) {
				$payments2[] = $val;
			}
			$payments = $payments2;
		}

		return self::responseOk($payments);

	}
}