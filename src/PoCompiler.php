<?php

namespace Sepia\PoParser;

use Sepia\PoParser\Catalog\Catalog;
use Sepia\PoParser\Catalog\Entry;
use Sepia\PoParser\Catalog\Header;

class PoCompiler
{
    /** @var string */
    const TOKEN_OBSOLETE = '#~ ';

    /** @var int */
    protected $wrappingColumn;

    /** @var string */
    protected $lineEnding;

    /** @var string */
    protected $tokenCarriageReturn;

    /**
     * PoCompiler constructor.
     *
     * @param int    $wrappingColumn
     * @param string $lineEnding
     */
    public function __construct($wrappingColumn = 80, $lineEnding = "\n")
    {
        $this->wrappingColumn = $wrappingColumn;
        $this->lineEnding = $lineEnding;
        $this->tokenCarriageReturn = chr(13);
    }

    /**
     * Compiles entries into a string
     *
     * @param Catalog $catalog
     *
     * @return string
     * @throws \Exception
     * @todo Write obsolete messages at the end of the file.
     */
    public function compile(Catalog $catalog)
    {
        $output = '';

        if (\count($catalog->getHeaders()) > 0) {
            $output .= 'msgid ""'.$this->eol();
            $output .= 'msgstr ""'.$this->eol();
            foreach ($catalog->getHeaders() as $header) {
                $output .= '"'.$header.'\n"'.$this->eol();
            }
            $output .= $this->eol();
        }


        $entriesCount = \count($catalog->getEntries());
        $counter = 0;
        foreach ($catalog->getEntries() as $entry) {
            if ($entry->isObsolete() === false) {
                $output .= $this->buildPreviousEntry($entry);
                $output .= $this->buildTranslatorComment($entry);
                $output .= $this->buildDeveloperComment($entry);
                $output .= $this->buildReference($entry);
            }

            $output .= $this->buildFlags($entry);

//            if (isset($entry['@'])) {
//                $output .= "#@ ".$entry['@'].$this->eol();
//            }

            $output .= $this->buildContext($entry);
            $output .= $this->buildMsgId($entry);
            $output .= $this->buildMsgIdPlural($entry);
            $output .= $this->buildMsgStr($entry, $catalog->getHeader());


            $counter++;
            // Avoid inserting an extra newline at end of file
            if ($counter < $entriesCount) {
                $output .= $this->eol();
            }
        }

        return $output;
    }

    /**
     * @return string
     */
    protected function eol()
    {
        return $this->lineEnding;
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function buildPreviousEntry(Entry $entry)
    {
        $previous = $entry->getPreviousEntry();
        if ($previous === null) {
            return '';
        }

        return '#| msgid '.$this->cleanExport($previous->getMsgId()).$this->eol();
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function buildTranslatorComment(Entry $entry)
    {
        if ($entry->getTranslatorComments() === null) {
            return '';
        }

        $output = '';
        foreach ($entry->getTranslatorComments() as $comment) {
            $output .= '# '.$comment.$this->eol();
        }

        return $output;
    }

    protected function buildDeveloperComment(Entry $entry)
    {
        if ($entry->getDeveloperComments() === null) {
            return '';
        }

        $output = '';
        foreach ($entry->getDeveloperComments() as $comment) {
            $output .= '#. '.$comment.$this->eol();
        }

        return $output;
    }

    protected function buildReference(Entry $entry)
    {
        $reference = $entry->getReference();
        if ($reference === null || \count($reference) === 0) {
            return '';
        }

        $output = '';
        foreach ($reference as $ref) {
            $output .= '#: '.$ref.$this->eol();
        }

        return $output;
    }

    protected function buildFlags(Entry $entry)
    {
        $flags = $entry->getFlags();
        if ($flags === null || \count($flags) === 0) {
            return '';
        }

        return '#, '.\implode(', ', $flags).$this->eol();
    }

    protected function buildContext(Entry $entry)
    {
        if ($entry->getMsgCtxt() === null) {
            return '';
        }

        return
            ($entry->isObsolete() ? '#~ ' : '').
            'msgctxt '.$this->cleanExport($entry->getMsgCtxt()).$this->eol();
    }

    protected function buildMsgId(Entry $entry)
    {
        if ($entry->getMsgId() === null) {
            return '';
        }

        return $this->buildProperty('msgid', $entry->getMsgId(), $entry->isObsolete());
    }

    protected function buildMsgStr(Entry $entry, Header $headers)
    {
        $value = $entry->getMsgStr();
        $plurals = $entry->getMsgStrPlurals();

        if ($value === null && $plurals === null) {
            return '';
        }

        if ($entry->isPlural()) {
            $output = '';
            $nPlurals = $headers->getPluralFormsCount();
            $pluralsFound = \count($plurals);
            $maxIterations = \max($nPlurals, $pluralsFound);
            for ($i = 0; $i < $maxIterations; $i++) {
                $value = isset($plurals[$i]) ? $plurals[$i] : '';
                $output .= $this->buildProperty('msgstr['.$i.']', $value, $entry->isObsolete());
            }

            return $output;
        }

        return $this->buildProperty('msgstr', $value, $entry->isObsolete());
    }

    /**
     * @param Entry $entry
     *
     * @return string
     */
    protected function buildMsgIdPlural(Entry $entry)
    {
        $value = $entry->getMsgIdPlural();
        if ($value === null) {
            return '';
        }

        return $this->buildProperty('msgid_plural', $value, $entry->isObsolete());
    }

    protected function buildProperty($property, $value, $obsolete = false)
    {
        $tokens = $this->wrapString($value);

        $output = '';
        if (\count($tokens) > 1) {
            \array_unshift($tokens, '');
        }

        foreach ($tokens as $i => $token) {
            $output .= $obsolete ? self::TOKEN_OBSOLETE : '';
            $output .= ($i === 0) ? $property.' ' : '';
            $output .= $this->cleanExport($token).$this->eol();
        }

        return $output;
    }

    /**
     * Prepares a string to be outputed into a file.
     *
     * @param string $string The string to be converted.
     *
     * @return string
     */
    protected function cleanExport($string)
    {
        /**
         * Replace newline character with $this->tokenCarriageReturn that is later replaced back and thus
         * newline won't be escaped by addcslashes function
         */
        $string = str_replace("\n", $this->tokenCarriageReturn , $string);

        // only quotation mark (" or \42) and backslash (\ or \134) chars needs to be escaped
        $string = sprintf('"%s"', addcslashes($string, "\42\134"));

        // Replace newline character with \n after addcslashes to prevent escaping backslash.
        return str_replace($this->tokenCarriageReturn, '\n', $string);
    }

    /**
     * @param string $value
     * @return array
     */
    private function wrapString($value)
    {
        $length = mb_strlen($value);
        if ($length <= $this->wrappingColumn) {
            return array($value);
        }

        $lines = array();
        $parts = preg_split('/( )/', $value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $lineIndex = 0;
        foreach ($parts as $part) {
            if (
                array_key_exists($lineIndex, $lines)
                && mb_strlen($lines[$lineIndex] . $part) > $this->wrappingColumn
            ) {
                $lineIndex++;
            }

            if (!array_key_exists($lineIndex, $lines)) {
                $lines[$lineIndex] = '';
            }

            $lines[$lineIndex] .= $part;
        }

        return $lines;
    }
}
