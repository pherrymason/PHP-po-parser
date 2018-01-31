<?php

namespace Sepia;

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
 * @version 4.2.2
 */
class PoParser
{
    protected $entries = array();
    protected $headers = array();
    protected $sourceHandle = null;
    protected $options = array();
    protected $lineEndings = array('unix' => "\n", 'win' => "\r\n");

    /**
     * Reads and parses a string
     *
     * @param string $string po content
     * @param array  $options
     *
     * @throws \Exception.
     * @return PoParser
     */
    public static function parseString($string, $options = array())
    {
        $parser = new PoParser(new StringHandler($string), $options);
        $parser->parse();

        return $parser;
    }


    /**
     * Reads and parses a file
     *
     * @param string $filepath
     * @param array  $options
     *
     * @throws \Exception.
     * @return PoParser
     */
    public static function parseFile($filepath, $options = array())
    {
        $parser = new PoParser(new FileHandler($filepath), $options);
        $parser->parse();

        return $parser;
    }


    public function __construct(InterfaceHandler $handler = null, $options = array())
    {
        $this->sourceHandle = $handler;
        $defaultOptions = array(
            'multiline-glue' => '<##EOL##>',  // Token used to separate lines in msgid
            'context-glue' => '<##EOC##>',  // Token used to separate ctxt from msgid
            'line-ending' => 'unix',
        );
        $this->options = array_merge($defaultOptions, $options);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Reads and parses strings of a .po file.
     *
     * @param InterfaceHandler . Optional
     *
     * @throws \Exception, \InvalidArgumentException
     * @return array. List of entries found in .po file.
     */
    public function parse(InterfaceHandler $handle = null)
    {
        if ($handle === null) {

            if ($this->sourceHandle === null) {
                throw new \InvalidArgumentException('Must provide a valid InterfaceHandler');
            }

            $handle = $this->sourceHandle;
        }

        $headers = array();
        $hash = array();
        $entry = array();
        $justNewEntry = false; // A new entry has been just inserted.
        $firstLine = true;
        $lastPreviousKey = null; // Used to remember last key in a multiline previous entry.
        $state = null;
        $lineNumber = 0;

        while (!$handle->ended()) {
            $line = trim($handle->getNextLine());
            $split = preg_split('/\s+/ ', $line, 2);
            $key = $split[0];

            // If a blank line is found, or a new msgid when already got one
            if ($line === '' || ($key === 'msgid' && isset($entry['msgid']))) {
                // Two consecutive blank lines
                if ($justNewEntry) {
                    $lineNumber++;
                    continue;
                }

                if ($firstLine) {
                    $firstLine = false;
                    if (self::isHeader($entry)) {
                        array_shift($entry['msgstr']);
                        $headers = $entry['msgstr'];
                    } else {
                        $hash[] = $entry;
                    }
                } else {
                    // A new entry is found!
                    $hash[] = $entry;
                }

                $entry = array();
                $state = null;
                $justNewEntry = true;
                $lastPreviousKey = null;
                if ($line === '') {
                    $lineNumber++;
                    continue;
                }
            }

            $justNewEntry = false;
            $data = isset($split[1]) ? $split[1] : null;

            switch ($key) {
                // Flagged translation
                case '#,':
                    $entry['flags'] = preg_split('/,\s*/', $data);
                    break;

                // # Translator comments
                case '#':
                    $entry['tcomment'] = !isset($entry['tcomment']) ? array() : $entry['tcomment'];
                    $entry['tcomment'][] = $data;
                    break;

                // #. Comments extracted from source code
                case '#.':
                    $entry['ccomment'] = !isset($entry['ccomment']) ? array() : $entry['ccomment'];
                    $entry['ccomment'][] = $data;
                    break;

                // Reference
                case '#:':
                    $entry['reference'][] = addslashes($data);
                    break;


                case '#|':      // #| Previous untranslated string
                case '#~':      // #~ Old entry
                case '#~|':     // #~| Previous-Old untranslated string. Reported by @Cellard

                    switch ($key) {
                        case '#|':
                            $key = 'previous';
                            break;
                        case '#~':
                            $key = 'obsolete';
                            break;
                        case '#~|':
                            $key = 'previous-obsolete';
                            break;
                    }

                    $tmpParts = explode(' ', $data);
                    $tmpKey = $tmpParts[0];

                    if (!in_array($tmpKey, array('msgid', 'msgid_plural', 'msgstr', 'msgctxt'))) {
                        $tmpKey = $lastPreviousKey; // If there is a multiline previous string we must remember what key was first line.
                        $str = $data;
                    } else {
                        $str = implode(' ', array_slice($tmpParts, 1));
                    }

                    $entry[$key] = isset($entry[$key]) ? $entry[$key] : array('msgid' => array(), 'msgstr' => array());

                    if (strpos($key, 'obsolete') !== false) {
                        $entry['obsolete'] = true;
                        switch ($tmpKey) {
                            case 'msgid':
                                $entry['msgid'][] = $str;
                                $lastPreviousKey = $tmpKey;
                                break;

                            case 'msgstr':
                                if ($str === "\"\"") {
                                    $entry['msgstr'][] = trim($str, '"');
                                } else {
                                    $entry['msgstr'][] = $str;
                                }
                                $lastPreviousKey = $tmpKey;
                                break;

                            default:
                                break;
                        }
                    }

                    if ($key !== 'obsolete') {
                        switch ($tmpKey) {
                            case 'msgid':
                            case 'msgid_plural':
                            case 'msgstr':
                                $entry[$key][$tmpKey][] = $str;
                                $lastPreviousKey = $tmpKey;
                                break;

                            default:
                                $entry[$key][$tmpKey] = $str;
                                break;
                        }
                    }
                    break;


                // context
                // Allows disambiguations of different messages that have same msgid.
                // Example:
                //
                // #: tools/observinglist.cpp:700
                // msgctxt "First letter in 'Scope'"
                // msgid "S"
                // msgstr ""
                //
                // #: skycomponents/horizoncomponent.cpp:429
                // msgctxt "South"
                // msgid "S"
                // msgstr ""
                case 'msgctxt':
                    // untranslated-string
                case 'msgid':
                    // untranslated-string-plural
                case 'msgid_plural':
                    $state = $key;
                    $entry[$state][] = $data;
                    break;
                // translated-string
                case 'msgstr':
                    $state = 'msgstr';
                    $entry[$state][] = $data;
                    break;

                default:
                    if (strpos($key, 'msgstr[') !== false) {
                        // translated-string-case-n
                        $state = $key;
                        $entry[$state][] = $data;
                    } else {
                        // "multiline" lines
                        switch ($state) {
                            case 'msgctxt':
                            case 'msgid':
                            case 'msgid_plural':
                            case (strpos($state, 'msgstr[') !== false):
                                if (is_string($entry[$state])) {
                                    // Convert it to array
                                    $entry[$state] = array($entry[$state]);
                                }
                                $entry[$state][] = $line;
                                break;

                            case 'msgstr':
                                // Special fix where msgid is ""
                                if ($entry['msgid'] === "\"\"") {
                                    $entry['msgstr'][] = trim($line, '"');
                                } else {
                                    $entry['msgstr'][] = $line;
                                }
                                break;

                            default:
                                throw new \Exception(
                                    'PoParser: Parse error! Unknown key "'.$key.'" on line '.($lineNumber + 1)
                                );
                        }
                    }
                    break;
            }

            $lineNumber++;
        }
        $handle->close();

        // add final entry
        if ($state === 'msgstr') {
            $hash[] = $entry;
        }

        // - Cleanup header data
        $this->headers = array();
        foreach ($headers as $header) {
            $header = $this->clean($header);
            $this->headers[] = "\"".preg_replace("/\\n/", '\n', $header)."\"";
        }

        // - Cleanup data,
        // - merge multiline entries
        // - Reindex hash for ksort
        $temp = $hash;
        $this->entries = array();
        foreach ($temp as $entry) {
            foreach ($entry as &$v) {
                $or = $v;
                $v = $this->clean($v);
                if ($v === false) {
                    // parse error
                    throw new \Exception(
                        'PoParser: Parse error! poparser::clean returned false on "'.htmlspecialchars($or).'"'
                    );
                }
            }

            // check if msgid and a key starting with msgstr exists
            if (isset($entry['msgid']) && count(preg_grep('/^msgstr/', array_keys($entry)))) {
                $id = $this->getEntryId($entry);
                $this->entries[$id] = $entry;
            }
        }

        return $this->entries;
    }

    /**
     * Get headers from .po file
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set new headers
     *
     * {code}
     *  array(
     *   '"Project-Id-Version: \n"',
     *   '"Report-Msgid-Bugs-To: \n"',
     *   '"POT-Creation-Date: \n"',
     *   '"PO-Revision-Date: \n"',
     *   '"Last-Translator: none\n"',
     *   '"Language-Team: \n"',
     *   '"MIME-Version: 1.0\n"',
     *   '"Content-Type: text/plain; charset=UTF-8\n"',
     *  );
     * {code}
     *
     * @param array $newHeaders
     *
     * @return bool
     */
    public function setHeaders($newHeaders)
    {
        if (!is_array($newHeaders)) {
            return false;
        } else {
            $this->headers = $newHeaders;

            return true;
        }
    }


    /**
     * Updates an entry.
     * If entry not found returns false. If $createNew is true, a new entry will be created.
     * $entry is an array that can contain following indexes:
     *  - msgid: String Array. Required.
     *  - msgstr: String Array. Required.
     *  - reference: String Array.
     *  - msgctxt: String. Disambiguating context.
     *  - tcomment: String Array. Translator comments.
     *  - ccomment: String Array. Source comments.
     *  - msgid_plural: String Array.
     *  - flags: Array. List of entry flags. Example: array('fuzzy','php-format')
     *  - previous: Array: Contains previous untranslated strings in a sub array with msgid and msgstr.
     *
     * @param String  $msgid     Id of entry. Be aware that some entries have a multiline msgid. In that case \n must
     *                           be replaced by the value of 'multiline-glue' option (by default "<##EOL##>").
     * @param Array   $entry     Array with all entry data. Fields not setted will be removed.
     * @param boolean $createNew If msgid not found, it will create a new entry. By default true. You want to set this
     *                           to false if need to change the msgid of an entry.
     */
    public function setEntry($msgid, $entry, $createNew = true)
    {
        // In case of new entry
        if (!isset($this->entries[$msgid])) {

            if ($createNew == false) {
                return;
            }

            $this->entries[$msgid] = $entry;
        } else {
            // Be able to change msgid.
            if ($msgid !== $entry['msgid']) {
                unset($this->entries[$msgid]);
                $new_msgid = is_array($entry['msgid']) ? implode(
                    $this->options['multiline-glue'],
                    $entry['msgid']
                ) : $entry['msgid'];
                $this->entries[$new_msgid] = $entry;
            } else {
                $this->entries[$msgid] = $entry;
            }
        }
    }


    public function setEntryPlural($msgid, $plural = false)
    {
        if ($plural) {
            $this->entries[$msgid]['msgid_plural'] = $plural;
        } else {
            unset($this->entries[$msgid]['msgid_plural']);
        }
    }

    public function setEntryContext($msgid, $context = false)
    {
        if ($context) {
            $this->entries[$msgid]['msgctxt'][0] = $context;
        } else {
            unset($this->entries[$msgid]['msgctxt']);
        }
    }


    /**
     * Gets entries.
     */
    public function entries()
    {
        return $this->entries;
    }


    /**
     * Writes entries to a po file
     *
     * @example
     *        $pofile = new PoParser();
     *        $pofile->parse('ca.po');
     *
     *        // Modify an antry
     *        $pofile->setEntry( $msgid, $msgstr );
     *        // Save Changes back into `ca.po`
     *        $pofile->writeFile('ca.po');
     *
     * @param string $filepath
     *
     * @throws \Exception
     * @return boolean
     */
    public function writeFile($filepath)
    {
        $output = $this->compile();
        $result = file_put_contents($filepath, $output);
        if ($result === false) {
            throw new \Exception('Could not write into file '.htmlspecialchars($filepath));
        }

        return true;
    }


    /**
     * Compiles entries into a string
     *
     * @throws \Exception
     * @return string
     */
    public function compile()
    {
        $output = '';

        if (count($this->headers) > 0) {
            $output .= "msgid \"\"".$this->eol();
            $output .= "msgstr \"\"".$this->eol();
            foreach ($this->headers as $header) {
                $output .= $header.$this->eol();
            }
            $output .= $this->eol();
        }


        $entriesCount = count($this->entries);
        $counter = 0;
        foreach ($this->entries as $entry) {
            $isObsolete = isset($entry['obsolete']) && $entry['obsolete'];
            $isPlural = isset($entry['msgid_plural']);

            if (isset($entry['previous'])) {
                foreach ($entry['previous'] as $key => $data) {

                    if (is_string($data)) {
                        $output .= "#| ".$key." ".$this->cleanExport($data).$this->eol();
                    } elseif (is_array($data) && count($data) > 0) {
                        foreach ($data as $line) {
                            $output .= "#| ".$key." ".$this->cleanExport($line).$this->eol();
                        }
                    }

                }
            }

            if (isset($entry['tcomment'])) {
                foreach ($entry['tcomment'] as $comment) {
                    $output .= "# ".$comment.$this->eol();
                }
            }

            if (isset($entry['ccomment'])) {
                foreach ($entry['ccomment'] as $comment) {
                    $output .= '#. '.$comment.$this->eol();
                }
            }

            if (isset($entry['reference'])) {
                foreach ($entry['reference'] as $ref) {
                    $output .= '#: '.$ref.$this->eol();
                }
            }

            if (isset($entry['flags']) && !empty($entry['flags'])) {
                $output .= "#, ".implode(', ', $entry['flags']).$this->eol();
            }

            if (isset($entry['@'])) {
                $output .= "#@ ".$entry['@'].$this->eol();
            }

            if (isset($entry['msgctxt'])) {
                $output .= 'msgctxt '.$this->cleanExport($entry['msgctxt'][0]).$this->eol();
            }


            if ($isObsolete) {
                $output .= "#~ ";
            }

            if (isset($entry['msgid'])) {
                // Special clean for msgid
                if (is_string($entry['msgid'])) {
                    $msgid = explode($this->eol(), $entry['msgid']);
                } elseif (is_array($entry['msgid'])) {
                    $msgid = $entry['msgid'];
                } else {
                    throw new \Exception('msgid not string or array');
                }

                $output .= 'msgid ';
                foreach ($msgid as $i => $id) {
                    if ($i > 0 && $isObsolete) {
                        $output .= "#~ ";
                    }
                    $output .= $this->cleanExport($id).$this->eol();
                }
            }

            if (isset($entry['msgid_plural'])) {
                // Special clean for msgid_plural
                if (is_string($entry['msgid_plural'])) {
                    $msgidPlural = explode($this->eol(), $entry['msgid_plural']);
                } elseif (is_array($entry['msgid_plural'])) {
                    $msgidPlural = $entry['msgid_plural'];
                } else {
                    throw new \Exception('msgid_plural not string or array');
                }

                $output .= 'msgid_plural ';
                foreach ($msgidPlural as $plural) {
                    $output .= $this->cleanExport($plural).$this->eol();
                }
            }

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
                            if ($isObsolete) {
                                $output .= "#~ ";
                            }

                            $output .= 'msgstr '.$this->cleanExport($t).$this->eol();
                        } else {
                            if ($isObsolete) {
                                $output .= "#~ ";
                            }

                            $output .= $this->cleanExport($t).$this->eol();
                        }
                    }
                }
            }

            $counter++;
            // Avoid inserting an extra newline at end of file
            if ($counter < $entriesCount) {
                $output .= $this->eol();
            }
        }

        return $output;
    }

    /**
     * Returns configured line ending (option 'line-ending' ['win', 'unix'])
     *
     * @return string
     */
    protected function eol()
    {
        return $this->lineEndings[$this->options['line-ending']] ?: "\n";
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
     * Generates the internal key for a msgid.
     *
     * @param array $entry
     *
     * @return string
     */
    protected function getEntryId(array $entry)
    {
        if (isset($entry['msgctxt'])) {
            $id = implode(
                    $this->options['multiline-glue'],
                    (array)$entry['msgctxt']
                ).$this->options['context-glue'].implode($this->options['multiline-glue'], (array)$entry['msgid']);
        } else {
            $id = implode($this->options['multiline-glue'], (array)$entry['msgid']);
        }

        return $id;
    }


    /**
     * Undos `cleanExport` actions on a string.
     *
     * @param string|array $x
     *
     * @return string|array.
     */
    protected function clean($x)
    {
        if (is_array($x)) {
            foreach ($x as $k => $v) {
                $x[$k] = $this->clean($v);
            }
        } else {
            // Remove double quotes from start and end of string
            if ($x == '') {
                return '';
            }

            if ($x[0] == '"') {
                $x = substr($x, 1, -1);
            }

            // Escapes C-style escape sequences (\a,\b,\f,\n,\r,\t,\v) and converts them to their actual meaning
            $x = stripcslashes($x);

        }

        return $x;
    }


    /**
     * Checks if entry is a header by
     *
     * @param array $entry
     *
     * @return bool
     */
    protected static function isHeader(array $entry)
    {
        if (empty($entry) || !isset($entry['msgstr'])) {
            return false;
        }

        $headerKeys = array(
            'Project-Id-Version:' => false,
            //  'Report-Msgid-Bugs-To:' => false,
            //  'POT-Creation-Date:'    => false,
            'PO-Revision-Date:' => false,
            //  'Last-Translator:'      => false,
            //  'Language-Team:'        => false,
            'MIME-Version:' => false,
            //  'Content-Type:'         => false,
            //  'Content-Transfer-Encoding:' => false,
            //  'Plural-Forms:'         => false
        );
        $count = count($headerKeys);
        $keys = array_keys($headerKeys);

        $headerItems = 0;
        foreach ($entry['msgstr'] as $str) {
            $tokens = explode(':', $str);
            $tokens[0] = trim($tokens[0], "\"").':';

            if (in_array($tokens[0], $keys)) {
                $headerItems++;
                unset($headerKeys[$tokens[0]]);
                $keys = array_keys($headerKeys);
            }
        }

        return ($headerItems == $count) ? true : false;
    }
}
