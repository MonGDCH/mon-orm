<?php
require __DIR__ . '/../vendor/autoload.php';

use mon\Model;
date_default_timezone_set('PRC');

class Test extends Model
{
	/**
	 * 操作表
	 * @var string
	 */
	public $table = 'test';

	/**
	 * db配置
	 * @var [type]
	 */
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

	/**
	 * 新增自动写入字段
	 * @var [type]
	 */
	protected $insert = [
		'create_time'	=> '',
		'update_time'	=> '',
		'status'	=> 1,
	];

	/**
	 * 更新自动写入字段
	 * @var [type]
	 */
	protected $update = [
		'update_time'
	];

	/**
	 * 自动补全查询数据
	 * @var array
	 */
	protected $append = [
		'count',
		'age'	=> 18,
	];

	/**
	 * 自动完成create_time字段
	 * 
	 * @param [type] $val 默认值
	 * @param array  $row 列值
	 */
	protected function setCreateTimeAttr($val, $row = []){
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * 自动完成update_time字段
	 * 
	 * @param [type] $val 默认值
	 * @param array  $row 列值
	 */
	protected function setUpdateTimeAttr($val, $row = []){
		return $_SERVER['REQUEST_TIME'];
	}

	/**
	 * 自动完成格式化获取create_time结果
	 *
	 * @param  [type] $val [description]
	 * @param  array  $row [description]
	 * @return [type]      [description]
	 */
	protected function getCreateTimeAttr($val, $row){
		return date('Y-m-d H:i:s', $val);
	}

	/**
	 * 自动完成格式化append中count字段的数据
	 * @param  [type] $val [description]
	 * @param  [type] $row [description]
	 * @return [type]      [description]
	 */
	protected function getcountAttr($val, $row)
	{
		return count($row);
	}

	/**
	 * 自动完成格式化补全test字段的数据
	 * @param  [type] $val [description]
	 * @param  [type] $row [description]
	 * @return [type]      [description]
	 */
	protected function getTestAttr($val, $row){
		var_dump($val, $row);
		return 'test';
	}

	/**
	 * 测试查询场景
	 *
	 * @return [type] [description]
	 */
	protected function scopeTest($query)
	{
		return $query->where('status', 1)->limit(3);
	}

	/**
	 * 测试sava方法
	 *
	 * @return [type] [description]
	 */
	public function testScopee()
	{
		return $this->scope(function($query){
			return  $query->where('id', '>', 50);
		})->select();
	}
}

$test = new Test;

// 新增
// $data = $test->save(['name' => mt_rand(1, 100), 'update_time' => 123456]);
// $data = $test->save(['name' => 'get insert id'], null, 'id');

// 修改
// $data  = $test->save(['name' => 'hello complete'], ['id' => 45]);

// 场景使用，相当于前置查询
// $data = $test->scope('test')->save(['name'=>'test scope'], []);
// $data = $test->testScopee();

// 查询
// $data =  $test->get(['id' => ['>', 52]]);
$data = $test->scope('test')->get();

// $data = $test->all(['status' => 1]);
// $data = $test->scope('test')->all();

var_dump($data->toArray());
$data->test = 123;
var_dump($data->getdata(), $data->toArray());


// var_dump($data, $test->getLastSql());

// var_dump($data->isEmpty(), $data->toArray(), $test->getLastSql());

