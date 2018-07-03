<?php
namespace mon\exception;

use Exception;

/**
* MonDb自定义异常
*
* @author Mon <985558837@qq.com>
* @version v1.0
*/
class MondbException extends Exception
{
	/**
	 * 解析where条件失败
	 */
	const PARSE_WHERE_ERROR = 10100;

	/**
	 * 查询条件为空
	 */
	const WHERE_IS_NULL = 10110;

	/**
	 * DB类型不支持
	 */
	const TYPE_NOT_ALLOW = 10200;

	/**
	 * DB链接失败
	 */
	const LINK_FAILURE = 10300;

	/**
	 * 参数绑定失败
	 */
	const BIND_VALUE_ERROR = 10400;

	/**
	 * 未设置查询的表
	 */
	const TABLE_NULL_FOUND = 10500;
}