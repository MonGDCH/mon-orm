<?php
require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;
use mon\orm\db\Raw;
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

Db::setConfig($config);

class Test extends Model
{
	use Instance;

	protected $table = 'invest_cate';

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

	public function demo()
	{
		$check = $this->validate()->check();
	}
}

// $data = Test::instance()->allowField(['status'])->save(['name' => '111', 'status' => 2], ['id' => 1]);
$map = [
	['a', '=', 1],
	['b', null],
	['c',  new Raw('abc')],
	['d', '<>', new Raw('111')],
	['e|f', '<>', new Raw('222')],
	new Raw('g > 4'),
	['x'],
	['y', 1]
];
// $data = Test::instance()->where($map)->where('h', 'i')->where('s', '<>', 'k')->orderRand()->debug()->select();
// $data = Test::instance()->where($map)->where('h', 'i')->where('s', '<>', 'k')->order(new Raw('abc'))->field(new Raw('CONCAT(a, "、") AS aa'))->debug()->setInc('aa,vv', 1);

// $data = Db::table(new Raw('(select * from a) AS a'))->where('a', 1)->debug()->find();

$data = Test::instance()->alias('b')->join(new Raw('(select * from we_user) AS a'), 'a.uid = b.id')->debug()->select();

debug($data);


// $raw = new Raw('aaaaa');
// echo $raw;
