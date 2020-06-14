<?php

namespace DatabaseJson\Core\Helpers;

class Data extends File
{

    public static function table($name)
    {
        $file = new Data;
        $file->name = $name;
        $file->setType('data');

        return $file;
    }

}
