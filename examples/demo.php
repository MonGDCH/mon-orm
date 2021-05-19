<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;
use mon\orm\Model;

$config = require('config.php');

Db::setConfig($config);

class A extends Model
{
	protected $table = 'chat_app';
}

class B extends Model
{
	protected $table = 'mon_config';
	protected $connection = 'test';
}

class C extends Model
{
	protected $config = [
		'host' => 'louis.db',
		'database' => 'new_sysccd',
		'username' => 'monmon',
		'password' => 'monmon',
		'params'          => [
			PDO::ATTR_ERRMODE           => PDO::ERRMODE_SILENT,
			PDO::ATTR_EMULATE_PREPARES  => true,
		]
	];
}

$data = (new A)->select();
debug($data);


$data2 = (new B)->select();
debug($data2);

$data3 = (new C)->query('call data_bridge("B21033115DtxM")');
debug($data3);


// $config2 = [
// 	'host' => 'louis.db',
// 	'database' => 'new_sysccd',
// 	'username' => 'monmon',
// 	'password' => 'monmon',
// 	'params'          => [
// 		PDO::ATTR_ERRMODE           => PDO::ERRMODE_SILENT,
// 		PDO::ATTR_EMULATE_PREPARES  => true,
// 	],
// ];

// $data2 = Db::connect($config2)->query('call data_bridge("B21033115DtxM")');

// debug($data2);

// $data3 = Db::connect('test')->table('cz_comment')->select();
// debug($data3);

// $data = Db::table('chat_stay')->select();
// debug($data);
// Db::setConfig($config);

// $sql = 'call data_bridge("B21033115DtxM")';

// $data = Db::query($sql);

// debug($data);
