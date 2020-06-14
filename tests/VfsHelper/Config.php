<?php

namespace DatabaseJson\Test\VfsHelper;

use org\bovigo\vfs\vfsStream;

trait Config
{

    protected $root;

    protected function setUpFilesystem()
    {
        $this->root = vfsStream::setup('data');
        vfsStream::copyFromFileSystem(realpath(__DIR__) . '/../../' . 'tests/db');
    }
}
