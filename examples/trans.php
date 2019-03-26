<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;

$config = [
	'host'	   => '127.0.0.1',
	'database' => 'test',
	'username' => 'root',
	'password' => 'root',
	'prot'	   => 3306
];

Db::setConfig($config);

// 获取库表
// $data = Db::getTables();

// 获取表字段信息
// $data = Db::getFields('test');

// SQL性能测试
// $data = Db::explain( Db::table('lmf_user')->where('id', '1')->debug()->select() );


// 事务嵌套

function a($save = true)
{
	Db::startTrans();

	$insert = Db::table('test')->insert([
		'name'	=> mt_rand(1, 100) . 'a',
		'update_time' => $_SERVER['REQUEST_TIME'],
		'create_time' => $_SERVER['REQUEST_TIME'],
	]);

	if($save){
		Db::commit();
	}else{
		Db::rollBack();
	}

	return $save;
}

function b($save = true)
{
	Db::startTrans();

	$insert = Db::table('test')->insert([
		'name'	=> mt_rand(1, 100) . 'b',
		'update_time' => $_SERVER['REQUEST_TIME'],
		'create_time' => $_SERVER['REQUEST_TIME'],
	]);

	if($save){
		Db::commit();
	}else{
		Db::rollBack();
	}

	return $save;
}

function c(){

	Db::startTrans();

	$insert = Db::table('test')->insert([
		'name'	=> mt_rand(1, 100) . 'c',
		'update_time' => $_SERVER['REQUEST_TIME'],
		'create_time' => $_SERVER['REQUEST_TIME'],
	]);
	var_dump(1);
	var_dump(Db::table('test')->select());

	$a = a();
	if(!$a){
		Db::rollBack();
		return false;
	}
	var_dump(2);
	var_dump(Db::table('test')->select());

	$b = b(true);
	if(!$b){
		Db::rollBack();
		return false;
	}
	var_dump(3);
	var_dump(Db::table('test')->select());

	Db::commit();
	return [$a, $b];
}

function d()
{
	var_dump(4);
	var_dump(Db::table('test')->select());
	Db::startTrans();

	$insert = Db::table('test')->insert([
		'name'	=> mt_rand(1, 100) . 'd',
		'update_time' => $_SERVER['REQUEST_TIME'],
		'create_time' => $_SERVER['REQUEST_TIME'],
	]);

	var_dump(5);
	var_dump(Db::table('test')->select());
	Db::commit();
	var_dump('ddd');
}

$data = c();
d();
var_dump(Db::table('test')->select());

var_dump($data);