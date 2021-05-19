<?php

namespace mon\orm\db;

use mon\orm\exception\DbException;

/**
 * 原生表达式定义类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Raw
{
    /**
     * 查询表达式
     *
     * @var string
     */
    protected $value;

    /**
     * 创建一个查询表达式
     *
     * @param  string  $value
     * @throws DbException
     * @return void
     */
    public function __construct($value)
    {
        if (!is_string($value)) {
            throw new DbException('Raw Expression not string', DbException::RAW_EXPRESSION_FAILD);
        }
        $this->value = $value;
    }

    /**
     * 获取表达式
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * 字符串输出
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
