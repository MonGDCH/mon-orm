# Mon-ORM

基于PHP5.6+，Mysql的便捷式ORM框架，主要实现：

* 链式操作生成SQL语句
* 事务支持
* 模型支持查询场景、数据自动完成(设置器，获取器)

## 安装

```
composer require mongdch/mon-orm
```

## 文档Wiki

[查看文档Wiki](https://github.com/MonGDCH/mon-orm/wiki)

## 使用

### Db类

```
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

```
use mon\orm\Db;
$config = [
	'host'     => '127.0.0.1',
	'database' => 'test',
	'username' => 'root',
	'password' => 'root',
	'port'     => '3306',
];
// 全局设置默认配置信息
Db::setConfig($config);
Db::table('test a')->join('demo b', 'a.pid=b.id', 'left')->select();
Db::getLastSql();
```
### 模型

定义模型

```
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
	 * @var string
	 */
	public $config = [模型独立使用的配置信息];

	/**
	 * 新增自动写入字段
	 * @var [type]
	 */
	protected $insert = [
		'create_time'	=> '',
		'update_time'	=> '',
		'status'	=> 1,
	];

	/**
	 * 更新自动写入字段
	 * @var [type]
	 */
	protected $update = [
		'update_time'
	];

	/**
	 * 自动补全查询数据
	 * @var array
	 */
	protected $append = [
		'count',
		'age'	=> 18,
	];

	/**
	 * 自动完成create_time字段
	 * 
	 * @param [type] $val 默认值
	 * @param array  $row 列值
	 */
	protected function setCreateTimeAttr($val, $row = []){
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * 自动完成update_time字段
	 * 
	 * @param [type] $val 默认值
	 * @param array  $row 列值
	 */
	protected function setUpdateTimeAttr($val, $row = []){
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * 自动完成格式化获取create_time结果
	 *
	 * @param  [type] $val [description]
	 * @param  array  $row [description]
	 * @return [type]      [description]
	 */
	protected function getCreateTimeAttr($val, $row){
		return date('Y-m-d H:i:s', $val);
	}

	/**
	 * 自动完成格式化append中count字段的数据
	 * @param  [type] $val [description]
	 * @param  [type] $row [description]
	 * @return [type]      [description]
	 */
	protected function getcountAttr($val, $row)
	{
		return count($row);
	}

	/**
	 * 测试查询场景
	 *
	 * @return [type] [description]
	 */
	protected function scopeTest($query)
	{
		return $query->where('status', 1)->limit(3);
	}

	/**
	 * 测试sava方法
	 *
	 * @return [type] [description]
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

```
// 调用结果Db类查询
$find = Test::where('id', 1)->find();

// 结合自动完成工具使用
$test = new Test;

// 新增
$test->save(['name' => mt_rand(1, 100)]);
// 新增返回自增ID
$test->save(['name' => 'get insert id'], null, 'id');

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

##### 更多使用方式请查看examples

---

# 版本

### 2.1.4

* 优化代码，增加注解
* 增加duplicate、using、extra、page等方法

### 2.1.3

* 优化代码，增强注解。

### 2.1.2

* 优化代码
* 增加connect、query、execute事件等DB类的全局事件绑定

### 2.1.1

* 优化代码
* 增加DB类的事件绑定，分别对应select、update、insert、delete事件

### 2.1.0

* 修复Connection对象getError方法与Model对象getError方法重名的BUG, 获取Connection::getError方法改为getQueryError
* 调整命名空间，改为mon\orm
* 优化代码结构

### 2.0.3

* 修复未定义自动处理的字段也自动处理的BUG

### 2.0.2

* 修复批量写入insertAll写入BUG

### 2.0.1

* 优化模型查询结果集
* 优化自动完成设置器及获取器
* 优化代码结构，修复lock查询无效的问题
* 优化模型scope方法。支持传参
* 优化Query类查询方法

### 2.0.0

* 优化事务支持
* 增强模型对象，增加save、get、all、scope等模型方法
* 增强模型功能，增加设置器、获取器的功能
* 优化查询结果集

### 1.0.3

* 修正count，svg, sum等方法无法使用debug获取查询语句
* 优化代码结构


### 1.0.2

* 增加setInc,setDec字段自增自减查询方法
* 修复find查询下debug方法无效的bug，优化闭包查询。

---

# 致谢

感谢您的支持和阅读，如果有什么不足的地方或者建议还请@我，如果你觉得对你有帮助的话还请给个star。

---

# 关于

作者邮箱： 985558837@qq.com
作者博客： [http://blog.gdmon.com](http://blog.gdmon.com)
