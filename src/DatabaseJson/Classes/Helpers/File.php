<?php

namespace DatabaseJson\Classes\Helpers;

use DatabaseJson\Classes\DatabaseJsonException;

class File implements FileInterface
{

    /**
     * File name
     * @var string
     */
    protected $name;

    /**
     * File type (data|config)
     * @var string
     */
    protected $type;

    public static function table($name)
    {
        $file       = new File;
        $file->name = $name;

        return $file;
    }

    final public function setType($type)
    {
        $this->type = $type;
    }

    final public function getPath()
    {
        if (!env('JSON_DATABASE_PATCH')) {
            throw new DatabaseJsonException('Please define constant JSON_DATABASE_PATCH (check README.md)');
        } else if (!empty($this->type)) {
            return env('JSON_DATABASE_PATCH') . $this->name . '.' . $this->type . '.json';
        } else {
            throw new DatabaseJsonException('Please specify the type of file in class: ' . __CLASS__);
        }
    }

    final public function get($assoc = false)
    {
        return json_decode(file_get_contents($this->getPath()), $assoc);
    }

    final public function put($data)
    {
        return file_put_contents($this->getPath(), json_encode($data));
    }

    final public function exists()
    {
        return file_exists($this->getPath());
    }

    final public function remove()
    {
        $type = ucfirst($this->type);
        if ($this->exists()) {
            if (unlink($this->getPath())) {
                return true;
            }

            throw new DatabaseJsonException($type . ': Deleting failed');
        }

        throw new DatabaseJsonException($type . ': File does not exists');
    }

}
