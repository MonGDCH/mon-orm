<?php

namespace mon\orm;

use Closure;
use mon\util\Container;
use mon\orm\db\Connection;
use mon\orm\exception\MondbException;

/**
 * DB操作类
 *
 * @method static \mon\orm\db\Query table(string $table) 设置表名(含表前缀)
 * @method static \mon\orm\db\Query where(mixed $field, string $op = null, mixed $condition = null) 查询条件
 * @method static \mon\orm\db\Query whereOr(mixed $field, string $op = null, mixed $condition = null) 查询条件(OR)
 * @method static \mon\orm\db\Query join(mixed $join, mixed $condition = null, string $type = 'INNER') JOIN查询
 * @method static \mon\orm\db\Query union(mixed $union, boolean $all = false) UNION查询
 * @method static \mon\orm\db\Query limit(mixed $offset, mixed $length = null) 查询LIMIT
 * @method static \mon\orm\db\Query page(integer $page, integer $length) 分页查询
 * @method static \mon\orm\db\Query order(mixed $field, string $order = null) 查询ORDER
 * @method static \mon\orm\db\Query field(mixed $field) 指定查询字段
 * @method static \mon\orm\db\Query alias(string $alias) 指定表别名
 * @method static \mon\orm\db\Query inc(string $field, integer $step = 1) 字段值增长
 * @method static \mon\orm\db\Query dec(string $field, integer $step = 1) 字段值减少
 * @method static \mon\orm\db\Query query(string $sql, array $bind = [], boolean $class = false) 执行查询sql语句
 * @method static \mon\orm\db\Query execute(string $sql, array $bind = []) 执行sql指令语句
 * @method static \mon\orm\db\Query action(Closure $callback) 回调方法封装执行事务
 * @method static \mon\orm\db\Query actionXA(Closure $callback) 回调方法封装执行XA事务
 * @method static \mon\orm\db\Connection getLastSql() 获取最后执行的sql
 * @method static \mon\orm\db\Connection getLastInsID(string $pk) 获取最后新增的ID
 * @method static \mon\orm\db\Connection startTrans() 开启事务
 * @method static \mon\orm\db\Connection commit() 提交事务
 * @method static \mon\orm\db\Connection rollBack() 回滚事务
 * @method static \mon\orm\db\Connection startTransXA(string $xid) 开启XA分布式事务
 * @method static \mon\orm\db\Connection commitXA(string $xid) 提交XA事务
 * @method static \mon\orm\db\Connection rollbackXA(string $xid) 回滚XA事务
 * @method static \mon\orm\db\Connection prepareXA(string $xid) 预编译XA事务
 * @author Mon <985558837@qq.com>
 * @version v2.3.0
 */
class Db
{
	/**
	 * DB实例列表
	 *
	 * @var array
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
	 * 链接Db
	 *
	 * @param  array   $config DB链接配置
	 * @param  boolean $reset  链接标示，true则重连
	 * @return Connection 链接实例
	 */
	public static function connect(array $config = [], $reset = false)
	{
		if (empty($config)) {
			$config = self::getConfig();
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

		return isset(self::$config[$name]) ? self::$config[$name] : null;
	}

	/**
	 * 监听事件
	 *
	 * @param mixed $event   钩子名称
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
	 * @throws MondbException
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
					throw new MondbException('Event callback faild!', MondbException::EVENT_CALLBACK_FAILD);
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
