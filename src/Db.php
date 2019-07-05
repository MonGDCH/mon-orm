<?php

namespace mon\orm;

use mon\orm\db\Connection;

/**
 * DB操作类
 *
 * @author Mon <985558837@qq.com>
 * @version v1.0
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
	 * @return [type]          [description]
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
	 * 配置对应加密key
	 *
	 * @param  array  $config 配置信息
	 * @return [type]         [description]
	 */
	public static function getKey(array $config)
	{
		return md5(serialize($config));
	}

	/**
	 * 设置DB配置，方便直接调用
	 *
	 * @param array  $config 配置信息
	 * @param [type] $name   [description]
	 */
	public static function setConfig(array $config)
	{
		self::$config = array_merge(self::$config, $config);
	}

	/**
	 * 获取Db配置
	 *
	 * @return [type] [description]
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
