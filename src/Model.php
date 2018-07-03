<?php
namespace mon;

use \mon\Db;

/**
* 模型基类
*
* @author Mon 985558837@qq.com
* @version v1.0
*/
class Model
{
    /**
     * 模型操作表名
     *
     * @var string
     */
    public $table;

    /**
     * DB配置
     *
     * @var [type]
     */
    public $config = [];

    /**
     * 获取DB实例
     *
     * @return [type] [description]
     */
    public function db($newLink = false)
    {
        if(empty($this->config))
        {
            $this->config = Db::getConfig();
        }
        // 获取DB实例
        $connect =  Db::connect($this->config);
        if(!empty($this->table))
        {
            $connect = $connect->table($this->table);
        }

        return $connect;
    }

    /**
     * 动态调用
     * 
     * @param  [type] $method [description]
     * @param  [type] $args   [description]
     * @return [type]         [description]
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->db(), $method], $args);
    }

    /**
     * 静态调用
     *
     * @param  [type] $method [description]
     * @param  [type] $args   [description]
     * @return [type]         [description]
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([(new static())->db(), $method], $args);
    }
}
