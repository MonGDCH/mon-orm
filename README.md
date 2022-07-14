# Mon-ORM

基于PHP5.6+，Mysql的便捷式ORM框架，主要实现：

* 链式操作生成SQL语句
* 事务支持
* 模型支持查询场景、数据自动完成(设置器，获取器)
* 自动参数绑定
* 支持断线重连
* 支持查询事件监听
* 支持自动分布式部署读写分离

## 安装

```
composer require mongdch/mon-orm
```

## 文档Wiki

[查看文档](/doc/Home.md)

## 版本更新日志

[查看日志](./CHANGELOG.md)

## 使用

```php

// 数据库配置
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

### Db类

```php
use mon\orm\Db;
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

```php
use mon\orm\Db;
$config = [
	'default' => [
		'host'     => '127.0.0.1',
		'database' => 'test',
		'username' => 'root',
		'password' => 'root',
		'port'     => '3306',
	]
];
// 全局设置默认配置信息
Db::setConfig($config);
Db::table('test a')->join('demo b', 'a.pid=b.id', 'left')->select();
Db::getLastSql();
```

### 模型

定义模型

```php
use mon\orm\Model;
class Test extends Model
{
	/**
	 * 模型默认操作表名
	 * @var string
	 */
	public $table = [模型操作的表名];

	/**
	 * 模型独立使用的配置信息
	 * @var array
	 */
	public $config = [模型独立使用的配置信息];

	/**
	 * 新增自动写入字段
	 * @var array
	 */
	protected $insert = ['create_time' => '', 'update_time'	=> '', 'status'	=> 1];

	/**
	 * 更新自动写入字段
	 * @var array
	 */
	protected $update = ['update_time'];

	/**
	 * 自动补全查询数据
	 * @var array
	 */
	protected $append = ['count', 'age'	=> 18,];

	/**
	 * 自动完成create_time字段
	 * 
	 * @param mixed $val 默认值
	 * @param array  $row 列值
	 */
	protected function setCreateTimeAttr($val, $row = []){
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * 自动完成update_time字段
	 * 
	 * @param mixed $val 默认值
	 * @param array  $row 列值
	 */
	protected function setUpdateTimeAttr($val, $row = []){
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * 自动完成格式化获取create_time结果
	 *
	 * @param  mixed $val [description]
	 * @param  array  $row [description]
	 * @return string
	 */
	protected function getCreateTimeAttr($val, $row){
		return date('Y-m-d H:i:s', $val);
	}

	/**
	 * 自动完成格式化append中count字段的数据
	 * @param  mixed $val [description]
	 * @param  array $row [description]
	 * @return integer
	 */
	protected function getCountAttr($val, $row)
	{
		return count($row);
	}

	/**
	 * 测试查询场景
	 *
	 * @return \mon\orm\db\Query
	 */
	protected function scopeTest($query)
	{
		return $query->where('status', 1)->limit(3);
	}

	/**
	 * 测试sava方法
	 *
	 * @return array
	 */
	public function testScopee()
	{
		return $this->scope(function($query){
			return  $query->where('id', '>', 50);
		})->select();
	}
}

```

使用模型

```php
// 调用结果Db类查询
$find = Test::where('id', 1)->find();

// 结合自动完成工具使用
$test = new Test;

// 新增
$test->save(['name' => mt_rand(1, 100)]);
// 新增返回自增ID
$test->save(['name' => 'get insert id'], null, 'id');

// 批量新增
$test->saveAll([['a' => 1], ['a' => 2]]);

// 修改
$test->save(['name' => 'hello complete'], ['id' => 45]);

// 场景使用，相当于前置查询
$test->scope('test')->save(['name'=>'test scope'], []);
$test->testScopee();

// 查询一条记录
$test->get(['id' => ['>', 52]]);
$test->scope('test')->get();

// 查询多条记录
$test->all(['status' => 1]);
$data = $test->scope('test')->where('id', 20)->all();
```

定义断线重连，默认断线不重连，开启断线重连将使用长链接的方式链接数据库

```php
Db::reconnect(true);

```

##### 更多使用方式examples

---

# 致谢

感谢您的支持和阅读，如果有什么不足的地方或者建议还请@我，如果你觉得对你有帮助的话还请给个star。

---

# 关于

作者邮箱： 985558837@qq.com
作者博客： [http://gdmon.com](http://gdmon.com)
