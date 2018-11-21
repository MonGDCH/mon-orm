<?php
namespace mon;

use mon\Db;
use mon\model\Data;
use mon\model\DataCollection;
use mon\exception\MondbException;

/**
* 模型基类
*
* @author Mon 985558837@qq.com
* @version v1.0
*/
abstract class Model
{
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
     * 更新操作自动完成字段配置
     *
     * @var array
     */
    protected $update = [];

    /**
     * 新增操作自动完成字段配置
     *
     * @var array
     */
    protected $insert = [];

    /**
     * 查询后字段完成的数据
     *
     * @var array
     */
    protected $append = [];

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
     * @param  [type] $args         可变传参
     * @return \mon\db\Connection   返回DB操作实例
     */
    public function scope($name, ...$args)
    {
        // 固定第一个参数为Db实例
        array_unshift($args, $this->db());

        if($name instanceof \Closure){
            return call_user_func_array($name, $args);
        }
        $method = 'scope' . ucfirst($name);
        if(method_exists($this, $method)){
            return call_user_func_array([$this, $method], $args);
        }
        throw new MondbException(
            'The scope is not found ['.$method.']', 
            MondbException::SCOPE_NULL_FOUND
        );  
    }

    /**
     * 保存数据
     *
     * @param  [type] $data     操作数据
     * @param  [type] $where    where条件，存在则为更新，反之新增
     * @param  [type] $sequence 自增序列名, 存在且为新增操作则放回自增ID
     * @param  [type] $query    查询对象实例
     * @return [type]           [description]
     */
    public function save($data, $where = null, $sequence = null, $query = null)
    {
        $result = !is_null($where) ? 
            $this->updateData($data, $where, $query) : 
            $this->insertData($data, $sequence, $query);

        return $result;
    }

    /**
     * 获取一条数据
     *
     * @param  where  $where    where条件
     * @param  [type] $db       查询对象实例
     * @return \mon\model\Data  数据集对象
     */
    public function get($where = [], $db = null)
    {
        // 获取DB链接
        if(!$db){
            $db = $this->db();
        }
        $data = $db->where($where)->find();

        return new Data($data, $this, $this->append);
    }

    /**
     * 获取多条数据
     *
     * @param  where  $where    where条件
     * @param  [type] $db       查询对象实例
     * @return \mon\model\Data  数据集对象
     */
    public function all($where = [], $db = null)
    {
        // 获取DB链接
        if(!$db){
            $db = $this->db();
        }
        $data = $db->where($where)->select();
        // 有数据，生成数据集合
        if(count($data) > 0){
            // 遍历转换生成对象集合
            foreach($data as $k => &$v){
                $v = new Data($v, $this, $this->append);
            }
            $data = new DataCollection($data);
        }else{
            $data = new DataCollection($data);
        }

        return $data;
    }

    /**
     * 更新数据
     *
     * @param  [type] $where where条件
     * @param  [type] $db    查询对象实例
     * @return [type]        [description]
     */
    protected function updateData($data, $where, $db = null)
    {
        // 自动完成
        $updateData = $this->autoCompleteData($this->update, $data);
        // 获取DB链接
        if(!$db){
            $db = $this->db();
        }

        return $db->where($where)->update($updateData);
    }

    /**
     * 新增数据
     *
     * @param  [type] $sequence 自增序列名
     * @param  [type] $sequence 查询对象实例
     * @return [type]           [description]
     */
    protected function insertData($data, $sequence, $db = null)
    {
        // 自动完成
        $insertData = $this->autoCompleteData($this->insert, $data);
        // 获取DB链接
        if(!$db){
            $db = $this->db();
        }

        $getLastInsID = $sequence ? true : false;
        return $db->insert($insertData, false, $getLastInsID, $sequence);
    }

    /**
     * 设置器，设置修改操作数据
     *
     * @param [type] $name  属性名
     * @param [type] $value 属性值
     * @param array  $data  元数据
     * @return $this
     */
    public function setAttr($name, $value = null, $data = [])
    {
        // 检测设置器是否存在
        $method = 'set'.$this->parseAttrName($name).'Attr';
        if(method_exists($this, $method))
        {
            $value = $this->$method($value, $data);
        }
        return $value;
    }

    /**
     * 获取器, 修改获取数据
     *
     * @param  [type] $name  属性名
     * @param  [type] $value 属性值
     * @param  array  $data  元数据
     */
    public function getAttr($name, $value = null, $data = [])
    {
        // 检测设置器是否存在
        $method = 'get'.$this->parseAttrName($name).'Attr';
        if(method_exists($this, $method))
        {
            $value = $this->$method($value, $data);
        }

        return $value;
    }

    /**
     * 数据自动完成
     *
     * @see v2.0.3 修复未定义自动处理的字段也自动处理的BUG
     * @param  array  $auto 自动补全的字段
     * @param  array  $data 数据数据源
     * @return [type]       [description]
     */
    protected function autoCompleteData($auto = [], $data = [])
    {
        $result = $data;
        // 处理补全数据
        foreach($auto as $field => $value)
        {
            if(is_integer($field)){
                $field = $value;
                $value = null;
            }
            // 处理数据字段
            $result[$field] = $this->setAttr($field, $value, $data);
        }

        return $result;
    }
    /**
     * 检测命名, 转换下划线命名规则为驼峰法命名规则
     *
     * @param  [type] $name 字段名称
     * @return [type]       [description]
     */
    protected function parseAttrName($name)
    {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
        return ucfirst($name);
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
