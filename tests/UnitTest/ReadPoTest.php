<?php

namespace Sepia\Test\UnitTest;

use Sepia\PoParser\Catalog\Entry;
use Sepia\Test\AbstractFixtureTest;

class ReadPoTest extends AbstractFixtureTest
{
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
        $catalog = $this->parseFile('healthy.po');
        $entry = $catalog->getEntry('string.2');

        $this->assertNotNull($entry);
        $this->assertEquals(array('Translator comment'), $entry->getTranslatorComments());
    }

    public function testDeveloperComment()
    {
        $catalog = $this->parseFile('healthy.po');
        $entry = $catalog->getEntry('string.2');

        $this->assertNotNull($entry);
        $this->assertEquals(array('Code comment'), $entry->getDeveloperComments());
    }

    public function testEntriesWithContext()
    {
        $catalog = $this->parseFile('context.po');

        $withContext = $catalog->getEntry('1', 'start of week');
        $withoutContext = $catalog->getEntry('1');

        $this->assertNotNull($withContext);
        $this->assertNotNull($withoutContext);
        $this->assertNotEquals($withContext, $withoutContext);
    }

    public function testPreviousUntranslated()
    {
        $catalog = $this->parseFile('previous_unstranslated.po');

        $this->assertCount(1, $catalog->getEntries());

        $entry = new Entry('this is a string', 'this is a translation');
        $entry->setPreviousEntry(new Entry('this is a previous string', 'this is a previous translation string'));
        $this->assertEquals(
            $entry,
            $catalog->getEntry('this is a string')
        );
    }

    public function testPreviousUntranslatedMultiline()
    {
        $this->markTestIncomplete('TODO');
        $catalog = $this->parseFile('previous_unstranslated.po');
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
    }

    public function testNoHeader()
    {
        $catalog = $this->parseFile('noheader.po');

        $this->assertCount(2, $catalog->getEntries());
    }

    public function testNoBlankLinesSeparatingEntries()
    {
        $catalog = $this->parseFile('noblankline.po');

        $this->assertCount(2, $catalog->getEntries());
    }
}
