<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;

$config = [
	'database' => 'test',
	'username' => 'root',
	'password' => 'root',
];

// 通过connect方法连接DB操作DB
$data1 = Db::connect($config)->table('lmf_user')->select();

// 通过setConfig设置全局默认DB配置操作DB
Db::setConfig($config);
$data2 = Db::table('lmf_user a')->join('blog_user b', 'a.id=b.id', 'left')->select();
$sql = Db::getLastSql();

var_dump($data1, $data2, $sql);
