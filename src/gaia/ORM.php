<?php

declare(strict_types=1);

namespace mon\orm\gaia;

use mon\orm\Db;
use mon\log\Logger;
use mon\env\Config;
use Workerman\Timer;

/**
 * 辅助Gaia的ORM工具
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ORM
{
    /**
     * U注册ORM使用
     *
     * @param boolean $reconnect    ORM是否开启断线重连
     * @param integer $timer        开启断线重连后定时器轮询，0则不使用，大于0则定时执行 $querySql
     * @param string $querySql      定时执行的SQL
     * @return void
     */
    public static function register(bool $reconnect = false, int $timer = 55, string $querySql = 'SELECT 1'): void
    {
        // 定义配置
        $config = Config::instance()->get('database', []);
        Db::setConfig($config);
        // 绑定事件
        Db::listen('connect', function ($dbConnect, $dbConfig) {
            // 连接数据库
            $log = "connect database => mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            Logger::instance()->channel()->log('sql', $log);
        });
        Db::listen('query', function ($dbConnect, $dbConfig) use ($querySql) {
            // SQL查询
            $sql = $dbConnect->getLastSql();
            if ($sql != $querySql) {
                Logger::instance()->channel()->log('sql', $sql);
            }
        });
        Db::listen('execute', function ($dbConnect, $dbConfig) {
            // SQL执行
            Logger::instance()->channel()->log('sql', $dbConnect->getLastSql());
        });
        // 打开长链接
        Db::reconnect($reconnect);
        if ($reconnect && $timer > 0) {
            // 轮询查询一次，确保不断开
            Timer::add($timer, function () use ($config, $querySql) {
                foreach ($config as $key => $value) {
                    Db::connect($key)->query($querySql);
                }
            });
        }
    }
}
