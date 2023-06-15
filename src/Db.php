<?php

namespace mon\orm;

use Closure;
use mon\util\Container;
use mon\orm\db\Connection;
use mon\orm\exception\DbException;

/**
 * DB操作类
 *
 * @method static \mon\orm\db\Query table(string $table) 设置表名(含表前缀)
 * @method static \mon\orm\db\Query where(mixed $field, string $op = null, mixed $condition = null) 查询条件
 * @method static \mon\orm\db\Query whereOr(mixed $field, string $op = null, mixed $condition = null) 查询条件(OR)
 * @method static \mon\orm\db\Query whereLike(string $field, mixed $condition, $logic = 'AND') 指定Like查询条件
 * @method static \mon\orm\db\Query whereNotLike(string $field, mixed $condition, $logic = 'AND') 指定NotLike查询条件
 * @method static \mon\orm\db\Query whereBetween(string $field, mixed $condition, $logic = 'AND') 指定Between查询条件
 * @method static \mon\orm\db\Query whereNotBetween(string $field, mixed $condition, $logic = 'AND') 指定NotBetween查询条件
 * @method static \mon\orm\db\Query whereIn(string $field, mixed $condition, $logic = 'AND') 指定In查询条件
 * @method static \mon\orm\db\Query whereNotIn(string $field, mixed $condition, $logic = 'AND') 指定NotIn查询条件
 * @method static \mon\orm\db\Query whereNull(string $field, $logic = 'AND') 指定Null查询条件
 * @method static \mon\orm\db\Query whereNotNull(string $field, $logic = 'AND') 指定NotNull查询条件
 * @method static \mon\orm\db\Query join(mixed $join, mixed $condition = null, string $type = 'INNER') JOIN查询
 * @method static \mon\orm\db\Query union(mixed $union, boolean $all = false) UNION查询
 * @method static \mon\orm\db\Query limit(mixed $offset, mixed $length = null) 查询LIMIT
 * @method static \mon\orm\db\Query page(integer $page, integer $length) 分页查询
 * @method static \mon\orm\db\Query order(mixed $field, string $order = null) 查询ORDER
 * @method static \mon\orm\db\Query field(mixed $field) 指定查询字段
 * @method static \mon\orm\db\Query alias(string $alias) 指定表别名
 * @method static \mon\orm\db\Query inc(string $field, float $step = 1) 字段值增长
 * @method static \mon\orm\db\Query dec(string $field, float $step = 1) 字段值减少
 * @method static integer insert(array $data = [], $replace = false, $getLastInsID = false, $key = null) 插入操作, 默认返回影响行数
 * @method static integer insertAll(array $data = [], $replace = false) 批量插入操作, 返回影响行数
 * @method static array query(string $sql, array $bind = [], boolean $class = false) 执行查询sql语句
 * @method static integer execute(string $sql, array $bind = []) 执行sql指令语句
 * @method static mixed action(Closure $callback) 回调方法封装执行事务
 * @method static mixed actionXA(Closure $callback, array $dbs = [])) 回调方法封装执行XA事务
 * @method static string getLastSql() 获取最后执行的sql
 * @method static integer getLastInsID(string $pk) 获取最后新增的ID
 * @method static void startTrans() 开启事务
 * @method static void commit() 提交事务
 * @method static void rollBack() 回滚事务
 * @method static void startTransXA(string $xid) 开启XA分布式事务
 * @method static void commitXA(string $xid) 提交XA事务
 * @method static void rollbackXA(string $xid) 回滚XA事务
 * @method static void prepareXA(string $xid) 预编译XA事务
 * @author Mon <985558837@qq.com>
 * @version v2.3.0
 */
class Db
{
	/**
	 * 默认配置节点名称
	 * 
	 * @var string
	 */
	const DEFAULT_KEY = 'default';

	/**
	 * DB实例列表
	 *
	 * @var Connection[]
	 */
	private static $pool = [];

	/**
	 * 事件钩子
	 *
	 * @var array
	 */
	private static $events = [
		// 链接DB
		'connect'			=> [],
		// select查询
		'select'			=> [],
		// insert查询
		'insert'			=> [],
		// delete查询
		'delete'			=> [],
		// update查询
		'update'			=> [],
		// query全局查询
		'query'				=> [],
		// execute全局指令
		'execute'			=> [],
		// 开启事务
		'startTrans'		=> [],
		// 提交事务
		'commitTrans'		=> [],
		// 回滚事务
		'rollbackTrans'		=> [],
		// 开启事扩库务
		'startTransXA'		=> [],
		// 开启预编译XA事务
		'prepareTransXA'	=> [],
		// 提交跨库事务
		'commitTransXA'		=> [],
		// 回滚跨库事务
		'rollbackTransXA'	=> [],
	];

	/**
	 * DB配置
	 *
	 * @var array
	 */
	private static $config = [];

	/**
	 * 断开连接是否重连，注意：强制重连有可能导致数据库core掉
	 *
	 * @var boolean
	 */
	private static $break_reconnect = false;

	/**
	 * 链接Db
	 *
	 * @param  string|array   $config DB链接配置
	 * @param  boolean $reset  链接标示，true则重连
	 * @return Connection 链接实例
	 */
	public static function connect($config = [], $reset = false)
	{
		if (empty($config)) {
			// 未定义配置信息，获取默认配置信息
			$config = self::getConfig(self::DEFAULT_KEY);
		} elseif (is_string($config)) {
			// 非空字符串，即指定配置节点，获取对应配置
			$config = self::getConfig($config);
		}

		// 获取链接池key值
		$key = self::getKey($config);
		// 重连或者不存在链接
		if ($reset === true || !isset(self::$pool[$key])) {
			self::$pool[$key] = new Connection($config);
		}

		return self::$pool[$key];
	}

	/**
	 * 设置DB配置，方便直接调用
	 *
	 * @param array  $config 配置信息
	 * @return void
	 */
	public static function setConfig(array $config)
	{
		self::$config = array_merge(self::$config, $config);
	}

	/**
	 * 配置对应加密key
	 *
	 * @param string $name 配置节点名称
	 * @return array 数据库配置信息
	 */
	public static function getConfig($name = '')
	{
		if ($name === '') {
			return self::$config;
		}

		return isset(self::$config[$name]) ? self::$config[$name] : [];
	}

	/**
	 * 获取连接池
	 *
	 * @return Connection[]
	 */
	public static function getPool(): array
	{
		return static::$pool;
	}

	/**
	 * 定义或获取数据库断开是否自动重连
	 *
	 * @param boolean $reconnect
	 * @return boolean
	 */
	public static function reconnect($reconnect = null)
	{
		if (!is_null($reconnect)) {
			self::$break_reconnect = boolval($reconnect);
		}

		return self::$break_reconnect;
	}

	/**
	 * 监听事件
	 *
	 * @param string $event   钩子名称
	 * @param mixed $callbak 钩子回调
	 * @return void
	 */
	public static function listen($event, $callbak)
	{
		isset(self::$events[$event]) || self::$events[$event] = [];
		self::$events[$event][] = $callbak;
	}

	/**
	 * 监听事件，执行事件回调
	 *
	 * @param mixed $event 事件名
	 * @param Connection $connection 链接实例
	 * @param mixed &$params 参数
	 * @throws DbException
	 * @return array
	 */
	public static function trigger($event, Connection $connection, $params = [])
	{
		$result = [];
		if (isset(self::$events[$event])) {
			$callbacks = self::$events[$event];
			foreach ($callbacks as $k => $callback) {
				if (is_string($callback) && !empty($callback)) {
					$class = [$callback, 'handler'];
				} elseif ($callback instanceof Closure) {
					$class = $callback;
				} else {
					throw new DbException('Event callback faild!', DbException::EVENT_CALLBACK_FAILD);
				}

				$result[$k] = Container::instance()->invoke($class, [$connection, $params]);
				if ($result[$k] === false) {
					// 如果返回false 则中断事件执行
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * 配置对应加密key
	 *
	 * @param  array  $config 配置信息
	 * @return string 配置key值
	 */
	public static function getKey(array $config)
	{
		return md5(serialize($config));
	}

	/**
	 * 调用Connection类的方法
	 *
	 * @param  string $method 方法名
	 * @param  array  $params 参数
	 * @return mixed
	 */
	public static function __callStatic($method, $params)
	{
		return call_user_func_array([self::connect(), $method], $params);
	}
}
