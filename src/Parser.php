<?php

namespace Sepia\PoParser;

use Sepia\PoParser\Catalog\Catalog;
use Sepia\PoParser\Catalog\CatalogArray;
use Sepia\PoParser\Catalog\EntryFactory;
use Sepia\PoParser\Catalog\Header;
use Sepia\PoParser\Exception\ParseException;
use Sepia\PoParser\SourceHandler\FileSystem;
use Sepia\PoParser\SourceHandler\SourceHandler;
use Sepia\PoParser\SourceHandler\StringSource;

/**
 *    Copyright (c) 2012 Raúl Ferràs raul.ferras@gmail.com
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions
 *    are met:
 *    1. Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *    3. Neither the name of copyright holders nor the names of its
 *       contributors may be used to endorse or promote products derived
 *       from this software without specific prior written permission.
 *
 *    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 *    ''AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 *    TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 *    PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL COPYRIGHT HOLDERS OR CONTRIBUTORS
 *    BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 *    CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 * https://github.com/raulferras/PHP-po-parser
 *
 * Class to parse .po file and extract its strings.
 *
 * @version 5.0
 */
class Parser
{
    /** @var SourceHandler */
    protected $sourceHandler;

    /** @var int */
    protected $lineNumber;

    /** @var string */
    protected $property;

    /**
     * Reads and parses a string
     *
     * @param string $string po content
     *
     * @throws \Exception.
     * @return Catalog
     */
    public static function parseString($string)
    {
        $parser = new Parser(new StringSource($string));

        return $parser->parse();
    }

    /**
     * Reads and parses a file
     *
     * @param string $filePath
     *
     * @throws \Exception.
     * @return Catalog
     */
    public static function parseFile($filePath)
    {
        $parser = new Parser(new FileSystem($filePath));

        return $parser->parse();
    }

    public function __construct(SourceHandler $sourceHandler)
    {
        $this->sourceHandler = $sourceHandler;
    }

    /**
     * Reads and parses strings of a .po file.
     *
     * @param SourceHandler . Optional
     *
     * @throws \Exception, \InvalidArgumentException, ParseException
     * @return Catalog
     */
    public function parse(Catalog $catalog = null)
    {
        $catalog = $catalog === null ? new CatalogArray() : $catalog;
        $this->lineNumber = 0;
        $entry = array();
        $this->property = null; // current property

        // Flags
        $headersFound = false;

        while (!$this->sourceHandler->ended()) {
            $line = trim($this->sourceHandler->getNextLine());

            if ($this->shouldIgnoreLine($line, $entry)) {
                $this->lineNumber++;
                continue;
            }

            if ($this->shouldCloseEntry($line, $entry)) {
                if (!$headersFound && $this->isHeader($entry)) {
                    $headersFound = true;
                    $catalog->addHeaders(
                        $this->parseHeaders($entry['msgstr'])
                    );
                } else {
                    $catalog->addEntry(EntryFactory::createFromArray($entry));
                }

                $entry = array();
                $this->property = null;

                if (empty($line)) {
                    $this->lineNumber++;
                    continue;
                }
            }

            $entry = $this->parseLine($line, $entry);

            $this->lineNumber++;
            continue;
        }
        $this->sourceHandler->close();

        // add final entry
        if (count($entry)) {
            if ($this->isHeader($entry)) {
                $catalog->addHeaders(
                    $this->parseHeaders($entry['msgstr'])
                );
            } else {
                $catalog->addEntry(EntryFactory::createFromArray($entry));
            }
        }

        return $catalog;
    }

    /**
     * @param string $line
     * @param array  $entry
     *
     * @return array
     * @throws ParseException
     */
    protected function parseLine($line, $entry)
    {
        $firstChar = strlen($line) > 0 ? $line[0] : '';

        switch ($firstChar) {
            case '#':
                $entry = $this->parseComment($line, $entry);
                break;

            case 'm':
                $entry = $this->parseProperty($line, $entry);
                break;

            case '"':
                $entry = $this->parseMultiline($line, $entry);
                break;
        }

        return $entry;
    }

    /**
     * @param string $line
     * @param array  $entry
     *
     * @return array
     * @throws ParseException
     */
    protected function parseProperty($line, array $entry)
    {
        list($key, $value) = $this->getProperty($line);

        if (!isset($entry[$key])) {
            $entry[$key] = '';
        }

        switch (true) {
            case $key === 'msgctxt':
            case $key === 'msgid':
            case $key === 'msgid_plural':
            case $key === 'msgstr':
                $entry[$key] .= $this->unquote($value);
                $this->property = $key;
                break;

            case strpos($key, 'msgstr[') !== false:
                $entry[$key] .= $this->unquote($value);
                $this->property = $key;
                break;

            default:
                throw new ParseException(sprintf('Could not parse %s at line %d', $key, $this->lineNumber));
        }

        return $entry;
    }

    /**
     * @param string $line
     * @param array  $entry
     *
     * @return array
     * @throws ParseException
     */
    protected function parseMultiline($line, $entry)
    {
        switch (true) {
            case $this->property === 'msgctxt':
            case $this->property === 'msgid':
            case $this->property === 'msgid_plural':
            case $this->property === 'msgstr':
            case strpos($this->property, 'msgstr[') !== false:
                $entry[$this->property] .= $this->unquote($line);
                break;

            default:
                throw new ParseException(
                    sprintf('Error parsing property %s as multiline.', $this->property)
                );
        }

        return $entry;
    }

    /**
     * @param string $line
     * @param array  $entry
     *
     * @return array
     * @throws ParseException
     */
    protected function parseComment($line, $entry)
    {
        $comment = trim(substr($line, 0, 2));

        switch ($comment) {
            case '#,':
                $line = trim(substr($line, 2));
                $entry['flags'] = preg_split('/,\s*/', $line);
                break;

            case '#.':
                $entry['ccomment'] = !isset($entry['ccomment']) ? array() : $entry['ccomment'];
                $entry['ccomment'][] = trim(substr($line, 2));
                break;


            case '#|':  // Previous string
            case '#~':  // Old entry
            case '#~|': // Previous string old
                $mode = array(
                    '#|' => 'previous',
                    '#~' => 'obsolete',
                    '#~|' => 'previous-obsolete'
                );

                $line = trim(substr($line, 2));
                $property = $mode[$comment];
                if ($property === 'previous') {
                    if (!isset($entry[$property])) {
                        $subEntry = array();
                    } else {
                        $subEntry = $entry[$property];
                    }

                    $subEntry = $this->parseLine($line, $subEntry);
                    //$subEntry = $this->parseProperty($line, $subEntry);
                    $entry[$property] = $subEntry;
                } else {
                    $entry = $this->parseLine($line, $entry);
                    $entry['obsolete'] = true;
                }
                break;

            // Reference
            case '#:':
                $entry['reference'][] = trim(substr($line, 2));
                break;

            case '#':
            default:
                $entry['tcomment'] = !isset($entry['tcomment']) ? array() : $entry['tcomment'];
                $entry['tcomment'][] = trim(substr($line, 1));
                break;
        }

        return $entry;
    }

    /**
     * @param string $msgstr
     *
     * @return Header
     */
    protected function parseHeaders($msgstr)
    {
        $headers = array_filter(explode('\\n', $msgstr));

        return new Header($headers);
    }

    /**
     * @param string $line
     * @param array  $entry
     *
     * @return bool
     */
    protected function shouldIgnoreLine($line, array $entry)
    {
        return empty($line) && count($entry) === 0;
    }

    /**
     * @param string $line
     * @param array  $entry
     *
     * @return bool
     */
    protected function shouldCloseEntry($line, array $entry)
    {
        $tokens = $this->getProperty($line);
        $property = $tokens[0];

        return ($line === '' || ($property === 'msgid' && isset($entry['msgid'])));
    }

    /**
     * @param string $value
     * @return string
     */
    protected function unquote($value)
    {
        return preg_replace('/^\"|\"$/', '', $value);
    }

    /**
     * Checks if entry is a header by
     *
     * @param array $entry
     *
     * @return bool
     */
    protected function isHeader(array $entry)
    {
        if (empty($entry) || !isset($entry['msgstr'])) {
            return false;
        }

        if (!isset($entry['msgid']) || !empty($entry['msgid'])) {
            return false;
        }

        $standardHeaders = array(
            'Project-Id-Version:',
            'Report-Msgid-Bugs-To:',
            'POT-Creation-Date:',
            'PO-Revision-Date:',
            'Last-Translator:',
            'Language-Team:',
            'MIME-Version:',
            'Content-Type:',
            'Content-Transfer-Encoding:',
            'Plural-Forms:',
        );

        $headers = explode('\n', $entry['msgstr']);
        // Remove text after double colon
        $headers = array_map(
            function ($header) {
                $pattern = '/(.*?:)(.*)/i';
                $replace = '${1}';
                return preg_replace($pattern, $replace, $header);
            },
            $headers
        );

        if (count(array_intersect($standardHeaders, $headers)) > 0) {
            return true;
        }

        // If it does not contain any of the standard headers
        // Let's see if it contains any custom header.
        $customHeaders = array_filter(
            $headers,
            function ($header) {
                return preg_match('/^X\-(.*):/i', $header) === 1;
            }
        );

        return count($customHeaders) > 0;
    }

    /**
     * @param string $line
     *
     * @return array
     */
    protected function getProperty($line)
    {
        $tokens = preg_split('/\s+/ ', $line, 2);

        return $tokens;
    }
}
