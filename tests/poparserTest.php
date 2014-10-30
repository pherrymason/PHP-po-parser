<?php

namespace Sepia;

class PoParserTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown() {
        parent::tearDown();

        if (file_exists(__DIR__ . '/pofiles/temp.po')) {
            unlink(__DIR__ . '/pofiles/temp.po');
        }
    }

    public function testRead()
    {
        $poparser = new PoParser();
        try {
            $result = $poparser->parseFile(__DIR__ . '/pofiles/healthy.po');
        } catch (\Exception $e) {
            $result = array();
            $this->fail($e->getMessage());
        }

        $this->assertCount(2, $result);


        // Read file without headers.
        // It should not skip first entry
        $poparser = new PoParser();
        try {
            $result = $poparser->parseFile(__DIR__ . '/pofiles/noheader.po');
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
        $poparser = new PoParser();
        try {
            $poparser->parseFile(__DIR__ . '/pofiles/healthy.po');
            $headers = $poparser->getHeaders();

            $this->assertCount(18, $headers);
            $this->assertEquals("\"Project-Id-Version: \\n\"", $headers[0]);
            $this->assertEquals("\"Report-Msgid-Bugs-To: \\n\"", $headers[1]);
            $this->assertEquals("\"POT-Creation-Date: 2013-09-25 15:55+0100\\n\"", $headers[2]);
            $this->assertEquals("\"PO-Revision-Date: \\n\"", $headers[3]);
            $this->assertEquals("\"Last-Translator: Raúl Ferràs <xxxxxxxxxx@xxxxxxx.xxxxx>\\n\"", $headers[4]);
            $this->assertEquals("\"Language-Team: \\n\"", $headers[5]);
            $this->assertEquals("\"MIME-Version: 1.0\\n\"", $headers[6]);
            $this->assertEquals("\"Content-Type: text/plain; charset=UTF-8\\n\"", $headers[7]);
            $this->assertEquals("\"Content-Transfer-Encoding: 8bit\\n\"", $headers[8]);
            $this->assertEquals("\"Plural-Forms: nplurals=2; plural=n != 1;\\n\"", $headers[9]);
            $this->assertEquals("\"X-Poedit-SourceCharset: UTF-8\\n\"", $headers[10]);
            $this->assertEquals("\"X-Poedit-KeywordsList: __;_e;_n;_t\\n\"", $headers[11]);
            $this->assertEquals("\"X-Textdomain-Support: yes\\n\"", $headers[12]);
            $this->assertEquals("\"X-Poedit-Basepath: .\\n\"", $headers[13]);
            $this->assertEquals("\"X-Generator: Poedit 1.5.7\\n\"", $headers[14]);
            $this->assertEquals("\"X-Poedit-SearchPath-0: .\\n\"", $headers[15]);
            $this->assertEquals("\"X-Poedit-SearchPath-1: ../..\\n\"", $headers[16]);
            $this->assertEquals("\"X-Poedit-SearchPath-2: ../../../modules\\n\"", $headers[17]);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
//			$this->assertTrue( false, $e->getMessage() );
        }
    }


    public function testMultilineId()
    {
        $poparser = new PoParser();
        try {
            $result = $poparser->parseFile(__DIR__ . '/pofiles/multilines.po');
            $headers = $poparser->getHeaders();

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
        $poparser = new PoParser();
        try {
            $result = $poparser->parseFile(__DIR__ . '/pofiles/plurals.po');
            $headers = $poparser->getHeaders();

            $this->assertCount(7, $headers);
            $this->assertCount(15, $result);
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }


    /**
     *    Test Writing file
     */
    public function testWrite()
    {
        // Read & write a simple file
        $pofile = new PoParser();
        $pofile->parseFile(__DIR__ . '/pofiles/healthy.po');
        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $this->assertFileEquals(__DIR__ . '/pofiles/healthy.po', __DIR__ . '/pofiles/temp.po');

        // Read & write a file with no headers
        $pofile = new PoParser();
        $pofile->parseFile(__DIR__ . '/pofiles/noheader.po');
        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $this->assertFileEquals(__DIR__ . '/pofiles/noheader.po', __DIR__ . '/pofiles/temp.po');

        // Read & write a po file with multilines
        $pofile = new PoParser();
        $pofile->parseFile(__DIR__ . '/pofiles/multilines.po');
        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $this->assertFileEquals(__DIR__ . '/pofiles/multilines.po', __DIR__ . '/pofiles/temp.po');

        // Read & write a po file with contexts
        $pofile = new PoParser();
        $pofile->parseFile(__DIR__ . '/pofiles/context.po');
        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $this->assertFileEquals(__DIR__ . '/pofiles/context.po', __DIR__ . '/pofiles/temp.po');


        // Read & write a po file with previous unstranslated strings
        $pofile = new PoParser();
        $pofile->parseFile( __DIR__ . '/pofiles/previous_unstranslated.po' );
        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $this->assertFileEquals(__DIR__ . '/pofiles/previous_unstranslated.po', __DIR__.'/pofiles/temp.po');

        // Read & write a po file with multiple flags
        $pofile = new PoParser();
        $pofile->parseFile(__DIR__ . '/pofiles/multiflags.po');
        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $this->assertFileEquals(__DIR__ . '/pofiles/multiflags.po', __DIR__.'/pofiles/temp.po');


        unlink(__DIR__ . '/pofiles/temp.po');
    }

    /**
     * Test update entry, update plural forms
     */
    public function testUpdatePlurals()
    {
        $msgid = '%s post not updated, somebody is editing it.';
        $msgstr = array(
            "%s entrada no actualizada, alguien la está editando...",
            "%s entradas no actualizadas, alguien las está editando..."
        );

        $pofile = new PoParser();
        $pofile->parseFile(__DIR__ . '/pofiles/plurals.po');

        $pofile->updateEntry($msgid, $msgstr);

        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $tmpPofile = new PoParser();
        $newPlurals = $tmpPofile->parseFile(__DIR__ . '/pofiles/temp.po');
        $this->assertEquals($newPlurals[$msgid]['msgstr'], $msgstr);
    }

    /**
     * Test update comments
     */
    public function testUpdateComments()
    {
        $pofile = new PoParser();
        $options = $pofile->getOptions();
        $ctxtGlue = $options['context-glue'];

        $msgid = 'Background Attachment'.$ctxtGlue.'Attachment';
        $ccomment = 'Test write ccomment';
        $tcomment = 'Test write tcomment';

        $pofile->parseFile(__DIR__ . '/pofiles/context.po');

        $pofile->updateEntry($msgid, null, $tcomment, $ccomment);

        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $tmpPofile = new PoParser();
        $newParsedArray = $tmpPofile->parseFile(__DIR__ . '/pofiles/temp.po');

        $this->assertEquals($newParsedArray[$msgid]['tcomment'][0], $tcomment);
        $this->assertEquals($newParsedArray[$msgid]['ccomment'][0], $ccomment);
    }

    /**
     * Test update with fuzzy flag
     */
    public function testUpdateWithFuzzy()
    {
        $msgid = '%1$s-%2$s';
        $msgstr = 'translate';

        $pofile = new PoParser();
        $pofile->parseFile(__DIR__ . '/pofiles/context.po');

        $pofile->updateEntry($msgid, $msgstr);
    }

    /**
     * Test for success update headers
     */
    public function testUpdateHeaders()
    {
        $pofile = new PoParser();
        $pofile->parseFile(__DIR__.'/pofiles/context.po');

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
            '"Plural-Forms: nplurals=2; plural=n != 1;\n"'
        );

        $result = $pofile->setHeaders($newHeaders);
        $this->assertTrue($result);
        $pofile->writeFile(__DIR__ . '/pofiles/temp.po');

        $newPoFile = new PoParser();
        $newPoFile->parseFile(__DIR__ . '/pofiles/temp.po');
        $readHeaders = $newPoFile->getHeaders();
        $this->assertEquals($newHeaders, $readHeaders);
    }

    /**
     * Test for fail update headers
     */
    public function testUpdateHeadersWrong()
    {
        $pofile = new PoParser();
        $result = $pofile->setHeaders('header');
        $this->assertFalse($result);
    }

    /**
     * Test for po files with no blank lines between entries
     */
    public function testNoBlankLines()
	{
		$pofile = new PoParser();
		$entries = $pofile->parseFile( __DIR__ . '/pofiles/noblankline.po' );

		$expected = array(
            'one' => array(
			    'msgid' => array(0 => 'one'),
			    'msgstr' => array(0 => 'uno'),
			 ),
			'two' => array(
			  'msgid' => array( 0 => 'two'),
			  'msgstr' => array( 0 => 'dos')
			  )
		);

		$this->assertEquals( $entries, $expected );
	}




    /**
     *  Test for entries with multiple flags
     */
    /*
    public function testFlags()
    {
    
    }
    */


    /**
     *  Test for reading previous unstranslated strings
     */
    public function testPreviousUnstranslated()
    {
        $pofile = new PoParser();
        $entries = $pofile->parseFile( __DIR__ . '/pofiles/previous_unstranslated.po' );

        $expected = array(
            'this is a string' => array(
                'msgid' => array('this is a string'),
                'msgstr'=> array('this is a translation'),
                'previous' => array(
                    'msgid' => array('this is a previous string'),
                    'msgstr'=> array('this is a previous translation string')
                )
            )
        );

        $this->assertEquals( $entries, $expected );
    }
}
