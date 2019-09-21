<?php

/**
 * 数据库配置
 *
 * @author Mon <985558837@qq.com>
 * @version v2.0 DB默认 配置
 */

return [
    // 数据库类型
    'type'            => 'mysql',
    // 服务器地址
    'host'            => '127.0.0.1',
    // 数据库名
    'database'        => '',
    // 用户名
    'username'        => '',
    // 密码
    'password'        => '',
    // 端口
    'port'            => '3306',
    // 数据库连接参数
    'params'          => [
        // 强制列名为指定的大小写, CASE_NATURAL根据DB列名
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        // 错误则抛出异常
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        // 不转换 NULL 和空字符串
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        // 默认还是一次传送,false改为分次传送
        PDO::ATTR_STRINGIFY_FETCHES => false,
        // 默认还是一次传送,false改为分次传送
        PDO::ATTR_EMULATE_PREPARES  => false,
    ],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 返回结果集类型
    'result_type'     => PDO::FETCH_ASSOC,
];
