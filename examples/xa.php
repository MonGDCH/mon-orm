<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;
use mon\orm\Model;
use mon\util\Instance;

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

$config2 = [
	// 服务器地址
	'host'        	  => '127.0.0.1',
	// 数据库名
	'database'        => 'demo',
	// 用户名
	'username'        => 'root',
	// 密码
	'password'        => 'root',
	// 端口
	'port'        	  => '3306',
];

Db::setConfig($config);

// try {
// 	$data = Db::action(function () {
// 		$save1 = Db::table('we_user')->where(['uid' => 2])->update(['mobile' => '888888']);
// 		if (!$save2) {
// 			throw new Exception('update we_user faild uid => 2');
// 		}
// 		$save2 = Db::table('we_user')->where(['uid' => 1])->update(['mobile' => '68686868']);
// 		if (!$save2) {
// 			throw new Exception('update we_user faild uid => 1');
// 		}

// 		return true;
// 	});

// 	debug($data);
// } catch (Exception $e) {
// 	echo "faild: " . $e->getMessage();
// }


// try {
// 	$data = Db::actionXA(function () use ($config2) {
// 		$save2 = Db::table('we_user')->where(['uid' => 1])->update(['mobile' => '88']);

// 		if (!$save2) {
// 			throw new Exception('update we_user faild uid => 1');
// 		}

// 		$save = Db::connect($config2)->table('chat_visitor')->where('id', 1)->update(['username' => '789456']);
// 		throw new Exception('12345');
// 	}, [Db::connect(), Db::connect($config2)]);
// } catch (Exception $e) {
// 	echo $e->getMessage();

// 	debug($e->getFile());
// 	debug($e->getLine());
// }

class A extends Model
{
	use Instance;

	protected $table = 'we_user';
}

class B extends Model
{
	use Instance;

	protected $table = 'chat_visitor';

	protected $config = [
		// 服务器地址
		'host'        	  => '127.0.0.1',
		// 数据库名
		'database'        => 'demo',
		// 用户名
		'username'        => 'root',
		// 密码
		'password'        => 'root',
		// 端口
		'port'        	  => '3306',
	];
}



$data = B::instance()->actionXA(function(){
	A::instance()->save(['mobile' => 33], ['uid' => 1]);
	B::instance()->save(['username' => 33], ['id' => 1]);
	// var_dump(1);
	// exit;
	// throw new Exception('12345');
	return 1;
}, [A::instance(), B::instance()]);

var_dump($data);

// (new A())->startTransXA('12345aa');

// debug(method_exists(new A(), 'startTransXA'));


// debug(new A() instanceof Model);