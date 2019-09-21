<?php
require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Model;

date_default_timezone_set('PRC');

class Test extends Model
{
    protected $table = "mon_notice";

    protected $config = [
        // 服务器地址
        'host'            => '127.0.0.1',
        // 数据库名
        'database'        => 'test',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => 'root',
        // 端口
        'port'            => '3306',
    ];

    public function addAll()
    {
        $data = [
            [
                'name'          => 't1',
                'content'       => 't1-content',
                'online_time'   => 123,
                'offline_time'  => 456,
                'create_time'   => 789,
                'update_time'   => 147,
                'author'        => 1
            ],
            [
                'name'          => 't2',
                'content'       => 't2-content',
                'online_time'   => 123,
                'offline_time'  => 456,
                'create_time'   => 789,
                'update_time'   => 147,
                'author'        => 1
            ],
        ];


        $save = $this->insertAll($data);
        return $save;
    }

    public function as()
    {
        $data = $this->alias(['mon_notice' => 'c', 'mon_admin' => 'a', 'mon_auth_rule' => 'b'])
            ->field('c.name, c.author, a.username')
            ->join('mon_admin', 'c.author=a.id')
            ->select();
        return $data;
    }

    public function un()
    {
        $data = $this->field('name AS n, author AS id')->union('select id, username AS n from mon_admin')->select();
        return $data;
    }
}

$t = new Test;

// var_dump($t->addAll(), $t->getLastSql());


// var_dump($t->as(), $t->getLastSql());


var_dump($t->un(), $t->getLastSql());
