<?php

namespace Sepia\PoParser\Catalog;

class CatalogArray implements Catalog
{
    /** @var Header */
    protected $headers;

    /** @var array */
    protected $entries;

    /**
     * @param Entry[] $entries
     */
    public function __construct(array $entries = array())
    {
        $this->entries = array();
        $this->headers = new Header();
        foreach ($entries as $entry) {
            $this->addEntry($entry);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addEntry(Entry $entry)
    {
        $key = $this->getEntryHash(
            $entry->getMsgId(),
            $entry->getMsgCtxt()
        );
        $this->entries[$key] = $entry;
    }

    /**
     * {@inheritdoc}
     */
    public function addHeaders(Header $headers)
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function removeEntry($msgid, $msgctxt = null)
    {
        $key = $this->getEntryHash($msgid, $msgctxt);
        if (isset($this->entries[$key])) {
            unset($this->entries[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers->asArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntry($msgId, $context = null)
    {
        $key = $this->getEntryHash($msgId, $context);
        if (!isset($this->entries[$key])) {
            return null;
        }

        return $this->entries[$key];
    }

    /**
     * @param string      $msgId
     * @param string|null $context
     *
     * @return string
     */
    private function getEntryHash($msgId, $context = null)
    {
        return md5($msgId.$context);
    }
}
