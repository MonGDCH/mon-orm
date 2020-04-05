<?php

namespace mon\orm;

use Closure;
use mon\orm\Db;
use mon\orm\db\Query;
use mon\orm\model\Data;
use mon\orm\model\DataCollection;
use mon\orm\exception\MondbException;

/**
 * 模型基类
 *
 * @mixin Query
 * @method Query startTrans() static 开启事务
 * @method Query commit() static 提交事务
 * @method Query rollback() static 回滚事务
 * @method Query table(string $table) static 设置表名(含表前缀)
 * @method Query where(mixed $field, string $op = null, mixed $condition = null) static 查询条件
 * @method Query whereOr(mixed $field, string $op = null, mixed $condition = null) static 查询条件(OR)
 * @method Query join(mixed $join, mixed $condition = null, string $type = 'INNER') static JOIN查询
 * @method Query union(mixed $union, boolean $all = false) static UNION查询
 * @method Query limit(mixed $offset, mixed $length = null) static 查询LIMIT
 * @method Query page(integer $page, integer $length) static 分页查询
 * @method Query order(mixed $field, string $order = null) static 查询ORDER
 * @method Query field(mixed $field) static 指定查询字段
 * @method Query getLastSql() static 获取最后执行的SQL
 * @author Mon 985558837@qq.com
 * @version v1.1
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
     * @var array
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
     * @return mixed 错误信息
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
     * @param boolean $newLink 是否重新创建链接
     * @return void
     */
    public function db($newLink = false)
    {
        if (empty($this->config)) {
            $this->config = Db::getConfig();
        }
        // 获取DB实例
        $connect =  Db::connect((array) $this->config, $newLink)->model($this);
        if (!empty($this->table)) {
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

        if ($name instanceof Closure) {
            return call_user_func_array($name, (array) $args);
        }
        $method = 'scope' . ucfirst($name);
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], (array) $args);
        }
        throw new MondbException(
            'The scope is not found [' . $method . ']',
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
            $this->updateData($data, $where, $query) : $this->insertData($data, $sequence, $query);

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
        if (!$db) {
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
        if (!$db) {
            $db = $this->db();
        }
        $data = $db->where($where)->select();
        // 有数据，生成数据集合
        if (count($data) > 0) {
            // 遍历转换生成对象集合
            foreach ($data as $k => &$v) {
                $v = new Data($v, $this, $this->append);
            }
            $data = new DataCollection($data);
        } else {
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
        if (!$db) {
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
        if (!$db) {
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
        $method = 'set' . $this->parseAttrName($name) . 'Attr';
        if (method_exists($this, $method)) {
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
        $method = 'get' . $this->parseAttrName($name) . 'Attr';
        if (method_exists($this, $method)) {
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
        foreach ($auto as $field => $value) {
            if (is_integer($field)) {
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
     * @param  [type] $method 回调方法
     * @param  [type] $args   参数
     * @return [type]         [description]
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->db(), $method], (array) $args);
    }

    /**
     * 静态调用
     *
     * @param  [type] $method 回调方法
     * @param  [type] $args   参数
     * @return [type]         [description]
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([(new static())->db(), $method], (array) $args);
    }
}
