<?php

namespace mon\orm\db;

use PDO;
use Closure;
use Exception;
use Throwable;
use mon\orm\Db;
use PDOStatement;
use mon\orm\Model;
use mon\orm\db\Builder;
use mon\orm\model\Data;
use mon\orm\db\Connection;
use mon\orm\model\DataCollection;
use mon\orm\exception\DbException;

/**
 * 查询构造器
 *
 * @author Mon 985558837@qq.com
 * @version 2.0.1  修正saevAll方法缺失，优化代码    2022-07-08
 */
class Query
{
    /**
     * DB链接实例
     *
     * @var Connection
     */
    protected $connection = null;

    /**
     * SQL构造实例
     *
     * @var Builder
     */
    protected $builder = null;

    /**
     * 当前模型对象
     *
     * @var Model
     */
    protected $model = null;

    /**
     * 查询表
     *
     * @var string
     */
    protected $table;

    /**
     * 查询条件
     *
     * @var array
     */
    protected $options = [];

    /**
     * 参数绑定标识位
     *
     * @var array
     */
    protected $bind = [];

    /**
     * 构造方法
     *
     * @param Connection $connection 链接实例
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->builder = $this->getBuilder();
    }

    /**
     * 指定模型
     *
     * @param Model $model 模型对象实例
     * @return Query 当前实例自身
     */
    public function model(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * 获取当前的模型对象
     *
     * @return Model|null   当前操作模型对象
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 获取当前的查询参数
     *
     * @param  string $name 参数名称
     * @return mixed 查询参数
     */
    public function getOptions($name = '')
    {
        if ($name === '') {
            return $this->options;
        } else {
            return isset($this->options[$name]) ? $this->options[$name] : null;
        }
    }

    /**
     * 执行查询 返回数据集
     *
     * @param string      $sql    sql指令
     * @param array       $bind   参数绑定
     * @param bool|string $class  指定返回的数据集对象
     * @return mixed 数据集
     */
    public function query($sql, $bind = [], $class = false)
    {
        return $this->connection->query($sql, $bind, $class);
    }

    /**
     * 执行语句
     *
     * @param string $sql  sql指令
     * @param array  $bind 参数绑定
     * @return integer 数据集影响行数
     */
    public function execute($sql, $bind = [])
    {
        return $this->connection->execute($sql, $bind);
    }

    /**
     * 获取最近插入的ID
     *
     * @param string $sequence 自增序列名
     * @return integer 最后写入的ID
     */
    public function getLastInsID($sequence = null)
    {
        return $this->connection->getLastInsID($sequence);
    }

    /**
     * 获取最近一次查询的sql语句
     *
     * @return string 最后执行的SQL
     */
    public function getLastSql()
    {
        return $this->connection->getLastSql();
    }

    /**
     * 启动事务
     *
     * @return void
     */
    public function startTrans()
    {
        $this->connection->startTrans();
    }

    /**
     * 用于非自动提交状态下面的查询提交
     *
     * @return void
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * 事务回滚
     *
     * @return void
     */
    public function rollback()
    {
        $this->connection->rollback();
    }

    /**
     * 事务处理，采用回调函数实现
     *
     * @param  Closure $callback 回调函数
     * @return mixed 结果集
     */
    public function action($callback)
    {
        // 开启事务
        $this->startTrans();
        try {
            $result = null;
            if (is_callable($callback)) {
                // 执行匿名回调
                $result = call_user_func($callback, $this);
            }

            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 启动XA事务
     *
     * @param string $xid XA事务id
     * @return void
     */
    public function startTransXA($xid)
    {
        $this->connection->startTransXA($xid);
    }

    /**
     * 提交XA事务
     *
     * @param string $xid XA事务id
     * @return void
     */
    public function commitXA($xid)
    {
        $this->connection->commitXA($xid);
    }

    /**
     * XA事务回滚
     *
     * @param string $xid XA事务id
     * @return void
     */
    public function rollbackXA($xid)
    {
        $this->connection->rollbackXA($xid);
    }

    /**
     * 预编译XA事务
     *
     * @param string $xid XA事务id
     * @return void
     */
    public function prepareXA($xid)
    {
        $this->connection->prepareXA($xid);
    }

    /**
     * XA事务处理
     *
     * @see 注意：使用XA事务无法使用本地事务及锁表操作，更无法支持事务嵌套
     * @param Closure $callback 回调函数
     * @param array   $dbs 回调函数中涉及使用的数据库连接实例列表
     * @return mixed 结果期
     */
    public function actionXA($callback, $dbs = [])
    {
        $xids = [];
        $prepareXA = [];
        if (empty($dbs)) {
            $dbs[] = $this->connection;
        }

        // 所有链接实例都需要开启XA事务
        foreach ($dbs as $k => $db) {
            $prepareXA[$k] = false;
            $xids[$k] = uniqid('mon_xa');
            $db->startTransXA($xids[$k]);
        }

        try {
            $result = null;
            if (is_callable($callback)) {
                // 执行匿名回调
                $result = call_user_func($callback, $this);
            }

            // 所有链接实例都需要预编译XA事务
            foreach ($dbs as $k => $db) {
                if (!$prepareXA[$k]) {
                    $db->prepareXA($xids[$k]);
                    $prepareXA[$k] = true;
                }
            }

            // 所有链接实例都需要提交XA事务
            foreach ($dbs as $k => $db) {
                $db->commitXA($xids[$k]);
            }
            return $result;
        } catch (Exception $e) {
            // 所有链接实例都需要预编译XA事务
            foreach ($dbs as $k => $db) {
                if (!$prepareXA[$k]) {
                    $db->prepareXA($xids[$k]);
                    $prepareXA[$k] = true;
                }
            }
            // 所有链接实例都需要回滚XA事务
            foreach ($dbs as $k => $db) {
                $db->rollbackXA($xids[$k]);
            }
            throw $e;
        } catch (Throwable $e) {
            // 所有链接实例都需要预编译XA事务
            foreach ($dbs as $k => $db) {
                if (!$prepareXA[$k]) {
                    $db->prepareXA($xids[$k]);
                    $prepareXA[$k] = true;
                }
            }
            // 所有链接实例都需要回滚XA事务
            foreach ($dbs as $k => $db) {
                $db->rollbackXA($xids[$k]);
            }
            throw $e;
        }
    }

    /**
     * 查询数据
     *
     * @return array 查询结果集
     */
    public function select()
    {
        $options = $this->parseExpress();

        // 生成sql
        $sql = $this->builder->select($options);
        // 获取绑定值
        $bind = $this->getBind();
        // 判断调试模式,返回sql
        if (isset($options['debug']) && $options['debug']) {
            return $this->connection->getRealSql($sql, $bind);
        }
        $obj = (isset($options['obj']) && $options['obj']);

        $result = $this->query($sql, $bind, $obj);

        // 触发查询事件
        Db::trigger('select', $this->connection, $options);

        return $result;
    }

    /**
     * 查找单条记录
     *
     * @return array 查询结果集
     */
    public function find()
    {
        $result = $this->limit(1)->select();
        if ($result instanceof PDOStatement || is_string($result)) {
            // 返回PDOStatement对象或者查询语句
            return $result;
        }

        return isset($result[0]) ? $result[0] : [];
    }

    /**
     * 更新查询
     *
     * @param  array  $data 更新的数据
     * @throws DbException
     * @return integer  影响行数
     */
    public function update(array $data = [])
    {
        $options = $this->parseExpress();
        if (empty($options['where'])) {
            // 更新操作，查询条件不能为空
            throw new DbException(
                "The update operation query condition cannot be empty!",
                DbException::WHERE_IS_NULL
            );
        }
        $data = array_merge($options['data'], $data);
        // 生成sql
        $sql = $this->builder->update($data, $options);
        // $data未空，生成空sql语句
        if ($sql == '') {
            throw new DbException(
                "The generated query statement is empty!",
                DbException::SQL_IS_NULL
            );
        }
        // 获取绑定值
        $bind = $this->getBind();
        // 判断调试模式,返回sql
        if (isset($options['debug']) && $options['debug']) {
            return $this->connection->getRealSql($sql, $bind);
        }
        $result = $this->execute($sql, $bind);
        // 触发更新事件
        Db::trigger('update', $this->connection, $options);

        return $result;
    }

    /**
     * 字段自增
     *
     * @param string|array  $field 字段名
     * @param float $step  步长
     * @return integer 影响行数
     */
    public function setInc($field, $step = 1)
    {
        return $this->inc($field, $step)->update();
    }

    /**
     * 字段自减
     *
     * @param string|array  $field 字段名
     * @param float $step  步长
     * @return integer 影响行数
     */
    public function setDec($field, $step = 1)
    {
        return $this->dec($field, $step)->update();
    }

    /**
     * 插入操作, 默认返回影响行数
     *
     * @param  array   $data         插入数据
     * @param  boolean $replace      是否replace
     * @param  boolean $getLastInsID 返回自增主键ID
     * @param  string  $key          自增主键名
     * @return integer 影响行数或自增ID  
     */
    public function insert(array $data = [], $replace = false, $getLastInsID = false, $key = null)
    {
        $options = $this->parseExpress();
        $data    = array_merge($options['data'], $data);
        // 生成SQL语句
        $sql = $this->builder->insert($data, $options, $replace);
        // 获取参数绑定
        $bind = $this->getBind();
        // 判断调试模式,返回sql
        if (isset($options['debug']) && $options['debug']) {
            return $this->connection->getRealSql($sql, $bind);
        }

        // 执行操作
        $result = (false === $sql) ? false : $this->execute($sql, $bind);

        // 触发写入事件
        Db::trigger('insert', $this->connection, $options);

        // 执行成功，判断是否返回自增ID
        if ($result && $getLastInsID) {
            return $this->getLastInsID($key);
        }

        return $result;
    }

    /**
     * 批量插入数据
     *
     * @param  array   $data    数据集
     * @param  boolean $replace 是否replace
     * @return integer  影响行数
     */
    public function insertAll(array $data = [], $replace = false)
    {
        $options = $this->parseExpress();
        if (!is_array($data)) {
            // 批量操作, 必须通过insertAll方法传递数组数据
            return false;
        }
        // 生成SQL语句
        $sql = $this->builder->insertAll($data, $options, $replace);
        // 获取参数绑定
        $bind = $this->getBind();
        // 判断调试模式,返回sql
        if (isset($options['debug']) && $options['debug']) {
            return $this->connection->getRealSql($sql, $bind);
        }
        // 执行SQL
        $result = $this->execute($sql, $bind);
        // 触发写入事件
        Db::trigger('insert', $this->connection, $options);

        return $result;
    }

    /**
     * 操作操作
     *
     * @throws DbException
     * @return integer  影响行数
     */
    public function delete()
    {
        $options = $this->parseExpress();
        if (empty($options['where'])) {
            // 操作操作，查询条件不能为空
            throw new DbException(
                "The delete operation query condition cannot be empty!",
                DbException::WHERE_IS_NULL
            );
        }
        // 生成删除SQL语句
        $sql = $this->builder->delete($options);
        // 获取参数绑定
        $bind = $this->getBind();
        // 判断调试模式,返回sql
        if (isset($options['debug']) && $options['debug']) {
            return $this->connection->getRealSql($sql, $bind);
        }
        // 执行SQL
        $result = $this->execute($sql, $bind);
        // 触发删除事件
        Db::trigger('delete', $this->connection, $options);

        return $result;
    }

    /**
     * COUNT查询
     * 
     * @param string|Raw $field 字段名
     * @return integer|string   结果集
     */
    public function count($field = '*')
    {
        if ($field instanceof Raw) {
            $field = $field->getValue();
        }
        $result = $this->field('COUNT(' . $field . ') AS mondb_count')->find();
        if ($result instanceof PDOStatement || is_string($result)) {
            // 返回PDOStatement对象或者查询语句
            return $result;
        }

        return isset($result['mondb_count']) ? $result['mondb_count'] : false;
    }

    /**
     * SUM查询
     * 
     * @param string|Raw $field 字段名
     * @return float|integer    结果集
     */
    public function sum($field)
    {
        if ($field instanceof Raw) {
            $field = $field->getValue();
        }
        $result = $this->field('SUM(' . $field . ') AS mondb_sum')->find();
        if ($result instanceof PDOStatement || is_string($result)) {
            // 返回PDOStatement对象或者查询语句
            return $result;
        }

        return isset($result['mondb_sum']) ? $result['mondb_sum'] : false;
    }

    /**
     * MIN查询
     *
     * @param string|Raw $field 字段名
     * @return mixed  结果集
     */
    public function min($field)
    {
        if ($field instanceof Raw) {
            $field = $field->getValue();
        }
        $result = $this->field('MIN(' . $field . ') AS mondb_min')->find();
        if ($result instanceof PDOStatement || is_string($result)) {
            // 返回PDOStatement对象或者查询语句
            return $result;
        }

        return isset($result['mondb_min']) ? $result['mondb_min'] : false;
    }

    /**
     * MAX查询
     * 
     * @param string|Raw $field 字段名
     * @return mixed    结果集
     */
    public function max($field)
    {
        if ($field instanceof Raw) {
            $field = $field->getValue();
        }
        $result = $this->field('MAX(' . $field . ') AS mondb_max')->find();
        if ($result instanceof PDOStatement || is_string($result)) {
            // 返回PDOStatement对象或者查询语句
            return $result;
        }

        return isset($result['mondb_max']) ? $result['mondb_max'] : false;
    }

    /**
     * AVG查询
     * 
     * @param string|Raw $field 字段名
     * @return mixed    结果集
     */
    public function avg($field)
    {
        if ($field instanceof Raw) {
            $field = $field->getValue();
        }
        $result = $this->field('AVG(' . $field . ') AS mondb_avg')->find();
        if ($result instanceof PDOStatement || is_string($result)) {
            // 返回PDOStatement对象或者查询语句
            return $result;
        }

        return isset($result['mondb_avg']) ? $result['mondb_avg'] : false;
    }

    /**
     * 调试模式,只返回SQL
     *
     * @return Query 当前实例自身
     */
    public function debug()
    {
        $this->options['debug'] = true;
        return $this;
    }

    /**
     * 获取PDO结果集,不解析
     *
     * @return Query 当前实例自身
     */
    public function getObj()
    {
        $this->options['obj'] = true;
        return $this;
    }

    /**
     * 设置表名(含表前缀)
     *
     * @param  string|Raw $table 表名
     * @return Query    当前实例自身
     */
    public function table($table)
    {
        if (is_string($table)) {
            if (strpos($table, ')')) {
                // 子查询
            } elseif (strpos($table, ',')) {
                // 多表
                $tables = explode(',', $table);
                $table  = [];
                foreach ($tables as $item) {
                    list($item, $alias) = explode(' ', trim($item));
                    if ($alias) {
                        $this->alias([$item => $alias]);
                        $table[$item] = $alias;
                    } else {
                        $table[] = $item;
                    }
                }
            } elseif (strpos($table, ' ')) {
                list($table, $alias) = explode(' ', $table);

                $table = [$table => $alias];
                $this->alias($table);
            }
        } else if ($table instanceof Raw) {
            $table = $table->getValue();
        }

        $this->options['table'] = $table;
        return $this;
    }

    /**
     * 指定数据表别名
     *
     * @param string $alias 数据表别名
     * @return Query    当前实例自身
     */
    public function alias($alias)
    {
        if (is_array($alias)) {
            foreach ($alias as $key => $val) {
                $table = $key;
                $this->options['alias'][$table] = $val;
            }
        } else {
            $table = is_array($this->options['table']) ? key($this->options['table']) : $this->options['table'];
            $this->options['alias'][$table] = $alias;
        }

        return $this;
    }

    /**
     * 查询字符串
     *
     * @param  mixed $field 查询字段
     * @return Query    当前实例自身
     */
    public function field($field)
    {
        if (empty($field)) {
            return $this;
        } elseif ($field instanceof Raw) {
            $this->options['field'][] = $field;
            return $this;
        }
        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
        }
        if ($field === true) {
            // 获取全部字段
            $field  = ['*'];
        }

        if (isset($this->options['field'])) {
            $field = array_merge((array) $this->options['field'], $field);
        }
        $this->options['field'] = array_unique($field);
        return $this;
    }

    /**
     * 指定查询数量
     *
     * @param mixed $offset 起始位置
     * @param mixed $length 查询数量
     * @return Query    当前实例自身
     */
    public function limit($offset, $length = null)
    {
        if (is_null($length) && strpos($offset, ',')) {
            list($offset, $length) = explode(',', $offset);
        }
        $this->options['limit'] = intval($offset) . ($length ? ',' . intval($length) : '');

        return $this;
    }

    /**
     * 分页查询
     *
     * @param integer $page     当前页数，从1开始
     * @param integer $length   每页记录条数
     * @return Query  当前实例自身
     */
    public function page($page, $length)
    {
        $page = intval($page);
        $page = $page > 0 ? ($page - 1) : 0;
        $length = intval($length);
        return $this->limit($page * $length, $length);
    }

    /**
     * 指定排序 order('id','desc') 或者 order(['id'=>'desc','create_time'=>'desc'])
     *
     * @param string|array $field 排序字段
     * @param string       $order 排序
     * @return Query    当前实例自身
     */
    public function order($field, $order = '')
    {
        if (!empty($field)) {
            if (is_string($field)) {
                if (strpos($field, ',')) {
                    $field = array_map('trim', explode(',', $field));
                } else {
                    $field = empty($order) ? $field : [$field => $order];
                }
            }

            if (!isset($this->options['order'])) {
                $this->options['order'] = [];
            }
            if (is_array($field)) {
                $this->options['order'] = array_merge($this->options['order'], $field);
            } else {
                $this->options['order'][] = $field;
            }
        }

        return $this;
    }

    /**
     * 随机排序
     *
     * @return Query 当前实例自身
     */
    public function orderRand()
    {
        $this->options['order'][] = '[rand]';
        return $this;
    }

    /**
     * 指定group查询
     *
     * @param string $group GROUP
     * @return Query    当前实例自身
     */
    public function group($group)
    {
        $this->options['group'] = $group;
        return $this;
    }

    /**
     * 指定having查询
     *
     * @param string $having having
     * @return Query    当前实例自身
     */
    public function having($having)
    {
        $this->options['having'] = $having;
        return $this;
    }

    /**
     * join查询SQL组装
     *
     * @param mixed  $join      关联的表名
     * @param mixed  $condition 条件
     * @param string $type      JOIN类型
     * @return Query    当前实例自身
     */
    public function join($join, $condition = null, $type = 'INNER')
    {
        if (empty($condition)) {
            // 如果为组数，则循环调用join
            foreach ($join as $key => $value) {
                if (is_array($value) && 2 <= count($value)) {
                    $this->join($value[0], $value[1], isset($value[2]) ? $value[2] : $type);
                }
            }
        } else {
            $table = $this->getJoinTable($join);

            $this->options['join'][] = [$table, strtoupper($type), $condition];
        }

        return $this;
    }

    /**
     * USING支持 用于多表删除
     *
     * @param string|array $using USING
     * @return Query    当前实例自身
     */
    public function using($using)
    {
        $this->options['using'] = $using;
        return $this;
    }

    /**
     * 设置查询的额外参数
     *
     * @param string $extra 额外信息
     * @return Query    当前实例自身
     */
    public function extra($extra)
    {
        $this->options['extra'] = $extra;
        return $this;
    }

    /**
     * 设置DUPLICATE
     *
     * @param array|string $duplicate DUPLICATE信息
     * @return Query    当前实例自身
     */
    public function duplicate($duplicate)
    {
        $this->options['duplicate'] = $duplicate;
        return $this;
    }

    /**
     * 指定AND查询条件
     *
     * @param mixed $field     查询字段
     * @param mixed $op        查询表达式
     * @param mixed $condition 查询条件
     * @return Query    当前实例自身
     */
    public function where($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        $this->parseWhereExp('AND', $field, $op, $condition, $param);
        return $this;
    }

    /**
     * 指定OR查询条件
     *
     * @param mixed $field     查询字段
     * @param mixed $op        查询表达式
     * @param mixed $condition 查询条件
     * @return Query    当前实例自身
     */
    public function whereOr($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        $this->parseWhereExp('OR', $field, $op, $condition, $param);
        return $this;
    }

    /**
     * 指定XOR查询条件
     *
     * @param mixed $field     查询字段
     * @param mixed $op        查询表达式
     * @param mixed $condition 查询条件
     * @return Query    当前实例自身
     */
    public function whereXor($field, $op = null, $condition = null)
    {
        $param = func_get_args();
        array_shift($param);
        $this->parseWhereExp('XOR', $field, $op, $condition, $param);
        return $this;
    }

    /**
     * 指定Like查询条件
     *
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereLike($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'like', $condition, [], true);
        return $this;
    }

    /**
     * 指定NotLike查询条件
     *
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereNotLike($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'not like', $condition, [], true);
        return $this;
    }

    /**
     * 指定Between查询条件
     *
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereBetween($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'between', $condition, [], true);
        return $this;
    }

    /**
     * 指定NotBetween查询条件
     *
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereNotBetween($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'not between', $condition, [], true);
        return $this;
    }

    /**
     * 指定Null查询条件
     *
     * @param mixed  $field 查询字段
     * @param string $logic 查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereNull($field, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'null', null, [], true);
        return $this;
    }

    /**
     * 指定NotNull查询条件
     *
     * @param mixed  $field 查询字段
     * @param string $logic 查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereNotNull($field, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'notnull', null, [], true);
        return $this;
    }

    /**
     * 指定Exists查询条件
     *
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereExists($condition, $logic = 'AND')
    {
        $this->options['where'][strtoupper($logic)][] = ['exists', $condition];
        return $this;
    }

    /**
     * 指定NotExists查询条件
     *
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereNotExists($condition, $logic = 'AND')
    {
        $this->options['where'][strtoupper($logic)][] = ['not exists', $condition];
        return $this;
    }

    /**
     * 指定In查询条件
     *
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereIn($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'in', $condition, [], true);
        return $this;
    }

    /**
     * 指定NotIn查询条件
     *
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return Query    当前实例自身
     */
    public function whereNotIn($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'not in', $condition, [], true);
        return $this;
    }

    /**
     * 设置数据
     *
     * @param mixed $field 字段名或者数据
     * @param mixed $value 字段值
     * @return Query    当前实例自身
     */
    public function data($field, $value = null)
    {
        if (is_array($field)) {
            $this->options['data'] = isset($this->options['data']) ? array_merge($this->options['data'], $field) : $field;
        } else {
            $this->options['data'][$field] = $value;
        }
        return $this;
    }

    /**
     * 字段值增长
     *
     * @param string|array $field 字段名
     * @param float $step  增长值
     * @return Query    当前实例自身
     */
    public function inc($field, $step = 1)
    {
        $fields = is_string($field) ? explode(',', $field) : $field;
        foreach ($fields as $field) {
            $this->data($field, ['inc', $step]);
        }
        return $this;
    }

    /**
     * 字段值减少
     *
     * @param string|array $field 字段名
     * @param float $step  增长值
     * @return Query    当前实例自身
     */
    public function dec($field, $step = 1)
    {
        $fields = is_string($field) ? explode(',', $field) : $field;
        foreach ($fields as $field) {
            $this->data($field, ['dec', $step]);
        }
        return $this;
    }

    /**
     * 查询lock
     *
     * @param boolean|string $lock 是否lock
     * @return Query    当前实例自身
     */
    public function lock($lock = false)
    {
        $this->options['lock']   = $lock;
        return $this;
    }

    /**
     * distinct查询
     *
     * @param string|boolean $distinct 是否唯一
     * @return Query    当前实例自身
     */
    public function distinct($distinct = false)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    /**
     * 查询 union
     *
     * @param mixed   $union
     * @param boolean $all
     * @return Query    当前实例自身
     */
    public function union($union, $all = false)
    {
        $this->options['union']['type'] = $all ? 'UNION ALL' : 'UNION';

        if (is_array($union)) {
            $this->options['union'] = array_merge($this->options['union'], $union);
        } else {
            $this->options['union'][] = $union;
        }
        return $this;
    }

    /**
     * 指定强制索引
     *
     * @param string $force 索引名称
     * @return Query    当前实例自身
     */
    public function force($force)
    {
        $this->options['force'] = $force;

        return $this;
    }

    /**
     * 查询注释
     *
     * @param string $comment 注释
     * @return Query    当前实例自身
     */
    public function comment($comment)
    {
        $this->options['comment'] = $comment;

        return $this;
    }

    /**
     * 参数绑定
     *
     * @param mixed   $key   参数名
     * @param mixed   $value 绑定变量值
     * @param integer $type  绑定类型
     * @return Query    当前实例自身
     */
    public function bind($key, $value = false, $type = PDO::PARAM_STR)
    {
        if (is_array($key)) {
            $this->bind = array_merge((array) $this->bind, $key);
        } else {
            $this->bind[$key] = [$value, $type];
        }
        return $this;
    }

    /**
     * 检测参数是否已经绑定
     *
     * @param string $key 参数名
     * @return boolean
     */
    public function isBind($key)
    {
        return isset($this->bind[$key]);
    }

    /**
     * 获取绑定的参数 并清空
     *
     * @return array
     */
    public function getBind()
    {
        $bind = $this->bind;
        $this->bind = [];
        return $bind;
    }

    /**
     * 获取数据库的配置参数
     *
     * @param string $name 参数名称
     * @return mixed
     */
    public function getConfig($name = '')
    {
        return $this->connection->getConfig($name);
    }

    /**
     * 模型类save方法支持
     *
     * @param  array $data     操作数据
     * @param  array $where    where条件，存在则为更新，反之新增
     * @param  mixed $sequence 自增序列名, 存在且为新增操作则放回自增ID
     * @param  boolean $replace replace操作
     * @throws DbException
     * @return integer 影响行数
     */
    public function save(array $data, $where = null, $sequence = null, $replace = false)
    {
        if (!$this->getModel()) {
            throw new DbException(
                'The instance is not bound to the Model!',
                DbException::QUERY_MODEL_NOT_BIND
            );
        }
        if (!method_exists($this->getModel(), 'save')) {
            throw new DbException(
                'The model not support autocomplete of [save!',
                DbException::MODEL_NOT_SUPPORT_SAVE
            );
        }

        return call_user_func_array([$this->getModel(), 'save'], [$data, $where, $sequence, $replace, $this]);
    }

    /**
     * 模型类saveAll方法支持
     *
     * @param array $data   操作数据
     * @param boolean $replace  是否replace
     * @throws DbException
     * @return integer 影响行数
     */
    public function saveAll(array $data, $replace = false)
    {
        if (!$this->getModel()) {
            throw new DbException(
                'The instance is not bound to the Model!',
                DbException::QUERY_MODEL_NOT_BIND
            );
        }
        if (!method_exists($this->getModel(), 'saveAll')) {
            throw new DbException(
                'The model not support autocomplete of [saveAll]',
                DbException::MODEL_NOT_SUPPORT_SAVEALL
            );
        }
        return call_user_func_array([$this->getModel(), 'saveAll'], [$data, $replace, $this]);
    }

    /**
     * 模型类get方法支持
     *
     * @param  array $where    where条件，存在则为更新，反之新增
     * @throws DbException
     * @return Data 结果集
     */
    public function get($where = [])
    {
        if (!$this->getModel()) {
            throw new DbException(
                'The instance is not bound to the Model!',
                DbException::QUERY_MODEL_NOT_BIND
            );
        }
        if (!method_exists($this->getModel(), 'get')) {
            throw new DbException(
                'The model not support autocomplete of [get]',
                DbException::MODEL_NOT_SUPPORT_GET
            );
        }

        return call_user_func_array([$this->getModel(), 'get'], [$where, $this]);
    }

    /**
     * 模型类all方法支持
     *
     * @param  array $where    where条件，存在则为更新，反之新增
     * @throws DbException
     * @return DataCollection 结果集
     */
    public function all($where = [])
    {
        if (!$this->getModel()) {
            throw new DbException(
                'The instance is not bound to the Model!',
                DbException::QUERY_MODEL_NOT_BIND
            );
        }
        if (!method_exists($this->getModel(), 'all')) {
            throw new DbException(
                'The model not support autocomplete of [all]',
                DbException::MODEL_NOT_SUPPORT_ALL
            );
        }

        return call_user_func_array([$this->getModel(), 'all'], [$where, $this]);
    }

    /**
     * 分析查询表达式
     *
     * @param string        $logic     查询逻辑 and or xor
     * @param string|array  $field     查询字段
     * @param mixed         $op        查询表达式
     * @param mixed         $condition 查询条件
     * @param array         $param     查询参数
     * @param boolean       $strict    严格模式
     * @return void
     */
    protected function parseWhereExp($logic, $field, $op, $condition, $param = [], $strict = false)
    {
        $logic = strtoupper($logic);
        if ($field instanceof Raw) {
            $this->options['where'][$logic][] = is_string($op) ? [$op, $field] : $field;
            return;
        }

        if ($strict) {
            // 使用严格模式查询
            $where[$field] = [$op, $condition];
            // 记录一个字段多次查询条件
            $this->options['multi'][$logic][$field][] = $where[$field];
        } elseif (is_null($op) && is_null($condition)) {
            if (is_array($field)) {
                // 数组批量查询
                $where = $field;
                foreach ($where as $k => $val) {
                    $this->options['multi'][$logic][$k][] = $val;
                }
            } elseif ($field && is_string($field)) {
                // 字符串查询
                if (preg_match('/[,=\<\'\"\(\s]/', $field)) {
                    // 手写where条件，不做处理，直接写入
                    $this->options['where'][$logic][] = $field;
                } else {
                    $where[$field] = ['null', ''];
                    $this->options['multi'][$logic][$field][] = $where[$field];
                }
            }
        } elseif (is_array($op)) {
            $where[$field] = $param;
        } elseif (in_array(strtolower($op), ['null', 'notnull', 'not null'])) {
            // null查询
            $where[$field] = [$op, ''];
            $this->options['multi'][$logic][$field][] = $where[$field];
        } elseif (is_null($condition)) {
            // 字段相等查询
            $where[$field] = ['=', $op];
            $this->options['multi'][$logic][$field][] = $where[$field];
        } else {
            // 记录一个字段多次查询条件
            $where[$field] = [$op, $condition];
            $this->options['multi'][$logic][$field][] = $where[$field];
        }

        if (!empty($where)) {
            if (!isset($this->options['where'][$logic])) {
                $this->options['where'][$logic] = [];
            }
            if (is_string($field) && $this->checkMultiField($field, $logic)) {
                $where[$field] = $this->options['multi'][$logic][$field];
            } elseif (is_array($field)) {
                foreach ($field as $key => $val) {
                    if ($this->checkMultiField($key, $logic)) {
                        $where[$key] = $this->options['multi'][$logic][$key];
                    }
                }
            }
            $this->options['where'][$logic] = array_merge($this->options['where'][$logic], $where);
        }
    }

    /**
     * 分析表达式（可用于查询或者写入操作）
     *
     * @throws DbException
     * @return array
     */
    protected function parseExpress()
    {
        $options = $this->options;

        if (empty($options['table'])) {
            throw new DbException(
                'The query table is not set!',
                DbException::TABLE_NULL_FOUND
            );
        }

        if (!isset($options['where'])) {
            $options['where'] = [];
        }

        if (!isset($options['field'])) {
            $options['field'] = '*';
        }

        if (!isset($options['data'])) {
            $options['data'] = [];
        }

        foreach (['lock', 'distinct'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = false;
            }
        }

        foreach (['join', 'union', 'group', 'having', 'limit', 'order', 'force', 'comment', 'extra', 'using', 'duplicate'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = '';
            }
        }

        $this->options = [];
        return $options;
    }

    /**
     * 获取Join表名及别名 支持
     * ['prefix_table或者子查询'=>'alias'] 'prefix_table alias' 'table alias'
     *
     * @param array|string $join
     * @return array|string
     */
    protected function getJoinTable($join, &$alias = null)
    {
        // 传入的表名为数组
        if (is_array($join)) {
            $table = $join;
            $alias = array_shift($join);
        } else {
            $join = trim($join);
            if (false !== strpos($join, '(')) {
                // 使用子查询
                $table = $join;
            } else {
                if (strpos($join, ' ')) {
                    // 使用别名
                    list($table, $alias) = explode(' ', $join);
                } else {
                    $table = $join;
                    if (false === strpos($join, '.') && 0 !== strpos($join, '__')) {
                        $alias = $join;
                    }
                }
            }
            if (isset($alias) && $table != $alias) {
                $table = [$table => $alias];
            }
        }
        return $table;
    }

    /**
     * 检查是否存在一个字段多次查询条件
     *
     * @param string $field 查询字段
     * @param string $logic 查询逻辑 and or xor
     * @return boolean
     */
    protected function checkMultiField($field, $logic)
    {
        return isset($this->options['multi'][$logic][$field]) && count($this->options['multi'][$logic][$field]) > 1;
    }

    /**
     * 获取Builder类对象实例
     *
     * @return Builder 查询语句构造器实例
     */
    protected function getBuilder()
    {
        return new Builder($this->connection, $this);
    }
}
