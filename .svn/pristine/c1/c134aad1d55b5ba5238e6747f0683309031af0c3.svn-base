<?php

class Notice extends My {

	//消息首页
	public function index() {
		global $_W;

		
	}

	//是否有新消息
	public function hasNew() {
		global $_W;

		$item_notice = pdo_fetch("SELECT COUNT(*) AS count FROM `ims_notice_public`");
		$item_status = pdo_fetch("SELECT COUNT(*) AS count FROM `ims_notice_public_status` WHERE member_id='{$_W['member_id']}'");
		if($item_notice['count'] > $item_notice['count']) {
			return self::responseOk('yes');
		}

		return self::responseOk('no');
	}

	//消息列表
	//可选参数: count=获取数量，最大40，默认10
	//可选参数: page=分页
	public function itemList() {
		global $_W;


/*
		pdo_insert('notice_public', array(
			'title'	=> '测试发布第二条公共消息',
			'content'	=> 'ajsldkjlsakdjlsakdjsalkdjsadiwdlsakjdlaskd<br>lkasjdlamxlkasdiwd',
			'dateline'	=> TIMESTAMP
		));
*/
		$count = intval($_GET['count']);
		$page = max(1, intval($_GET['page']));

		$count = ($count && $count <= 40) ? $count : 20;

		$where = "1";
		$limit = ($page-1)*$count.','.$count;
		$items_end = array();

		//获取公共消息
		$items = pdo_fetchall("SELECT np.*, nps.member_id FROM `ims_notice_public` np ".
			"LEFT JOIN `ims_notice_public_status` nps ON (nps.notice_id=np.id AND nps.member_id='{$_W['member_id']}') ".
			"WHERE {$where} ORDER BY np.id DESC LIMIT {$limit}");

		foreach($items AS $item) {
			$item['noticeId']	= $item['id'];
			$item['sender']		= '系统消息';
			$item['logo']		= 'https://statics.shunliandongli.com/resource/image/logo/system.png';
			$item['isnew']		= $item['member_id'] ? 0 : 1;
			$item['dateline']	= $item['dateline'] + 604800 > TIMESTAMP ? fcm_gmdate('m-d H:i', $item['dateline'], true) : fcm_gmdate('Y-m-d', $item['dateline']);
			$item['content']	= cutstr($item['content'], 30, 1);

			unset($item['member_id']);
			unset($item['id']);

			$items_end[]		= $item;
		}
		unset($items);

		$results = array();
		$results['itemCount'] = pdo_fetchcolumn("SELECT COUNT(*) FROM `ims_notice_public` np WHERE {$where}");
		$results['itemCount'] = intval($results['itemCount']);
		$results['allPage'] = ceil($results['itemCount']/$count);
		$results['page'] = $page;
		$results['count'] = $count;
		$results['items'] = $items_end;

		return self::responseOk($results);
	}

	//查看消息
	public function item() {
		global $_W;

		$id = intval($_GET['noticeId']);
		if(empty($id)) return self::responseError(10200, 'Parameter [noticeId] is missing.');

		$item = pdo_fetch("SELECT * FROM `ims_notice_public` WHERE id='{$id}'");
		if(empty($item)) return self::responseError(10201, 'Item is not found.');

		$item['noticeId'] = $item['id'];
		$item['dateline'] = $item['dateline'] + 604800 > TIMESTAMP ? fcm_gmdate('m-d H:i', $item['dateline'], true) : fcm_gmdate('Y-m-d', $item['dateline']);

		unset($item['id']);

		//设置为已读
		if(!pdo_fetch("SELECT * FROM `ims_notice_public_status` WHERE member_id='{$_W['member_id']}' AND notice_id='{$id}'")) {
			pdo_insert('notice_public_status', array('member_id'=>$_W['member_id'], 'notice_id'=>$id));
		}

		return self::responseOk(array('item'=>$item));
	}

	//删除消息
	public function delete() {
		global $_W;

		$id = intval($_GET['noticeId']);
		if(empty($id)) return self::responseError(10300, 'Parameter [noticeId] is missing.');

		return self::responseOk('删除成功.');
	}

}