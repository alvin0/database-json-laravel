<?php
namespace DatabaseJson\Core;

use Carbon\CarbonInterface;
use DatabaseJson\Core\Helpers\Validate;
use DatabaseJson\Core\Traits\ForwardsCalls;
use DatabaseJson\Core\Traits\HasRelationships;
use DatabaseJson\Core\Traits\Query;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

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
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

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

        $filterDataGet = $this->filterDataGet($name);

        if ($filterDataGet) {
            return $filterDataGet;
        }

        if (isset($this->structure[$name])) {
            return $this->data->{$name};
        }

        return null;
    }

    /**
     * filterDataGet
     *
     * @param  mixed $name
     * @return void
     */
    public function filterDataGet($name)
    {
        $name = Str::studly($name);
        $nameFunction = 'get' . $name . 'Attribute';

        if (method_exists($this, $nameFunction)) {
            return $this->{$nameFunction}();
        }

        return false;
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
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

        if (in_array($method, ['getById', 'setPaginate', 'getWith', 'removeData', 'updateData'])) {
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
     * fillAttr
     *
     * @param  mixed $name
     * @param  mixed $value
     * @return void
     */
    public function fillAttr($name, $value)
    {
        if (Validate::table($this->getTable())->field($name) && Validate::table($this->getTable())->type($name, $value)) {
            if (is_string($value) && false === mb_check_encoding($value, 'UTF-8')) {
                $this->attribute->{$name} = utf8_encode($value);
            } else {
                $this->attribute->{$name} = $value;
            }
        }
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
            if (gettype($value) != 'object') {
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
            'update' => 'updateData',
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

    /**
     * Return a timestamp as unix timestamp.
     *
     * @param  mixed  $value
     * @return int
     */
    protected function asTimestamp($value)
    {
        return $this->asDateTime($value)->toDateTimeString();
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    protected function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            return Date::instance($value);
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof DateTimeInterface) {
            return Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if ($this->isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        $format = $this->getDateFormat();

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        if (Date::hasFormat($value, $format)) {
            return Date::createFromFormat($format, $value);
        }

        return Date::parse($value);
    }
}
