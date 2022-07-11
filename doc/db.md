# 链接初始化数据库

> 通过抽象数据库访问层，将数据库链接、SQL查询构建、SQL查询生成等业务功能封装起来，只需要使用公共的DB类进行操作即可。

### 数据库配置

```php

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
    // 测试数据库连接配置
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


```

### 全局配置DB链接配置

> 全局设置DB链接默认配置，方便调用

```php
setConfig( array $config ) : void
```

#### Demo

```php
Db::setConfig($config);
```

### 获取全局默认DB链接配置

> 获取设置的默认DB链接配置，对应setConfig方法，空则获取所有节点配置

```php
getConfig([string $name]) : array
```

#### Demo

```php
$config = Db::getConfig();
```

### 链接数据库

> 链接DB

```php
connect( [ array|string $config, boolean $reset ] ) : Connection
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| config | array|string | 否 | 链接数据库配置信息或配置节点名称，默认使用`default`配置节点 | 无 |
| reset | boolean | 否 | 是否重新链接数据库 | false |

#### Demo

```php

// 基础配置信息
$config = [
	'host'     => '127.0.0.1',
	'database' => 'test',
	'username' => 'root',
	'password' => 'root',
	'port'     => '3306',
];

// 通过connect方法连接DB操作DB
Db::connect($config)->table('test')->select();

```

### 断线重连

> 开启断线重连将使用长链接的方式链接数据库

```php
reconnect([boolean $reconnect]) : boolean
```

#### Demo

```php
Db::reconnect(true);

```



### 监听事件

```php
listen(string $evnets, string|Closure $callback) : void
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| evnets | string | 是 | 监听事件名 | 无 |
| callback | string|Closure | 是 | 事件回调 | 无 |


#### 内置事件支持

```php
$events = [
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
]
```

#### Demo

```php
Db::evnets('connect', ConnectEvent::class);
Db::evnets('select', function(Connection $connection, $options){
    var_dump($connection, $options)
});
```

### 触发事件

> 触发事件，对应`listen`方法

```php
trigger(string $event, Connection $connection, array $options) : void
```


#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| evnets | string | 是 | 触发事件名 | 无 |
| connection | Connection | 是 | DB链接实例 | 无 |
| options | array | 否 | 额外参数 | 无 |

