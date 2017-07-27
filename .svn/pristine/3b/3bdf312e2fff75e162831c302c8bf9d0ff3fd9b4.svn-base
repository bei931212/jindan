<?php

class Info extends Api {

//获取最近支付用户

	//检查更新
	//type=Android|IOS
	//version=x.x.x
	public function checkUpdate() {

		$type = trim($_GET['type']);
		$version = trim($_GET['version']);

		$result = array();

		$result['needUpdate'] = 'no';
		$result['updateType'] = 'optional';//optional force,升级方式，可选，强制
		$result['localVersion'] = $version;

		if($result['localVersion'] == '1.1.3') {
			$result['changeLog'] = " 由于系统原因，请先卸载当前版本，然后在顺联动力官网或者应用市场下载最新版本，\n给您带来不便，敬请谅解，谢谢！\n";
		} else {
			$result['changeLog'] = "顺联家人们大家好~~\n";
			$result['changeLog'] .= "夏天除了西瓜、啤酒、烧烤，还有我们酷酷的设计狮团队，这一次外观更新了，整体更加小清新，简约~~\n";
			$result['changeLog'] .= "我们也修改了一些小小的交互体验，让大家体验更加好。\n";
			$result['changeLog'] .= "当然了，更多好用好玩的新功能以及新想法在接下去的一段时间也会尽快去实现出来。\n";


			$result['changeLog'] .= "我们不断改进产品的功能和服务，力求为消费者提供更优质、便捷的购物体验。\n";
			$result['changeLog'] .= "给我们留下您的反馈和评分吧，您的好评是我们继续做好商城的动力，因为你们，顺联动力变得越来越好";

		}

//		$result['newVersion'] = $result['localVersion'] == '1.1.4' ? '1.1.6' : '1.0.0';
		$result['newVersion'] = '1.5.6';
		$result['updateType'] = 'optional';
		if($type == 'IOS') {
			$result['updateUrl'] = 'https://itunes.apple.com/cn/app/xxx/idxxx?mt=8';
		} else {
			if(version_compare($result['localVersion'], $result['newVersion'], '<')) {
				$result['needUpdate'] = 'yes';
			}
			if(self::$platform == 'Android' AND version_compare(self::$device_version, '4.0.0', '<')) {
				$result['needUpdate'] = 'no';
			}
			if(version_compare($result['localVersion'], '1.4.5', '<')) {
				$result['needUpdate'] = 'yes';
				$result['updateType'] = 'force';
			}
			$result['fileMd5'] = '285918067e3b2048c07cd9a57052b082';
			$result['updateUrl'] = 'https://down.shunliandongli.com/app/Android_'.$result['newVersion'].'.apk';
		}

		return self::responseOk($result);
	}

	//错误日志
	//type=Android|IOS
	//version=x.x.x
	//error_log=string
	public function errorLog() {
		$insert['type'] = trim($_POST['type']);
		$insert['version'] = trim($_POST['version']);
		$insert['dateline'] = TIMESTAMP;
		$insert['error_log'] = trim($_POST['error_log']);

		pdo_insert('app_error_log', $insert);

		return self::responseOk('OK');
	}
}