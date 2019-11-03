<?php
require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;

date_default_timezone_set('PRC');

$config = [
	// 服务器地址
	'host'        	  => '127.0.0.1',
	// 数据库名
	'database'        => 'test',
	// 用户名
	'username'        => 'root',
	// 密码
	'password'        => '19930603',
	// 端口
	'port'        	  => '3306',
];

class Test
{
	// 获取最后写入的ID
	public function getLastId($option, $query)
	{
		var_dump($query->getLastInsID());
	}

	// 获取最后执行的SQL
	public function getLastSql($option, $query)
	{
		// var_dump($option);
		var_dump($query->getLastSql());
	}
}

Db::setConfig($config);

// 绑定事件
// 链接DB事件
Db::event('connect', function ($option, $query) {
	// var_dump($option);
	// var_dump($query->getLastSql());
});
Db::event('select', 'Test@getLastSql');
Db::event('insert', 'Test@getLastId');
Db::event('delete', 'Test@getLastId');
Db::event('update', 'Test@getLastId');

// 全局查询事件，如已绑定了select事件，使用select方法进行查询，会同时触发2种事件
Db::event('query', 'Test@getLastSql');
// 全局指令事件，如已绑定了insert、update、delete事件，使用对应方法执行指令，会同时触发2种事件
Db::event('execute', 'Test@getLastSql');

// $res = Db::table('test')->select();

// $res = Db::table('test')->insert([
// 	'name'	=> 'w2',
// 	'status'=> '5'
// ]);

// $res = Db::table('test')->insertAll([
// 	['name' => 'q3', 'status' => 4],
// 	['name' => 'q4', 'status' => 3],
// ]);

// $res = Db::table('test')->where('id', 3)->update([
// 	'status' => 9
// ]);

// $res = Db::table('test')->where('id', '4')->delete();

// $res = Db::query('select * from test');

$res = Db::execute("INSERT INTO `test` (`name` , `status`) VALUES ('w3' , '6')");

var_dump($res);
