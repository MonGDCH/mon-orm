<?php
require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Model;


// 模型用法
class User extends Model
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

	public function demo3()
	{
		return $this->debug()->sum('id');
	}

	public function inc()
	{
		return $this->where('id', 1)->setInc('status', 1);
	}

	public function  incs()
	{
		return $this->where('id', 2)->inc('status, admin', 1)->update();
	}
}

$user =  new User;
$demo2 = $user->demo2();
$demo3 = $user->demo3();
var_dump($demo2, $demo3);

// 静态调用
$data2 = User::select();
var_dump($data2);

$data3 = User::sum('status');
var_dump($data3);
// $setInc = $user->incs();
// var_dump($setInc);

