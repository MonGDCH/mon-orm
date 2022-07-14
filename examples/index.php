<?php

require __DIR__ . '/../vendor/autoload.php';

use mon\orm\Db;
use mon\orm\exception\DbException;
use mon\orm\Model;

$config = require('config.php');
Db::setConfig($config);
// Db::reconnect(true);

class App extends Model
{
    protected $table = 'gh_housing';

    public function test1()
    {
        $data = $this->where('id', 1)->field('city')->find();
        debug($data);
    }

    public function test2()
    {
        $save = $this->save([
            'Usage' => 1,
            'Currency' => 333,
            'Symbol' => 'xxx',
            'Order' => 'vvv',
        ]);
        debug($save);
    }

    public function test3()
    {
        $this->startTrans();
        try {
            $data = $this->where('id', 1)->find();
            debug($data);
            unset($data['ID']);
            $save = $this->save($data);
            debug($save);
            $data2 = $this->order('id', 'desc')->limit(1)->find();
            debug($data2);
            $save2 = $this->where('id', 2)->update(['State' => 3]);
            var_dump($save2);

            $this->commit();
        } catch (DbException $e) {
            debug($e->getMessage());
        }
    }

    public function test4()
    {
        $data = $this->where('id', 1)->find();
        unset($data['ID']);
        $save = $this->save($data);
        debug($data);
    }

    public function test5()
    {
        // $data = $this->execute("OPTIMIZE TABLE `{$this->table}`");
        $data = $this->execute("REPAIR TABLE `{$this->table}`");
        debug($data);
    }
}

// (new App)->test1();
(new App)->test5();
