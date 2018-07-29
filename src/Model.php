<?php
namespace mon;

use mon\Db;
use mon\model\AutoComplete;

/**
* 模型基类
*
* @author Mon 985558837@qq.com
* @version v1.0
*/
abstract class Model
{
    use AutoComplete;

    /**
     * 操作数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 模型操作表名
     *
     * @var string
     */
    protected $table;

    /**
     * DB配置
     *
     * @var [type]
     */
    protected $config = [];

    /**
     * 错误信息
     *
     * @var string
     */
    protected $error = '';

    /**
     * 获取错误信息
     *
     * @return [type] [description]
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = '';
        return $error;
    }

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
        $connect =  Db::connect($this->config, $newLink)->model($this);
        if(!empty($this->table))
        {
            $connect = $connect->table($this->table);
        }

        return $connect;
    }

    /**
     * 设置查询场景, 相当于查询前置条件
     *
     * @param  [type] $name         场景名称或者闭包函数
     * @return \mon\db\Connection   返回DB操作实例
     */
    public function scope($name)
    {
        if($name instanceof \Closure){
            return call_user_func($name, $this->db());
        }
        $method = 'scope' . ucfirst($name);
        if(method_exists($this, $method)){
            return $this->$method($this->db());
        }
        throw new MondbException(
            'The scope is not found ['.$method.']', 
            MondbException::SCOPE_NULL_FOUND
        );  
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
