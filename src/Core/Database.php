<?php

namespace DatabaseJson\Core;

use DatabaseJson\Core\Helpers\Config;
use DatabaseJson\Core\Helpers\Data;
use DatabaseJson\Core\Helpers\Exception;
use DatabaseJson\Core\Helpers\Validate;

class Database implements \IteratorAggregate, \Countable
{

    /**
     * Contain returned data from file as object or array of objects
     * @var mixed Data from table
     */
    protected $data;

    /**
     * Name of file (table)
     * @var string Name of table
     */
    protected $name;

    /**
     * Object with setted data
     * @var object Setted data
     */
    protected $set;

    /**
     * ID of current row if setted
     * @var integer Current ID
     */
    protected $currentId;

    /**
     * Key if current row if setted
     * @var integer Current key
     */
    protected $currentKey;

    /**
     * Pending functions with values
     * @see Core_Database::setPending()
     * @var array
     */
    protected $pending;

    /**
     * Information about to reset keys in array or not to
     * @var integer
     */
    protected $resetKeys = 1;

    /**
     * Factory pattern
     * @param string $name Name of table
     * @return self
     * @throws Exception If there's problems with load file
     */
    public static function table($name)
    {
        Validate::table($name)->exists();

        $self = new self();
        $self->name = $name;

        $self->setFields();
        $self->setPending();

        return $self;
    }

    /**
     * Get rows from table
     * @uses Data::get() to get data from file
     * @return array
     */
    protected function getData()
    {
        return Data::table($this->name)->get();
    }

    /**
     * Setting data to Database::$data
     */
    protected function setData()
    {
        $this->data = $this->getData();
    }

    /**
     * Returns array key of row with specified ID
     * @param integer $id Row ID
     * @return integer Row key
     * @throws Exception If there's no data with that ID
     */
    protected function getRowKey($id)
    {
        foreach ($this->getData() as $key => $data) {
            if ($data->id == $id) {
                return $key;
                break;
            }
        }
        throw new Exception('No data found with ID: ' . $id);
    }

    /**
     * Set NULL for currentId and currentKey
     */
    protected function clearKeyInfo()
    {
        $this->currentId = $this->currentKey = null;
    }

    /**
     * Setting fields with default values
     * @uses Validate::isNumeric() to check if type of field is numeric
     */
    protected function setFields()
    {
        $this->set = new \stdClass();
        $schema = $this->schema();

        foreach ($schema as $field => $type) {
            if (Validate::isNumeric($type) && $field != 'id') {
                $this->setField($field, 0);
            } else {
                $this->setField($field, null);
            }
        }
    }

    /**
     * Set pending functions in right order with default values (Empty).
     */
    protected function setPending()
    {
        $this->pending = array(
            'where' => array(),
            'orderBy' => array(),
            'limit' => array(),
            'with' => array(),
            'groupBy' => array(),
        );
    }

    /**
     * Clear info about previous queries
     */
    protected function clearQuery()
    {
        $this->setPending();
        $this->clearKeyInfo();
    }

    /**
     * Validating array and setting variables to current operations
     *
     * @uses Database::setField() to set field value
     * @param array $data key value pair
     * @throws Exception
     */
    public function set(array $data)
    {
        foreach ($data as $name => $value) {
            $this->setField($name, $value);
        }
    }

    /**
     * Validating array and setting variables to current operations
     *
     * @uses Database::setField() to set field value
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function __set($name, $value)
    {
        $this->setField($name, $value);
    }

    /**
     * Validating fields and setting variables to current operations
     *
     * @uses Validate::field() to check that field exist
     * @uses Validate::type() to check that field type is correct
     * @param string $name  Field name
     * @param mixed  $value Field value
     * @return self
     * @throws Exception
     */
    public function setField($name, $value)
    {
        if (Validate::table($this->name)->field($name) && Validate::table($this->name)->type($name, $value)) {
            $this->set->{$name} = is_string($value) && false === mb_check_encoding($value, 'UTF-8')
            ? utf8_encode($value)
            : $value;
        }

        return $this;
    }

    /**
     * Returning variable from Object
     * @param string $name Field name
     * @return mixed Field value
     * @throws Exception
     */
    public function getField($name)
    {
        if ($this->issetField($name)) {
            return $this->set->{$name};
        }

        throw new Exception('There is no data');
    }

    /**
     * Check if the given field exists
     * @param string $name Field name
     * @return boolean True if the field exists, false otherwise
     */
    public function issetField($name)
    {
        return property_exists($this->set, $name);
    }

    /**
     * Returning variable from Object
     * @param string $name Field name
     * @return mixed Field value
     * @throws Exception
     */
    public function __get($name)
    {
        return $this->getField($name);
    }

    /**
     * Check if the given field exists
     * @param string $name Field name
     * @return boolean True if the field exists, false otherwise
     */
    public function __isset($name)
    {
        return $this->issetField($name);
    }

    /**
     * Execute pending functions
     */
    protected function pending()
    {
        $this->setData();
        foreach ($this->pending as $func => $args) {
            if (!empty($args)) {
                call_user_func(array($this, $func . 'Pending'));
            }
        }

        //clear pending values after executed query
        $this->clearQuery();
    }

    /**
     * Creating new table
     *
     * For example few fields:
     *
     * Database::create('news', array(
     *  'title' => 'string',
     *  'content' => 'string',
     *  'rating' => 'double',
     *  'author' => 'integer'
     * ));
     *
     * Types of field:
     * - boolean
     * - integer
     * - string
     * - double (also for float type)
     *
     * ID field isn't required (it will be created automatically) but you can specify it at first place.
     *
     * @uses Data::arrToLower() to lower case keys and values of array
     * @uses Data::exists() to check if data file exists
     * @uses Config::exists() to check if config file exists
     * @uses Validate::types() to check if type of fields are correct
     * @uses Data::put() to save data file
     * @uses Config::put() to save config file
     * @param string $name Table name
     * @param array $fields Field configuration
     * @throws Exception If table exist
     */
    public static function create($name, array $fields)
    {
        $fields = Validate::arrToLower($fields);

        if (Data::table($name)->exists() && Config::table($name)->exists()) {
            throw new Exception('Table "' . $name . '" already exists');
        }

        $types = array_values($fields);

        Validate::types($types);

        if (!array_key_exists('id', $fields)) {
            $fields = array('id' => 'integer') + $fields;
        }

        $data = new \stdClass();
        $data->last_id = 0;
        $data->schema = $fields;
        $data->relations = new \stdClass();

        Data::table($name)->put(array());
        Config::table($name)->put($data);
    }

    /**
     * Removing table with config
     * @uses Data::remove() to remove data file
     * @uses Config::remove() to remove config file
     * @param string $name Table name
     * @return boolean|Exception
     */
    public static function remove($name)
    {
        if (Data::table($name)->remove() && Config::table($name)->remove()) {
            return true;
        }

        return false;
    }

    /**
     * Grouping results by one field
     * @param string $column
     * @return self
     */
    public function groupBy($column)
    {
        if (Validate::table($this->name)->field($column)) {
            $this->resetKeys = 0;
            $this->pending[__FUNCTION__] = $column;
        }

        return $this;
    }

    /**
     * Grouping array pending method
     */
    protected function groupByPending()
    {
        $column = $this->pending['groupBy'];

        $grouped = array();
        foreach ($this->data as $object) {
            $grouped[$object->{$column}][] = $object;
        }

        $this->data = $grouped;
    }

    /**
     * JOIN other tables
     * @param string $table relations separated by :
     * @return self
     */
    public function with($table)
    {
        $this->pending['with'][] = explode(':', $table);
        return $this;
    }

    /**
     * Pending function for with(), joining other tables to current
     */
    protected function withPending()
    {
        $joins = $this->pending['with'];
        foreach ($joins as $join) {
            $local = (count($join) > 1) ? array_slice($join, -2, 1)[0] : $this->name;
            $foreign = end($join);

            $relation = Relation::table($local)->with($foreign);

            $data = $this->data;

            foreach ($join as $part) {
                $data = $relation->build($data, $part);
            }
        }
    }

    /**
     * Sorting data by field
     * @param string $key Field name
     * @param string $direction ASC|DESC
     * @return self
     */
    public function orderBy($key, $direction = 'ASC')
    {
        if (Validate::table($this->name)->field($key)) {
            $directions = array(
                'ASC' => SORT_ASC,
                'DESC' => SORT_DESC,
            );
            $this->pending[__FUNCTION__][$key] = isset($directions[$direction]) ? $directions[$direction] : 'ASC';
        }

        return $this;
    }

    /**
     * Sort an array of objects by more than one field.
     * @
     * @link http://blog.amnuts.com/2011/04/08/sorting-an-array-of-objects-by-one-or-more-object-property/ It's not mine algorithm
     */
    protected function orderByPending()
    {
        $properties = $this->pending['orderBy'];
        uasort($this->data, function ($a, $b) use ($properties) {
            foreach ($properties as $column => $direction) {
                if (is_int($column)) {
                    $column = $direction;
                    $direction = SORT_ASC;
                }
                $collapse = function ($node, $props) {
                    if (is_array($props)) {
                        foreach ($props as $prop) {
                            $node = (!isset($node->$prop)) ? null : $node->$prop;
                        }
                        return $node;
                    } else {
                        return (!isset($node->$props)) ? null : $node->$props;
                    }
                };
                $aProp = $collapse($a, $column);
                $bProp = $collapse($b, $column);

                if ($aProp != $bProp) {
                    return ($direction == SORT_ASC) ? strnatcasecmp($aProp, $bProp) : strnatcasecmp($bProp, $aProp);
                }
            }
            return false;
        });
    }

    /**
     * Where function, like SQL
     *
     * Operators:
     * - Standard operators (=, !=, >, <, >=, <=)
     * - IN (only for array value)
     * - NOT IN (only for array value)
     *
     * @param string $field Field name
     * @param string $op Operator
     * @param mixed $value Field value
     * @return self
     */
    public function where($field, $op, $value)
    {
        $this->pending['where'][] = array(
            'type' => 'and',
            'field' => $field,
            'op' => $op,
            'value' => $value,
        );
        return $this;
    }

    /**
     * Alias for where()
     * @param string $field Field name
     * @param string $op Operator
     * @param mixed $value Field value
     * @return self
     */
    public function andWhere($field, $op, $value)
    {
        $this->where($field, $op, $value);

        return $this;
    }

    /**
     * Alias for where(), setting OR for searching
     * @param string $field Field name
     * @param string $op Operator
     * @param mixed $value Field value
     * @return self
     */
    public function orWhere($field, $op, $value)
    {
        $this->pending['where'][] = array(
            'type' => 'or',
            'field' => $field,
            'op' => $op,
            'value' => $value,
        );

        return $this;
    }

    /**
     * Filter function for array_filter() in where()
     * @return boolean
     */
    protected function wherePending()
    {
        $operator = array(
            '=' => '==',
            '!=' => '!=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
            'and' => '&&',
            'or' => '||',
        );

        $this->data = array_filter($this->data, function ($row) use ($operator) {
            $clause = '';
            $result = true;

            foreach ($this->pending['where'] as $key => $condition) {
                $value = $condition['value'];
                $type = $condition['type'];
                $op = $condition['op'];
                $field = $condition['field'];

                if (is_array($value) && $op == 'IN') {
                    $value = (in_array($row->{$field}, $value)) ? 1 : 0;
                    $op = '==';
                    $field = 1;
                } elseif (!is_array($value) && in_array($op, array('LIKE', 'like'))) {
                    $regex = "/^" . str_replace('%', '(.*?)', preg_quote($value)) . "$/si";
                    $value = preg_match($regex, $row->{$field});
                    $op = '==';
                    $field = 1;
                } elseif (!is_array($value) && $op != 'IN') {
                    $value = is_string($value) ?
                    '\'' . mb_strtolower($value) . '\'' :
                    $value;

                    $op = $operator[$op];
                    $field = is_string($row->{$field}) ?
                    'mb_strtolower($row->' . $field . ')' :
                    '$row->' . $field;
                }

                $type = (!$key) ?
                null :
                $operator[$type];

                $query = array($type, $field, $op, $value);
                $clause .= implode(' ', $query) . ' ';

                eval('$result = ' . $clause . ';');
            }

            return $result;
        });
    }

    /**
     * Returning data as indexed or assoc array.
     * @param string $key Field that will be the key, NULL for Indexed
     * @param string $value Field that will be the value
     * @return array
     */
    public function asArray($key = null, $value = null)
    {
        if (!is_null($key)) {
            Validate::table($this->name)->field($key);
        }
        if (!is_null($value)) {
            Validate::table($this->name)->field($value);
        }

        $datas = array();
        if (!$this->resetKeys) {
            if (is_null($key) && is_null($value)) {
                return $this->data;
            } else {
                foreach ($this->data as $rowKey => $data) {
                    $datas[$rowKey] = array();
                    foreach ($data as $row) {
                        if (is_null($key)) {
                            $datas[$rowKey][] = $row->{$value};
                        } elseif (is_null($value)) {
                            $datas[$rowKey][$row->{$key}] = $row;
                        } else {
                            $datas[$rowKey][$row->{$key}] = $row->{$value};
                        }
                    }
                }
            }
        } else {
            if (is_null($key) && is_null($value)) {
                foreach ($this->data as $data) {
                    $datas[] = get_object_vars($data);
                }
            } else {
                foreach ($this->data as $data) {
                    if (is_null($key)) {
                        $datas[] = $data->{$value};
                    } elseif (is_null($value)) {
                        $datas[$data->{$key}] = $data;
                    } else {
                        $datas[$data->{$key}] = $data->{$value};
                    }
                }
            }
        }

        return $datas;
    }

    /**
     * Limit returned data
     *
     * Should be used at the end of chain, before end method
     * @param integer $number Limit number
     * @param integer $offset Offset number
     * @return self
     */
    public function limit($number, $offset = 0)
    {
        $this->pending['limit'] = array(
            'offset' => $offset,
            'number' => $number,
        );

        return $this;
    }

    /**
     * Pending function for limit()
     */
    protected function limitPending()
    {
        $offset = $this->pending['limit']['offset'];
        $num = $this->pending['limit']['number'];
        $this->data = array_slice($this->data, $offset, $num);
    }

    /**
     * Add new fields to table, array schema like in create() function
     * @param array $fields Associative array
     */
    public function addFields(array $fields)
    {
        $fields = Validate::arrToLower($fields);

        Validate::types(array_values($fields));

        $schema = $this->schema();
        $fields = array_diff_assoc($fields, $schema);

        if (!empty($fields)) {
            $config = $this->config();
            $config->schema = array_merge($schema, $fields);

            $data = $this->getData();
            foreach ($data as $key => $object) {
                foreach ($fields as $name => $type) {
                    if (Validate::isNumeric($type)) {
                        $data[$key]->{$name} = 0;
                    } else {
                        $data[$key]->{$name} = null;
                    }

                }
            }

            Data::table($this->name)->put($data);
            Config::table($this->name)->put($config);
        }
    }

    /**
     * Delete fields from array
     * @param array $fields Indexed array
     */
    public function deleteFields(array $fields)
    {
        $fields = Validate::arrToLower($fields);

        Validate::table($this->name)->fields($fields);

        $config = $this->config();
        $config->schema = array_diff_key($this->schema(), array_flip($fields));

        $data = $this->getData();
        foreach ($data as $key => $object) {
            foreach ($fields as $name) {
                unset($data[$key]->{$name});
            }
        }

        Data::table($this->name)->put($data);
        Config::table($this->name)->put($config);
    }

    /**
     * Returns table name
     * @return string table name
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Returning object with config for table
     * @return object Config
     */
    public function config()
    {
        return Config::table($this->name)->get();
    }

    /**
     * Return array with names of fields
     * @return array Fields
     */
    public function fields()
    {
        return Config::table($this->name)->fields();
    }

    /**
     * Returning assoc array with types of fields
     * @return array Fields type
     */
    public function schema()
    {
        return Config::table($this->name)->schema();
    }

    /**
     * Returning assoc array with relationed tables
     * @param string|null $tableName
     * @return array Fields type
     */
    public function relations($tableName = null)
    {
        return Config::table($this->name)->relations($tableName, true);
    }

    /**
     * Returning last ID from table
     * @return integer Last ID
     */
    public function lastId()
    {
        return Config::table($this->name)->lastId();
    }

    /**
     * Insert a row
     *
     * @throws Exception
     */
    public function insert()
    {
        $this->save(true);
    }

    /**
     * Saving inserted or updated data
     * @param bool $forceInsert
     * @throws Exception
     */
    public function save($forceInsert = false)
    {
        $data = $this->getData();
        $itemId = null;

        if (!$this->currentId || $forceInsert) {
            $config = $this->config();
            $config->last_id++;

            $this->setField('id', $config->last_id);
            $itemId = $config->last_id;
            array_push($data, $this->set);

            Config::table($this->name)->put($config);
        } else {
            $this->setField('id', $this->currentId);
            $itemId = $this->currentId;
            $data[$this->currentKey] = $this->set;
        }

        Data::table($this->name)->put($data);

        // after save, clear all $set data
        $this->set = new \stdClass();
        $this->setField('id', $itemId);
    }

    /**
     * Deleting loaded data
     * @return boolean
     */
    public function delete()
    {
        $data = $this->getData();
        if (isset($this->currentId)) {
            unset($data[$this->currentKey]);
        } else {
            $this->pending();
            $old = $data;
            $data = array_diff_key($old, $this->data);
        }
        $this->data = array_values($data);

        return Data::table($this->name)->put($this->data) ? true : false;
    }

    /**
     * Return count in integer or array of integers (if grouped)
     * @return mixed
     */
    public function count()
    {
        if (!$this->resetKeys) {
            $count = array();
            foreach ($this->data as $group => $data) {
                $count[$group] = count($data);
            }
        } else {
            $count = count($this->data);
        }

        return $count;
    }

    /**
     * Returns one row with specified ID
     * @param integer $id Row ID
     * @return self
     */
    public function find($id = null)
    {
        if ($id !== null) {
            $data = $this->getData();
            $this->currentId = $id;
            $this->currentKey = $this->getRowKey($id);
            foreach ($data[$this->currentKey] as $field => $value) {
                $this->setField($field, $value);
            }
        } else {
            $this->limit(1)->findAll();
            $data = $this->data;
            if (count($data)) {
                foreach ($data[0] as $field => $value) {
                    $this->setField($field, $value);
                }

                $this->currentId = $this->getField('id');
                $this->currentKey = $this->getRowKey($this->currentId);
            }
        }
        return clone $this;
    }

    /**
     * Make data ready to read
     */
    public function findAll()
    {
        $this->pending();
        $this->data = $this->resetKeys ? array_values($this->data) : $this->data;

        return clone $this;
    }

    /**
     * Iterator for Data
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
    
    /**
     * getSet
     *
     * @return void
     */
    public function getSet()
    {
        return $this->set;
    }
    
    /**
     * getPending
     *
     * @return void
     */
    public function getPending()
    {
        return $this->pending;
    }
}
