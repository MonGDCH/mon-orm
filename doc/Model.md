# 模型定义及使用

## 模型定义

> 模型为Db实例的封装，提供了查询场景、数据自动完成(设置器，获取器)等功能实现

```php

class Test extends \mon\orm\Model
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
	 * @param array  $row 行值
	 */
	protected function setCreateTimeAttr($val, $row = []){
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * 自动完成update_time字段
	 * 
	 * @param [type] $val 默认值
	 * @param array  $row 行值
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

### 模型查询

> 模型可使用DB实例中封装好的数据库链接、SQL查询构建、SQL查询生成等业务功能暴露的接口方法。

```php
$find = Test::where('id', 1)->find();
```

### 获取DB实例

> 获取模型封装的DB实例

```php
db( boolean $newLink ) : Connect
```

### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| newLink | boolean | 否 | 是否重新链接数据库 | false |

### Demo

```php

// 创建实例调用
$test = new Test();
$connect = $test->db();

// 静态调用
$connect = Test::db();

```

### 查询场景

> 使用模型查询场景可以更好的抽象化查询业务，对特定业务特定场景的业务逻辑进行封装。

```PHP
scope( Closure|String $name, [...$args] ) : Query
```

### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| name | Closure\|String | 是 | 使用的查询场景名称或闭包 | 无 |
| args |  | 否 | 附加的参数 | 无 |

```php

/**
 * 测试查询场景
 *
 * @return [type] [description]
 */
protected function scopeTest($query)
{
    return $query->where('status', 1)->limit(3);
}

// 使用查询场景名称调用【测试查询场景】进行查询
$test->scope('test')->get();

// 使用闭包查询场景进行查询
$this->scope(function($query){
    return  $query->where('id', '>', 50);
})->select();

```

### 保存数据

> 结合insert、update两个属性可以做到在新增或者更新数据时，实现数据自动完成（设置器）

```php
save( array $data [ , array $where, string $sequence, Query $query ] ) : Data
```

### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| data | array | 是 | 保存的的数据 | 无 |
| where | array | 否 | 查询where条件，存在则为更新操作，不设置则为新增操作 | null |
| sequence | string | 否 | 自增序列名，存在且为新增操作时，返回自增ID | null |
| query | Db | 否 | 查询实例 | null |

#### Demo

```php

// 写入数据，insert
$save = $this->save(['name' => 'test']);

// 更新数据
$save = $this->save(['name' => 'demo'], ['id' => 1]);

```

#### 新增时自动写入或补全

定义$this->insert属性，设置新增时要自动完成的数据

```php

/**
 * 新增自动写入字段
 * @var [type]
 */
protected $insert = [
    'create_time'   => '',
    'update_time'   => '',
    'status'        => 1,
];

/**
 * 自动完成update_time字段
 * 
 * @param [type] $val 默认值
 * @param array  $row 行值
 */
protected function setUpdateTimeAttr($val, $row = []){
    return $_SERVER['REQUEST_TIME'];
}

```

* 当设置了insert属性后，新增时会自动查找写入的数据并对数据进行调整和补全
* insert属性中设置了update_time字段，且存在setUpdateTimeAttr方法，则会调用setUpdateTimeAttr方法并传入写入的数据中的值，及对应的写入数据。如不存在setUpdateTimeAttr方法则使用insert属性中设置的值
* 对应自动完成使用名为驼峰法命名，会将设置的字段名中的“_”转换为驼峰式


#### 更新时自动写入或补全

* 当设置了update属性后，更新时会自动查找写入的数据并对数据进行调整和补全
* 具体实现方式与上述的新增时一致，使用设置器实现


### 获取单条记录

> 结合append属性可以做到在获取数据时，实现数据自动完成（获取器）

```php
get( [ array $where, Db $db ] ) : Data
```

### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| where | array | 否 | 查询where条件 | 无 |
| Db | Db | 否 | 查询实例 | null |

#### Demo

```php

// 使用参数作为where条件
$find = $this->get(['id' => 1]);

// 使用链式操作where条件
$find = $this->where('id', 1)->where(['status' => 1])->get();

```

#### 读取数据时，补全数据

定义$this->append属性，设置新增时要自动完成的数据

```php

/**
 * 自动补全查询数据
 * @var array
 */
protected $append = [
    'count',
    'age'	=> 18,
];

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
 * 自动完成格式化获取create_time结果
 *
 * @param  [type] $val [description]
 * @param  array  $row [description]
 * @return [type]      [description]
 */
protected function getCreateTimeAttr($val, $row){
    return date('Y-m-d H:i:s', $val);
}

```

* 获取数据后获取器会自动识别查询的数据中是否存在create_time字段，如果存在，则会调用getCreateTimeAttr方法进行补全，将原值及行数据闯入，并使用返回值作为新的字段值。
* 设置了append属性后，获取器同时会扫描append属性，将append属性中设置的字段自动写入到查询的结果中。
* 对应自动完成使用名为驼峰法命名，会将设置的字段名中的“_”转换为驼峰式


### 查询多条记录

> 结合append属性可以做到在获取数据时，实现数据自动完成（获取器）

```php
all( [ array $where, Db $db ] ) : Data
```

### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| where | array | 否 | 查询where条件 | 无 |
| Db | Db | 否 | 查询实例 | null |

#### Demo

```php

// 使用参数作为where条件
$find = $this->all(['id' => 1]);

// 使用链式操作where条件
$find = $this->where('id', 1)->where(['status' => 1])->all();

```

* 与上述的获取一条记录get方法一样，all方法同样支持获取器的使用。