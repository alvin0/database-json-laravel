<?php
namespace DatabaseJson\Core\Traits;

use DatabaseJson\Core\Database;
use DatabaseJson\Core\Helpers\Validate;
use DatabaseJson\Core\Relation;
use Illuminate\Support\Str;

/**
 * HasRelationships
 */
trait HasRelationships
{

    /**
     * belongsTo
     *
     * @param  mixed $class
     * @param  mixed $foreignKey
     * @param  mixed $localKey
     * @return void
     */
    public function belongsTo($class, $localKey = null, $foreignKey = 'id')
    {
        $model = new $class;
        $localKey = $localKey == null ? $this->generateForeignKey($model) : $localKey;
        $this->checkTable($model->table);
        $this->checkFillable($model->table, $foreignKey);
        Relation::table($this->table)->belongsTo($model->table)->localKey($localKey)->foreignKey($foreignKey)->setRelation();

        return $model;
    }

    /**
     * hasMany
     *
     * @param  mixed $class
     * @param  mixed $foreignKey
     * @param  mixed $localKey
     * @return void
     */
    public function hasMany($class, $foreignKey = null, $localKey = 'id')
    {
        $model = new $class;
        $foreignKey = $foreignKey == null ? $this->generateForeignKey($model) : $foreignKey;
        $this->checkTable($model->table);
        $this->checkFillable($model->table, $foreignKey);
        Relation::table($this->table)->hasMany($model->table)->localKey($localKey)->foreignKey($foreignKey)->setRelation();
        return $model;
    }

    // public function manyToMany($table, $foreignKey = null, $localKey = 'id')
    // {
    //     $foreignKey = $foreignKey == null ? $this->generateForeignKey($table) : $foreignKey;
    //     $this->checkTable($table);
    //     $this->checkFillable($table, $foreignKey);
    //     Relation::table($this->table)->hasAndBelongsToMany($table)->localKey($localKey)->foreignKey($foreignKey)->setRelation();
    //     return new $table;
    // }

    /**
     * checkTable
     *
     * @param  mixed $table
     * @return void
     * @throws Exception
     */
    protected function checkTable(string $table)
    {
        try {
            Validate::table($table)->exists();
        } catch (Exception $e) {
            throw new \Exception("Table " . $table . " doesn't exist");
        }
    }

    /**
     * generateForeignKey
     *
     * @return string
     */
    public function generateForeignKey($model)
    {
        return Str::singular($model->table) . '_id';
    }

    /**
     * checkFillable
     *
     * @param  mixed $table
     * @param  mixed $fillable
     * @return boolean
     * @throws Exception
     */
    protected function checkFillable($table, $fillable)
    {
        if (Database::table($table)->issetField($fillable)) {
            return true;
        } else {
            throw new \Exception("Fillable " . $fillable . " doesn't exist in table " . $table);
        };
    }

}
