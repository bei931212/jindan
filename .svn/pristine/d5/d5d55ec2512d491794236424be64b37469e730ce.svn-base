<?php
class CacheModel{
    private static $_instance = NULL;
    private $handdle = NULL;
    
    /**
     * @return CacheModel
     */
    final public static function getInstance()
    {
        if (!isset(self::$_instance) || !self::$_instance instanceof self) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
    * 私有实例化
    */
    private function __construct(){
        global $_W;

        $this->handdle = &$_W['mc'];
    }
    
    /**
    * 获取缓存
    */
    public function get($key){
        return $this->handdle->get($key);
    }

    /**
    * 设置缓存
    * @param $compress 是否压缩
    * @return 成功 返回True 失败False
    */
    public function set($key, $val, $expire=0, $compress=0){
        return $this->handdle->set($key, $val, $compress? MEMCACHE_COMPRESSED : $compress, $expire);
    }

    /**
    * 删除
    */
    public function delete($key){
        return $this->handdle->delete($key);
    }

    /**
    * 增加
    */
    public function increment($key, $offset=1, $expire=0){
        $flag = $this->handdle->increment($key, $offset);
        if($flag===false){
            $flag = $this->set($key, $offset, $expire);
        }
        return $flag;
    }

    /**
    * 减少
    */
    public function decrement($key, $offset=1, $expire=0){
        $flag = $this->handdle->decrement($key, $offset);
        if($flag===false){
            $flag = $this->set($key, $offset*-1, $expire);
        }
        return $flag;
    }

    /**
    * 并发操作验证验证开始
    */
    public function doBegin($key){
        $val = $this->get($key);
        if(empty($val)){
            $this->set($key, 1, 1800);
            return true;
        }else{
            return false;
        }
    }

    /**
    * 并发操作验证介绍
    */
    public function doEnd($key){
        $this->delete($key);
    }

}