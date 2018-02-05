<?php

namespace Sepia\Test;

use PHPUnit\Framework\TestCase;
use Sepia\PoParser\Parser;

abstract class AbstractFixtureTest extends TestCase
{
    /** @var string */
    protected $resourcesPath;

    public function setUp()
    {
        $this->resourcesPath = dirname(__DIR__).'/fixtures/';
    }

    /**
     * @param string $file
     *
     * @return \Sepia\PoParser\CatalogArray
     */
    protected function parseFile($file)
    {
        //try {
            return Parser::parseFile($this->resourcesPath.$file);
        //} catch (\Exception $e) {
        //    $this->fail($e->getMessage());
        //}
    }
}
