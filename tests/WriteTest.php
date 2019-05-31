<?php

namespace Sepia\Test;

use Exception;
use Faker\Factory;
use ReflectionClass;
use ReflectionException;
use Sepia\PoParser\Catalog\Catalog;
use Sepia\PoParser\Catalog\CatalogArray;
use Sepia\PoParser\Catalog\EntryFactory;
use Sepia\PoParser\PoCompiler;
use Sepia\PoParser\SourceHandler\FileSystem;

class WriteTest extends AbstractFixtureTest
{
    public function testWrite()
    {
        $faker = Factory::create();
        $catalogSource = new CatalogArray();

        // Normal Entry
        $entry = EntryFactory::createFromArray(array(
            'msgid' => 'string.1',
            'msgstr' => 'translation.1',
            'msgctxt' => 'context.1',
            'reference' => array('src/views/forms.php:44'),
            'tcomment' => array('translator comment'),
            'ccomment' => array('code comment'),
            'flags' => array('1', '2', '3')
        ));
        $previousEntry = EntryFactory::createFromArray(array(
           'msgid' => 'previous.string.1',
           'msgctxt' => 'previous.context.1'
        ));
        $entry->setPreviousEntry($previousEntry);
        $catalogSource->addEntry($entry);

        // Obsolete entry
        $entry = EntryFactory::createFromArray(array(
            'msgid' => 'obsolete.1',
            'msgstr' => $faker->paragraph(5),
            'msgctxt' => 'obsolete.context',
            'obsolete' => true
        ));
        $catalogSource->addEntry($entry);

        try {
            $this->saveCatalog($catalogSource);
        } catch (Exception $e) {
            $this->fail('Cannot save catalog.');
        }

        $catalog = $this->parseFile('temp.po');
        $this->assertPoFile($catalogSource, $catalog);
    }

    public function testWritePlurals()
    {
        $catalogSource = new CatalogArray();
        // Normal Entry
        $entry = EntryFactory::createFromArray(array(
            'msgid' => 'string.1',
            'msgstr' => 'translation.1',
            'msgstr[0]' => 'translation.plural.0',
            'msgstr[1]' => 'translation.plural.1',
            'msgstr[2]' => 'translation.plural.2',
            'reference' => array('src/views/forms.php:44'),
            'tcomment' => array('translator comment'),
            'ccomment' => array('code comment'),
            'flags' => array('1', '2', '3')
        ));

        $catalogSource->addEntry($entry);

        try {
            $this->saveCatalog($catalogSource);
        } catch (Exception $e) {
            $this->fail('Cannot save catalog.');
        }
        $catalog = $this->parseFile('temp.po');
        $entry = $catalog->getEntry('string.1');
        $this->assertCount(3, $entry->getMsgStrPlurals());
    }

    public function testDoubleEscaped()
    {
        $catalogSource = new CatalogArray();
        // Normal Entry
        $entry = EntryFactory::createFromArray(array(
            'msgid' => 'a\"b\"c',
            'msgstr' => 'quotes'
        ));
        $catalogSource->addEntry($entry);

        $entry = EntryFactory::createFromArray(array(
            'msgid' => 'a\nb\nc',
            'msgstr' => 'linebreaks'
        ));
        $catalogSource->addEntry($entry);

        try {
            $this->saveCatalog($catalogSource);
        } catch (Exception $e) {
            $this->fail('Cannot save catalog.');
        }

        $catalog = $this->parseFile('temp.po');
        $this->assertCount(2, $catalog->getEntries());
        $this->assertNotNull($catalog->getEntry('a\"b\"c'));
        $this->assertNotNull($catalog->getEntry('a\nb\nc'));
    }

    public function testWrapping()
    {

        // Make sure that encoding is set to UTF-8 for this test
        $mbEncoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');

        $class = new ReflectionClass('\Sepia\PoParser\PoCompiler');
        try {
            // Use Reflection and make private method accessible...
            $method = $class->getMethod('wrapString');
            $method->setAccessible(true);
            $compiler = new PoCompiler();

        } catch (ReflectionException $e) {
            $this->fail('Method wrapString not found in PoCompiler');
            return;
        }

        $tests = array(
            // Test Multibyte Wrap (char 80)
            array(
                'value' => 'Hello everybody, Hello ladies and gentlemen... this is a multibyte translation á with a multibyte beginning at char 80.',
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen... this is a multibyte translation ',
                    'á with a multibyte beginning at char 80.'
                ),
            ),
            // Test Multibyte Wrap (char 79)
            array(
                'value' => 'Hello everybody, Hello ladies and gentlemen.. this is a multibyte translation á with multibytes beginning at char 79.',
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen.. this is a multibyte translation á ',
                    'with multibytes beginning at char 79.'
                ),
            ),
            // Test Escape-Sequence Wrap (char 80+81)
            array(
                'value' => 'Hello everybody, Hello ladies and gentlemen..... this is a line with more than \"eighty\" chars. And char 80+81 is an escaped double quote.',
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen..... this is a line with more than ',
                    '\"eighty\" chars. And char 80+81 is an escaped double quote.'
                ),
            ),
            // Test Escape-Sequence Wrap (char 79+80)
            array(
                'value' => 'Hello everybody, Hello ladies and gentlemen.... this is a line with more than \"eighty\" chars. And char 79+80 is an escaped double quote.',
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen.... this is a line with more than ',
                    '\"eighty\" chars. And char 79+80 is an escaped double quote.'
                ),
            ),
            // Test Escaped Line-break
            array(
                'value' => 'Hello everybody, \\nHello ladies and gentlemen.',
                'assert' => array(
                    'Hello everybody, \\nHello ladies and gentlemen.'
                ),
            ),

        );

        // Test if the wrapping equals the assert
        foreach($tests as $test) {
            // Test the private method
            $res = $method->invokeArgs($compiler, array($test['value']));
            $this->assertEquals($test['assert'], $res);
        }


        // Create a po-file with all the test-values as msgid and a fake translation as msgstr
        // And test if the entry could be fetched and the translation equals the msgstr.

        $faker = Factory::create();
        $catalogSource = new CatalogArray();

        foreach($tests as &$test) {

            $test['translation'] = $faker->paragraph(5);

            $entry = EntryFactory::createFromArray(array(
                'msgid' => $test['value'],
                'msgstr' => $test['translation']
            ));

            $catalogSource->addEntry($entry);
        }
        unset($test);
        try {
            $this->saveCatalog($catalogSource);
        } catch (Exception $e) {
            $this->fail('Cannot save catalog');
        }

        $catalog = $this->parseFile('temp.po');
        foreach($tests as $test) {

            $entry = $catalog->getEntry($test['value']);

            $this->assertNotNull($entry);
            $this->assertEquals($test['translation'], $entry->getMsgStr());
        }


        // Revert encoding to previous setting
        mb_internal_encoding($mbEncoding);
    }


    public function testWriteObsoletePlural()
    {

        $catalogSource = new CatalogArray();

        // Obsolete entry
        $entry = EntryFactory::createFromArray(array(
            'msgid' => '%d obsolete string',
            'msgid_plural' => '%d obsolete strings',
            'msgstr' => 'translation.2',
            'msgstr[0]' => 'translation.plural.0',
            'msgstr[1]' => 'translation.plural.1',
            'msgstr[2]' => 'translation.plural.2',
            'reference' => array('src/views/forms.php:45'),
            'tcomment' => array('translator comment'),
            'ccomment' => array('code comment'),
            'flags' => array('fuzzy'),
            'obsolete' => true
        ));

        $catalogSource->addEntry($entry);

        try {
            $this->saveCatalog($catalogSource);
        } catch (Exception $e) {
            $this->fail('Cannot save catalog');
        }

        $written_contents = file_get_contents($this->resourcesPath.'temp.po');

        $eol = "\n";

        $expected_contents = '' .
            '#, fuzzy' . $eol .
            '#~ msgid "%d obsolete string"' . $eol .
            '#~ msgid_plural "%d obsolete strings"' . $eol .
            '#~ msgstr[0] "translation.plural.0"' . $eol .
            '#~ msgstr[1] "translation.plural.1"' . $eol .
            '#~ msgstr[2] "translation.plural.2"' . $eol;

        $this->assertEquals($expected_contents, $written_contents);

    }

    /**
     * @param Catalog $catalog
     * @param int $wrappingColumn
     * @throws Exception
     */
    protected function saveCatalog(Catalog $catalog, $wrappingColumn = 80)
    {
        $fileHandler = new FileSystem($this->resourcesPath.'temp.po');
        $compiler = new PoCompiler($wrappingColumn);
        $fileHandler->save($compiler->compile($catalog));
    }

    private function assertPoFile(CatalogArray $catalogSource, Catalog $catalogNew)
    {
        foreach ($catalogSource->getEntries() as $entry) {
            $entryWritten = $catalogNew->getEntry($entry->getMsgId(), $entry->getMsgCtxt());

            $this->assertNotNull($entryWritten, 'Entry not found:'.$entry->getMsgId().','.$entry->getMsgCtxt());

            $this->assertEquals($entry->getMsgStr(), $entryWritten->getMsgStr());
            $this->assertEquals($entry->getMsgCtxt(), $entryWritten->getMsgCtxt());
            $this->assertEquals($entry->getFlags(), $entryWritten->getFlags());
            $this->assertEquals($entry->isObsolete(), $entryWritten->isObsolete());

            if ($entry->isObsolete() === true) {
                $this->assertEmpty($entryWritten->getReference());
                $this->assertEmpty($entryWritten->getTranslatorComments());
                $this->assertEmpty($entryWritten->getDeveloperComments());
            } else {
                $this->assertEquals($entry->getReference(), $entryWritten->getReference());
                $this->assertEquals($entry->getDeveloperComments(), $entryWritten->getDeveloperComments());
                $this->assertEquals($entry->getTranslatorComments(), $entryWritten->getTranslatorComments());
            }
        }
    }

    public function tearDown()
    {
        parent::tearDown();

        //if (file_exists($this->resourcesPath.'temp.po')) {
        //    unlink($this->resourcesPath.'temp.po');
        //}
    }
}
