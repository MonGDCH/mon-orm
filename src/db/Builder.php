<?php

namespace mon\orm\db;

use mon\orm\exception\DbException;

/**
 * 查询语句构造器
 * 
 * @author Mon <985558837@qq.com>
 * @version 2.0.0
 */
class Builder
{
    /**
     * DB链接实例
     *
     * @var Connection
     */
    protected $connection = null;

    /**
     * 查询构造器实例
     *
     * @var Query
     */
    protected $query = null;

    /**
     * 数据库表达式
     *
     * @var array
     */
    protected $exp = [
        'eq'            => '=',
        'neq'           => '<>',
        'gt'            => '>',
        'egt'           => '>=',
        'lt'            => '<',
        'elt'           => '<=',
        'notlike'       => 'NOT LIKE',
        'not like'      => 'NOT LIKE',
        'like'          => 'LIKE',
        'in'            => 'IN',
        'notin'         => 'NOT IN',
        'not in'        => 'NOT IN',
        'between'       => 'BETWEEN',
        'not between'   => 'NOT BETWEEN',
        'notbetween'    => 'NOT BETWEEN',
        'exists'        => 'EXISTS',
        'notexists'     => 'NOT EXISTS',
        'not exists'    => 'NOT EXISTS',
        'null'          => 'NULL',
        'notnull'       => 'NOT NULL',
        'not null'      => 'NOT NULL',
    ];

    /**
     * SQL表达式(select)
     *
     * @var string
     */
    protected $selectSql = 'SELECT%DISTINCT%%EXTRA% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT%%LOCK%%COMMENT%';

    /**
     * SQL表达式(insert)
     *
     * @var string
     */
    protected $insertSql = '%INSERT%%EXTRA% INTO %TABLE% (%FIELD%) VALUES (%DATA%) %DUPLICATE%%COMMENT%';

    /**
     * SQL表达式(insertAll)
     *
     * @var string
     */
    protected $insertAllSql = '%INSERT%%EXTRA% INTO %TABLE% (%FIELD%) VALUES %DATA% %DUPLICATE%%COMMENT%';

    /**
     * SQL表达式(update)
     *
     * @var string
     */
    protected $updateSql = 'UPDATE%EXTRA% %TABLE% %JOIN% SET %SET% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * SQL表达式(delete)
     *
     * @var string
     */
    protected $deleteSql = 'DELETE%EXTRA% FROM %TABLE%%USING%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * 构造方法
     *
     * @param Connection $connection    链接实例
     * @param Query $query              查询实例
     */
    public function __construct(Connection $connection, Query $query)
    {
        $this->connection = $connection;
        $this->query = $query;
    }

    /**
     * 生成查询SQL
     *
     * @param array $options 表达式
     * @return string
     */
    public function select(array $options = [])
    {
        $sql = str_replace(
            ['%TABLE%', '%DISTINCT%', '%EXTRA%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($options['table'], $options),
                $this->parseDistinct($options['distinct']),
                $this->parseExtra($options['extra']),
                $this->parseField($options['field'], $options),
                $this->parseJoin($options['join'], $options),
                $this->parseWhere($options['where'], $options),
                $this->parseGroup($options['group']),
                $this->parseHaving($options['having']),
                $this->parseOrder($options['order'], $options),
                $this->parseLimit($options['limit']),
                $this->parseUnion($options['union']),
                $this->parseLock($options['lock']),
                $this->parseComment($options['comment']),
                $this->parseForce($options['force']),
            ],
            $this->selectSql
        );

        return $sql;
    }

    /**
     * 生成insert SQL
     *
     * @param array     $data 数据
     * @param array     $options 表达式
     * @param boolean   $replace 是否replace
     * @return string
     */
    public function insert(array $data, array $options = [], $replace = false)
    {
        // 分析并处理数据
        $data = $this->parseData($data, $options);
        if (empty($data)) {
            return false;
        }
        $fields = array_keys($data);
        $values = array_values($data);

        $sql = str_replace(
            ['%INSERT%', '%TABLE%', '%EXTRA%', '%FIELD%', '%DATA%', '%DUPLICATE%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($options['table'], $options),
                $this->parseExtra($options['extra']),
                implode(' , ', $fields),
                implode(' , ', $values),
                $this->parseDuplicate($options['duplicate']),
                $this->parseComment($options['comment']),
            ],
            $this->insertSql
        );

        return $sql;
    }

    /**
     * 生成insertall SQL
     *
     * @param array     $dataSet 数据集
     * @param array     $options 表达式
     * @param boolean   $replace 是否replace
     * @return string
     */
    public function insertAll($dataSet, $options = [], $replace = false)
    {
        // 获取合法的字段
        $fields = $options['field'];

        foreach ($dataSet as $data) {
            foreach ($data as $key => $val) {
                if (is_array($fields) && !in_array($key, $fields)) {
                    // 过滤掉合法字段外的字段
                    unset($data[$key]);
                } elseif (is_null($val)) {
                    $data[$key] = 'NULL';
                } elseif (is_scalar($val)) {
                    $data[$key] = $this->parseValue($val, $key);
                } else {
                    // 过滤掉非标量数据
                    unset($data[$key]);
                }
            }
            $value    = array_values($data);
            $values[] = '( ' . implode(',', $value) . ' )';

            if (!isset($insertFields)) {
                $insertFields = $this->quoteField(array_keys($data));
            }
        }

        return str_replace(
            ['%INSERT%', '%TABLE%', '%EXTRA%', '%FIELD%', '%DATA%', '%DUPLICATE%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($options['table'], $options),
                $this->parseExtra($options['extra']),
                implode(' , ', $insertFields),
                implode(' , ', $values),
                $this->parseDuplicate($options['duplicate']),
                $this->parseComment($options['comment']),
            ],
            $this->insertAllSql
        );
    }

    /**
     * 生成update SQL
     *
     * @param array     $data 数据
     * @param array     $options 表达式
     * @return string
     */
    public function update($data, $options)
    {
        $data  = $this->parseData($data, $options);
        if (empty($data)) {
            return '';
        }
        foreach ($data as $key => $val) {
            $set[] = $key . '=' . $val;
        }

        $sql = str_replace(
            ['%TABLE%', '%EXTRA%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($options['table'], $options),
                $this->parseExtra($options['extra']),
                implode(',', $set),
                $this->parseJoin($options['join'], $options),
                $this->parseWhere($options['where'], $options),
                $this->parseOrder($options['order'], $options),
                $this->parseLimit($options['limit']),
                $this->parseLock($options['lock']),
                $this->parseComment($options['comment']),
            ],
            $this->updateSql
        );

        return $sql;
    }

    /**
     * 生成delete SQL
     *
     * @param array $options 表达式
     * @return string
     */
    public function delete($options)
    {
        $sql = str_replace(
            ['%TABLE%', '%EXTRA%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($options['table'], $options),
                $this->parseExtra($options['extra']),
                !empty($options['using']) ? ' USING ' . $this->parseTable($options['using'], $options) . ' ' : '',
                $this->parseJoin($options['join'], $options),
                $this->parseWhere($options['where'], $options),
                $this->parseOrder($options['order'], $options),
                $this->parseLimit($options['limit']),
                $this->parseLock($options['lock']),
                $this->parseComment($options['comment']),
            ],
            $this->deleteSql
        );

        return $sql;
    }

    /**
     * 字段和表名处理
     *
     * @param mixed  $key
     * @param array  $options
     * @return string
     */
    protected function parseKey($key, $options = [], $strict = false)
    {
        if (is_numeric($key)) {
            return $key;
        } elseif ($key instanceof Raw) {
            return $key->getValue();
        }

        $key = trim($key);
        // 表字段支持
        if (strpos($key, '.') && !preg_match('/[,\'\"\(\)`\s]/', $key)) {
            list($table, $key) = explode('.', $key, 2);

            if (isset($options['alias'][$table])) {
                $table = $options['alias'][$table];
            }
        }

        if ('*' != $key && ($strict || !preg_match('/[,\'\"\*\(\)`.\s]/', $key))) {
            $key = '`' . $key . '`';
        }
        if (isset($table)) {
            if (strpos($table, '.')) {
                $table = str_replace('.', '`.`', $table);
            }
            $key = '`' . $table . '`.' . $key;
        }
        return $key;
    }

    /**
     * table分析
     *
     * @param mixed $tables
     * @param array $options
     * @return string
     */
    public function parseTable($tables, $options = [])
    {
        $item = [];
        foreach ((array) $tables as $key => $table) {
            if (!is_numeric($key)) {
                $item[] = $this->parseKey($key) . ' ' . (isset($options['alias'][$table]) ? $this->parseKey($options['alias'][$table]) : $this->parseKey($table));
            } else {
                if (isset($options['alias'][$table])) {
                    $item[] = $this->parseKey($table) . ' ' . $this->parseKey($options['alias'][$table]);
                } else {
                    $item[] = $this->parseKey($table);
                }
            }
        }
        return implode(',', $item);
    }

    /**
     * distinct分析
     *
     * @param mixed $distinct
     * @return string
     */
    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * field分析
     *
     * @param mixed     $fields
     * @param array     $options
     * @return string
     */
    protected function parseField($fields, $options = [])
    {
        if ('*' == $fields || empty($fields)) {
            $fieldsStr = '*';
        } elseif (is_array($fields)) {
            // 支持 'field1'=>'field2' 这样的字段别名定义
            $array = [];
            foreach ($fields as $key => $field) {
                if (!is_numeric($key)) {
                    $array[] = $this->parseKey($key, $options) . ' AS ' . $this->parseKey($field, $options, true);
                } else {
                    $array[] = $this->parseKey($field, $options);
                }
            }
            $fieldsStr = implode(',', $array);
        }
        return $fieldsStr;
    }

    /**
     * join分析
     *
     * @param array $join
     * @param array $options 查询条件
     * @return string
     */
    protected function parseJoin($join, $options = [])
    {
        $joinStr = '';
        if (!empty($join)) {
            foreach ($join as $item) {
                list($table, $type, $on) = $item;
                $condition = [];
                foreach ((array) $on as $val) {
                    if (strpos($val, '=')) {
                        list($val1, $val2) = explode('=', $val, 2);
                        $condition[] = $this->parseKey($val1, $options) . '=' . $this->parseKey($val2, $options);
                    } else {
                        $condition[] = $val;
                    }
                }

                $table = $this->parseTable($table, $options);
                $joinStr .= ' ' . $type . ' JOIN ' . $table . ' ON ' . implode(' AND ', $condition);
            }
        }
        return $joinStr;
    }

    /**
     * where分析
     *
     * @param mixed $where   查询条件
     * @param array $options 查询参数
     * @return string
     */
    protected function parseWhere($where, $options)
    {
        $whereStr = $this->buildWhere($where, $options);

        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * 生成查询条件SQL
     *
     * @param mixed $where
     * @param array $options
     * @return string
     */
    protected function buildWhere($where, $options)
    {
        if (empty($where)) {
            $where = [];
        }

        $whereStr = '';
        foreach ($where as $logic => $val) {
            $str = [];
            foreach ($val as $field => $value) {

                if ($value instanceof Raw) {
                    // 表达式查询
                    $str[] = ' ' . $logic . ' ( ' . $value->getValue() . ' )';
                    continue;
                }

                // 二维数组where支持，例：[['a', '=', 'b'], ['b', 'in', ['1', '2']]]
                if (is_array($value) && count($value) == 3) {
                    if (key($value) !== 0) {
                        throw new DbException('where express error:' . var_export($value, true), DbException::PARSE_WHERE_EXPRESS_ERROR);
                    }
                    $field = array_shift($value);
                }

                if (strpos($field, '|')) {
                    // 不同字段使用相同查询条件（OR）
                    $array = explode('|', $field);
                    $item  = [];
                    foreach ($array as $k) {
                        $item[] = $this->parseWhereItem($k, $value, '', $options);
                    }
                    $str[] = ' ' . $logic . ' ( ' . implode(' OR ', $item) . ' )';
                } elseif (strpos($field, '&')) {
                    // 不同字段使用相同查询条件（AND）
                    $array = explode('&', $field);
                    $item  = [];
                    foreach ($array as $k) {
                        $item[] = $this->parseWhereItem($k, $value, '', $options);
                    }
                    $str[] = ' ' . $logic . ' ( ' . implode(' AND ', $item) . ' )';
                } elseif (is_int($field)) {
                    if (is_string($value)) {
                        // 字符串直接写入的where条件
                        $str[] = ' ' . $logic . ' ( ' . $value . ' ) ';
                    } elseif (is_array($value)) {
                        $field = array_shift($value);
                        $value = isset($value[0]) ? (is_null($value[0]) ? ['null', ''] : ['=', $value[0]]) : ['null', ''];
                        $str[] = ' ' . $logic . ' ' . $this->parseWhereItem($field, $value, $logic, $options);
                    }
                } else {
                    // 对字段使用表达式查询
                    $field = is_string($field) ? $field : '';
                    $str[] = ' ' . $logic . ' ' . $this->parseWhereItem($field, $value, $logic, $options);
                }
            }

            $whereStr .= empty($whereStr) ? substr(implode(' ', $str), strlen($logic) + 1) : implode(' ', $str);
        }

        return $whereStr;
    }

    /**
     * where子单元分析
     *
     * @param  mixed  $field    字段
     * @param  mixed  $val      值
     * @param  string $rule     规则
     * @param  mixed  $options  查询参数
     * @param  mixed  $bindName 绑定值
     * @throws DbException
     * @return string
     */
    protected function parseWhereItem($field, $val, $rule = '', $options = [], $bindName = null)
    {
        // 字段分析
        $key = $field ? $this->parseKey($field, $options, true) : '';

        // 查询规则和条件
        if (!is_array($val)) {
            $val = is_null($val) ? ['null', ''] : ['=', $val];
        }
        list($exp, $value) = $val;

        // 对一个字段使用多个查询条件
        if (is_array($exp)) {
            $item = array_pop($val);
            // 传入 or 或者 and
            if (is_string($item) && in_array($item, ['AND', 'and', 'OR', 'or'])) {
                $rule = $item;
            } else {
                array_push($val, $item);
            }
            foreach ($val as $k => $item) {
                $bindName = 'where_' . str_replace('.', '_', $field) . '_' . $k;
                $str[]    = $this->parseWhereItem($field, $item, $rule, $options, $bindName);
            }
            return '( ' . implode(' ' . $rule . ' ', $str) . ' )';
        }

        // 检测操作符
        if (!in_array($exp, $this->exp)) {
            $exp = strtolower($exp);
            if (isset($this->exp[$exp])) {
                $exp = $this->exp[$exp];
            } else {
                throw new DbException('where express error:' . $exp, DbException::PARSE_WHERE_ERROR);
            }
        }
        $bindName = $bindName ?: 'where_' . $rule . '_' . str_replace(['.', '-'], '_', $field);
        if (preg_match('/\W/', $bindName)) {
            // 处理带非单词字符的字段名
            $bindName = md5($bindName);
        }

        if (is_scalar($value) && !in_array($exp, ['EXP', 'NOT NULL', 'NULL', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'])) {
            if (strpos($value, ':') !== 0 || !$this->query->isBind(substr($value, 1))) {
                if ($this->query->isBind($bindName)) {
                    $bindName .= '_' . str_replace('.', '_', uniqid('', true));
                }
                $this->query->bind($bindName, $value);
                $value = ':' . $bindName;
            }
        }

        $whereStr = '';
        if (in_array($exp, ['=', '<>', '>', '>=', '<', '<='])) {
            // 比较运算
            $whereStr .= $key . ' ' . $exp . ' ' . $this->parseValue($value, $field);
        } elseif ('LIKE' == $exp || 'NOT LIKE' == $exp) {
            // 模糊匹配
            if (is_array($value)) {
                foreach ($value as $item) {
                    $array[] = $key . ' ' . $exp . ' ' . $this->parseValue($item, $field);
                }
                $logic = isset($val[2]) ? $val[2] : 'AND';
                $whereStr .= '(' . implode(' ' . strtoupper($logic) . ' ', $array) . ')';
            } else {
                $whereStr .= $key . ' ' . $exp . ' ' . $this->parseValue($value, $field);
            }
        } elseif (in_array($exp, ['NOT NULL', 'NULL'])) {
            // NULL 查询
            $whereStr .= $key . ' IS ' . $exp;
        } elseif (in_array($exp, ['NOT IN', 'IN'])) {
            // IN 查询
            if ($value instanceof Raw) {
                $value = [$value];
            } else {
                $value = array_unique(is_array($value) ? $value : explode(',', $value));
            }
            $zone = implode(',', $this->parseValue($value, $field));

            $whereStr .= $key . ' ' . $exp . ' (' . (empty($zone) ? "''" : $zone) . ')';
        } elseif (in_array($exp, ['NOT BETWEEN', 'BETWEEN'])) {
            // BETWEEN 查询
            $data = is_array($value) ? $value : explode(',', $value);
            $between = $this->parseValue($data[0], $field) . ' AND ' . $this->parseValue($data[1], $field);

            $whereStr .= $key . ' ' . $exp . ' ' . $between;
        } elseif (in_array($exp, ['NOT EXISTS', 'EXISTS'])) {
            $whereStr .= $exp . ' (' . $value . ')';
        }

        return $whereStr;
    }

    /**
     * value分析
     *
     * @param mixed     $value
     * @return string|array
     */
    protected function parseValue($value, $field = '')
    {
        if (is_string($value)) {
            $value = strpos($value, ':') === 0 && $this->query->isBind(substr($value, 1)) ? $value : $this->connection->quote($value);
        } elseif (is_array($value)) {
            $value = array_map([$this, 'parseValue'], $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        } elseif ($value instanceof Raw) {
            $value = $value->getValue();
        }
        return $value;
    }

    /**
     * group分析
     *
     * @param mixed $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY ' . $this->parseKey($group) : '';
    }

    /**
     * having分析
     *
     * @param string $having
     * @return string
     */
    protected function parseHaving($having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * order分析
     *
     * @param mixed $order
     * @param array $options 查询条件
     * @return string
     */
    protected function parseOrder($order, $options = [])
    {
        if (empty($order)) {
            return '';
        }
        $array = [];
        foreach ($order as $key => $val) {
            if ($val instanceof Raw) {
                $array[] = $val->getValue();
            } elseif ('[rand]' == $val) {
                $array[] = $this->parseRand();
            } else {
                if (is_numeric($key)) {
                    list($key, $sort) = explode(' ', strpos($val, ' ') ? $val : $val . ' ');
                } else {
                    $sort = $val;
                }
                $sort = strtoupper($sort);
                $sort = in_array($sort, ['ASC', 'DESC'], true) ? ' ' . $sort : '';
                $array[] = $this->parseKey($key, $options, true) . $sort;
            }
        }

        return ' ORDER BY ' . implode(',', $array);
    }

    /**
     * 随机排序
     *
     * @return string
     */
    protected function parseRand()
    {
        return 'RAND()';
    }

    /**
     * limit分析
     *
     * @param mixed $limit
     * @return string
     */
    protected function parseLimit($limit)
    {
        return (!empty($limit) && false === strpos($limit, '(')) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * union分析
     *
     * @param mixed $union
     * @return string
     */
    protected function parseUnion($union)
    {
        if (empty($union)) {
            return '';
        }
        $type = $union['type'];
        unset($union['type']);
        $sql = [];
        foreach ($union as $u) {
            if (is_string($u)) {
                $sql[] = $type . ' ( ' . $u . ' )';
            }
        }
        return ' ' . implode(' ', $sql);
    }

    /**
     * 设置锁机制
     *
     * @param boolean|string $lock
     * @return string
     */
    protected function parseLock($lock = false)
    {
        if (is_bool($lock)) {
            return $lock ? ' FOR UPDATE ' : '';
        } elseif (is_string($lock)) {
            return ' ' . trim($lock) . ' ';
        }

        return '';
    }

    /**
     * comment分析
     *
     * @param string $comment
     * @return string
     */
    protected function parseComment($comment)
    {
        if (false !== strpos($comment, '*/')) {
            $comment = strstr($comment, '*/', true);
        }
        return !empty($comment) ? ' /* ' . $comment . ' */' : '';
    }

    /**
     * index分析，可在操作链中指定需要强制使用的索引
     *
     * @param mixed $index
     * @return string
     */
    protected function parseForce($index)
    {
        if (empty($index)) {
            return '';
        }

        return sprintf(" FORCE INDEX ( %s ) ", is_array($index) ? implode(',', $index) : $index);
    }

    /**
     * 数据分析
     *
     * @param array $data 数据
     * @param array $options 查询参数
     * @return array
     */
    protected function parseData($data, $options)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $key => $val) {
            $item = $this->parseKey($key, $options, true);

            if (is_null($val)) {
                $result[$item] = 'NULL';
            } elseif (is_array($val) && !empty($val)) {
                switch (strtolower($val[0])) {
                    case 'inc':
                        $result[$item] = $item . ' + ' . floatval($val[1]);
                        break;
                    case 'dec':
                        $result[$item] = $item . ' - ' . floatval($val[1]);
                        break;
                }
            } elseif (is_scalar($val)) {
                // 过滤非标量数据
                if (0 === strpos($val, ':') && $this->query->isBind(substr($val, 1))) {
                    $result[$item] = $val;
                } else {
                    $key = str_replace('.', '_', $key);
                    $this->query->bind('data__' . $key, $val);
                    $result[$item] = ':data__' . $key;
                }
            }
        }
        return $result;
    }

    /**
     * 查询额外参数分析
     *
     * @param  string $extra    额外参数
     * @return string
     */
    protected function parseExtra($extra)
    {
        return preg_match('/^[\w]+$/i', $extra) ? ' ' . strtoupper($extra) : '';
    }

    /**
     * ON DUPLICATE KEY UPDATE 分析
     *
     * @param  mixed  $duplicate
     * @return string
     */
    protected function parseDuplicate($duplicate)
    {
        if ('' == $duplicate) {
            return '';
        }
        $updates = [];
        if (is_string($duplicate)) {
            $updates[] = $duplicate;
        } else {
            foreach ($duplicate as $key => $val) {
                if (is_numeric($key)) {
                    $val = $this->parseKey($val);
                    $updates[] = $val . ' = VALUES(' . $val . ')';
                } else {
                    $updates[] = $this->parseKey($key) . " = " . $this->connection->quote($val);
                }
            }
        }

        return ' ON DUPLICATE KEY UPDATE ' . implode(' , ', $updates) . ' ';
    }

    /**
     * insertAll转义字段名
     *
     * @param array $fields
     * @return array
     */
    protected function quoteField($fields)
    {
        foreach ($fields as &$field) {
            if (!preg_match('/[,\'\"\*\(\)`.\s]/', $field)) {
                $field = '`' . $field . '`';
            }
        }

        return $fields;
    }
}
