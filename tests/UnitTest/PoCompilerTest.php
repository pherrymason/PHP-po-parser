<?php

namespace Sepia\Test\UnitTest;

use PHPUnit\Framework\TestCase;
use Sepia\PoParser\Catalog\CatalogArray;
use Sepia\PoParser\PoCompiler;
use Sepia\Test\EntryBuilder;

class PoCompilerTest extends TestCase
{
    /** @test */
    public function should_compile_single_line_translation()
    {
        $catalog = new CatalogArray([
            EntryBuilder::anEntry()
                ->withId('a-message')
                ->withTranslation('hello fellow ant')
                ->withContext('context 1')
                ->withReference(['src/views/forms.php:44'])
                ->withTranslatorComment(['translator comment'])
                ->withDeveloperComment(['developer comment'])
                ->withFlags(['1','2','3'])
                ->withPreviousEntry(
                    EntryBuilder::anEntry()
                        ->withId('previous.string.1')
                        ->withContext('previous context')
                        ->build()
                )
                ->build(),

            EntryBuilder::anEntry()
                ->withId('second message')
                ->withTranslation('segón missatge')
                ->build()
        ]);

        $compiler = new PoCompiler();
        $output = $compiler->compile($catalog);

        $this->assertEquals(
            <<<POFILE
            #| msgid "previous.string.1"
            # translator comment
            #. developer comment
            #: src/views/forms.php:44
            #, 1, 2, 3
            msgctxt "context 1"
            msgid "a-message"
            msgstr "hello fellow ant"
            
            msgid "second message"
            msgstr "segón missatge"
            
            POFILE
            , $output);
    }

    /** @test */
    public function should_compile_obsolete_translation()
    {
        $catalog = new CatalogArray([
            EntryBuilder::anEntry()
                ->withId('a-message')
                ->withTranslation('hello fellow ant')
                ->obsolete()
                ->build()
        ]);

        $compiler = new PoCompiler();
        $output = $compiler->compile($catalog);

        $this->assertEquals(
            <<<POFILE
            #~ msgid "a-message"
            #~ msgstr "hello fellow ant"
            
            POFILE
            , $output);
    }

    /** @test */
    public function should_compile_multiple_line_translation()
    {
        $catalog = new CatalogArray([
            EntryBuilder::anEntry()
                ->withId('a-message')
                ->withTranslation('hello fellow ant')
                ->build()
        ]);

        $compiler = new PoCompiler();
        $output = $compiler->compile($catalog);

        $this->assertEquals(
            <<<POFILE
            msgid "a-message"
            msgstr "hello fellow ant"
            
            POFILE
            , $output);
    }

    /** @test */
    public function should_compile_translation_with_plurals()
    {
        $catalog = new CatalogArray([
            EntryBuilder::anEntry()
                ->withId('a-message')
                ->withPluralId('a-message %d')
                ->withTranslation('hello fellow ant')
                ->withPluralTranslation(0, 'translation plural 0')
                ->withPluralTranslation(1, 'translation plural 1')
                ->withPluralTranslation(2, 'translation plural 2')
                ->build()
        ]);

        $compiler = new PoCompiler();
        $output = $compiler->compile($catalog);

        $this->assertEquals(
            <<<POFILE
            msgid "a-message"
            msgid_plural "a-message %d"
            msgstr[0] "translation plural 0"
            msgstr[1] "translation plural 1"
            msgstr[2] "translation plural 2"
            
            POFILE
            , $output);
    }

    /** @test */
    public function should_compile_obsolete_plurals()
    {
        $catalog = new CatalogArray([
            EntryBuilder::anEntry()
                ->withId('a-message')
                ->withPluralId('%d obsolete strings')
                ->withTranslation('hello fellow ant')
                ->withPluralTranslation(0, 'translation plural 0')
                ->withPluralTranslation(1, 'translation plural 1')
                ->withPluralTranslation(2, 'translation plural 2')
                ->obsolete()
                ->build()
        ]);

        $compiler = new PoCompiler();
        $output = $compiler->compile($catalog);

        $this->assertEquals(
            <<<POFILE
            #~ msgid "a-message"
            #~ msgid_plural "%d obsolete strings"
            #~ msgstr[0] "translation plural 0"
            #~ msgstr[1] "translation plural 1"
            #~ msgstr[2] "translation plural 2"
            
            POFILE
            , $output);
    }

    /** @test */
    public function should_compile_escaping_special_chars()
    {
        $catalog = new CatalogArray([
            EntryBuilder::anEntry()
                ->withId('a\"b\"c')
                ->withTranslation('quotes')
                ->build(),

            EntryBuilder::anEntry()
                ->withId('a\nb\nc')
                ->withTranslation('slashes')
                ->build(),

            EntryBuilder::anEntry()
                ->withId("a\nb\nc")
                ->withTranslation("proper\nlinebreaks")
                ->build(),
        ]);

        $compiler = new PoCompiler();
        $output = $compiler->compile($catalog);

        $this->assertEquals(
            'msgid "a\\\\\"b\\\\\"c"
msgstr "quotes"

msgid "a\\\\nb\\\\nc"
msgstr "slashes"

msgid "a\nb\nc"
msgstr "proper\nlinebreaks"
', $output);
    }

    /**
     * @test
     * @dataProvider wrappingDataProvider
     */
    public function should_compile_translation_with_wrapping_long_lines(string $value, int $wrappingColumn, bool $shouldWrapLines, array $assert)
    {
        // Make sure that encoding is set to UTF-8 for this test
        \mb_internal_encoding();
        \mb_internal_encoding('UTF-8');

        $catalog = new CatalogArray([
            EntryBuilder::anEntry()
                ->withId('a-message')
                ->withTranslation($value)
                ->build()
        ]);

        $compiler = new PoCompiler($wrappingColumn);
        $output = $compiler->compile($catalog);

        $expected = 'msgid "a-message"'."\n";
        if ($shouldWrapLines) {
            $expected .= 'msgstr ""' . "\n";
        } else {
            $expected.= 'msgstr ';
        }
        foreach ($assert as $line) {
            $expected.= '"'.$line.'"'."\n";
        }

        $this->assertEquals($expected, $output);
    }

    public function wrappingDataProvider(): array
    {
        return array(
            'Multibyte Wrap (char 81)' => array(
                'value' => 'Hello everybody, Hello ladies and gentlemen.... this is a multibyte translation á with a multibyte beginning at char 81.',
                'wrappingColumn' => 80,
                'shouldWrap' => true,
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen.... this is a multibyte translation ',
                    'á with a multibyte beginning at char 81.'
                ),
            ),
            'Multibyte Wrap (char 80)' => array(
                'value' => 'Hello everybody, Hello ladies and gentlemen... this is a multibyte translation á with a multibyte beginning at char 80.',
                'wrappingColumn' => 80,
                'shouldWrap' => true,
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen... this is a multibyte translation á',
                    ' with a multibyte beginning at char 80.'
                ),
            ),
            'Multibyte Wrap (char 79)' => array(
                'value' => 'Hello everybody, Hello ladies and gentlemen.. this is a multibyte translation á with multibytes beginning at char 79.',
                'wrappingColumn' => 80,
                'shouldWrap' => true,
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen.. this is a multibyte translation á ',
                    'with multibytes beginning at char 79.'
                ),
            ),
            'Escape-Sequence Wrap (char 80+81)' => array(
                'value' => 'Hello everybody, Hello ladies and gentlemen..... this is a line with more than \"eighty\" chars. And char 80+81 is an escaped double quote.',
                'wrappingColumn' => 80,
                'shouldWrap' => true,
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen..... this is a line with more than ',
                    '\\\\\"eighty\\\\\" chars. And char 80+81 is an escaped double quote.'
                ),
            ),
            'Escape-Sequence Wrap (char 79+80)' => array(
                'value' => 'Hello everybody, Hello ladies and gentlemen.... this is a line with more than \"eighty\" chars. And char 79+80 is an escaped double quote.',
                'wrappingColumn' => 80,
                'shouldWrap' => true,
                'assert' => array(
                    'Hello everybody, Hello ladies and gentlemen.... this is a line with more than ',
                    '\\\\\"eighty\\\\\" chars. And char 79+80 is an escaped double quote.'
                ),
            ),
            /*    'Escaped Line-break' => array(
                    'value' => 'Hello everybody, \\nHello ladies and gentlemen.',
                    'wrappingColumn' => 80,
                    'assert' => array(
                        'Hello everybody, \\\\nHello ladies and gentlemen.'
                    ),
                ),
              */  'String with a lot of multibyte characters should not break when wrappingColumn is at its mb_strlen' => array(
                'value' => 'kategóriáját kötelező',
                'wrappingColumn' => 21,
                'shouldWrap' => false,
                'assert' => array(
                    'kategóriáját kötelező'
                ),
            ),
        );
    }
}