<?php

namespace Sepia\Test\UnitTest;

use PHPUnit\Framework\TestCase;
use Sepia\PoParser\Catalog\Header;
use Sepia\PoParser\Parser;

class ParserTest extends TestCase
{
    /** @test */
    public function should_parse_headers()
    {
        $doc =
        'msgid ""
        msgstr ""
        "Header 1: value 1\n"
        "Header 2: value 2\n"
        ';
        $catalog = $this->parse($doc);

        $expectedHeaders = new Header([
            'Header 1' => 'value 1',
            'Header 2' => 'value 2',
        ]);
        $this->assertEquals(
            $expectedHeaders,
            $catalog->getHeader()
        );
    }

    /**
     * @param string $doc
     * @return \Sepia\PoParser\Catalog\Catalog|\Sepia\PoParser\Catalog\CatalogArray
     * @throws \Exception
     */
    public function parse(string $doc)
    {
        $parser = new Parser(new StringSource($doc));
        return $parser->parse();
    }
}