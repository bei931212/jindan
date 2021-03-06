<?php
define('YOUR_TOKEN_HERE', 'your-token');
define('TOKEN_COOKIE_NAME', 'token');
class Api
{
    const STATUS_OK = 'OK';
    const STATUS_ERR = 'ERROR';
    private static $supported_versions = array(
        'v1.0',
    );
    private static $supported_formats = array(
        'json',
    );
    private static $main_version = 'v1.0';
    private static $main_format = 'json';
    protected static $request_api = null;
    private static $request_func = null;
	public static $child_func = null;
    private static $request_version = null;
    private static $request_format = 'json';
    public static $request_device_id = null;
    public static $request_user_agent = null;
	/*
    private static $public_routes = array(
        'system' => array(
			'regex' => 'system',
        ),
        'records' => array(
            'regex' => 'records(?:/?([0-9]+)?)',
        ),
        'home' => array(
            'regex' => 'home',
        ),
    );
	*/
    public static $input = null;
    public static $input_data = array();
	public static $platform = '';
	public static $client_version = '1.0.0';
	public static $device_version = null;
	public static $test = true;

    public static function serve()
    {

        $path_info = '/';
        if (!empty($_SERVER['PATH_INFO'])) {
            $path_info = $_SERVER['PATH_INFO'];
        } else {
            if (!empty($_SERVER['REQUEST_URI'])) {
                if (strpos($_SERVER['REQUEST_URI'], '?') > 0) {
                    $path_info = strstr($_SERVER['REQUEST_URI'], '?', true);
                } else {
                    $path_info = $_SERVER['REQUEST_URI'];
                }
            }
        }
        preg_match('/^\/v\d\/(.+)\/(.+)\.(.+)$/', $path_info, $request_info);
		if(empty($request_info) || !isset($request_info[1]) || !isset($request_info[2]) || !isset($request_info[3])) {
			self::responseError(400, 'There is no route for this request. ');
		}

        //self::$request_version = $request_info[1];
        self::$request_api = ucfirst($request_info[1]);
        self::$request_func = $request_info[2];
        self::$request_format = $request_info[3];

		if(self::$request_api == 'Goods' && self::$request_func == 'detail') {
			self::$request_format = 'html';
		}
		if(self::$request_api == 'Special' && self::$request_func == 'index') {
		    self::$request_format = 'html';
		}

        self::$request_user_agent = trim($_SERVER['HTTP_USER_AGENT']);
        self::$request_device_id = trim($_SERVER['HTTP_X_DEVICE_ID']);
		
		$host=strtolower($_SERVER['HTTP_HOST']);
		$host_ary=explode('.',$host);
		if($host_ary[0]=='api-test'){
		}else{
			if((empty(self::$request_user_agent) || !preg_match('/^shunlian/i', self::$request_user_agent)) && !(self::$request_api == 'Goods' && self::$request_func == 'detail') && !(self::$request_api == 'Special' && self::$request_func == 'show') && !(self::$request_api == 'Games') && (self::$request_format != 'html')) {
				self::responseError(400, 'This device is not support(-1).'.self::$request_user_agent);
			}
		}
//		if(empty(self::$request_user_agent) || !preg_match('/^shunlian/i', self::$request_user_agent)) {
//			self::responseError(400, 'This device is not support(-1).'.self::$request_user_agent);
//		}

		//平台
		self::$platform = explode(' ', self::$request_user_agent);
		self::$platform = strtolower(self::$platform[1]);
		if(self::$platform == 'iphone') {
			self::$platform = 'IOS';
		} elseif(self::$platform == 'android') {
			self::$platform = 'Android';
		} elseif(self::$platform == 'mobile') {
			self::$platform = 'Touch';
		}
		
		if(trim($_SERVER['HTTP_SAFE_TYPE'])=='OFF'){
			global $_W;
			$_W['attachurl'] = 'http://img01.shunliandongli.com/attachment/';
		}

		$client_version_tmp = explode('/', self::$request_user_agent);
		self::$client_version = $client_version_tmp[1];

		self::$device_version = $client_version_tmp[0];
		self::$device_version = explode(' ', self::$device_version);
		self::$device_version = self::$device_version[2];
		
		$host=strtolower($_SERVER['HTTP_HOST']);
		$host_ary=explode('.',$host);
		if($host_ary[0]=='api-test'){
		}else{
			if (self::$request_api == 'Games') {
			} else if ((empty(self::$request_device_id) ||!preg_match('/[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}/', self::$request_device_id)) && $_SERVER['HTTP_USER_AGENT'] != 'ShunLian Mobile 1.0' && !(self::$request_api == 'Goods' && self::$request_func == 'detail') && !(self::$request_api == 'Special' && self::$request_func == 'show') && (self::$request_format != 'html')) {
				self::responseError(400, 'This device is not support.');
			}
		}
//		if((empty(self::$request_device_id) ||!preg_match('/[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}/', self::$request_device_id)) && $_SERVER['HTTP_USER_AGENT'] != 'ShunLian Mobile 1.0' && !(self::$request_api == 'goods' && self::$request_func == 'detail')) {
//			self::responseError(400, 'This device is not support.');
//		}

		/*
		self::$input = file_get_contents('php://input');
        // For PUT/DELETE there is input data instead of request variables
        if (!empty(self::$input)) {
            preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
             if (isset($matches[1]) && strpos(self::$input, $matches[1]) !== false) {
                $this->parse_raw_request(self::$input, self::$input_data);
            } else {
                parse_str(self::$input, self::$input_data);
            }
        }
		*/
        $request_method = strtolower($_SERVER['REQUEST_METHOD']);
        // If this is OPTIONS request return it right now
        if ($request_method == 'get' || $request_method == 'post') {
		/*
            $handler = null;
            // How url should start, example: /api/v1.0/
            $url_start = '/(?:/)/';
            // How url should end, example: .json
            $url_end = '\.(?:'.implode('|', self::$supported_formats).')';
            foreach (self::$public_routes as $handler_name => $route_config) {
                $regex = $url_start.$route_config['regex'].$url_end;
                if (preg_match('#^'.$regex.'$#', $path_info, $params_matches)) {
                    $handler = $handler_name;
                    break;
                }
            }

            if (!$handler) {
                self::responseError(400, '接口不存在');
            }
		*/

		//	$_GET[]
			if($request_method == 'post') {
			//	print_r(file_get_contents('php://input'));
			//	parse_str(json_decode(file_get_contents('php://input')), $_POST);
				$_POST = json_decode(file_get_contents('php://input'), true);
				$params_matches = array();
				if(self::$test && $_POST['testMode'] == 'YES') {
					self::$test = true;
				} else {
					self::$test = false;
				}
			} else {
				$params_matches = array_splice($_GET, 0 ,1);
				self::$test = false;
			}

            preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
             if (isset($matches[1]) && strpos(self::$input, $matches[1]) !== false) {
                $this->parse_raw_request(self::$input, self::$input_data);
            } else {
                parse_str(self::$input, self::$input_data);
            }

			if (!file_exists(IA_ROOT.'/source/apis/'.self::$request_api.'.php')) {
				self::responseError(400, 'API '.self::$request_api.' is not supported. ');
			}
			require IA_ROOT.'/source/apis/'.self::$request_api.'.php';
/**/
			if(strpos(self::$request_func, '.')) {
				self::$request_func = explode('.', self::$request_func);
				self::$child_func = self::$request_func[1];
				self::$request_func = self::$request_func[0];
			}

            $api_object = new self::$request_api();
            if (!method_exists($api_object, self::$request_func)) {
				self::responseError(400, 'Method '.self::$request_func.' in API '.self::$request_api.' is not found.');
            }
            // Finally call to our inner class
            call_user_func_array(array($api_object, self::$request_func), $params_matches);

        } else {
			self::responseError(400, 'This method is not support. ');
        }
    }
/**
 * Helper method to parse raw requests
*/
private static function parse_raw_request($input, &$a_data)
{
    // grab multipart boundary from content type header
    preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
    $boundary = $matches[1];
    // split content by boundary and get rid of last -- element
    $a_blocks = preg_split("/-+$boundary/", $input);
    array_pop($a_blocks);
    // loop data blocks
    foreach ($a_blocks as $id => $block) {
        if (empty($block)) {
            continue;
        }
        // parse uploaded files
        if (strpos($block, 'application/octet-stream') !== false) {
            // match "name", then everything after "stream" (optional) except for prepending newlines
            preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
        // parse all other fields
        } else {
            // match "name" and optional value in between newline sequences
          preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
        }
        $a_data[$matches[1]] = $matches[2];
    }
}

    // This method will handle both cross origin and same domain requests
    public static function outputHeaders($cookies = array())
    {
		/*
        $referer = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;
        if (!$referer) {
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        }
        $origin = '*';
        // If we have referer information try to parse it
        if ($referer) {
            $info = parse_url($referer);
            if ($info && isset($info['scheme']) && ($info['scheme'] == 'http' || $info['scheme'] == 'https')) {
                $origin = $info['host'];
                if ($origin == $_SERVER['HTTP_HOST']) {
                    $origin = $info['scheme'].'://'.$origin;
                } else {
                    $origin = '*';
                }
            }
        }
		*/
		/*
        // Do not send any cookies that might be issued
        header_remove('Set-Cookie');
        // If this is packaged app or request from 3rd party, append auth token to the headers
        if ($origin == '*' || (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) && !empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Expose-Headers: x-authorization');
            header('Access-Control-Allow-Headers: origin, content-type, accept, x-authorization');
            header('X-Authorization: '.YOUR_TOKEN_HERE);
        // Or if this is simple crossdomain call from our domain
        } else {
            header('Access-Control-Allow-Origin: '.$origin);
            header('Access-Control-Expose-Headers: set-cookie, cookie');
            header('Access-Control-Allow-Headers: origin, content-type, accept, set-cookie, cookie');
            // Allow cookie credentials because we're on the same domain
            header('Access-Control-Allow-Credentials: true');
            // Let's set all the cookies we want except for options method. It does not support them.
            if (strtolower($_SERVER['REQUEST_METHOD']) != 'options') {
                setcookie(TOKEN_COOKIE_NAME, YOUR_TOKEN_HERE, time()+86400*30, '/', '.'.$_SERVER['HTTP_HOST']);
                // Any other cookies
                if (sizeof($cookies)) {
                    foreach ($cookies as $cookie) {
                        call_user_func_array('setcookie', $cookie);
                    }
                }
            }
        }
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
        header('Access-Control-Max-Age: 86400');
		*/
        header('Access-Control-Allow-Methods: GET, POST');
    }

    public static function responseOk($result = array(), $metadata = array(), $cookies = array())
    {
        // For now we will support only this
        if (self::$request_format == 'json') {
            //http_response_code(200);
            header('Content-type: application/json; charset=utf-8');
            header("Access-Control-Allow-Origin: *");
            self::outputHeaders($cookies);
			if(self::$test) {
				$ret = json_encode(array(
					'status' => 0,
					'data' => $result,
					'request'=>$_POST,
                    'request_raw'=>file_get_contents('php://input'),
				));
			} else {
                $ret = json_encode(array(
					'status' => 0,
					'data' => $result
				));
			}
            // 6月7 8 9号过滤“隐形”
            $date = date('m-d', time());
            if (($date == '06-07') || ($date == '06-08') || ($date == '06-09')) {
                $ret = str_replace('\u9690\u5f62', '', $ret);
				$ret = str_replace('隐形', '', $ret);
            }
			echo $ret;
        } elseif(self::$request_format == 'xml') {
            header('Content-type: application/xml; charset=utf-8');
            self::outputHeaders($cookies);
            echo self::asXML(array(
                'status' => 0,
                'data' => $result,
            ));
		} elseif(self::$request_format == 'html' && self::$request_api == 'Goods' && self::$request_func == 'detail') {
			header('Content-type: text/html; charset=utf-8');
			echo self::asHTML($result);
		} elseif(self::$request_format == 'png' && self::$request_api == 'User' && self::$request_func == 'captcha') {
			echo self::$result;
		}elseif(self::$request_format == 'html' && self::$request_api == 'Special' && self::$request_func == 'index') {
			header('Content-type: text/html; charset=utf-8');
			echo self::asSpecialHTML($result);
		}
		exit;
    }
    public static function responseError($code = 404, $info = null, $debug_data=array())
    {
		self::outputHeaders();
        //http_response_code($code);
        if (self::$request_format == 'json') {
            header('Content-type: application/json; charset=utf-8');
            header("Access-Control-Allow-Origin: *");
			if(self::$test) {
				echo json_encode(array(
					'status' => $code,
					'error' => $info,
					'request'=>$_POST,
                    'debug_data' => $debug_data,
                    'request_raw'=>file_get_contents('php://input'),
				));
			} else {
				echo json_encode(array(
					'status' => $code,
					'error' => $info
				));
			}
        } elseif(self::$request_format == 'xml') {
            header('Content-type: application/xml; charset=utf-8');
            echo self::asXML(array(
                'status' => $code,
                'error' => $info,
            ));
		} else {
			if(self::$request_api == 'Special' && self::$request_func == 'index'){
			    header('Content-type: text/html; charset=utf-8');
			    echo self::asHTML($info);
			}
		    if(self::$request_format == 'html' && self::$request_api == 'Goods' && self::$request_func == 'detail') {
				header('Content-type: text/html; charset=utf-8');
				echo self::asHTML($info);
			}else {
				echo json_encode(array(
					'status' => $code,
					'error' => 'This format \''.self::$request_format.'\' is not supported!',
				));
			}
		}
		exit();
    }

    public function asXML($pMessage)
    {
        $xml = self::toXml($pMessage);
        $xml = "<?xml version=\"1.0\"?>\n<response>\n$xml</response>\n";

        //$this->setContentLength($xml);
        return $xml;

    }

    /**
     * @param  mixed  $pData
     * @param  string $pParentTagName
     * @param  int    $pDepth
     * @return string XML
     */
    public function toXml($pData, $pParentTagName = '', $pDepth = 1)
    {
        if (is_array($pData)) {
            $content = '';

            foreach ($pData as $key => $data) {
                $key = is_numeric($key) ? $pParentTagName.'-item' : $key;
                $content .= str_repeat('  ', $pDepth)
                    .'<'.htmlspecialchars($key).'>'.
                    self::toXml($data, $key, $pDepth+1)
                    .'</'.htmlspecialchars($key).">\n";
            }

            return $content;
        } else {
            return htmlspecialchars($pData);
        }

    }
    
    public function asSpecialHTML($data){
        $content = $data['content'];
        $css = $data['css'];
        $title = $data['title'];
        $content = preg_replace(array(
            '/<a([^>]+)href="\/([^\/]+)\/([^>]+)">/i'
            //,'/<img([^>]+)src="([^>]+)"([^>]*)>/i'
        ),array(
            "<a href=\"slmall://goods/item.json?goodsId=$3\">"
            //,"<img class=\"lazy\" data-original=\"$2\"$3>",
        ), $content);
        
        $html = '<!DOCTYPE html><html><head><title>'.$title.'</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="format-detection" content="telephone=no">
<link href="https://statics.shunliandongli.com/source/modules/bj_qmxk/recouse/css/dzd_bjcommon.css?v=20160529" rel="stylesheet" type="text/css">
<script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript" src="https://apps.bdimg.com/libs/jquery-lazyload/1.9.5/jquery.lazyload.js"></script>
<script type="text/javascript">
$(function(){
	$("img.lazy").lazyload({
		placeholder: "https://img.alicdn.com/imgextra/i2/26049987/TB2dk_WfpXXXXX2XXXXXXXXXXXX-26049987.gif",
		effect: "fadeIn"
	});
});
</script><style type="text/css">'.$css.'</style>
</head>
<body style="margin:0 auto; padding:0 auto">
<div id="viewport" class="viewport">
 <div id="home-page" data-role="page" data-member-sn="ejWCX" data-member-subscribe="true">
  <div role="main" class="ui-content ">
  <div class="zidingyi">'.$content.'</div></div></div></div></body></html>';
        return $html;
    }

    public function asHTML($pMessage='') {

		$pMessage = preg_replace(array(
			'/max-width:([^>]+);/i',
			'/data-mce-style="([^>]+)"/i',
			'/<img([^>]+)src="([^>]+)"([^>]*)>/i',
			'/<table([^>]+)width\:([^>]+);([^>]*)>/i'
		), array(
			'',
			'',
			"<img class=\"lazy\" data-original=\"$2\"$3>",
			"<table $1 width:100%;$3>"
		), $pMessage);

		$html = "<!DOCTYPE html>\n".
				"<html>\n".
				"<head>\n".
				"<meta charset=\"utf-8\">\n".
				"<meta name=\"viewport\" content=\"initial-scale=1, maximum-scale=3, minimum-scale=1, user-scalable=no\">\n".
				"<meta name=\"format-detection\" content=\"telephone=no\">\n".
				"<script type=\"text/javascript\" src=\"https://apps.bdimg.com/libs/jquery/1.7.2/jquery.min.js\"></script>\n".
				"<script type=\"text/javascript\" src=\"https://apps.bdimg.com/libs/jquery-lazyload/1.9.5/jquery.lazyload.js\"></script>\n".
				"<link rel=\"stylesheet\" type=\"text/css\" href=\"https://h5.api.shunliandongli.com/v1/statics/css/goods_detail.css?v=20160622001\">\n".
				"<script type=\"text/javascript\">\n".
				"\$(document).ready(function(){\$(\"img.lazy\").lazyload()});".
				"/*\$(document).ready(function(){\$(\"img.lazy\").lazyload({no_fake_img_loader:false,effect : \"fadeIn\"});});0*/\n".
				"\n".
				"</script>\n".
				"<title>顺联动力商城</title>\n".
				"</head>\n".
				"<body>\n".
				"<div class=\"pro-detial\" id=\"pro-detial\">\n".
				"{$pMessage}\n".
				"</div>\n".
				"</body>\n".
				"</html>";

        return $html;
	}
}
