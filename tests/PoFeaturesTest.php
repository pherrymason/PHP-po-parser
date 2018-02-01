<?php

namespace Sepia\Test;

use Sepia\PoParser\Parser;
use Sepia\PoParser\PoReader\StringHandler;

class PoFeaturesTest extends AbstractFixtureTest
{
    public function tearDown()
    {
        parent::tearDown();

        if (file_exists($this->resourcesPath.'temp.po')) {
            unlink($this->resourcesPath.'temp.po');
        }
    }

    public function testRead()
    {
        try {
            $catalog = Parser::parseFile($this->resourcesPath.'healthy.po');
            $result = $catalog->getEntries();
        } catch (\Exception $e) {
            $result = array();
            $this->fail($e->getMessage());
        }

        $this->assertCount(3, $result);


        // Read file without headers.
        // It should not skip first entry
        try {
            $catalog = Parser::parseFile($this->resourcesPath.'noheader.po');
            $result = $catalog->getEntries();
        } catch (\Exception $e) {
            $result = array();
            $this->fail($e->getMessage());
        }

        $this->assertCount(2, $result, 'Did not read properly po file without headers.');
    }


    /**
     *    Tests reading the headers.
     *
     */
    public function testHeaders()
    {
        try {
            $catalog = Parser::parseFile($this->resourcesPath.'healthy.po');
            $headers = $catalog->getHeaders();

            $this->assertCount(18, $headers);
            $this->assertEquals("Project-Id-Version: ", $headers[0]);
            $this->assertEquals("Report-Msgid-Bugs-To: ", $headers[1]);
            $this->assertEquals("POT-Creation-Date: 2013-09-25 15:55+0100", $headers[2]);
            $this->assertEquals("PO-Revision-Date: ", $headers[3]);
            $this->assertEquals("Last-Translator: Raúl Ferràs <xxxxxxxxxx@xxxxxxx.xxxxx>", $headers[4]);
            $this->assertEquals("Language-Team: ", $headers[5]);
            $this->assertEquals("MIME-Version: 1.0", $headers[6]);
            $this->assertEquals("Content-Type: text/plain; charset=UTF-8", $headers[7]);
            $this->assertEquals("Content-Transfer-Encoding: 8bit", $headers[8]);
            $this->assertEquals("Plural-Forms: nplurals=2; plural=n != 1;", $headers[9]);
            $this->assertEquals("X-Poedit-SourceCharset: UTF-8", $headers[10]);
            $this->assertEquals("X-Poedit-KeywordsList: __;_e;_n;_t", $headers[11]);
            $this->assertEquals("X-Textdomain-Support: yes", $headers[12]);
            $this->assertEquals("X-Poedit-Basepath: .", $headers[13]);
            $this->assertEquals("X-Generator: Poedit 1.5.7", $headers[14]);
            $this->assertEquals("X-Poedit-SearchPath-0: .", $headers[15]);
            $this->assertEquals("X-Poedit-SearchPath-1: ../..", $headers[16]);
            $this->assertEquals("X-Poedit-SearchPath-2: ../../../modules", $headers[17]);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
//			$this->assertTrue( false, $e->getMessage() );
        }
    }


    public function testMultilineId()
    {
        try {
            $catalog = Parser::parseFile($this->resourcesPath.'multilines.po');
            $result = $catalog->getEntries();
            $headers = $catalog->getHeaders();

            $this->assertCount(18, $headers);
            $this->assertCount(9, $result);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }


    /**
     *
     *
     */
    public function testPlurals()
    {
        try {
            $catalog = Parser::parseFile($this->resourcesPath.'plurals.po');
            $headers = $catalog->getHeaders();
            $result = $catalog->getEntries();

            $this->assertCount(7, $headers);
            $this->assertCount(15, $result);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testPluralsMultiline()
    {
        try {
            $catalog = Parser::parseFile($this->resourcesPath.'pluralsMultiline.po');
            $this->assertCount(2, $catalog->getEntries());
            $entries = $catalog->getEntries();
            foreach ($entries as $entry) {
                $this->assertNotEmpty($entry->getMsgStrPlurals());
            }
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }


    /**
     *    Test Writing file
     */
    public function testWrite()
    {
        $this->markTestSkipped();
        // Read & write a simple file
        $catalog = Parser::parseFile($this->resourcesPath.'healthy.po');
        $catalog->writeFile($this->resourcesPath.'temp.po');

        $this->assertFileEquals($this->resourcesPath.'healthy.po', $this->resourcesPath.'temp.po');

        // Read & write a file with no headers
        $catalog = Parser::parseFile($this->resourcesPath.'noheader.po');
        $catalog->writeFile($this->resourcesPath.'temp.po');

        $this->assertFileEquals($this->resourcesPath.'noheader.po', $this->resourcesPath.'temp.po');

        // Read & write a po file with multilines
        $catalog = Parser::parseFile($this->resourcesPath.'multilines.po');
        $catalog->writeFile($this->resourcesPath.'temp.po');

        $this->assertFileEquals($this->resourcesPath.'multilines.po', $this->resourcesPath.'temp.po');

        // Read & write a po file with contexts
        $catalog = Parser::parseFile($this->resourcesPath.'context.po');
        $catalog->writeFile($this->resourcesPath.'temp.po');

        $this->assertFileEquals($this->resourcesPath.'context.po', $this->resourcesPath.'temp.po');


        // Read & write a po file with previous unstranslated strings
        $catalog = Parser::parseFile($this->resourcesPath.'previous_unstranslated.po');
        $catalog->writeFile($this->resourcesPath.'temp.po');

        $this->assertFileEquals($this->resourcesPath.'previous_unstranslated.po', $this->resourcesPath.'temp.po');

        // Read & write a po file with multiple flags
        $catalog = Parser::parseFile($this->resourcesPath.'multiflags.po');
        $catalog->writeFile($this->resourcesPath.'temp.po');

        $this->assertFileEquals($this->resourcesPath.'multiflags.po', $this->resourcesPath.'temp.po');


        unlink($this->resourcesPath.'temp.po');
    }

    /**
     * Test update entry, update plural forms
     */
    public function testUpdatePlurals()
    {
        $this->markTestSkipped();
        $msgid = '%s post not updated, somebody is editing it.';
        $msgstr = array(
            "%s entrada no actualizada, alguien la está editando...",
            "%s entradas no actualizadas, alguien las está editando...",
        );

        $parser = Parser::parseFile($this->resourcesPath.'plurals.po');

        $parser->setEntry(
            $msgid,
            array(
                'msgid' => $msgid,
                'msgstr' => $msgstr,
            )
        );

        $parser->writeFile($this->resourcesPath.'temp.po');

        $parser = Parser::parseFile($this->resourcesPath.'temp.po');
        $newPlurals = $parser->getEntries();
        $this->assertEquals($newPlurals[$msgid]['msgstr'], $msgstr);
    }


    /**
     * Test update with fuzzy flag.
     *
     * @todo
     */
    public function testUpdateWithFuzzy()
    {
        $this->markTestSkipped();
        $msgid = '%1$s-%2$s';

        $parser = Parser::parseFile($this->resourcesPath.'context.po');
        $entries = $parser->getEntries();

        $entries[$msgid]['msgstr'] = array('translate');
        $parser->setEntry($msgid, $entries[$msgid]);
    }

    /**
     * Test for success update headers
     */
    public function testUpdateHeaders()
    {
        $this->markTestSkipped();
        $parser = Parser::parseFile($this->resourcesPath.'context.po');

        $newHeaders = array(
            '"Project-Id-Version: \n"',
            '"Report-Msgid-Bugs-To: \n"',
            '"POT-Creation-Date: \n"',
            '"PO-Revision-Date: \n"',
            '"Last-Translator: none\n"',
            '"Language-Team: \n"',
            '"MIME-Version: 1.0\n"',
            '"Content-Type: text/plain; charset=UTF-8\n"',
            '"Content-Transfer-Encoding: 8bit\n"',
            '"Plural-Forms: nplurals=2; plural=n != 1;\n"',
        );

        $result = $parser->setHeaders($newHeaders);
        $this->assertTrue($result);
        $parser->writeFile($this->resourcesPath.'temp.po');

        $newPoFile = Parser::parseFile($this->resourcesPath.'temp.po');
        $readHeaders = $newPoFile->getHeaders();
        $this->assertEquals($newHeaders, $readHeaders);
    }

    /**
     * Test for fail update headers
     */
    public function testUpdateHeadersWrong()
    {
        $this->markTestSkipped();
        $pofile = new Parser(new StringHandler(''));
        $result = $pofile->setHeaders('header');
        $this->assertFalse($result);
    }

    /**
     * Test for po files with no blank lines between entries
     */
    public function testNoBlankLines()
    {
        $catalog = Parser::parseFile($this->resourcesPath.'noblankline.po');
        $entries = $catalog->getEntries();

        $expected = array(
            'one' => array(
                'msgid' => array(0 => 'one'),
                'msgstr' => array(0 => 'uno'),
            ),
            'two' => array(
                'msgid' => array(0 => 'two'),
                'msgstr' => array(0 => 'dos'),
            ),
        );

        $this->assertEquals($entries, $expected);
    }


    /**
     *  Test for entries with multiple flags
     */
    public function testFlags()
    {
        $this->markTestSkipped();
        // Read po file with 'php-format' flag. Add 'fuzzy' flag. 
        // Compare the result with the version that has 'php-format' and 'fuzzy' flags
        $catalog = Parser::parseFile($this->resourcesPath.'flags-phpformat.po');
        $entries = $catalog->getEntries();

        foreach ($entries as $msgid => $entry) {
            $entry['flags'][] = 'fuzzy';
            $catalog->setEntry($msgid, $entry);
        }

        $catalog->writeFile($this->resourcesPath.'temp.po');
        $this->assertFileEquals($this->resourcesPath.'flags-phpformat-fuzzy.po', $this->resourcesPath.'temp.po');
    }


    /**
     *  Test for reading previous unstranslated strings
     */
    public function testPreviousUnstranslated()
    {
        $catalog = Parser::parseFile($this->resourcesPath.'previous_unstranslated.po');
        $entries = $catalog->getEntries();

        $expected = array(
            'this is a string' => array(
                'msgid' => array('this is a string'),
                'msgstr' => array('this is a translation'),
                'previous' => array(
                    'msgid' => array('this is a previous string'),
                    'msgstr' => array('this is a previous translation string'),
                ),
            ),
        );

        $this->assertEquals($entries, $expected);
    }
}
