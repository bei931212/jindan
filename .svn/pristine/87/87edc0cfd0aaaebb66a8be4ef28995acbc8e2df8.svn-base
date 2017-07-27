<?php
/**
 * autoloader 类
 */
class Autoloader {
	
	private static $loader;
	/**
	 * 构造函数
	 */
	private function __construct() {
		spl_autoload_register ( array ($this, 'inc_class' ) );
	}
	
	private function inc_class($className) {
	    if(substr($className,-5)=='Model'){
	        $className = substr($className,0,-5);
    		$filename = $className . '.class.php';
    		$filepath = IA_ROOT .'/source/model/'. $filename;
    		if (file_exists ( $filepath )) {
    			return include $filepath;
    		} else {
    			$this->err_fn ( $className );
    		}
	    }
	}
	
	public static function init() {
		// 静态化自调用
		if (self::$loader == NULL)
			self::$loader = new self ();
		
		return self::$loader;
	}
	
	// 文件出错提示
	private function err_fn($className) {
		echo "class $className files includes err!!";
		exit ();
	}
}
?>