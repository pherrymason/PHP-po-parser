<?php

namespace Sepia\PoParser;

use Sepia\PoParser\Catalog\Entry;

class PoCompiler
{
    const TOKEN_OBSOLETE = '#~ ';
    /** @var int */
    protected $wrappingColumn;

    /** @var string */
    protected $lineEnding;

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

        if (count($catalog->getHeaders()) > 0) {
            $output .= 'msgid ""'.$this->eol();
            $output .= 'msgstr ""'.$this->eol();
            foreach ($catalog->getHeaders() as $header) {
                $output .= $header.$this->eol();
            }
            $output .= $this->eol();
        }


        $entriesCount = count($catalog->getEntries());
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
            $output .= $this->buildMsgStr($entry);


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

        $output = '#| msgid '.$this->cleanExport($previous->getMsgId()).$this->eol();

        /*
        foreach ($entry['previous'] as $key => $data) {

            if (is_string($data)) {
                $output .= '#| '.$key.' '.$this->cleanExport($data).$this->eol();
            } elseif (is_array($data) && count($data) > 0) {
                foreach ($data as $line) {
                    $output .= '#| '.$key.' '.$this->cleanExport($line).$this->eol();
                }
            }
        }*/

        return $output;
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
        if ($reference === null || count($reference) === 0) {
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
        if ($flags === null || count($flags) === 0) {
            return '';
        }

        return '#, '.implode(', ', $flags).$this->eol();
    }

    protected function buildContext(Entry $entry)
    {
        if ($entry->getMsgCtxt() === null) {
            return '';
        }

        return
            ($entry->isObsolete() ? '#~ ' : '' ).
            'msgctxt '.$this->cleanExport($entry->getMsgCtxt()).$this->eol();
    }

    protected function buildMsgId(Entry $entry)
    {
        if ($entry->getMsgId() === null) {
            return '';
        }

        $msgId = $entry->getMsgId();

        $output = '';
        $tokens = $this->wrapString($msgId);

        if (count($tokens) > 1) {
            array_unshift($tokens, '');
        }

        foreach ($tokens as $i => $token) {
            if ($entry->isObsolete()) {
                $output .= self::TOKEN_OBSOLETE;
            }

            if ($i === 0) {
                $output.= 'msgid ';
            }

            $output .= $this->cleanExport($token).$this->eol();
        }

        return $output;
    }

    protected function buildMsgStr(Entry $entry)
    {
        $value = $entry->getMsgStr();
        $plurals = $entry->getMsgStrPlurals();

        if ($value === null && $plurals === null) {
            return '';
        }

        if ($entry->isPlural()) {
            $output = '';
            foreach ($plurals as $i => $line) {
                $output .= 'msgstr['.$i.'] '.$this->cleanExport($line).$this->eol();
            }

            return $output;
        }

        $tokens = $this->wrapString($value);

        $output = '';
        if (count($tokens) > 1) {
            array_unshift($tokens, '');
        }

        foreach ($tokens as $i => $token) {
            if ($entry->isObsolete()) {
                $output .= self::TOKEN_OBSOLETE;
            }

            if ($i === 0) {
                $output.= 'msgstr ';
            }

            $output .= $this->cleanExport($token).$this->eol();
        }

        return $output;
        /*
        if (count(preg_grep('/^msgstr/', array_keys($entry)))) { // checks if there is a key starting with msgstr
            if ($isPlural) {
                $noTranslation = true;
                foreach ($entry as $key => $value) {
                    if (strpos($key, 'msgstr[') === false) {
                        continue;
                    }
                    $output .= $key." ";
                    $noTranslation = false;
                    foreach ($value as $i => $t) {
                        $output .= $this->cleanExport($t).$this->eol();
                    }
                }
                if ($noTranslation) {
                    $output .= 'msgstr[0] '.$this->cleanExport('').$this->eol();
                    $output .= 'msgstr[1] '.$this->cleanExport('').$this->eol();
                }
            } else {
                foreach ((array)$entry['msgstr'] as $i => $t) {
                    if ($i == 0) {
                        if ($entry->isObsolete()) {
                            $output .= "#~ ";
                        }

                        $output .= 'msgstr '.$this->cleanExport($t).$this->eol();
                    } else {
                        if ($entry->isObsolete()) {
                            $output .= "#~ ";
                        }

                        $output .= $this->cleanExport($t).$this->eol();
                    }
                }
            }
        }
        */
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

        return 'msgid_plural '.$this->cleanExport($value).$this->eol();
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
        $quote = '"';
        $slash = '\\';
        $newline = "\n";

        $replaces = array(
            "$slash" => "$slash$slash",
            "$quote" => "$slash$quote",
            "\t" => '\t',
        );

        $string = str_replace(array_keys($replaces), array_values($replaces), $string);

        $po = $quote.implode("${slash}n$quote$newline$quote", explode($newline, $string)).$quote;

        // remove empty strings
        return str_replace("$newline$quote$quote", '', $po);
    }

    /**
     * @param string $value
     *
     * @return array
     */
    private function wrapString($value)
    {
        if (strlen($value) > $this->wrappingColumn) {
            $tokens = str_split($value, $this->wrappingColumn);
        } else {
            $tokens = array($value);
        }

        return $tokens;
    }
}
