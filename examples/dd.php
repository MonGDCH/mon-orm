<?php
require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;
use mon\orm\Model;

date_default_timezone_set('PRC');

$config = [
	// 服务器地址
	'host'        	  => '127.0.0.1',
	// 数据库名
	'database'        => 'invest',
	// 用户名
	'username'        => 'root',
	// 密码
	'password'        => '19930603',
	// 端口
	'port'        	  => '3306',
];

Db::setConfig($config);

// var_dump(Db::table('mon_admin')->debug()->using(['auth_access', 'mon_admin'])->where('mon_admin.id = auth_access.uid')->delete());

// $sql = Db::table('invest_cate')->extra('IGNORE')->debug()->insert(['name' => 'aa']);

// $sql = Db::table('invest_cate')->extra('SQL_BUFFER_RESULT')->debug()->select();

// $sql = Db::table('invest_cate')->extra('IGNORE')->debug()->insertAll([['a' => 1], ['a' => 2]]);

// $sql = Db::table('invest_cate')->extra('IGNORE')->debug()->where('aaa')->update(['a' => 2]);



var_dump($sql);