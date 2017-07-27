<?php
class SearchLogModel{
	private static $_instance = NULL;

	final public static function getInstance()
	{
		if (!isset(self::$_instance) || !self::$_instance instanceof self) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	public function write($keyword='') {
		if(empty($keyword)) return;

		$log = $this->get($keyword);
		if($log['lasttime']) {
			pdo_query("UPDATE `ims_bj_qmxk_search_log` SET `count`=`count`+1,`lasttime`='".TIMESTAMP."' WHERE `keyword`='{$keyword}'");
		} else {
			pdo_insert('bj_qmxk_search_log', array(
				'keyword' => $keyword,
				'count' => 1,
				'lasttime' => TIMESTAMP
			), array('keyword'=>$keyword));
		}

		return true;
	}

	public function get($keyword) {
		$sql = "SELECT lasttime FROM `ims_bj_qmxk_search_log` WHERE `keyword`=:keyword";

		return pdo_fetch($sql,array(':keyword'=>$keyword));
	}

}
