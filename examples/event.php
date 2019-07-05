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

class Test{
    public function insert($option, $query){
        var_dump($query->getLastInsID());
    }
}

Db::setConfig($config);

// 绑定事件
DB::event('select', function($option, $query){
    var_dump($option, $query->getLastSql());
});

Db::event('insert', 'Test@insert');

// Db::event('delete', 'Test@insert');
// Db::event('update', 'Test@insert');


DB::table('mon_notice')->select();

// Db::table('mon_notice')->insert([
//     'name'  => 'tests',
//     'content'  => 'tests',
//     'online_time'  => '0',
//     'offline_time'  => '0',
//     'create_time'  => '0',
//     'update_time'  => '0',
// ]);
