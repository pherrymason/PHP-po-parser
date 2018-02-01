<?php

namespace Sepia\Test;

use Sepia\FileHandler;
use Sepia\PoParser;

class ModifyCatalogTest extends AbstractFixtureTest
{
    /**
     * Test update comments
     */
    public function testUpdateComments()
    {
        $fileHandler = new FileHandler($this->resourcesPath.'context.po');
        $parser = new PoParser($fileHandler);
        $entries = $parser->parse();
        $options = $parser->getOptions();
        $ctxtGlue = $options['context-glue'];

        $msgid = 'Background Attachment'.$ctxtGlue.'Attachment';
        $entry = $entries[$msgid];

        $entry['ccomment'] = array('Test write ccomment');
        $entry['tcomment'] = array('Test write tcomment');

        $parser->setEntry($msgid, $entry);
        $parser->writeFile($this->resourcesPath.'temp.po');

        $parser = PoParser::parseFile($this->resourcesPath.'temp.po');
        $entries = $parser->getEntries();

        $this->assertEquals($entries[$msgid]['tcomment'][0], $entry['tcomment'][0]);
        $this->assertEquals($entries[$msgid]['ccomment'][0], $entry['ccomment'][0]);
    }
}
