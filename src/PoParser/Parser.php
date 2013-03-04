<?php

namespace PoParser;

class Parser
{
    /**
     * @var PoEntry[]
     */
    protected $entries = array();

    /**
     * @var array
     */
    protected $entriesAsArrays = array();

    /**
     * @return PoEntry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @return array
     */
    public function getEntriesAsArrays()
    {
        return $this->entriesAsArrays;
    }

    /**
     * Reads and parses strings in a .po file.

        \return An array of entries located in the file:
        Format: array(
            'msgid'		=> <string> ID of the message.
            'msgctxt'	=> <string> Message context.
            'msgstr'	=> <string> Message translation.
            'tcomment'	=> <string> Comment from translator.
            'ccomment'	=> <string> Extracted comments from code.
            'reference'	=> <string> Location of string in code.
            'obsolete'  => <bool> Is the message obsolete?
            'fuzzy'		=> <bool> Is the message "fuzzy"?
            'flags'		=> <string> Flags of the entry. Internal usage.
        )

        \todo: What means the line "#@ "???

        #~ (old entry)
        # @ default
        #, fuzzy
        #~ msgid "Editar datos"
        #~ msgstr "editar dades"
     *
     * @param $filePath
     *
     * @return array|bool
     * @throws Exception
     */
    public function read($filePath)
    {
        if (empty($filePath)) {
            throw new Exception('Input File not defined.');
        } else {
            if (file_exists($filePath) === false) {
                throw new Exception('File does not exist: "' . htmlspecialchars($filePath) . '".');
            } else {
                if (is_readable($filePath) === false) {
                    throw new Exception('File is not readable.');
                }
            }
        }

        $handle = fopen($filePath, 'r');
        $hash = array();
        $fuzzy = false;
        $tcomment = $ccomment = $reference = null;
        $entry = $entryTemp = array();
        $state = null;
        $justNewEntry = false; // A new entry has ben just inserted


        while (!feof($handle)) {
            $line = trim(fgets($handle));

            if ($line === '') {
                if ($justNewEntry) {
                    // Two consecutive blank lines
                    continue;
                }

                // A new entry is found!
                $hash[] = $entry;
                $entry = array();
                $state = null;
                $justNewEntry = true;
                continue;
            }

            $justNewEntry = false;

            $split = preg_split('/\s/ ', $line, 2);
            $key = $split[0];
            $data = isset($split[1]) ? $split[1] : null;

            switch ($key) {
                case '#,':
                    //flag
                    $entry['fuzzy'] = in_array('fuzzy', preg_split('/,\s*/', $data));
                    $entry['flags'] = $data;
                    break;
                case '#':
                    //translation-comments
                    $entryTemp['tcomment'] = $data;
                    $entry['tcomment'] = $data;
                    break;
                case '#.':
                    //extracted-comments
                    $entryTemp['ccomment'] = $data;
                    break;
                case '#:':
                    //reference
                    $entryTemp['reference'][] = addslashes($data);
                    $entry['reference'][] = addslashes($data);
                    break;
                case '#|':
                    //msgid previous-untranslated-string
                    // start a new entry
                    break;
                case '#@':
                    // ignore #@ default
                    $entry['@'] = $data;
                    break;
                // old entry
                case '#~':
                    $tmpParts = explode(' ', $data);
                    $tmpKey = $tmpParts[0];
                    $str = implode(' ', array_slice($tmpParts, 1));
                    $entry['obsolete'] = true;
                    switch ($tmpKey) {
                        case 'msgid':
                            $entry['msgid'] = trim($str, '"');
                            break;
                        case 'msgstr':
                            $entry['msgstr'][] = trim($str, '"');
                            break;
                        default:
                            break;
                    }

                    continue;
                    break;
                case 'msgctxt':
                    // context
                case 'msgid':
                    // untranslated-string
                case 'msgid_plural':
                    // untranslated-string-plural
                    $state = $key;
                    $entry[$state] = $data;
                    break;
                case 'msgstr':
                    // translated-string
                    $state = 'msgstr';
                    $entry[$state][] = $data;
                    break;
                default:
                    if (strpos($key, 'msgstr[') !== false) {
                        // translated-string-case-n
                        $state = 'msgstr';
                        $entry[$state][] = $data;
                    } else {
                        // continued lines
                        //echo "O NDE ELSE:".$state.':'.$entry['msgid'];
                        switch ($state) {
                            case 'msgctxt':
                            case 'msgid':
                            case 'msgid_plural':
                                //$entry[$state] .= "\n" . $line;
                                if (is_string($entry[$state])) {
                                    // Convert it to array
                                    $entry[$state] = array($entry[$state]);
                                }
                                $entry[$state][] = $line;
                                break;
                            case 'msgstr':
                                //Special fix where msgid is ""
                                if ($entry['msgid'] == "\"\"") {
                                    $entry['msgstr'][] = trim($line, '"');
                                } else {
                                    //$entry['msgstr'][sizeof($entry['msgstr']) - 1] .= "\n" . $line;
                                    $entry['msgstr'][] = trim($line, '"');
                                }
                                break;
                            default:
                                throw new Exception('Parse error!');
                        }
                    }
                    break;
            }
        }
        fclose($handle);

        // add final entry
        if ($state == 'msgstr') {
            $hash[] = $entry;
        }

        // Cleanup data, merge multiline entries, reindex hash for ksort
        $temp = $hash;
        $this->entriesAsArrays = array();
        $this->entries = array();
        foreach ($temp as $entry) {
            foreach ($entry as & $v) {
                $v = $this->clean($v);
                if ($v === false) {
                    // parse error
                    return false;
                }
            }

            $id = is_array($entry['msgid']) ? implode('', $entry['msgid']) : $entry['msgid'];

            $this->entriesAsArrays[$id] = $entry;
            $this->entries[$id] = new Entry($entry);
        }

        return $this->entriesAsArrays;
    }


    /**
     * Allows modification a msgid.
     * By default disabled fuzzy flag if defined.
     * todo Allow change any of the fields of the entry.
     *
     * @param $original
     * @param $translation
     */
    public function updateEntry($original, $translation)
    {
        $this->entriesAsArrays[$original]['fuzzy'] = false;
        $this->entriesAsArrays[$original]['msgstr'] = array($translation);

        if (isset($this->entriesAsArrays[$original]['flags'])) {
            $flags = $this->entriesAsArrays[$original]['flags'];
            $this->entriesAsArrays[$original]['flags'] = str_replace('fuzzy', '', $flags);
        }
    }


    /**
     * Writes entries into the po file.

        It writes the entries stored in the object.
        Example:

        1. Parse the file:
            $pofile = new PoParser();
            $pofile->read('myfile.po');

        2. Modify those entries you want.
            $pofile->updateEntry($msgid, $mgstr);

        3. Save changes
            $pofile->write('myfile.po');
     *
     * @param $filePath
     */
    public function write($filePath)
    {
        $handle = @fopen($filePath, "wb");

        //	fwrite( $handle, "\xEF\xBB\xBF" );	//UTF-8 BOM header

        $entriesCount = count($this->entriesAsArrays);
        $counter = 0;
        foreach ($this->entriesAsArrays as $entry) {
            $counter++;

            $isObsolete = isset($entry['obsolete']) && $entry['obsolete'];
            $isPlural = isset($entry['msgid_plural']);

            if (isset($entry['tcomment'])) {
                fwrite($handle, "# " . $entry['tcomment'] . "\n");
            }

            if (isset($entry['ccomment'])) {
                fwrite($handle, '#. ' . $entry['ccomment'] . "\n");
            }

            if (isset($entry['reference'])) {
                foreach ($entry['reference'] as $ref) {
                    fwrite($handle, '#: ' . $ref . "\n");
                }
            }

            if (isset($entry['flags']) && !empty($entry['flags'])) {
                fwrite($handle, "#, " . $entry['flags'] . "\n");
            }

            if (isset($entry['@'])) {
                fwrite($handle, "#@ " . $entry['@'] . "\n");
            }

            if (isset($entry['msgctxt'])) {
                fwrite($handle, 'msgctxt ' . $entry['msgctxt'] . "\n");
            }

            if ($isObsolete) {
                fwrite($handle, "#~ ");
            }

            if (isset($entry['msgid'])) {
                // Special clean for msgid
                $msgid = explode("\n", $entry['msgid']);

                fwrite($handle, 'msgid ');
                foreach ($msgid as $i => $id) {
                    fwrite($handle, $this->cleanExport($id) . "\n");
                }
            }

            if (isset($entry['msgid_plural'])) {
                fwrite($handle, 'msgid_plural ' . $this->cleanExport($entry['msgid_plural']) . "\n");
            }

            if (isset($entry['msgstr'])) {
                if ($isPlural) {
                    foreach ($entry['msgstr'] as $i => $t) {
                        if ($isObsolete) {
                            fwrite($handle, "#~ ");
                        }
                        fwrite($handle, "msgstr[$i] " . $this->cleanExport($t) . "\n");
                    }
                } else {
                    foreach ($entry['msgstr'] as $i => $t) {
                        if ($i == 0) {
                            if ($isObsolete) {
                                fwrite($handle, "#~ ");
                            }
                            fwrite($handle, 'msgstr ' . $this->cleanExport($t) . "\n");
                        } else {
                            fwrite($handle, $this->cleanExport($t) . "\n");
                        }
                    }
                }
            }

            if ($counter != $entriesCount) {
                fwrite($handle, "\n");
            }
        }

        fclose($handle);
    }

    /**
     *
     */
    public function clearFuzzy()
    {
        foreach ($this->entriesAsArrays as &$str) {
            if (isset($str['fuzzy']) && $str['fuzzy'] == true) {
                $flags = $str['flags'];
                $str['flags'] = str_replace('fuzzy', '', $flags);
                $str['fuzzy'] = false;
                $str['msgstr'] = array('');
            }
        }
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    protected function cleanExport($string)
    {
        $quote = '"';
        $slash = '\\';
        $newline = "\n";

        $replaces = array(
            "$slash" => "$slash$slash",
            "$quote" => "$slash$quote",
            "\t"     => '\t',
        );

        $string = str_replace(array_keys($replaces), array_values($replaces), $string);

        $po = $quote . implode("${slash}n$quote$newline$quote", explode($newline, $string)) . $quote;

        // remove empty strings
        return str_replace("$newline$quote$quote", '', $po);
    }

    /**
     * @param $x
     *
     * @return array|string
     */
    public function clean($x)
    {
        if (is_array($x)) {
            foreach ($x as $k => $v) {
                $x[$k] = $this->clean($v);
            }
        } else {
            // Remove " from start and end
            if ($x == '') {
                return '';
            }

            if ($x[0] == '"') {
                $x = substr($x, 1, -1);
            }

            $x = stripcslashes($x);
        }

        return $x;
    }
}