<?php

/**
 * 数据库配置
 *
 * @author Mon <985558837@qq.com>
 * @version v2.0 DB默认 配置
 */
return [
    // 默认使用的链接配置
    'default'   => [
        // 数据库类型，只支持mysql
        'type'          => 'mysql',
        // 服务器地址
        'host'          => '127.0.0.1',
        // 数据库名
        'database'      => 'test',
        // 用户名
        'username'      => 'root',
        // 密码
        'password'      => 'root',
        // 端口
        'port'          => '3306',
        // 数据库连接参数
        'params'        => [],
        // 数据库编码默认采用utf8
        'charset'       => 'utf8mb4',
        // 返回结果集类型
        'result_type'   => PDO::FETCH_ASSOC,
        // 是否开启读写分离
        'rw_separate'   => true,
        // 查询数据库连接配置，二维数组随机获取节点覆盖默认配置信息
        'read'          => [
            [
                // 用户名
                'username'  => 'root',
                // 密码
                'password'  => '123456',
                // 端口
                'port'      => '3307',
            ],
            [
                // 数据库名
                'database'  => 'demo',
                // 密码
                'password'  => '654321',
                // 端口
                'port'      => '3308',
            ]
        ],
        // 写入数据库连接配置，同上，开启事务后，读取不会调用查询数据库配置
        'write'         => [
            [
                // 服务器地址
                'host'      => '127.0.0.1',
                // 数据库名
                'database'  => 'test',
                // 用户名
                'username'  => 'root',
                // 密码
                'password'  => 'root',
                // 端口
                'port'      => '3306',
            ]
        ]
    ],
    // 测试链接
    'test'      => [
        // 数据库类型，只支持mysql
        'type'          => 'mysql',
        // 服务器地址
        'host'          => '127.0.0.1',
        // 数据库名
        'database'      => 'test',
        // 用户名
        'username'      => 'root',
        // 密码
        'password'      => 'root',
        // 端口
        'port'          => '3306',
        // 数据库连接参数
        'params'        => [],
        // 数据库编码默认采用utf8
        'charset'       => 'utf8mb4',
        // 返回结果集类型
        'result_type'   => PDO::FETCH_ASSOC,
        // 是否开启读写分离
        'rw_separate'   => true,
        // 是否开启读写分离
        'rw_separate'   => false,
        // 查询数据库连接配置，二维数组随机获取节点覆盖默认配置信息
        'read'          => [],
        // 写入数据库连接配置，同上，开启事务后，读取不会调用查询数据库配置
        'write'         => []
    ]
];

// return [
//     // 数据库类型
//     'type'            => 'mysql',
//     // 服务器地址
//     'host'            => '127.0.0.1',
//     // 数据库名
//     'database'        => '',
//     // 用户名
//     'username'        => '',
//     // 密码
//     'password'        => '',
//     // 端口
//     'port'            => '3306',
//     // 数据库连接参数
//     'params'          => [
//         // 强制列名为指定的大小写, CASE_NATURAL根据DB列名
//         PDO::ATTR_CASE              => PDO::CASE_NATURAL,
//         // 错误则抛出异常
//         PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
//         // 不转换 NULL 和空字符串
//         PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
//         // 默认还是一次传送,false改为分次传送
//         PDO::ATTR_STRINGIFY_FETCHES => false,
//         // 默认还是一次传送,false改为分次传送
//         PDO::ATTR_EMULATE_PREPARES  => false,
//     ],
//     // 数据库编码默认采用utf8
//     'charset'         => 'utf8',
//     // 返回结果集类型
//     'result_type'     => PDO::FETCH_ASSOC,
// ];
