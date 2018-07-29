<?php
namespace mon\model;

use mon\model\Data;
use mon\model\DataCollection;

/**
 * 自动完成trait
 *
 * @author Mon <985558837@qq.com>
 * @version v1.0
 */
trait AutoComplete
{
	/**
	 * 更新操作自动完成字段配置
	 *
	 * @var array
	 */
	protected $update = [];

	/**
	 * 新增操作自动完成字段配置
	 *
	 * @var array
	 */
	protected $insert = [];

	/**
	 * 查询后字段完成的数据
	 *
	 * @var array
	 */
	protected $append = [];

	/**
     * 保存数据
     *
     * @param  [type] $data     操作数据
     * @param  [type] $where    where条件，存在则为更新，反之新增
     * @param  [type] $sequence 自增序列名, 存在且为新增操作则放回自增ID
     * @param  [type] $query    查询对象实例
     * @return [type]           [description]
     */
    public function save($data, $where = null, $sequence = null, $query = null)
    {
        // 重置操作数据
        $this->data = [];

        if(empty($data)){
            return false;
        }
        // 数据处理
        foreach($data as $key => $value){
            $this->setAttr($key, $value, $data);
        }

        $result = !is_null($where) ? $this->updateData($where, $query) : $this->insertData($sequence, $query);

        return $result;
    }

    /**
     * 获取一条数据
     *
     * @param  where  $where    where条件
     * @param  [type] $db       查询对象实例
     * @return \mon\model\Data  数据集对象
     */
    public function get($where = [], $db = null)
    {
        // 获取DB链接
        if(!$db){
            $db = $this->db();
        }
        $data = $db->where($where)->find();

        return new Data($data, $this, $this->append);
    }

    /**
     * 获取多条数据
     *
     * @param  where  $where    where条件
     * @param  [type] $db       查询对象实例
     * @return \mon\model\Data  数据集对象
     */
    public function all($where = [], $db = null)
    {
        // 获取DB链接
        if(!$db){
            $db = $this->db();
        }
        $data = $db->where($where)->select();
        // 有数据，生成数据集合
        if(count($data) > 0){
            // 遍历转换生成对象集合
            foreach($data as $k => &$v){
                $v = new Data($v, $this, $this->append);
            }
            $data = new DataCollection($data);
        }else{
            $data = new DataCollection($data);
        }

        return $data;
    }

    /**
     * 更新数据
     *
     * @param  [type] $where where条件
     * @param  [type] $db    查询对象实例
     * @return [type]        [description]
     */
    protected function updateData($where, $db = null)
    {
        // 自动完成
        $this->autoCompleteData($this->update);
        // 获取DB链接
        if(!$db){
            $db = $this->db();
        }

        return $db->where($where)->update($this->data);
    }

    /**
     * 新增数据
     *
     * @param  [type] $sequence 自增序列名
     * @param  [type] $sequence 查询对象实例
     * @return [type]           [description]
     */
    protected function insertData($sequence, $db = null)
    {
        // 自动完成
        $this->autoCompleteData($this->insert);
        // 获取DB链接
        if(!$db){
            $db = $this->db();
        }

        $getLastInsID = $sequence ? true : false;
        return $db->insert($this->data, false, $getLastInsID, $sequence);
    }

	/**
	 * 设置器，设置修改操作数据
	 *
	 * @param [type] $name  属性名
	 * @param [type] $value 属性值
	 * @param array  $data  元数据
	 * @return $this
	 */
	public function setAttr($name, $value = null, $data = [])
	{
		// 检测设置器是否存在
		$method = 'set'.$this->parseAttrName($name).'Attr';
		if(method_exists($this, $method))
		{
			$value = $this->$method($value, $data);
		}

		// 重置操作数据
		$this->data[$name] = $value;

		return $this;
	}

	/**
	 * 获取器, 修改获取数据
	 *
	 * @param  [type] $name  属性名
	 * @param  [type] $value 属性值
	 * @param  array  $data  元数据
	 */
	public function getAttr($name, $value = null, $data = [])
	{
		// 检测设置器是否存在
		$method = 'get'.$this->parseAttrName($name).'Attr';
		if(method_exists($this, $method))
		{
			$value = $this->$method($value, $data);
		}

		return $value;
	}

	/**
	 * 数据自动完成
	 *
	 * @param  array  $auto 自动补全的字段
	 * @return [type]       [description]
	 */
	protected function autoCompleteData($auto = [])
	{
		foreach($auto as $field => $value){
            if(is_integer($field)){
                $field = $value;
                $value = null;
            }

            if(!isset($this->data[$field])){
                $default = null;
            } else {
                $default = $this->data[$field];
            }

            $this->setAttr($field, !is_null($value) ? $value : $default);
        }
	}

	/**
     * 检测命名, 转换下划线命名规则为驼峰法命名规则
     *
     * @param  [type] $name 字段名称
     * @return [type]       [description]
     */
    protected function parseAttrName($name)
    {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
        return ucfirst($name);
    }
}