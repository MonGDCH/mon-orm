<?php

use mon\Db;
use mon\Model;

require __DIR__ . '/../vendor/autoload.php';

/*
// DB直调

$config['database'] = 'test';
$config['username'] = 'root';
$config['password'] = 'root';


$data1 = Db::connect($config)->table('lmf_user')->select();

Db::setConfig($config);
$data2 = Db::table('lmf_user a')->join('blog_user b', 'a.id=b.id', 'left')->select();
$sql = Db::getLastSql();



var_dump($data1, $data2, $sql);

*/

// 模型用法
class Test extends Model
{
	/**
	 * 模型默认操作表名
	 * @var string
	 */
	public $table = 'blog_user';

	public $config = [
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

	public function demo()
	{
		return $this->table('lmf_user')->where('id', 1)->select();
	}

	public function demo2()
	{
		return $this->debug()->find();
	}

	public function inc()
	{
		return $this->where('id', 1)->setInc('status', 1);
	}
}

// $data1 = Test::where(['id' => 2])->find();
// var_dump($data1);

$test =  new Test;

// $inc = $test->inc();

// var_dump($inc);


// $data2 = $test->demo();
$data3 = $test->demo2();
var_dump($data3);

// var_dump($data2, $data3);