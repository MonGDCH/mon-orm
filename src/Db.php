<?php

namespace mon\orm;

use mon\orm\db\Query;
use mon\factory\Container;
use mon\orm\db\Connection;

/**
 * DB操作类
 *
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
	 * @return Query 查询构造器实例
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

		return new Query(self::$pool[$key]);
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
