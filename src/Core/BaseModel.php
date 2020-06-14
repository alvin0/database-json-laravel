<?php
namespace DatabaseJson\Core;

use DatabaseJson\Core\Str;
use DatabaseJson\Core\Traits\ForwardsCalls;
use DatabaseJson\Core\Traits\HasRelationships;
use DatabaseJson\Core\Traits\Query;

class BaseModel implements \IteratorAggregate
{
    use HasRelationships, ForwardsCalls, Query;

    /**
     * The table name.
     *
     * @var string
     */
    protected $table;

    /**
     * The structure for the model.
     *
     * @var object
     */
    protected $structure;

    /**
     * Name model for the model.
     *
     * @var string
     */
    protected $model;

    /**
     * Data
     *
     * @var array
     */
    protected $data;

    /**
     * The timestamp for the model.
     *
     * @var object
     */
    protected $timestamp = true;

    /**
     * The attribute for the model.
     *
     * @var object
     */
    protected $attribute;

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * query
     *
     * @var void
     */
    private $query = null;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * __construct
     *
     * @param  array $fill
     * @param  string $table
     * @return void
     */
    public function __construct(array $fill = [], $table = null)
    {
        if ($table != null) {
            $this->table = $table;
        }
        $this->checkTable($this->getTable());
        $this->getTableInfo();
        if ($fill != []) {
            $this->fillAttrAll($fill);
        }
    }

    /**
     * Returning variable from Object
     * @param string $name Field name
     * @return mixed Field value
     * @throws Exception
     */
    public function __get($name = null)
    {
        if ($name == null) {
            return $this->data;
        }

        $checkMethod = method_exists($this, $name) ? $this->{$name}() : false;

        if ($checkMethod) {
            return $this->getModelRelation($this, $name);
        }

        if (isset($this->structure[$name])) {
            return $this->data->{$name};
        }

        return null;
    }

    /**
     * Validating array and setting variables to current operations
     *
     * @uses fillAttr() to set field value
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function __set($name, $value)
    {
        $this->fillAttr($name, $value);
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $method = $this->redirectFunctionClass($method);

        $parameters = $this->prepareMethod($method, $parameters);

        if ($this->query == null) {
            $this->query = $this->objectTable();
        }

        if (in_array($method, ['getById', 'setPaginate', 'getWith', 'removeData','updateData'])) {
            return $this->$method(...$parameters);
        }

        return $this->forwardCallTo($this, $method, $parameters);
    }

    /**
     * Handle dynamic static method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static )->$method(...$parameters);
    }

    /**
     * fillAttrAll
     *
     * @param  mixed $data
     * @return void
     */
    public function fillAttrAll(array $data)
    {
        foreach ($data as $name => $value) {
            if (gettype($value) == 'object' && get_class($data[$name]) != 'DatabaseJson\Core\Database') {
                $this->fillAttr($name, $value);
            } else {
                $this->fillAttr($name, $value);
            }
        }
    }

    /**
     * getIterator
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * objectTable
     *
     * @return Database
     */
    public function objectTable()
    {
        return Database::table($this->table);
    }

    public function toArray()
    {
        return (array) $this->data;
    }

    /**
     * toJson
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->data);
    }

    /**
     * redirectFunctionClass
     *
     * @param  string $method
     * @return string
     */
    public function redirectFunctionClass($method)
    {
        $methodRedirect = [
            'find' => 'getById',
            'paginate' => 'setPaginate',
            'with' => 'getWith',
            'delete' => 'removeData',
            'update' => 'updateData'
        ];
        return $methodRedirect[$method] ?? $method;
    }

    /**
     * prepareMethod
     *
     * @param  string $method
     * @param  string $parameters
     * @return string
     */
    public function prepareMethod($method, $parameters)
    {
        switch ($method) {
            case 'where':
                $parameters = $this->prepareWhere($parameters);
                break;
        }
        return $parameters;
    }

    /**
     * prepareWhere
     *
     * @param  string $parameters
     * @return array
     */
    public function prepareWhere($parameters)
    {
        $parameters = [
            'field' => $parameters[0],
            'op' => '=',
            'value' => $parameters[1],
        ];
        return array_values($parameters);
    }
}
