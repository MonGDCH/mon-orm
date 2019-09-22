# 链接初始化数据库

> 通过抽象数据库访问层，将数据库链接、SQL查询构建、SQL查询生成等业务功能封装起来，只需要使用公共的DB类进行操作即可。

### 数据库配置

```php

return [
    // 数据库类型,目前只支持mysql
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

```

### 全局配置DB链接配置

> 全局设置DB链接默认配置，方便调用

```php
Db::setConfig( array $config ) : void
```

#### Demo

```php
Db::setConfig($config);
```

### 获取全局默认DB链接配置

> 获取设置的默认DB链接配置，对应setConfig方法

```php
Db::getConfig() : array
```

#### Demo
```php
$config = Db::getConfig();
```

### 链接数据库

> 链接DB

```php
Db::connect( [ array $config, boolean $reset ] ) : Connection
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| config | array | 否 | 链接数据库配置信息，如果设置了默认配置，且不使用新的配置，则可不填写 | 无 |
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

### 获取数据库链接池标识位

> 获取数据库链接池标识位，可通过标识位定位链接哪个DB

```php
Db::getKey( array $config ) : String
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| config | array | 是 | 链接数据库配置信息，如果设置了默认配置，且不使用新的配置，则可不填写 | 无 |

#### Demo

```php
$key = Db::getKey($config);
```
