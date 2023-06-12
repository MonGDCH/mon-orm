<?php

declare(strict_types=1);

namespace mon\orm;

use support\Plugin;

/**
 * Gaia框架安装驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Install
{
    /**
     * 标志为Gaia的驱动
     */
    const GAIA_PLUGIN = true;

    /**
     * 移动的文件
     *
     * @var array
     */
    protected static $file_relation = [
        'gaia/database.php' => 'config/database.php',
    ];

    /**
     * 安装
     *
     * @return void
     */
    public static function install()
    {
        // 创建框架文件
        $source_path = __DIR__ . DIRECTORY_SEPARATOR;
        // 移动文件
        foreach (static::$file_relation as $source => $dest) {
            $sourceFile = $source_path . $source;
            Plugin::copyFile($sourceFile, $dest);
        }
    }

    /**
     * 更新升级
     *
     * @return void
     */
    public static function update()
    {
    }

    /**
     * 卸载
     *
     * @return void
     */
    public static function uninstall()
    {
    }

    /**
     * Gaia发布
     *
     * @return void
     */
    public static function publish()
    {
        // 创建框架文件
        $source_path = __DIR__ . DIRECTORY_SEPARATOR;
        // 移动文件
        foreach (static::$file_relation as $source => $dest) {
            $sourceFile = $source_path . $source;
            Plugin::copyFile($sourceFile, $dest, true);
        }
    }
}
