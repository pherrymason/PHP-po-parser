<?php

namespace Sepia\PoParser\Catalog;

class EntryFactory
{
    /**
     * @param array $entryArray
     * @return Entry
     */
    public static function createFromArray(array $entryArray)
    {
        $entry = new Entry(
            $entryArray['msgid'],
            isset($entryArray['msgstr']) ? $entryArray['msgstr'] : null
        );
        $plurals = array();

        foreach ($entryArray as $key => $value) {
            switch (true) {
                case $key === 'msgctxt':
                    $entry->setMsgCtxt($entryArray['msgctxt']);
                    break;

                case $key === 'flags':
                    $entry->setFlags($entryArray['flags']);
                    break;

                case $key === 'reference':
                    $entry->setReference($entryArray['reference']);
                    break;

                case $key === 'previous':
                    $entry->setPreviousEntry(self::createFromArray($entryArray['previous']));
                    break;

                case $key === 'tcomment':
                    $entry->setTranslatorComments($value);
                    break;

                case $key === 'ccomment':
                    $entry->setDeveloperComments($value);
                    break;

                case $key === 'obsolete':
                    $entry->setObsolete(true);
                    break;

                case 0 === strpos($key, 'msgstr['):
                    $plurals[] = $value;
                    break;
            }
        }

        if (count($plurals) > 0) {
            $entry->setMsgStrPlurals($plurals);
            if(!empty($entryArray['msgid_plural'])){
                $entry->setMsgIdPlural($entryArray['msgid_plural']);
            }
        }

        return $entry;
    }
}
