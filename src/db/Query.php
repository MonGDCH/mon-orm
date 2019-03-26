<?php
namespace mon\orm\db;

use PDO;
use Closure;
use PDOStatement;
use mon\orm\Model;
use mon\orm\db\Builder;
use mon\orm\db\Connection;
use mon\orm\exception\MondbException;


/**
* 查询构造器
*
* @author Mon 985558837@qq.com
* @version v1.0
*/
class Query
{
	/**
	 * DB链接实例
	 *
	 * @var null
	 */
	protected $connection = null;

	/**
	 * SQL构造实例
	 *
	 * @var null
	 */
	protected $builder = null;

	/**
     * 查询表
     *
     * @var [type]
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
     * @var [type]
     */
    protected $bind = [];

    /**
     * 当前模型对象
     *
     * @var [type]
     */
    protected $model;

	/**
	 * 构造方法
	 *
	 * @param Connection $connection [description]
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
     * @return $this
     */
    public function model(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * 获取当前的模型对象
     *
     * @return Model|null
     */
    public function getModel()
    {
        return $this->model;
    }

	/**
	 * 获取当前的查询参数
	 *
	 * @param  [type] $name 参数名称
	 * @return [type]       [description]
	 */
	public function getOptions($name = '')
	{
		if ('' === $name) {
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
     */
    public function execute($sql, $bind = [])
    {
        return $this->connection->execute($sql, $bind);
    }

    /**
     * 获取最近插入的ID
     *
     * @param string $sequence 自增序列名
     * @return string
     */
    public function getLastInsID($sequence = null)
    {
        return $this->connection->getLastInsID($sequence);
    }

    /**
     * 获取最近一次查询的sql语句
     *
     * @return string
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
     * @param  [type] $callback [description]
     * @return [type]           [description]
     */
    public function action($callback)
    {
        if($callback instanceof Closure){
        	// 开启事务
            $this->startTrans();

            // 执行匿名回调
            $result = call_user_func($callback, $this);
            if($result === false){
                $this->rollback();
            }else{
                $this->commit();
            }
            return $result;
        }else{
            return false;
        }
    }

	/**
	 * 查询数据
	 *
	 * @return [type] [description]
	 */
	public function select()
	{    
		$options = $this->parseExpress();

		// 生成sql
		$sql = $this->builder->select($options);
		// 获取绑定值
		$bind = $this->getBind();
		// 判断调试模式,返回sql
		if(isset($options['debug']) && $options['debug'])
        {
            return $this->connection->getRealSql($sql, $bind);
        }
        $obj = (isset($options['obj']) && $options['obj']);

		return $this->query($sql, $bind, $obj);
	}

	/**
	 * 查找单条记录
	 *
	 * @return [type] [description]
	 */
	public function find()
	{
		$result = $this->limit(1)->select();
		if($result instanceof PDOStatement || is_string($result))
        {
            // 返回PDOStatement对象或者查询语句
            return $result;
        }

        return isset($result[0]) ? $result[0] : [];
	}

	/**
	 * 更新查询
	 *
	 * @param  array  $data 更新的数据
	 * @return [type]       [description]
	 */
	public function update(array $data = [])
	{
		$options = $this->parseExpress();
        if(empty($options['where']))
        {
            // 更新操作，查询条件不能为空
            throw new MondbException(
                "The update operation query condition cannot be empty!",
                MondbException::WHERE_IS_NULL
            );
        }
		$data = array_merge($options['data'], $data);
        // 生成sql
        $sql = $this->builder->update($data, $options);
        // $data未空，生成空sql语句
        if($sql == '')
        {
            return 0;
        }
        // 获取绑定值
        $bind = $this->getBind();
        // 判断调试模式,返回sql
        if(isset($options['debug']) && $options['debug'])
        {
            return $this->connection->getRealSql($sql, $bind);
        }
        return $this->execute($sql, $bind);
	}

    /**
     * 字段自增
     *
     * @param [type]  $field 字段名
     * @param integer $step  步长
     */
    public function setInc($field, $step = 1)
    {
        return $this->inc($field, $step)->update();
    }

    /**
     * 字段自减
     *
     * @param [type]  $field 字段名
     * @param integer $step  步长
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
     * @return [type]                [description]
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
        if(isset($options['debug']) && $options['debug'])
        {
            return $this->connection->getRealSql($sql, $bind);
        }

        // 执行操作
        $result = (false === $sql) ? fasle : $this->execute($sql, $bind);
        // 执行成功，判断是否返回自增ID
        if($result && $getLastInsID)
        {
            return $this->getLastInsID($key);
        }

        return $result;
    }

    /**
     * 批量插入数据
     *
     * @param  array   $data    数据集
     * @param  boolean $replace 是否replace
     * @return [type]           [description]
     */
    public function insertAll(array $data = [], $replace = false)
    {
        $options = $this->parseExpress();
        if(!is_array($data))
        {
            // 批量操作, 必须通过insertAll方法传递数组数据
            return false;
        }
        // 生成SQL语句
        $sql = $this->builder->insertAll($data, $options, $replace);
        // 获取参数绑定
        $bind = $this->getBind();
        // 判断调试模式,返回sql
        if(isset($options['debug']) && $options['debug'])
        {
            return $this->connection->getRealSql($sql, $bind);
        }

        return $this->execute($sql, $bind);
    }

    /**
     * 操作操作
     *
     * @return [type] [description]
     */
    public function delete()
    {
        $options = $this->parseExpress();
        if(empty($options['where']))
        {
            // 操作操作，查询条件不能为空
            throw new MondbException(
                "The delete operation query condition cannot be empty!",
                MondbException::WHERE_IS_NULL
            );
        }
        // 生成删除SQL语句
        $sql = $this->builder->delete($options);
        // 获取参数绑定
        $bind = $this->getBind();
        // 判断调试模式,返回sql
        if(isset($options['debug']) && $options['debug'])
        {
            return $this->connection->getRealSql($sql, $bind);
        }

        return $this->execute($sql, $bind);
    }

    /**
     * COUNT查询
     * 
     * @param string $field 字段名
     * @return integer|string
     */
    public function count($field = '*')
    {
        $res = $this->field('COUNT('.$field.') AS mondb_count')->find();
        if($res instanceof PDOStatement || is_string($res))
        {
            // 返回PDOStatement对象或者查询语句
            return $res;
        }

        return isset($res['mondb_count']) ? $res['mondb_count'] : false;
    }

    /**
     * SUM查询
     * 
     * @param string $field 字段名
     * @return float|int
     */
    public function sum($field)
    {
        $res = $this->field('SUM('.$field.') AS mondb_sum')->find();
        if($res instanceof PDOStatement || is_string($res))
        {
            // 返回PDOStatement对象或者查询语句
            return $res;
        }

        return isset($res['mondb_sum']) ? $res['mondb_sum'] : false;
    }

    /**
     * MIN查询
     *
     * @param string $field 字段名
     * @return mixed
     */
    public function min($field)
    {
        $res = $this->field('MIN('.$field.') AS mondb_min')->find();
        if($res instanceof PDOStatement || is_string($res))
        {
            // 返回PDOStatement对象或者查询语句
            return $res;
        }

        return isset($res['mondb_min']) ? $res['mondb_min'] : false;
    }

    /**
     * MAX查询
     * 
     * @param string $field 字段名
     * @return mixed
     */
    public function max($field)
    {
        $res = $this->field('MAX('.$field.') AS mondb_max')->find();
        if($res instanceof PDOStatement || is_string($res))
        {
            // 返回PDOStatement对象或者查询语句
            return $res;
        }

        return isset($res['mondb_max']) ? $res['mondb_max'] : false;
    }

    /**
     * AVG查询
     * 
     * @param string $field 字段名
     * @return float|int
     */
    public function avg($field)
    {
        $res = $this->field('AVG('.$field.') AS mondb_avg')->find();
        if($res instanceof PDOStatement || is_string($res))
        {
            // 返回PDOStatement对象或者查询语句
            return $res;
        }

        return isset($res['mondb_avg']) ? $res['mondb_avg'] : false;
    }

	/**
	 * 调试模式,只返回SQL
	 *
	 * @return [type] [description]
	 */
	public function debug()
	{
		$this->options['debug'] = true;
		return $this;
	}

	/**
	 * 获取PDO结果集,不解析
	 *
	 * @return [type] [description]
	 */
	public function getObj()
	{
		$this->options['obj'] = true;
		return $this;
	}

	/**
     * 设置表名(含表前缀)
     *
     * @param  [type] $table 表名
     * @return [type]        [description]
     */
    public function table($table)
    {
        if (is_string($table)) {
            if (strpos($table, ')')) {
                // 子查询
            }
            elseif (strpos($table, ','))
            {
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
            }
            elseif(strpos($table, ' '))
            {
                list($table, $alias) = explode(' ', $table);

                $table = [$table => $alias];
                $this->alias($table);
            }
        }

        $this->options['table'] = $table;
        return $this;
    }

    /**
     * 指定数据表别名
     *
     * @param mixed $alias 数据表别名
     * @return $this
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
     * @param  [type] $field [description]
     * @return [type]        [description]
     */
    public function field($field)
    {
        if (empty($field)) {
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
     * @return $this
     */
    public function limit($offset, $length = null)
    {
        if(is_null($length) && strpos($offset, ','))
        {
            list($offset, $length) = explode(',', $offset);
        }
        $this->options['limit'] = intval($offset) . ($length ? ','.intval($length) : '');

        return $this;
    }

    /**
     * 指定排序 order('id','desc') 或者 order(['id'=>'desc','create_time'=>'desc'])
     *
     * @param string|array $field 排序字段
     * @param string       $order 排序
     * @return $this
     */
    public function order($field, $order = '')
    {
        if(!empty($field))
        {
            if(is_string($field))
            {
                if(strpos($field, ','))
                {
                    $field = array_map('trim', explode(',', $field));
                }
                else
                {
                    $field = empty($order) ? $field : [$field => $order];
                }
            }

            if(!isset($this->options['order']))
            {
                $this->options['order'] = [];
            }
            if(is_array($field))
            {
                $this->options['order'] = array_merge($this->options['order'], $field);
            }
            else
            {
                $this->options['order'][] = $field;
            }
        }

        return $this;
    }

    /**
     * 指定group查询
     *
     * @access public
     * @param string $group GROUP
     * @return $this
     */
    public function group($group)
    {
        $this->options['group'] = $group;
        return $this;
    }

    /**
     * 指定having查询
     *
     * @access public
     * @param string $having having
     * @return $this
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
     * @return $this
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
     * 指定AND查询条件
     *
     * @param mixed $field     查询字段
     * @param mixed $op        查询表达式
     * @param mixed $condition 查询条件
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function whereLike($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'like', $condition, [], true);
        return $this;
    }

    /**
     * 指定NotLike查询条件
     * @access public
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return $this
     */
    public function whereNotLike($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'not like', $condition, [], true);
        return $this;
    }

    /**
     * 指定Between查询条件
     * @access public
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return $this
     */
    public function whereBetween($field, $condition, $logic = 'AND')
    {
        $this->parseWhereExp($logic, $field, 'between', $condition, [], true);
        return $this;
    }

    /**
     * 指定NotBetween查询条件
     * @access public
     * @param mixed  $field     查询字段
     * @param mixed  $condition 查询条件
     * @param string $logic     查询逻辑 and or xor
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function data($field, $value = null)
    {
        if (is_array($field)) {
            $this->options['data'] = isset($this->options['data']) ? array_merge($this->options['data'], $field) : $field;
        } 
        else {
            $this->options['data'][$field] = $value;
        }
        return $this;
    }

    /**
     * 查询lock
     *
     * @param bool|string $lock 是否lock
     * @return $this
     */
    public function lock($lock = false)
    {
        $this->options['lock']   = $lock;
        return $this;
    }

    /**
     * distinct查询
     *
     * @param string $distinct 是否唯一
     * @return $this
     */
    public function distinct($distinct)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    /**
     * 查询 union
     *
     * @param mixed   $union
     * @param boolean $all
     * @return $this
     */
    public function union($union, $all = false)
    {
        $this->options['union']['type'] = $all ? 'UNION ALL' : 'UNION';

        if (is_array($union)) {
            $this->options['union'] = array_merge($this->options['union'], $union);
        } 
        else {
            $this->options['union'][] = $union;
        }
        return $this;
    }

    /**
     * 指定强制索引
     *
     * @param string $force 索引名称
     * @return $this
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
     * @return $this
     */
    public function comment($comment)
    {
        $this->options['comment'] = $comment;

        return $this;
    }

    /**
     * 字段值增长
     *
     * @param string|array $field 字段名
     * @param integer      $step  增长值
     * @return $this
     */
    public function inc($field, $step = 1)
    {
        $fields = is_string($field) ? explode(',', $field) : $field;
        foreach($fields as $field)
        {
            $this->data($field, ['inc', $step]);
        }
        return $this;
    }

    /**
     * 字段值减少
     *
     * @param string|array $field 字段名
     * @param integer      $step  增长值
     * @return $this
     */
    public function dec($field, $step = 1)
    {
        $fields = is_string($field) ? explode(',', $field) : $field;
        foreach($fields as $field)
        {
            $this->data($field, ['dec', $step]);
        }
        return $this;
    }

    /**
     * 参数绑定
     *
     * @param mixed   $key   参数名
     * @param mixed   $value 绑定变量值
     * @param integer $type  绑定类型
     * @return $this
     */
    public function bind($key, $value = false, $type = PDO::PARAM_STR)
    {
        if(is_array($key)){
            $this->bind = array_merge($this->bind, $key);
        } 
        else{
            $this->bind[$key] = [$value, $type];
        }
        return $this;
    }

    /**
     * 检测参数是否已经绑定
     *
     * @param string $key 参数名
     * @return bool
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
        $bind       = $this->bind;
        $this->bind = [];
        return $bind;
    }

    /**
     * 分析查询表达式
     *
     * @param string                $logic     查询逻辑 and or xor
     * @param string|array|			$field     查询字段
     * @param mixed                 $op        查询表达式
     * @param mixed                 $condition 查询条件
     * @param array                 $param     查询参数
     * @param  bool                 $strict    严格模式
     * @return void
     */
    protected function parseWhereExp($logic, $field, $op, $condition, $param = [], $strict = false)
    {
        $logic = strtoupper($logic);
        if ($field instanceof Closure) {
            $this->options['where'][$logic][] = is_string($op) ? [$op, $field] : $field;
            return;
        }

		if ($strict) 
		{
            // 使用严格模式查询
            $where[$field] = [$op, $condition];

            // 记录一个字段多次查询条件
            $this->options['multi'][$logic][$field][] = $where[$field];
        } 
        elseif (is_null($op) && is_null($condition)) 
        {
            if (is_array($field)) {
                // 数组批量查询
                $where = $field;
                foreach ($where as $k => $val) 
                {
                    $this->options['multi'][$logic][$k][] = $val;
                }
            } 
            elseif ($field && is_string($field)) {
                // 字符串查询
                $where[$field] = ['null', ''];
                $this->options['multi'][$logic][$field][] = $where[$field];
            }
        } 
        elseif (is_array($op)) 
        {
            $where[$field] = $param;
        } 
        elseif (in_array(strtolower($op), ['null', 'notnull', 'not null'])) 
        {
            // null查询
            $where[$field] = [$op, ''];

            $this->options['multi'][$logic][$field][] = $where[$field];
        } 
        elseif (is_null($condition)) 
        {
            // 字段相等查询
            $where[$field] = ['=', $op];

            $this->options['multi'][$logic][$field][] = $where[$field];
        }
        else
        {

            $where[$field] = [$op, $condition];
            // 记录一个字段多次查询条件
            $this->options['multi'][$logic][$field][] = $where[$field];
        }

        if (!empty($where))
        {
            if (!isset($this->options['where'][$logic]))
            {
                $this->options['where'][$logic] = [];
            }
            if (is_string($field) && $this->checkMultiField($field, $logic))
            {
                $where[$field] = $this->options['multi'][$logic][$field];
            }
            elseif(is_array($field))
            {
                foreach ($field as $key => $val)
                {
                    if ($this->checkMultiField($key, $logic)) {
                        $where[$key] = $this->options['multi'][$logic][$key];
                    }
                }
            }
            $this->options['where'][$logic] = array_merge($this->options['where'][$logic], $where);
        }
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
        } 
        else {
            $join = trim($join);
            if (false !== strpos($join, '(')) {
                // 使用子查询
                $table = $join;
            } 
            else {
                if (strpos($join, ' ')) {
                    // 使用别名
                    list($table, $alias) = explode(' ', $join);
                } 
                else {
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
     * @return bool
     */
    protected function checkMultiField($field, $logic)
    {
        return isset($this->options['multi'][$logic][$field]) && count($this->options['multi'][$logic][$field]) > 1;
    }

    /**
     * 获取数据库的配置参数
     *
     * @param string $name 参数名称
     * @return boolean
     */
    public function getConfig($name = '')
    {
        return $this->connection->getConfig($name);
    }

    /**
     * 分析表达式（可用于查询或者写入操作）
     *
     * @return array
     */
    public function parseExpress()
    {
        $options = $this->options;

        if (empty($options['table'])) {
            throw new MondbException(
                'The query table is not set!',
                MondbException::TABLE_NULL_FOUND
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

        foreach(['lock', 'distinct'] as $name) 
        {
            if (!isset($options[$name])) {
                $options[$name] = false;
            }
        }

        foreach(['join', 'union', 'group', 'having', 'limit', 'order', 'force', 'comment'] as $name) 
        {
            if (!isset($options[$name])) {
                $options[$name] = '';
            }
        }

        $this->options = [];
        return $options;
    }

    /**
     * 模型类save方法支持
     *
     * @param  [type] $data     操作数据
     * @param  [type] $where    where条件，存在则为更新，反之新增
     * @param  [type] $sequence 自增序列名, 存在且为新增操作则放回自增ID
     * @return [type] [description]
     */
    public function save($data, $where = null, $sequence = null)
    {
        if(!$this->getModel())
        {
            throw new MondbException(
                'The instance is not bound to the Model!', 
                MondbException::QUERY_MODEL_NOT_BIND
            );
        }
        if(!method_exists($this->getModel(), 'save'))
        {
            throw new MondbException(
                'The model not support autocomplete of save!',
                MondbException::MODEL_NOT_SUPPORT_SAVE
            );
        }

        return call_user_func_array([$this->getModel(), 'save'], [$data, $where, $sequence, $this]);
    }

    /**
     * 模型类get方法支持
     *
     * @param  [type] $data     操作数据
     * @param  [type] $where    where条件，存在则为更新，反之新增
     * @param  [type] $sequence 自增序列名, 存在且为新增操作则放回自增ID
     * @return [type] [description]
     */
    public function get($where = [])
    {
        if(!$this->getModel())
        {
            throw new MondbException(
                'The instance is not bound to the Model!', 
                MondbException::QUERY_MODEL_NOT_BIND);
        }
        if(!method_exists($this->getModel(), 'get'))
        {
            throw new MondbException(
                'The model not support autocomplete of get!',
                MondbException::MODEL_NOT_SUPPORT_GET
            );
        }

        return call_user_func_array([$this->getModel(), 'get'], [$where, $this]);
    }

    /**
     * 模型类all方法支持
     *
     * @param  [type] $data     操作数据
     * @param  [type] $where    where条件，存在则为更新，反之新增
     * @param  [type] $sequence 自增序列名, 存在且为新增操作则放回自增ID
     * @return [type] [description]
     */
    public function all($where = [])
    {
        if(!$this->getModel())
        {
            throw new MondbException(
                'The instance is not bound to the Model!', 
                MondbException::QUERY_MODEL_NOT_BIND);
        }
        if(!method_exists($this->getModel(), 'all'))
        {
            throw new MondbException(
                'The model not support autocomplete of all!',
                MondbException::MODEL_NOT_SUPPORT_ALL
            );
        }

        return call_user_func_array([$this->getModel(), 'all'], [$where, $this]);
    }

    /**
     * 获取Builder类对象实例
     * @return [type] [description]
     */
    private function getBuilder()
    {
    	return new Builder($this->connection, $this);
    }
}