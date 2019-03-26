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
	 * @var [type]
	 */
	protected $data;

	/**
	 * 构造方法
	 *
	 * @param [type] $data  结果集
	 * @param Model  $model 绑定的模型
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}

	/**
	 * 获取元数据
	 *
	 * @return [type] [description]
	 */
	public function getData()
	{
		return $this->data;
	}

    /**
     * 是否为空
     *
     * @return boolean [description]
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

	/**
	 * 转换为数组输出, 并自动完成数据
	 *
	 * @return [type] [description]
	 */
	public function toArray()
    {
        return array_map(function ($value) {
            return ($value instanceof Data || $value instanceof self) ? $value->toArray() : $value;
        }, $this->data);
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
     * @return string [description]
     */
    public function __toString()
    {
        return $this->toJson();
    }

    // ArrayAccess相关处理方法
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    //Countable相关处理方法
    public function count()
    {
        return count($this->data);
    }

    //IteratorAggregate相关处理方法
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    //JsonSerializable相关处理方法
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}