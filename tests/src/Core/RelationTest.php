<?php

namespace DatabaseJson\Test\Classes;

use DatabaseJson\Core\Database;
use DatabaseJson\Core\Relation;
use DatabaseJson\Test\VfsHelper\Config as TestHelper;

class RelationTest extends \PHPUnit\Framework\TestCase
{

    use TestHelper;

    /**
     * @var Database
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->setUpFilesystem();
        $this->object = new Relation();
    }

    public function testDummy()
    {
        $this->markTestSkipped('TODO tests for relation');
    }

}
