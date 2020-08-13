<?php

namespace mon\orm;

use mon\factory\Container;
use mon\orm\db\Connection;

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
 * @method static \mon\orm\db\Query mixed query(string $sql, array $bind = [], boolean $class = false) 执行查询sql语句
 * @method static \mon\orm\db\Query mixed execute(string $sql, array $bind = []) 执行sql指令语句
 * @method static \mon\orm\db\Connection string getLastSql() 获取最后执行的sql
 * @method static \mon\orm\db\Connection integer getLastInsID(string $pk) 获取最后新增的ID
 * @method static \mon\orm\db\Connection void startTrans() 开启事务
 * @method static \mon\orm\db\Connection void commit() 提交事务
 * @method static \mon\orm\db\Connection void rollBack() 回滚事务
 * @author Mon <985558837@qq.com>
 * @version v1.1
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
	 * 查询事件
	 *
	 * @var array
	 */
	private static $event = [];

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
	 * 注册回调方法
	 *
	 * @param string   $event    事件名
	 * @param \Closure  $callback 回调方法
	 * @return void
	 */
	public static function event($event, $callback)
	{
		self::$event[$event] = $callback;
	}

	/**
	 * 触发事件
	 *
	 * @param string 	 $event   		事件名
	 * @param array  	 $params  		额外参数
	 * @param Connection $connection	链接实例
	 * @return mixed
	 */
	public static function trigger($event, Connection $connection, $params = [])
	{
		if (isset(self::$event[$event])) {
			$callback = self::$event[$event];
			return Container::instance()->invoke($callback, [$params, $connection]);
		}
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
	 * 获取Db配置
	 *
	 * @return array 数据库配置信息
	 */
	public static function getConfig()
	{
		return self::$config;
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
