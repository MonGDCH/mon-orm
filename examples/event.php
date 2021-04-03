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
	'password'        => 'root',
	// 端口
	'port'        	  => '3306',
];

class Test
{
	// 获取最后写入的ID
	public function getLastId($connect, $option)
	{
		var_dump($connect->getLastInsID());
	}

	// 获取最后执行的SQL
	public function handler($connect, $option)
	{
		// var_dump($option);
		var_dump($connect->getLastSql());
	}
}

Db::setConfig($config);

// 绑定事件
// 链接DB事件
Db::listen('connect', function ($connect, $query) {
	// debug($connect->getLastSql());
	// debug($query);
});
// Db::listen('select', Test::class);
// Db::listen('insert', Test::class);
// Db::listen('delete', Test::class);
// Db::listen('update', Test::class);

// // 全局查询事件，如已绑定了select事件，使用select方法进行查询，会同时触发2种事件
// Db::listen('query', Test::class);
// // 全局指令事件，如已绑定了insert、update、delete事件，使用对应方法执行指令，会同时触发2种事件
// Db::listen('execute', Test::class);

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

$res = Db::table('addons_ads')->where('id', '4')->find();

// $res = Db::query('select * from test');

// $res = Db::execute("INSERT INTO `test` (`name` , `status`) VALUES ('w3' , '6')");

var_dump($res);
