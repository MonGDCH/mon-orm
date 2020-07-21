<?php
require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;
use mon\orm\Model;

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

Db::setConfig($config);

class Test extends Model
{
	protected $table = 'mon_admin';

	protected $readonly = ['username'];

	/**
	 * 查询场景传参
	 *
	 * @param  [type]  $query  [description]
	 * @param  integer $id     [description]
	 * @param  integer $status [description]
	 * @return [type]          [description]
	 */
	protected function scopeArgs($query, $id = 1, $status = 0)
	{
		return $query->where('id', $id)->where('status', $status);
	}
}

$test = new Test();

try {
	// scope参数传递
	// $data = $test->scope('args', 51)->select();
	// $data = $test->scope('args', 1, 1)->select();

	// scope不存在，抛出异常
	// $data = $test->scope('argss', 60, 1)->select();

	$info = [
		'username'		=> 'bb',
		'password'		=> md5(123456),
		'salt'			=> 'aabb',
		'create_time'	=> '123456789',
		'update_time'	=> '123456789',
		'asdf'			=> 1,
		'sdfgg'			=> 2,
	];
	// 设置过滤字段
	// $save = $test->allowField(['username', 'password', 'salt', 'create_time', 'update_time'])->save($info);

	// 字段只读，无法更新
	$save = $test->save(['username' => 'abc'], ['id' => 3]);

	var_dump($save, $test->getLastSql());
	// var_dump($data, $test->getLastSql());
} catch (\mon\orm\exception\MondbException $e) {
	var_dump($e->getMessage());
	var_dump($e->getCode());
}
