<?php

namespace Sepia\Test;

use Sepia\PoParser\Catalog;
use Sepia\PoParser\PoCompiler;
use Sepia\PoParser\PoReader\FileHandler;

class WriteTest extends AbstractFixtureTest
{
    public function testWrite()
    {
        $faker = \Faker\Factory::create();
        $catalogSource = new Catalog();

        // Normal Entry
        $entry = Catalog\EntryFactory::createFromArray(array(
            'msgid' => 'string.1',
            'msgstr' => 'translation.1',
            'msgctxt' => 'context.1',
            'reference' => array('src/views/forms.php:44'),
            'tcomment' => array('translator comment'),
            'ccomment' => array('code comment'),
            'flags' => array('1', '2', '3')
        ));
        $previousEntry = Catalog\EntryFactory::createFromArray(array(
           'msgid' => 'previous.string.1',
           'msgctxt' => 'previous.context.1'
        ));
        $entry->setPreviousEntry($previousEntry);
        $catalogSource->addEntry($entry);

        // Obsolete entry
        $entry = Catalog\EntryFactory::createFromArray(array(
            'msgid' => 'obsolete.1',
            'msgstr' => $faker->paragraph(5),
            'msgctxt' => 'obsolete.context',
            'obsolete' => true
        ));
        $catalogSource->addEntry($entry);

        $this->saveCatalog($catalogSource);

        $catalog = $this->parseFile('temp.po');
        $this->assertPoFile($catalogSource, $catalog);
    }

    /**
     * @throws \Exception
     */
    protected function saveCatalog(Catalog $catalog)
    {
        $fileHandler = new FileHandler($this->resourcesPath.'temp.po');
        $compiler = new PoCompiler();
        $fileHandler->save(
            $compiler->compile($catalog),
            $this->resourcesPath.'temp.po'
        );
    }

    private function assertPoFile(Catalog $catalogSource, Catalog $catalogNew)
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

        if (file_exists($this->resourcesPath.'temp.po')) {
        //    unlink($this->resourcesPath.'temp.po');
        }
    }
}
