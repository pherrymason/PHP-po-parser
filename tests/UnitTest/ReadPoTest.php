<?php

namespace Sepia\Test\UnitTest;

use Sepia\PoParser\Catalog\Entry;
use Sepia\Test\AbstractFixtureTest;

class ReadPoTest extends AbstractFixtureTest
{
    public function testBasic()
    {
        $catalog = $this->parseFile('basic.po');

        $entry = $catalog->getEntry('string.1');

        $this->assertNotNull($entry);
        $this->assertEquals('string.1', $entry->getMsgId());
        $this->assertEquals('translation.1', $entry->getMsgStr());

        $entry = $catalog->getEntry('string.2');
        $this->assertNotNull($entry);
        $this->assertEquals('string.2', $entry->getMsgId());
        $this->assertEquals('translation \"quoted\"', $entry->getMsgStr());
    }

    public function testBasicMultiline()
    {
        $catalog = $this->parseFile('basicMultiline.po');

        $entry = $catalog->getEntry('string.1');

        $this->assertNotNull($entry);
        $this->assertEquals('string.1', $entry->getMsgId());
        $this->assertEquals('translation line 1 translation line 2', $entry->getMsgStr());
    }

    public function testBasicCollection()
    {
        $catalog = $this->parseFile('basicCollection.po');

        $this->assertCount(2, $catalog->getEntries());

        $entry = $catalog->getEntry('string.1');
        $this->assertNotNull($entry);
        $this->assertEquals('string.1', $entry->getMsgId());
        $this->assertEquals('translation.1', $entry->getMsgStr());

        $entry = $catalog->getEntry('string.2');
        $this->assertNotNull($entry);
        $this->assertEquals('string.2', $entry->getMsgId());
        $this->assertEquals('translation.2', $entry->getMsgStr());
    }

    public function testEntriesWithContext()
    {
        $catalog = $this->parseFile('context.po');

        $withContext = $catalog->getEntry('string.1', 'register');
        $this->assertNotNull($withContext);
        $this->assertEquals('register', $withContext->getMsgCtxt());

        $withoutContext = $catalog->getEntry('string.1');
        $this->assertNotNull($withoutContext);
        $this->assertEmpty($withoutContext->getMsgCtxt());
        $this->assertNotEquals($withContext, $withoutContext);
    }

    public function testPlurals()
    {
        $catalog = $this->parseFile('plurals.po');

        $entry = $catalog->getEntry('%s post not updated, somebody is editing it.');
        $this->assertNotNull($entry);
        $this->assertNotEmpty($entry->getMsgStrPlurals());
        $this->assertEquals(
            array(
                '%s entrada no actualizada, alguien la está editando.',
                '%s entradas no actualizadas, alguien las está editando.',
            ),
            $entry->getMsgStrPlurals()
        );
    }

    public function testPluralsMultiline()
    {
        $catalog = $this->parseFile('pluralsMultiline.po');
        $entry = $catalog->getEntry('%s post not updated,somebody is editing it.');

        $this->assertNotNull($entry);
        $this->assertNotEmpty($entry->getMsgStrPlurals());
        $this->assertEquals(
            array(
                '%s entrada no actualizada,alguien la está editando.',
                '%s entradas no actualizadas,alguien las está editando.',
            ),
            $entry->getMsgStrPlurals()
        );
    }
    
    public function testFlags()
    {
        $catalog = $this->parseFile('multiflags.po');

        $this->assertCount(1, $catalog->getEntries());
        $entry = $catalog->getEntry('Attachment', 'Background Attachment');

        $this->assertNotNull($entry);
        $this->assertCount(2, $entry->getFlags());
        $this->assertEquals(array('php-format', 'fuzzy'), $entry->getFlags());
    }

    public function testTranslatorComment()
    {
        $catalog = $this->parseFile('translatorComments.po');
        $entry = $catalog->getEntry('string.1');

        $this->assertNotNull($entry);
        $this->assertEquals(
            array('translator comment', 'second translator comment'),
            $entry->getTranslatorComments()
        );
    }

    public function testDeveloperComment()
    {
        $catalog = $this->parseFile('codeComments.po');
        $entry = $catalog->getEntry('string.1');

        $this->assertNotNull($entry);
        $this->assertEquals(array('code comment', 'code translator comment'), $entry->getDeveloperComments());
    }


    public function testPreviousString()
    {
        $catalog = $this->parseFile('previousString.po');

        $this->assertCount(1, $catalog->getEntries());

        $entry = new Entry('this is a string', 'this is a translation');
        $entry->setPreviousEntry(new Entry('this is a previous string', 'this is a previous translation string'));
        $this->assertEquals(
            $entry,
            $catalog->getEntry('this is a string')
        );
    }

    public function testPreviousStringMultiline()
    {
        $catalog = $this->parseFile('previousStringMultiline.po');

        $entry = $catalog->getEntry('this is a string');
        $this->assertNotNull($entry);

        $previous = $entry->getPreviousEntry();
        $this->assertNotNull($previous);
        $this->assertEquals('this is a previous string', $previous->getMsgId());
        $this->assertEquals('Doloribus nulla odit et aut est. Rerum molestiae pariatur suscipit unde in quidem alias alias. Ut ea omnis placeat rerum quae asperiores. Et recusandae praesentium ea.', $previous->getMsgStr());
    }

    public function testHeaders()
    {
        $catalog = $this->parseFile('basicHeader.po');
        $this->assertCount(1, $catalog->getEntries());
    }

    public function testOnlyCustomHeaders()
    {
        $catalog = $this->parseFile('basicCustomHeaders.po');
        $this->assertCount(1, $catalog->getEntries());
        $this->assertGreaterThanOrEqual(1, count($catalog->getHeaders()));
    }

    public function testHeadersMultiline()
    {
        $catalog = $this->parseFile('basicHeadersMultiline.po');
        $this->assertCount(1, $catalog->getEntries());
        $this->assertCount(3,$catalog->getHeaders());
    }
/*
    public function testMultilineEntries()
    {
        $catalog = $this->parseFile('multilines.po');

        $longMsgId = '%user% acaba de responder tu comentario.<br>Consulta que te ha dicho %link%aquí</a>.';

        $entryExpected = new Entry(
            $longMsgId,
            '%user% acaba de respondre el teu comentari.<br>Consulta que t\'ha dit %link%aquí</a>.'
        );
        $entryExpected->setReference(
            array('../../classes/controller/ccccc.php:361')
        );

        $entry = $catalog->getEntry($longMsgId);
        $this->assertNotNull($entry);
        $this->assertEquals($entryExpected, $entry);
    }*/

    public function testNoBlankLinesSeparatingEntries()
    {
        $catalog = $this->parseFile('noblankline.po');

        $this->assertCount(2, $catalog->getEntries());
    }
}
