<?php

namespace mon\orm\db;

use PDO;
use Throwable;
use Exception;
use mon\orm\Db;
use PDOException;
use mon\orm\db\Query;
use mon\orm\exception\DbException;

/**
 * 数据库链接器
 *
 * @method \mon\orm\db\Query table(string $table) 设置表名(含表前缀)
 * @method \mon\orm\db\Query where(mixed $field, string $op = null, mixed $condition = null) 查询条件
 * @method \mon\orm\db\Query whereOr(mixed $field, string $op = null, mixed $condition = null) 查询条件(OR)
 * @method \mon\orm\db\Query whereLike(string $field, mixed $condition, $logic = 'AND') 指定Like查询条件
 * @method \mon\orm\db\Query whereNotLike(string $field, mixed $condition, $logic = 'AND') 指定NotLike查询条件
 * @method \mon\orm\db\Query whereBetween(string $field, mixed $condition, $logic = 'AND') 指定Between查询条件
 * @method \mon\orm\db\Query whereNotBetween(string $field, mixed $condition, $logic = 'AND') 指定NotBetween查询条件
 * @method \mon\orm\db\Query whereIn(string $field, mixed $condition, $logic = 'AND') 指定In查询条件
 * @method \mon\orm\db\Query whereNotIn(string $field, mixed $condition, $logic = 'AND') 指定NotIn查询条件
 * @method \mon\orm\db\Query whereNull(string $field, $logic = 'AND') 指定Null查询条件
 * @method \mon\orm\db\Query whereNotNull(string $field, $logic = 'AND') 指定NotNull查询条件
 * @method \mon\orm\db\Query join(mixed $join, mixed $condition = null, string $type = 'INNER') JOIN查询
 * @method \mon\orm\db\Query union(mixed $union, boolean $all = false) UNION查询
 * @method \mon\orm\db\Query limit(mixed $offset, mixed $length = null) 查询LIMIT
 * @method \mon\orm\db\Query page(integer $page, integer $length) 分页查询
 * @method \mon\orm\db\Query order(mixed $field, string $order = null) 查询ORDER
 * @method \mon\orm\db\Query field(mixed $field) 指定查询字段
 * @method \mon\orm\db\Query alias(string $alias) 指定表别名
 * @method \mon\orm\db\Query inc(string $field, float $step = 1) 字段值增长
 * @method \mon\orm\db\Query dec(string $field, float $step = 1) 字段值减少
 * @method integer insert(array $data = [], $replace = false, $getLastInsID = false, $key = null) 插入操作, 默认返回影响行数
 * @method integer insertAll(array $data = [], $replace = false) 批量插入操作, 返回影响行数
 * @method mixed action(Closure $callback) 回调方法封装执行事务
 * @method mixed actionXA(Closure $callback, array $dbs = []) 回调方法封装执行XA事务
 *
 * @author Mon 985558837@qq.com
 * @version v2.4.0  优化代码，支持读写分离  2022-07-11
 */
class Connection
{
    /**
     * PDO链接
     *
     * @var PDO
     */
    protected $link = null;

    /**
     * 写入操作PDO链接
     *
     * @var PDO
     */
    protected $writeLink = null;

    /**
     * 读取操作PDO链接
     *
     * @var PDO
     */
    protected $readLink = null;

    /**
     * 查询结果集
     *
     * @var \PDOStatement
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
     * @var array
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
     * @var array
     */
    protected $config = [
        // 数据库类型
        'type'              => 'mysql',
        // 服务器地址
        'host'              => '127.0.0.1',
        // 数据库名
        'database'          => '',
        // 用户名
        'username'          => '',
        // 密码
        'password'          => '',
        // 端口
        'port'              => '3306',
        // 数据库编码默认采用utf8mb4
        'charset'           => 'utf8mb4',
        // 返回结果集类型
        'result_type'       => PDO::FETCH_ASSOC,
    ];

    /**
     * 数据库连接参数
     *
     * @var array
     */
    protected $params = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES  => false,
    ];

    /**
     * 服务器断线标识字符
     *
     * @var array
     */
    protected $breakMatchStr = [
        'server has gone away',
        'no connection to the server',
        'Lost connection',
        'is dead or not enabled',
        'Error while sending',
        'decryption failed or bad record mac',
        'server closed the connection unexpectedly',
        'SSL connection has been closed unexpectedly',
        'Error writing data to the connection',
        'Resource deadlock avoided',
        'failed with errno',
        'child connection forced to terminate due to client_idle_limit',
        'query_wait_timeout',
        'reset by peer',
        'Physical connection is not usable',
        'TCP Provider: Error code 0x68',
        'ORA-03114',
        'Packets out of order. Expected',
        'Adaptive Server connection failed',
        'Communication link failure',
        'connection is no longer usable',
        'Login timeout expired',
        'SQLSTATE[HY000] [2002] Connection refused',
        'running with the --read-only option so it cannot execute this statement',
        'The connection is broken and recovery is not possible. The connection is marked by the client driver as unrecoverable. No attempt was made to restore the connection.',
        'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Try again',
        'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Name or service not known',
        'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error: EOF detected',
        'SQLSTATE[HY000] [2002] Connection timed out',
        'SSL: Connection timed out',
        'SQLSTATE[HY000]: General error: 1105 The last transaction was aborted due to Seamless Scaling. Please retry.'
    ];

    /**
     * 构造方法
     *
     * @param array $config 数据库配置信息
     */
    public function __construct(array $config)
    {
        if (!empty($config)) {
            $this->config = array_merge((array) $this->config, $config);
        }
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
     * @return Query 查询构造器对象实例
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
        return $name ? $this->config[$name] : $this->config;
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
     * @return mixed    最后新增的ID 
     */
    public function getLastInsID($pk = null)
    {
        return $this->getLink()->lastInsertId($pk);
    }

    /**
     * 获取最近一次查询的sql语句
     *
     * @return string 最后执行的sql语句
     */
    public function getLastSql()
    {
        return $this->getRealSql($this->queryStr, (array) $this->bind);
    }

    /**
     * 获取最近的错误信息
     *
     * @return string 错误信息
     */
    public function getQueryError()
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $error = (isset($error[1]) && isset($error[2])) ? ($error[1] . ':' . $error[2]) : '';
        } else {
            $error = '';
        }
        if ('' != $this->queryStr) {
            $error .= PHP_EOL . ' [ SQL ] : ' . $this->getLastSql();
        }
        return $error;
    }

    /**
     * 链接数据库
     *
     * @param array $config 链接配置信息
     * @return PDO
     */
    public function connect(array $config = [])
    {
        try {
            // 配置信息
            if (empty($config)) {
                $config = $this->config;
            } else {
                $config = array_merge($this->config, $config);
            }
            // 生成mysql连接dsn
            $dsn = $this->getConnectDsn($config);
            // 连接参数
            $params = $this->getConnectParams($config);
            // 链接数据库
            $link = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $params
            );
            // 触发链接事件
            Db::trigger('connect', $this, $config);

            return $link;
        } catch (PDOException $e) {
            throw new DbException(
                'Link Error: ' . $e->getMessage(),
                DbException::LINK_FAILURE,
                $this,
                $e
            );
        }
    }

    /**
     * 连接分布式数据库
     *
     * @param boolean $master 获取主服务器或者从服务器
     * @return PDO
     */
    public function multiConnect($master = true)
    {
        // 获取读取或者写入的配置信息
        $readWrite = $master ? 'write' : 'read';
        $dbConfigs = isset($this->config[$readWrite]) ? $this->config[$readWrite] : [];
        // 随机获取读写配置节点
        $dbConfig = [];
        if (!empty($dbConfigs)) {
            $key = array_rand($dbConfigs);
            $dbConfig = $dbConfigs[$key];
        }
        // 覆盖生成配置
        $config = $this->config;
        foreach (['host', 'database', 'username', 'password', 'port', 'params', 'charset'] as $name) {
            if (isset($dbConfig[$name])) {
                $config[$name] = $dbConfig[$name];
            }
        }

        return $this->connect($config);
    }

    /**
     * ping连数据库
     *
     * @param string $sql   执行的SQL
     * @return void
     */
    public function ping($sql = 'SELECT 1')
    {
        if (!is_null($this->link)) {
            $this->link->query($sql);
        }
        if (!is_null($this->readLink)) {
            $this->readLink->query($sql);
        }
        if (!is_null($this->writeLink)) {
            $this->writeLink->query($sql);
        }
    }

    /**
     * 获取数据库链接
     *
     * @param boolean $master 是否主服务器
     * @return PDO 数据库链接
     */
    public function getLink($master = true)
    {
        if (isset($this->config['rw_separate']) && $this->config['rw_separate']) {
            // 开启分布式数据库
            if ($master || $this->transLevel) {
                // 增、删、改、开启事务则使用主库连接
                if (is_null($this->writeLink)) {
                    $this->writeLink = $this->multiConnect(true);
                }

                $this->link = $this->writeLink;
            } else {
                // 查使用从库链接
                if (!$this->readLink) {
                    $this->readLink = $this->multiConnect(false);
                }

                $this->link = $this->readLink;
            }
        } else if (is_null($this->link)) {
            // 链接默认数据库
            $this->link = $this->connect();
        }

        return $this->link;
    }

    /**
     * 执行查询语句
     *
     * @param  string  $sql  SQL语句
     * @param  array   $bind 绑定的值
     * @param  boolean $pdo  是否返回PDO对象
     * @throws DbException
     * @throws Throwable
     * @throws Exception
     * @return mixed   查询结果集
     */
    public function query($sql, array $bind = [], $pdo = false)
    {
        $this->queryStr = $sql;
        $this->bind = $bind;

        // 释放上一次查询的结果集
        if (!empty($this->PDOStatement)) {
            $this->free();
        }

        try {
            // 预处理SQL
            if (empty($this->PDOStatement)) {
                $this->PDOStatement = $this->getLink(false)->prepare($sql);
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
            // 触发全局查询事件
            Db::trigger('query', $this, $bind);

            // 返回结果集
            return $this->getResult($pdo, $procedure);
        } catch (PDOException $e) {
            // 断线重连
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $pdo);
            }

            throw new DbException('PDO Error: ' . $e->getMessage(), DbException::PDO_EXCEPTION, $this, $e);
        } catch (Exception $e) {
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $pdo);
            }

            throw $e;
        } catch (Throwable $e) {
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $pdo);
            }

            throw $e;
        }
    }

    /**
     * 执行命令语句
     *
     * @param  string $sql  SQL语句
     * @param  array  $bind 绑定的值
     * @throws DbException
     * @throws Throwable
     * @throws Exception
     * @return integer 影响行数
     */
    public function execute($sql, array $bind = [])
    {
        $this->queryStr = $sql;
        $this->bind = $bind;

        //释放前次的查询结果
        if (!empty($this->PDOStatement) && $this->PDOStatement->queryString != $sql) {
            $this->free();
        }

        try {
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
            // 触发全局查询事件
            Db::trigger('execute', $this, $bind);
            // 返回影响行数
            $this->numRows = $this->PDOStatement->rowCount();

            return $this->numRows;
        } catch (PDOException $e) {
            // 断线重连
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind);
            }

            throw new DbException('PDO Error: ' . $e->getMessage(), DbException::PDO_EXCEPTION, $this, $e);
        } catch (Exception $e) {
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind);
            }

            throw $e;
        } catch (Throwable $e) {
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind);
            }

            throw $e;
        }
    }

    /**
     * 开启事务
     *
     * @throws Exception
     * @return void
     */
    public function startTrans()
    {
        ++$this->transLevel;
        try {
            // 只有当事务无嵌套才开启事务
            if ($this->transLevel == 1) {
                $this->getLink()->beginTransaction();
                // 触发开启事务事件
                Db::trigger('startTrans', $this);
            } elseif ($this->transLevel > 1) {
                $this->getLink()->exec($this->parseSavepoint('trans' . $this->transLevel));
            }
        } catch (Exception $e) {
            if ($this->isBreak($e)) {
                --$this->transLevel;
                return $this->close()->startTrans();
            }

            throw $e;
        }
    }

    /**
     * 提交事务
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transLevel == 1) {
            $this->getLink()->commit();
            // 触发提交事务事件
            Db::trigger('commitTrans', $this);
        }
        --$this->transLevel;
    }

    /**
     * 回滚事务
     *
     * @return void
     */
    public function rollback()
    {
        if ($this->transLevel == 1) {
            $this->transLevel = 0;
            $this->getLink()->rollBack();
            // 触发回滚事务事件
            Db::trigger('rollbackTrans', $this);
        } elseif ($this->transLevel > 1) {
            $this->getLink()->exec($this->parseSavepointRollBack('trans' . $this->transLevel));
        }
        $this->transLevel = max(0, $this->transLevel - 1);
    }

    /**
     * 开启XA分布式事务
     *
     * @param string $xid XA事务id，注意唯一性
     * @return void
     */
    public function startTransXA($xid)
    {
        $this->getLink()->exec("XA START '$xid'");
        // 触发开启跨库事件
        Db::trigger('startTransXA', $this);
    }

    /**
     * 预编译XA事务
     *
     * @param  string $xid XA事务id
     * @return void
     */
    public function prepareXA($xid)
    {
        $this->getLink()->exec("XA END '$xid'");
        $this->getLink()->exec("XA PREPARE '$xid'");
        // 触发预编译XA事务事件
        Db::trigger('prepareTransXA', $this);
    }

    /**
     * 提交XA事务
     *
     * @param  string $xid XA事务id
     * @return void
     */
    public function commitXA($xid)
    {
        $this->getLink()->exec("XA COMMIT '$xid'");
        // 触发提交跨库事务事件
        Db::trigger('commitTransXA', $this);
    }

    /**
     * 回滚XA事务
     *
     * @param  string $xid XA事务id
     * @return void
     */
    public function rollbackXA($xid)
    {
        $this->getLink()->exec("XA ROLLBACK '$xid'");
        // 触发回滚跨库事务事件
        Db::trigger('rollbackTransXA', $this);
    }

    /**
     * 获取表字段信息
     *
     * @param  string $table 表名
     * @return array 表字段信息
     */
    public function getFields($table)
    {
        $sql = "SHOW COLUMNS FROM `{$table}`";
        $pdoStatement = $this->query($sql, [], true);
        $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        $info = [];
        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => (bool) ('' === $val['null']),
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
     * @param  string $database 数据库名
     * @return array 表信息
     */
    public function getTables($database = '')
    {
        $sql = !empty($dbName) ? "SHOW TABLES FROM `{$database}`" : 'SHOW TABLES';
        $pdoStatement = $this->query($sql, [], true);
        $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        $info = [];
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * 优化表
     *
     * @param array|string $tables
     * @throws DbException
     * @return true
     */
    public function optimize($tables)
    {
        if (!$tables) {
            throw new DbException("Please specify the table to be optimization!");
        }
        if (is_array($tables)) {
            $tables = implode('`,`', $tables);
            $optimize = $this->execute("OPTIMIZE TABLE `{$tables}`");
        } else {
            $optimize = $this->execute("OPTIMIZE TABLE `{$tables}`");
        }
        if (!$optimize) {
            throw new DbException("data sheet [{$tables}] Repair mistakes please try again!");
        }

        return true;
    }

    /**
     * 修复表
     *
     * @param array|string $tables
     * @throws DbException
     * @return true
     */
    public function repair($tables)
    {
        if (!$tables) {
            throw new DbException("Please specify the table to be repaired!");
        }
        if (is_array($tables)) {
            $tables = implode('`,`', $tables);
            $repair = $this->execute("REPAIR TABLE `{$tables}`");
        } else {
            $repair = $this->execute("REPAIR TABLE `{$tables}`");
        }
        if (!$repair) {
            throw new DbException("data sheet [{$tables}] Repair mistakes please try again!");
        }

        return true;
    }

    /**
     * SQL性能分析
     *
     * @param  string $sql SQL语句
     * @return array  SQL分析结果
     */
    public function explain($sql)
    {
        $sql = 'EXPLAIN ' . $sql;
        $pdoStatement = $this->query($sql, [], true);
        $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        return array_change_key_case($result);
    }

    /**
     * PDO自带安全过滤
     *
     * @param  string $value 需要过滤的值
     * @return string   过滤后的值
     */
    public function quote($value)
    {
        return $this->getLink()->quote($value);
    }

    /**
     * 断开链接
     *
     * @return Connection 自身实例
     */
    public function close()
    {
        $this->link = null;

        return $this;
    }

    /**
     * 释放查询结果集
     * 
     * @return Connection 自身实例
     */
    public function free()
    {
        $this->PDOStatement = null;

        return $this;
    }

    /**
     * 根据参数绑定组装最终的SQL语句 便于调试
     *
     * @param string    $sql  带参数绑定的sql语句
     * @param array     $bind 参数绑定列表
     * @return string   拼装后的sql语句
     */
    public function getRealSql($sql, array $bind = [])
    {
        if (is_array($sql)) {
            $sql = implode(';', (array) $sql);
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
                substr_replace($sql, $value, strpos($sql, '?'), 1) : str_replace(
                    [':' . $key . ')', ':' . $key . ',', ':' . $key . ' ', ':' . $key . PHP_EOL],
                    [$value . ')', $value . ',', $value . ' ', $value . PHP_EOL],
                    $sql . ' '
                );
        }
        return rtrim($sql);
    }

    /**
     * 参数绑定
     * 支持 ['name'=>'value','id'=>123] 对应命名占位符
     * 或者 ['value',123] 对应问号占位符
     *
     * @param array $bind 要绑定的参数列表
     * @throws DbException
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
                throw new DbException(
                    "Bind value error: {$param}",
                    DbException::BIND_VALUE_ERROR,
                    $this
                );
            }
        }
    }

    /**
     * 存储过程的输入输出参数绑定
     *
     * @param array $bind 要绑定的参数列表
     * @throws DbException
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
                throw new DbException(
                    "Bind param error: {$param}",
                    DbException::BIND_VALUE_ERROR,
                    $this
                );
            }
        }
    }

    /**
     * 获得数据集数组
     *
     * @param boolean $pdo 是否返回PDOStatement
     * @param boolean $procedure 是否存储过程
     * @return PDOStatement|array 数据集
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
        $result = $this->PDOStatement->fetchAll($this->getConfig('result_type'));
        $this->numRows = count($result);
        return $result;
    }

    /**
     * 获得存储过程数据集
     *
     * @return array 存储过程数据集
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
     * @param string $name
     * @return string 执行的SQL
     */
    protected function parseSavepoint($name)
    {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * 生成回滚到保存点的SQL
     *
     * @param string $name
     * @return string 执行的SQL
     */
    protected function parseSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /**
     * 断线是否重连
     *
     * @see 注意：强制重连有可能导致数据库core掉
     * @param  PDOException|Exception  $e 异常对象
     * @return boolean
     */
    protected function isBreak($e)
    {
        if (!Db::reconnect()) {
            return false;
        }

        $error = $e->getMessage();
        foreach ($this->breakMatchStr as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取PDO连接DSN
     *
     * @param array $config 数据库配置
     * @return string
     */
    protected function getConnectDsn(array $config)
    {
        // 生成mysql连接dsn
        $dsn = 'mysql:host=' . $config['host'];
        if (is_int($config['port'] * 1)) {
            $dsn .= ';port=' . $config['port'];
        }
        if (!empty($config['database'])) {
            $dsn .= ';dbname=' . $config['database'];
        }
        if (!empty($config['charset'])) {
            $dsn .= ';charset=' . $config['charset'];
        }

        return $dsn;
    }

    /**
     * 获取数据库连接params
     *
     * @param array $config 数据库配置
     * @return array
     */
    protected function getConnectParams(array $config)
    {
        $params = $this->params;
        if (isset($config['params']) && is_array($config['params'])) {
            $params = $config['params'] + $this->params;
        }
        // 判断是否开启断线重连，开启断线重连，则采用PDO长链接
        if (Db::reconnect()) {
            $params[PDO::ATTR_PERSISTENT] = true;
        }

        return $params;
    }
}
