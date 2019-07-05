<?php

namespace mon\orm\model;

use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use mon\orm\Model;
use mon\orm\model\DataCollection;

/**
 * 模型数据结果集(一维数组)
 *
 * @author Mon <985558837@qq.com>
 * @version v1.0
 */
class Data implements JsonSerializable, ArrayAccess, Countable, IteratorAggregate
{
    /**
     * 元数据
     *
     * @var array
     */
    protected $data;

    /**
     * 绑定的模型
     *
     * @var [type]
     */
    protected $model;

    /**
     * 补充的数据字段
     *
     * @var [type]
     */
    protected $append;

    /**
     * 处理后的数据
     *
     * @var [type]
     */
    protected $formatData;

    /**
     * 构造方法
     *
     * @param [type] $data  结果集
     * @param Model  $model 绑定的模型
     */
    public function __construct($data, Model $model, $append = [])
    {
        $this->data = $data;
        $this->model = $model;
        $this->append = $append;
    }

    /**
     * 获取元数据
     *
     * @param [type] $name 字段名
     * @return void
     */
    public function getData($name = null)
    {
        if (!is_null($name)) {
            return isset($this->data[$name]) ? $this->data[$name] : null;
        }
        return $this->data;
    }

    /**
     * 获取绑定的模型
     *
     * @return [type] [description]
     */
    public function getModel()
    {
        return $this->model;
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
     * @param  boolean $new true则重新获取数据，不读取缓存
     * @return [type]       [description]
     */
    public function toArray($new = true)
    {
        if ($new || !$this->formatData) {
            // 转换数据
            foreach ($this->data as $key => $value) {
                if ($value instanceof self || $value instanceof DataCollection) {
                    $this->formatData[$key] = $value->toArray();
                } else {
                    $this->formatData[$key] = $this->model->getAttr($key, $value, $this->data);
                }
            }

            // 存在附加字段
            if (!empty($this->append)) {
                foreach ($this->append as $field => $val) {
                    if (is_integer($field)) {
                        $field = $val;
                        $val = null;
                    }
                    $this->formatData[$field] = $this->model->getAttr($field, $val, $this->data);
                }
            }
        }

        return $this->formatData;
    }

    /**
     * 转换为json数据
     *
     * @param integer $options json参数
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * 修改器,设置数据对象的值
     *
     * @param string $name  名称
     * @param mixed  $value 值
     * @return void
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
        if ($this->formatData) {
            $this->formatData[$name] = $value;
        }
    }

    /**
     * 获取器,获取数据对象的值
     *
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name)
    {
        return $this->model->getAttr($name, $this->getData($name), $this->data);
    }

    /**
     * 检测数据对象的值
     *
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
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

    /**
     * 销毁数据对象的值
     *
     * @param string $name 名称
     * @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name], $this->formatData[$name]);
    }

    // ArrayAccess相关处理方法
    public function offsetSet($name, $value)
    {
        $this->__set($name, $value);
    }

    public function offsetExists($name)
    {
        return $this->__isset($name);
    }

    public function offsetUnset($name)
    {
        $this->__unset($name);
    }

    public function offsetGet($name)
    {
        return $this->model->getAttr($name, $this->getData($name), $this->data);
    }

    // JsonSerializable相关处理方法
    public function jsonSerialize()
    {
        return $this->toArray();
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
}
