<?php
namespace mon\orm\db;

use PDO;
use Exception;
use Throwable;
use PDOException;
use mon\orm\db\Query;
use mon\orm\exception\MondbException;

/**
* 链接DB
*
* @author Mon 985558837@qq.com
* @version v1.0
*/
class Connection
{
	/**
	 * PDO链接
	 *
	 * @var null
	 */
	protected $link = null;

	/**
	 * 查询结果集
	 *
	 * @var null
	 */
	protected $PDOStatement = null;

	/**
	 * 查询语句
	 *
	 * @var string
	 */
	protected $queryStr = '';

	/**
	 * 绑定值
	 *
	 * @var [type]
	 */
	protected $bind = [];

	/**
	 * 错误信息
	 *
	 * @var string
	 */
	protected $error = '';

	/**
	 * 返回或者影响行数
	 *
	 * @var integer
	 */
	protected $numRows = 0;

    /**
     * 事务级别, 防止出现事务嵌套
     *
     * @var integer
     */
    protected $transLevel = 0;

	/**
	 * DB配置
	 *
	 * @var [type]
	 */
	protected $config = [
		 // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'host'        	  => '127.0.0.1',
        // 数据库名
        'database'        => '',
        // 用户名
        'username'        => '',
        // 密码
        'password'        => '',
        // 端口
        'port'        	  => '3306',
        // 数据库连接参数
        'params'          => [
        	PDO::ATTR_CASE              => PDO::CASE_NATURAL,
	        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
	        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
	        PDO::ATTR_STRINGIFY_FETCHES => false,
	        PDO::ATTR_EMULATE_PREPARES  => false,
        ],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 返回结果集类型
        'result_type'  	  => PDO::FETCH_ASSOC,
        // 断线是否重连，注意：强制重连有可能导致数据库core掉
        'break_reconnect' => false,
	];

	/**
	 * 构造方法
	 *
	 * @param [type] $config [description]
	 */
	public function __construct(array $config)
	{
		if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }

        $this->connect();
	}

	/**
	 * 析构方法
	 */
	public function __destruct()
	{
		// 释放查询
        if ($this->PDOStatement) {
            $this->free();
        }
		$this->close();
	}

    /**
     * 调用Query类的查询方法
     *
     * @param string    $method 方法名称
     * @param array     $args 调用参数
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->getQuery(), $method], $args);
    }

    /**
     * 获取Query对象
     *
     * @return [type] [description]
     */
    public function getQuery()
    {
        return new Query($this);
    }

	/**
     * 获取数据库的配置参数
     *
     * @param string $name 配置名称
     * @return mixed
     */
    public function getConfig($name = '')
    {
        return $name ? $this->config[$name] : $this->name;
    }

    /**
     * 获取返回或者影响的记录数
     *
     * @return integer
     */
    public function getNumRows()
    {
        return $this->numRows;
    }

    /**
     * 获取最后插入记录的ID
     *
     * @param  string|null $pk 自增序列名
     * @return [type]          [description]
     */
    public function getLastInsID($pk = null)
    {
        return $this->getLink()->lastInsertId($pk);
    }

	/**
	 * 获取最近一次查询的sql语句
	 *
	 * @return [type] [description]
	 */
	public function getLastSql()
	{
		return $this->getRealSql($this->queryStr, $this->bind);
	}

    /**
     * 获取最近的错误信息
     *
     * @return string
     */
    public function getQueryError()
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $error = $error[1] . ':' . $error[2];
        } else {
            $error = '';
        }
        if ('' != $this->queryStr) {
            $error .= PHP_EOL . ' [ SQL ] : ' . $this->getLastSql();
        }
        return $error;
    }

	/**
	 * 链接DB
	 *
	 * @param  array  $config [description]
	 * @return [type]         [description]
	 */
	public function connect(array $config = [])
	{
		try{
            if(!empty($config) && is_array($config)){
                $this->config = array_merge($this->config, $config);
            }

            // 生成mysql连接dsn
            $is_port = ( isset($this->config['port']) && is_int($this->config['port'] * 1) );
            $dsn = 'mysql:host=' . $this->config['host'] . 
                    ($is_port ? ';port=' . $this->config['port'] : '') . 
                    ';dbname=' . $this->config['database'];

            if(!empty($this->config['charset'])){
                $dsn .= ';charset=' . $this->config['charset'];
            }
 
            // 链接
            $this->link = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['params']
            );

            return $this;
        }catch(PDOException $e){
            throw new MondbException(
                'Link Error: '.$e->getMessage(), 
                MondbException::LINK_FAILURE, 
                $e
            );
        }
	}

	/**
	 * 获取DB链接
	 *
	 * @return [type] [description]
	 */
	public function getLink()
	{
		if(is_null($this->link))
		{
			$this->connect();
		}

		return $this->link;
	}

	/**
	 * 执行查询语句
	 *
	 * @param  string  $sql  SQL语句
	 * @param  array   $bind 绑定的值
	 * @param  boolean $pdo  是否返回PDO对象
	 * @return [type]        [description]
	 */
	public function query($sql, array $bind = [], $pdo = false)
	{
		$this->queryStr = $sql;
		if(!empty($bind))
		{
			$this->bind = $bind;
		}

        // 释放上一次查询的结果集
        if(!empty($this->PDOStatement))
        {
            $this->free();
        }        

        // 预处理SQL
        if (empty($this->PDOStatement))
        {
            $this->PDOStatement = $this->getLink()->prepare($sql);
        }
        // 是否为存储过程调用
        $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
        // 参数绑定
        if ($procedure) {
            $this->bindParam($bind);
        } else {
            $this->bindValue($bind);
        }
        // 执行查询
        $this->PDOStatement->execute();
        // 返回结果集
        return $this->getResult($pdo, $procedure); 
	}

	/**
	 * 执行命令语句
	 *
	 * @param  string $sql  SQL语句
	 * @param  array  $bind 绑定的值
	 * @return [type]       [description]
	 */
	public function execute($sql, array $bind = [])
	{
		$this->queryStr = $sql;
		if(!empty($bind))
		{
			$this->bind = $bind;
		}
        
        //释放前次的查询结果
        if (!empty($this->PDOStatement) && $this->PDOStatement->queryString != $sql) {
            $this->free();
        }
        // 预处理
        if (empty($this->PDOStatement)) {
            $this->PDOStatement = $this->getLink()->prepare($sql);
        }
        // 是否为存储过程调用
        $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
        // 参数绑定
        if ($procedure) {
            $this->bindParam($bind);
        } else {
            $this->bindValue($bind);
        }
        // 执行语句
        $this->PDOStatement->execute();
        // 返回影响行数
        $this->numRows = $this->PDOStatement->rowCount();
        return $this->numRows;
	}

	/**
     * 开启事务
     *
     * @return [type] [description]
     */
    public function startTrans()
    {
        ++$this->transLevel;
        // 只有当事务无嵌套才开启事务
        if($this->transLevel == 1){
            $this->getLink()->beginTransaction();
        }
        elseif($this->transLevel > 1){
            $this->getLink()->exec($this->parseSavepoint('trans'.$this->transLevel));
        }
    }

    /**
     * 提交事务
     *
     * @return [type] [description]
     */
    public function commit()
    {
        if($this->transLevel == 1){
            $this->getLink()->commit();
        }
        --$this->transLevel;
    }

    /**
     * 回滚事务
     *
     * @return [type] [description]
     */
    public function rollBack()
    {
        if($this->transLevel == 1){
            $this->transLevel = 0;
            $this->getLink()->rollBack();
        }
        elseif($this->transLevel > 1){
            $this->getLink()->exec($this->parseSavepointRollBack('trans'.$this->transLevel));
        }
        $this->transLevel = max(0, $this->transLevel - 1);
    }

    /**
     * 获取表字段信息
     *
     * @param  [type] $table 表名
     * @return [type]        [description]
     */
    public function getFields($table)
    {
        $sql = 'SHOW COLUMNS FROM ' . $table;
        $pdoStatement = $this->query($sql, [], true);
        $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        $info = [];
        if($result){
            foreach($result as $key => $val){
                $val = array_change_key_case($val);
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => (bool) ('' === $val['null']), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                ];
            }
        }

        return $info;
    }

    /**
     * 获取表信息
     *
     * @param  [type] $database 数据库名
     * @return [type]           [description]
     */
    public function getTables($database = '')
    {
        $sql    = !empty($dbName) ? 'SHOW TABLES FROM ' . $database : 'SHOW TABLES';
        $pdoStatement    = $this->query($sql, [], true);
        $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * SQL性能分析
     *
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function explain($sql)
    {
        $sql = 'EXPLAIN ' . $sql;
        $pdoStatement    = $this->query($sql, [], true);
        $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        return array_change_key_case($result);
    }

	/**
	 * PDO自带安全过滤
	 *
	 * @param  string $value 需要过滤的值
	 * @return [type]        [description]
	 */
	public function quote($value)
	{
		return $this->getLink()->quote($value);
	}

	/**
	 * 断开链接
	 *
	 * @return [type] [description]
	 */
	public function close()
	{
		$this->link = null;

		return $this;
	}

	/**
	 * 释放查询结果集
	 * 
	 * @return [type] [description]
	 */
	public function free()
	{
		$this->PDOStatement = null;
	}

	/**
     * 根据参数绑定组装最终的SQL语句 便于调试
     *
     * @param string    $sql  带参数绑定的sql语句
     * @param array     $bind 参数绑定列表
     * @return string
     */
    public function getRealSql($sql, array $bind = [])
    {
        if (is_array($sql)) {
            $sql = implode(';', $sql);
        }

        foreach ($bind as $key => $val) {
            $value = is_array($val) ? $val[0] : $val;
            $type  = is_array($val) ? $val[1] : PDO::PARAM_STR;
            if (PDO::PARAM_STR == $type) {
                $value = $this->quote($value);
            } elseif (PDO::PARAM_INT == $type) {
                $value = (float) $value;
            }
            // 判断占位符
            $sql = is_numeric($key) ?
            substr_replace($sql, $value, strpos($sql, '?'), 1) :
            str_replace(
                [':' . $key . ')', ':' . $key . ',', ':' . $key . ' ', ':' . $key . PHP_EOL],
                [$value . ')', $value . ',', $value . ' ', $value . PHP_EOL],
                $sql . ' ');
        }
        return rtrim($sql);
    }

	/**
     * 参数绑定
     * 支持 ['name'=>'value','id'=>123] 对应命名占位符
     * 或者 ['value',123] 对应问号占位符
     *
     * @param array $bind 要绑定的参数列表
     * @return void
     */
    protected function bindValue(array $bind = [])
    {
        foreach ($bind as $key => $val) {
            // 占位符
            $param = is_numeric($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                if (PDO::PARAM_INT == $val[1] && '' === $val[0]) {
                    $val[0] = 0;
                }
                $result = $this->PDOStatement->bindValue($param, $val[0], $val[1]);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }
            if (!$result) {
                throw new MondbException(
                    "Bind value error: {$param}",
                    MondbException:: BIND_VALUE_ERROR
                );
            }
        }
    }

    /**
     * 存储过程的输入输出参数绑定
     *
     * @param array $bind 要绑定的参数列表
     * @return void
     */
    protected function bindParam($bind)
    {
        foreach ($bind as $key => $val) {
            $param = is_numeric($key) ? $key + 1 : ':' . $key;
            if (is_array($val)) {
                array_unshift($val, $param);
                $result = call_user_func_array([$this->PDOStatement, 'bindParam'], $val);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }
            if (!$result) {
                $param = array_shift($val);
                throw new Exception(
                    "Bind param error: {$param}",
                    MondbException:: BIND_VALUE_ERROR
                );
            }
        }
    }

    /**
     * 获得数据集数组
     *
     * @param bool   $pdo 是否返回PDOStatement
     * @param bool   $procedure 是否存储过程
     * @return PDOStatement|array
     */
    protected function getResult($pdo = false, $procedure = false)
    {
        if ($pdo) {
            // 返回PDOStatement对象处理
            return $this->PDOStatement;
        }
        if ($procedure) {
            // 存储过程返回结果
            return $this->procedure();
        }
        $result        = $this->PDOStatement->fetchAll( $this->getConfig('result_type') );
        $this->numRows = count($result);
        return $result;
    }

    /**
     * 获得存储过程数据集
     *
     * @return array
     */
    protected function procedure()
    {
        $item = [];
        do {
            $result = $this->getResult();
            if ($result) {
                $item[] = $result;
            }
        } while ($this->PDOStatement->nextRowset());
        $this->numRows = count($item);
        return $item;
    }

    /**
     * 生成定义保存点的SQL
     *
     * @param $name
     * @return string
     */
    protected function parseSavepoint($name)
    {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * 生成回滚到保存点的SQL
     *
     * @param $name
     * @return string
     */
    protected function parseSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

}