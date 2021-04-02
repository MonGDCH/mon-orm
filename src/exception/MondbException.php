<?php

namespace mon\orm\exception;

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
	 * 多维查询条件只支持索引数组
	 */
	const PARSE_WHERE_EXPRESS_ERROR = 10110;

	/**
	 * 查询条件为空
	 */
	const WHERE_IS_NULL = 10110;

	/**
	 * 查询语句为空
	 */
	const SQL_IS_NULL = 10120;

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

	/**
	 * 场景查询不存在
	 */
	const SCOPE_NULL_FOUND = 10600;

	/**
	 * 查询对象未绑定模型
	 */
	const QUERY_MODEL_NOT_BIND = 10700;

	/**
	 * 操作模型不支持自动完成 - save方法
	 */
	const MODEL_NOT_SUPPORT_SAVE = 10710;

	/**
	 * 操作模型不支持自动完成 - get方法
	 */
	const MODEL_NOT_SUPPORT_GET = 10720;

	/**
	 * 操作模型不支持自动完成 - save方法
	 */
	const MODEL_NOT_SUPPORT_ALL = 10730;

	/**
	 * 操作模型不支持自动完成 - saveAll方法
	 */
	const MODEL_NOT_SUPPORT_SAVEALL = 10740;

	/**
	 * 表达式格式错误
	 */
	const RAW_EXPRESSION_FAILD = 10800;
}
