<?php

namespace Sepia\PoParser\Catalog;

interface Catalog
{
    public function addEntry(Entry $entry);

    public function addHeaders(Header $headers);

    /**
     * @param string      $msgid
     * @param string|null $msgctxt
     */
    public function removeEntry($msgid, $msgctxt = null);

    /**
     * @return array
     */
    public function getHeaders();

    /**
     * @return Header
     */
    public function getHeader();

    /**
     * @return Entry[]
     */
    public function getEntries();

    /**
     * @param string      $msgId
     * @param string|null $context
     *
     * @return Entry
     */
    public function getEntry($msgId, $context = null);
}