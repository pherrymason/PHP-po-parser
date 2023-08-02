<?php

namespace Sepia\Test\UnitTest;

use PHPUnit\Framework\TestCase;
use Sepia\PoParser\Catalog\Header;
use Sepia\PoParser\Parser;
use Sepia\PoParser\SourceHandler\StringSource;

class ParserTest extends TestCase
{
    /** @test */
    public function should_parse_headers()
    {
        $doc =
        'msgid ""
        msgstr ""
        "Project-Id-Version: value 1\n"
        "Report-Msgid-Bugs-To: value 2\n"
        
        msgid "string.1"
        msgstr "translation.1"
        ';
        $catalog = $this->parse($doc);

        $expectedHeaders = new Header(array(
            'Project-Id-Version: value 1',
            'Report-Msgid-Bugs-To: value 2',
        ));
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