<?php

namespace DatabaseJson\Core\Helpers;

use DatabaseJson\Core\Helpers\Exception;

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
        $file = new File;
        $file->name = $name;

        return $file;
    }

    final public function setType($type)
    {
        $this->type = $type;
    }

    final public function getPath()
    {
        $pathStorage = config('databasejson.path', storage_path('app/database-json'));
        if (!file_exists($pathStorage)) {
            mkdir($pathStorage, 0757, true);
        }
        return $pathStorage . $this->name . '.' . $this->type . '.json';
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

            throw new Exception($type . ': Deleting failed');
        }

        throw new Exception($type . ': File does not exists');
    }

}
