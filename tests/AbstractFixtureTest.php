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
        $this->resourcesPath = __DIR__.'/pofiles/';
    }

    /**
     * @param string $file
     *
     * @return \Sepia\PoParser\Catalog
     * @throws \Exception
     */
    protected function parseFile($file)
    {
        return Parser::parseFile($this->resourcesPath.$file);
    }
}
