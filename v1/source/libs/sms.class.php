<?php

class SMS {
	private $apikey = 'b8a4bf7a0ade97afedd9a260943d1978';
	private $apiurl = '';
	public $post_data = array();
	public $timeout = 30;

	public function __construct() {

		$this->apikey = 'b8a4bf7a0ade97afedd9a260943d1978';
		$this->apiurl = '';
		$this->post_data = array();
		$this->timeout = 30;
	}

	function setParameter($parameter, $parameterValue) {
		$this->$parameter = $parameterValue;
	}

	public function get_user($ch,$apikey){
		curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v1/user/get.json');
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('apikey' => $apikey)));
		return curl_exec($ch);
	}
	public function send_code(){
		$this->apiurl = 'https://sms.yunpian.com/v1/sms/send.json';
		$this->text = '您的验证码为：'.$this->text.'（有效期10分钟，请勿透漏给任何人）';

		return $this->curl();
	}

	private function curl() {
		if(!function_exists('curl_init')) return false;

		$this->post_data = http_build_query(array(
			'apikey'=>$this->apikey,
			'mobile'=>$this->mobile,
			'text'=>'【顺联动力】'.$this->text
		));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->apiurl);
		strpos($this->apiurl, 'https') === 0 && curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 0);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept:application/json;charset=utf-8", "Content-Type:application/x-www-form-urlencoded;charset=utf-8"));
		$gzip && curl_setopt($ch, CURLOPT_ENCODING , 'gzip');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_data);

		$data = curl_exec($ch);
		curl_close($ch);
		if($data) {
			return json_decode($data, true);
		} else {
			return false;
		}
	}
}

?>