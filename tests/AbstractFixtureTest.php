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

    protected function parseFile($file)
    {
        $parser = Parser::parseFile($this->resourcesPath.$file);

        return $parser;
    }
}
