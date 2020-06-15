<?php
namespace DatabaseJson\Core\Traits;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;

/**
 * Extension
 */
trait Query
{

    /**
     * create
     *
     * @param  array $data
     * @return void
     */
    public static function create(array $data)
    {
        if (isset($data['id'])) {
            unset($data['id']);
        }

        $className = get_called_class();
        $model = new $className($data);

        return $model->save($data);
    }

    /**
     * save
     *
     * @return void
     */
    public function save()
    {
        try {
            $table = $this->objectTable();
            foreach ($this->attribute as $key => $item) {
                $table->{$key} = $item;
            }

            if ($this->timestamp) {
                $table->created_at = (string) Carbon::now()->getTimestamp();
                $table->updated_at = (string) Carbon::now()->getTimestamp();
            }

            if ($this->attribute->id != null) {
                if ($this->timestamp) {
                    unset($table->created_at);
                }
                $this->update((array) $this->attribute, $this->attribute->id);
            } else {
                $table->save();
            }
            $this->id = $table->id;
            $className = get_called_class();
            return $className::find($table->id);
        } catch (\Throwable $th) {
            throw new \Exception('Save data error!');
        }
    }

    /**
     * updateData
     *
     * @param  array $data
     * @param  int $id
     * @return void
     */
    public function updateData(array $data, int $id)
    {
        try {
            $model = $this->query->find($id);
            $model->find($id);
            $model->set($data);
            $model->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * get
     *
     * @return void
     */
    public function get()
    {
        $this->data = collect([]);

        if ($this->query) {
            $this->data = $this->makeModelData($this->query->findAll(), get_class($this));
        }

        return $this->data;
    }

    /**
     * Paginate the given query.
     *
     * @param  int|null  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function setPaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->data = collect([]);

        if (sizeof($this->query->getPending()['where']) > 0) {
            $data = $this->query->findAll();
            $this->data = collect($data) ?? collect([]);
        } else {
            $this->data = $this->makeModelData($this->query->findAll(), get_class($this));
        }

        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $perPage = $perPage ?: $this->model->perPage;
        $total = $this->data->count();
        $results = $this->data->forPage($page, $perPage);

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * get by id
     *
     * @param  int $id
     * @return void
     */
    public function getById(int $id)
    {
        if (sizeof($this->query->getPending()['where']) > 0) {
            $data = $this->query->where($this->primaryKey, '=', $id)->findAll();
            $this->data = collect($data)[0] ?? null;
        } else {
            try {
                $this->data = $this->query->find($id)->getSet();

                if (isset($this->data->created_at)) {
                    $this->data->created_at = $this->asTimestamp($this->data->created_at);
                }

                if (isset($this->data->updated_at)) {
                    $this->data->updated_at = $this->asTimestamp($this->data->updated_at);
                }

                if (count($this->appends)) {
                    foreach ($this->appends as $key => $value) {
                        $this->data->{$value} = $this->{$value} ?? null;
                    }
                }

                foreach (array_keys((array) $this->attribute) as $key) {
                    $this->{$key} = $this->{$key} ?? null;
                }
            } catch (\Throwable $th) {
                return null;
            }
        }

        $this->clearQuery();

        return clone $this;
    }

    /**
     * all
     *
     * @return void
     */
    public static function all()
    {
        $className = get_called_class();
        $model = new $className;
        $model->data = $model->makeModelData($model->objectTable()->findAll(), $className);

        return $model->data;
    }

    /**
     * first
     *
     * @return void
     */
    public function first()
    {
        return $this->get()->first();
    }

    /**
     * getTable
     *
     * @return void
     */
    public function getTable()
    {
        if ($this->table == null) {
            $nameClass = (new \ReflectionClass($this))->getShortName();
            $this->model = $nameClass;
            $this->table = Str::snake(Str::plural($nameClass));
            return $this->table;
        } else {
            return $this->table;
        }
    }

    /**
     * removeData
     *
     * @return void
     */
    public function removeData()
    {
        try {
            if ($this->id != null) {
                $this->query->where('id', '=', $this->id)->delete();
            } else {
                $this->query->delete();
            }
            return true;
        } catch (\Throwable $th) {
            return false;
        }

    }

    /**
     * getTableInfo
     *
     * @return void
     */
    public function getTableInfo()
    {
        $this->attribute = $this->objectTable()->getSet();
        $this->structure = $this->objectTable()->schema();
        $this->relations = $this->objectTable()->relations();
    }

    /**
     * makeModelData
     *
     * @param  mixed $data
     * @param  string $classModel
     * @return void
     */
    public function makeModelData($data, string $classModel)
    {
        $table = $this->table;

        return collect($data)->transform(function ($item) use ($table, $classModel) {
            $model = new $classModel((array) $item, $table);
            $model->data = (object) collect(array_change_key_case((array) $item, CASE_LOWER))->transform(function ($item, $key) use ($model) {

                if (gettype($item) == 'object') {
                    if (get_class($item) == 'DatabaseJson\Core\Database') {
                        return collect($item->findAll())->toArray();
                    }
                    return (array) $item;
                }

                if ($key == 'created_at' || $key == 'updated_at') {
                    return $this->asTimestamp($item);
                }

                return $item;
            })->toArray();

            if (count($this->appends)) {
                foreach ($this->appends as $key => $value) {
                    $model->data->{$value} = $model->{$value} ?? null;
                }
            }

            foreach (array_keys((array) $model->attribute) as $key) {
                $model->{$key} = $model->data->{$key} ?? null;
            }

            $model->model = $classModel;

            return $model;
        });
    }

    /**
     * clearQuery
     *
     * @return void
     */
    public function clearQuery()
    {
        $this->query = null;
    }

    /**
     * getModelRelation
     *
     * @param  void $model
     * @param  string $relation
     * @return void
     */
    public function getModelRelation($model, $relation)
    {
        $relationTale = $model->{$relation}()->table;
        $relationInfo = $model->relations[$relationTale];
        $typeCollectDataByRelation = [
            'belongsTo' => 'first',
            'hasMany' => 'all',
        ];
        $data = $this->{$relation}()->where($relationInfo['keys']['foreign'], $model->id);

        return $data->{$typeCollectDataByRelation[$relationInfo['type']]}();
    }

    /**
     * getWith
     *
     * @param  string $relations
     * @return void
     */
    public function getWith($relations)
    {
        $type = gettype($relations);

        if ($type == 'array') {
            foreach ($relations as $relation) {
                $checkMethod = method_exists($this, $relation) ? $this->{$relation}() : false;
                if ($checkMethod) {
                    $modelRelation = $this->{$relation}();
                    $this->query = $this->query->with($modelRelation->table);
                } else {
                    throw new \Exception("Relation " . $relation . " doesn't exist");
                }
            }
        }

        if ($type == 'string') {
            $checkMethod = method_exists($this, $relations) ? $this->{$relations}() : false;
            if ($checkMethod) {
                $modelRelation = $this->{$relations}();
                $this->query = $this->query->with($modelRelation->table);
            } else {
                throw new \Exception("Relation " . $relations . " doesn't exist");
            }
        }

        return $this;
    }
}
