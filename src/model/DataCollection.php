<?php

namespace mon\orm\model;

use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use mon\orm\model\Data;

/**
 * 数据结果集合(多维数组)
 *
 * @author Mon <985558837@qq.com>
 * @version v1.0
 */
class DataCollection implements JsonSerializable, ArrayAccess, Countable, IteratorAggregate
{
    /**
     * 元数据
     *
     * @var array
     */
    protected $data;

    /**
     * 构造方法
     *
     * @param mixed  $data  结果集
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * 获取元数据
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 是否为空
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * 转换为数组输出, 并自动完成数据
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return ($value instanceof Data || $value instanceof self) ? $value->toArray() : $value;
        }, (array) $this->data);
    }

    /**
     * 转换当前数据集为JSON字符串
     *
     * @param integer $options json参数
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * 字符串输出
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * ArrayAccess相关处理方法, 判断是否存在某个值
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, (array) $this->data);
    }

    /**
     * ArrayAccess相关处理方法, 获取某个值
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * ArrayAccess相关处理方法, 设置某个值
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * ArrayAccess相关处理方法, 删除某个值
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Countable相关处理方法，获取计数长度
     *
     * @return integer
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * IteratorAggregate相关处理方法, 迭代器
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * JsonSerializable相关处理方法，转换json数据
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
