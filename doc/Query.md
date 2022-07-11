# 查询方法及使用

## 直调方法

### SQL查询

> 执行SQL查询语句, 返回查询结果或PDO结果集

```php
query( string $sql [, array $bind, boolean $pdo ] ) : result|PDO
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| sql | string | 是 | 执行查询的sql语句 | 无 |
| bind | array | 否 | 执行查询的sql语句中绑定的参数 | 无 |
| pdo | boolean | 否 | 是否返回PDO结果集实例 | false |

#### Demo

```php

// 直接调用
$res = Db::query('SELECT * FROM test');

// 参数绑定
$res = Db::query('SELECT * FROM test WHERE `name` = ? LIMIT ?', ['name', 1]);

$res = Db::query('SELECT * FROM test WHERE `name` = :name LIMIT :limit', ['name' => 'name', 'limit' => 1]);

```


### 执行命令指令

> 执行SQL指令语句，返回影响行数

```php
execute( string $sql [, array $bind] ) : numRows
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| sql | string | 是 | 执行的sql语句 | 无 |
| bind | array | 否 | 执行的sql语句中绑定的参数 | 无 |

#### Demo

```php

// 直接调用
$res = Db::execute('UPDATE test SET `name` = 1');

// 参数绑定
$res = Db::execute('UPDATE test SET `name` = ? WHERE `id` = ?', ['name', 1]);

$res = Db::execute('UPDATE test SET `name` = :name WHERE `id` = :id', ['name' => 'name', 'id' => 1]);

```

### 事务


* 使用事务数据表必须为innodb类型
* 事务支持嵌套及状态回滚

#### 开启mysql事务查询

```php
startTrans() : void
```

#### 提交事务

```php
commit() : void
```

#### 事务回滚

```php
rollBack() : void
```


#### Demo

```php

Db::startTrans();

try{
    $save = Db::table('test')->insert([
        'name'	=> mt_rand(1, 100) . 'b',
        'update_time' => $_SERVER['REQUEST_TIME'],
        'create_time' => $_SERVER['REQUEST_TIME'],
    ]);
    if(!$save){
        Db::rollBack();
        return false;
    }
    Db::commit();
}catch(DbException $e){
    Db::rollBack();
}
```


### 获取表字段信息

> 获取数据库表字段信息

```php
getFields( string $table ) : array
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| table | string | 是 | 查看的表 | 无 |


#### Demo

```php
$info = Db::getFields('test');
```

### 查看库表信息

> 查看数据库表信息

```php
getTables( string $database ) : array
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| database | string | 是 | 查看的数据库 | 无 |


#### Demo

```php
$info = Db::getTables('demo');
```

### SQL分析

> 分析执行的SQL性能

```php
explain( string $sql ) : array
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| sql | string | 是 | 要分析的sql语句 | 无 |


#### Demo

```php
$info = Db::explain('SELECT * FROM `test`');
```

### 关闭数据库链接

> 断开链接的数据

```php
close() : void
```

#### Demo

```php
Db::close();
```

### 获取影响行数

> 获取影响行数

```php
getNumRows() : int
```

#### Demo

```php
Db::getNumRows();
```

### 获取最后写入数据的ID

> 获取最后写入数据的ID

```php
getLastInsID( [ string $pk ] ) : array
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| pk | string | 否 | 自增主键名 | 无 |


#### Demo

```php
$id = Db::getLastInsID();
```

### 获取最后执行的SQL

> 获取最后执行的SQL

```php
getLastSql() : string
```

#### Demo

```php
$sql = Db::getLastSql();
```


### 获取查询失败结果

> 获取查询失败结果

```php
getQueryError() : string
```

#### Demo

```php
$queryError = Db::getQueryError();
```

## 链式操作

### 查询数据

> 查询记录，返回所有结果

```php
select() : array
```

#### Demo

```php

// 链接操作拼接SQL进行查询，返回二维数组
$data = Db::table('test')->where('status', 1)->select();

```

### 查询一条数据

> 查询记录，只返回一条记录

```php
find() : array
```


#### Demo

```php

// 链接操作拼接SQL进行查询，返回一维数组
$data = Db::table('test')->where('status', 1)->find();

```

### 更新数据

> 更新数据，返回影响行数, 更新数据必须存在where条件

```php
update( [ array $data ] ) : int
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| data | array | 否 | 更新的数据 | 无 |


#### Demo

```php
$save = Db::table('test')->where(['id' => 1])->update(['name' => 'uname']);
```

### 字段值自增

> 字段值自增，返回影响行数, 更新数据必须存在where条件且自增字段必须为整形

```php
setInc( string|array $field [, int $step ] ) : int

inc( string|array $field [, int $step ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 自增的字段 | 无 |
| step | int | 否 | 自增的步长 | 1 |


#### Demo

```php

$save = Db::table('test')->where(['id' => 1])->setInc('age');

$save = Db::table('test')->where(['id' => 1])->setInc('age', 2);

// 使用inc方法
$save = Db::table('test')->where(['id' => 1])->inc('age', 1)->update();

```


### 字段值自减

> 字段值自减，返回影响行数, 更新数据必须存在where条件且自减字段必须为整形

```php
setDec( string|array $field [, int $step ] ) : int

dec( string|array $field [, int $step ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 自减的字段 | 无 |
| step | int | 否 | 自减的步长 | 1 |


#### Demo

```php

$save = Db::table('test')->where(['id' => 1])->setDec('age');

$save = Db::table('test')->where(['id' => 1])->setDec('age', 2);

// 使用dec方法
$save = Db::table('test')->where(['id' => 1])->dec('age', 1)->update();

```

### 写入数据

> 写入数据，返回影响行数或自增ID

```php
insert( [ array $data, boolean $replace, boolean $getLastInsID, string $key ] ) : int
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| data | array | 否 | 写入的数据 | 无 |
| replace | boolean | 否 | 是否为replace写入 | false |
| getLastInsID | boolean | 否 | 是否返回最后自增的ID | false |
| key | string | 否 | 自增键名 | null |

#### Demo

```php

$save = Db::table('test')->data(['name' => 'bname'])->insert();

$save = Db::table('test')->insert(['name' => 'cname']);

```

### 写入多条数据

> 写入多条数据，返回影响行数

```php
insertAll( [ array $data, boolean $replace ] ) : int
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| data | array | 否 | 写入的数据 | 无 |
| replace | boolean | 否 | 是否为replace写入 | false |


#### Demo

```php

$save = Db::table('test')->data([
    ['name' => 'aa'],
    ['name' => 'bb'],
])->insertAll();

$save = Db::table('test')->insertAll([
    ['name' => 'cc'],
    ['name' => 'dd'],
]);

```

### 删除数据

> 删除数据，返回影响行数, 删除数据必须存在where条件

```php
delete() : int
```

#### Demo

```php
$save = Db::table('test')->where(['id' => 1])->delete();
```

### Count查询

> 查询记录总条数

```php
count( [ string $field ] ) : int
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 否 | 键名 | * |


#### Demo

```php
$save = Db::table('test')->count();
```

### sum查询

> 查询记录总和

```php
sum( string $field  ) : float
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 键名 | 无 |

#### Demo

```php
$save = Db::table('test')->sum('age');
```

### min查询

> 查询记录最小值

```php
min( string $field  ) : float
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 键名 | 无 |

#### Demo

```php
$save = Db::table('test')->min('age');
```

### max查询

> 查询记录最大值

```php
max( string $field  ) : float
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 键名 | 无 |

#### Demo

```php
$save = Db::table('test')->max('age');
```

### svg查询

> 查询记录平均值

```php
svg( string $field  ) : float
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 键名 | 无 |

#### Demo

```php
$save = Db::table('test')->svg('age');
```

### SQL调试模式

> 获取即将执行的SQL语句，但不执行

```php
debug() : Query
```

#### Demo

```php
$sql = Db::table('test')->debug()->select(');
echo $sql;
```

### 获取PDO结果集

> 获取PDO结果集

```php
getObj() : Query
```

#### Demo

```php
$pdo = Db::table('test')->getObj()->select(');
var_dump($pdo);
```

### 指定操作的表

```php
table( string $table  ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| table | string | 是 | 操作表名 | 无 |

#### Demo

```php
$save = Db::table('test')->where(['id' => 1])->select();
```

### 指定操作的表的别名

```php
alias( string $alias  ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| alias | string | 是 | 操作表名的别名 | 无 |

#### Demo

```php
$save = Db::table('test')->alias('a')->where(['id' => 1])->select();
```

### 指定查询的字段

```php
field( string|array $field  ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string\|array | 是 | 指定要查询的字段 | 无 |

#### Demo

```php

$save = Db::table('test')->alias('a')->field('name, age')->where(['id' => 1])->select();

$save = Db::table('test')->field(['name', 'age'])->select();
```

### Limit操作

```php
limit( int $offset [, int $limit ]  ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| offset | int | 是 | 查询的记录数或起始查询记录数 | 无 |
| limit | int | 否 | 查询的记录数 | 无 |

#### Demo

```php
$save = Db::table('test')->where('age', '>', 1)->limit(5)->select();

$save = Db::table('test')->where('age', '>', 1)->limit(5, 5)->select();
```

### order by操作

```php
order( string $field [, string $order ]  ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 进行排序的字段 | 无 |
| order | string | 否 | 排序的方式 | 无 |

#### Demo

```php
$save = Db::table('test')->where('age', '>', 1)->order('id desc')->limit(5)->select();

$save = Db::table('test')->where('age', '>', 1)->order('id', 'desc')->limit(5, 5)->select();
```

### group查询

```php
group( string $field  ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 指定要查询的字段 | 无 |

#### Demo

```php
$save = Db::table('test')->group('age')->select();
```

### having查询

```php
having( string $field  ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 指定要查询的字段 | 无 |

#### Demo

```php
$save = Db::table('test')->group('age')->having('age > 1')->select();
```

### join查询

```php
join( string $join [, string $condition, string $type ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| join | string | 是 | join关联的表名 | 无 |
| condition | string | 否 | 关联的条件 | 无 |
| type | string | 否 | join关联的方式 | INNER |

#### Demo

```php
$save = Db::table('test a')->join('demo b', 'a.id = b.id')->select();
$save = Db::table('test a')->join('demo b', 'a.id = b.id', 'LEFT')->select();
```

### where查询

> 使用 AND 的形式进行where条件的拼装

```php
where( string $field [, string $op, string $condition ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 查询字段或者查询条件 | 无 |
| condition | string | 否 | 查询条件 | null |
| op | string | 否 | 查询表达式 | INNER |

#### Demo

```php
// where 条件 ID = 1 的多种写法
$save = Db::table('test')->where('id = 1')->select();
$save = Db::table('test')->where('id', 1)->select();
$save = Db::table('test')->where('id', '=', 1)->select();
$save = Db::table('test')->where(['id' => 1])->select();
$save = Db::table('test')->where(['id' => ['=', 1]])->select();
```

### where or查询

> 使用 OR 的形式进行where条件的拼装

```php
whereOr( string $field [, string $op, string $condition ] ) : Query
```
* 与where方法用法一致

### where xor查询

> 使用 XOR 的形式进行where条件的拼装

```php
whereXor( string $field [, string $op, string $condition ] ) : Query
```
* 与where方法用法一致


### like查询

```php
whereLike( string $field, string $condition [, string $logic ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 查询字段 | 无 |
| condition | string | 是 | 查询条件 | 无 |
| logic | string | 否 | 拼接形式，默认AND | AND |

#### Demo

```php
$save = Db::table('test')->whereLike('name', '%a%')->select();
$save = Db::table('test')->whereLike('name', 'a%', 'OR')->select();
```


### not like查询

```php
whereNotLike( string $field, string $condition [, string $logic ] ) : Query
```

* 与whereLike方法用法一致


### Between查询

```php
whereBetween( string $field, string $condition [, string $logic ] ) : Query
```

* 与whereLike方法用法一致


### not between查询

```php
whereNotBetween( string $field, string $condition [, string $logic ] ) : Query
```

* 与whereLike方法用法一致

### null 查询

```php
whereNull( string $field [, string $logic ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 查询字段 | 无 |
| logic | string | 否 | 拼接形式，默认AND | AND |

#### Demo

```php
$save = Db::table('test')->whereNull('name')->select();
$save = Db::table('test')->whereNull('name', 'OR')->select();
```

### not null查询

```php
whereNotNull( string $field [, string $logic ] ) : Query
```

* 与whereNull方法用法一致


### exists查询

```php
whereExists( string $field [, string $logic ] ) : Query
```

* 与whereNull方法用法一致


### not exists查询

```php
whereNotExists( string $field [, string $logic ] ) : Query
```

* 与whereNull方法用法一致


### in查询

```php
whereIn( string $field, string $condition [, string $logic ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string | 是 | 查询字段 | 无 |
| condition | string | 是 | 查询条件 | 无 |
| logic | string | 否 | 拼接形式，默认AND | AND |

#### Demo

```php
$save = Db::table('test')->whereIn('name', ['a', 'b'])->select();
$save = Db::table('test')->whereIn('name', 'a, b')->select();
```


### not in查询

```php
whereNotIn( string $field [, string $logic ] ) : Query
```

* 与whereIn方法用法一致


### 设置操作数据

```php
data( string|array $field, [, string $value ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| field | string\|array | 是 | 设置的数据 | 无 |
| value | string | 否 | 设置的值 | 无 |

#### Demo

```php
$save = Db::table('test')->data('name', 'a')->where('id', 2)->update();
$save = Db::table('test')->data(['name' => 'b'])->whereIn('name', 'a, b')->update();
```


### lock操作

```php
lock( [ boolean $lock ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| lock | boolean | 否 | 是否锁 | 无 |

#### Demo

```php
$save = Db::table('test')->lock(true)->data('name', 'a')->where('id', 2)->update();
```

### union 操作

> 链接查询

```php
union( string $union [, boolean $all ] ) : Query
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| union | string | 是 | union的查询 | 无 |
| all | boolean | 否 | 是否 union all | false |

#### Demo

```php
$save = Db::table('test')->union('SELECT * FROM DEMO')->select();
```

### force 操作表名

> 指定强制索引

```php
force(string $force) : Query
```

#### Demo

```php
$save = Db::table('test')->force('status')->select();
```