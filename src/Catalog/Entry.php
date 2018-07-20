<?php

namespace Sepia\PoParser\Catalog;

class Entry
{
    /** @var string */
    protected $msgId;

    /** @var string */
    protected $msgStr;

    /** @var string */
    protected $msgIdPlural;

    /** @var string[] */
    protected $msgStrPlurals;

    /** @var string|null */
    protected $msgCtxt;

    /** @var Entry|null */
    protected $previousEntry;

    /** @var bool */
    protected $obsolete;

    /** @var array */
    protected $flags;

    /** @var array */
    protected $translatorComments;

    /** @var array */
    protected $developerComments;

    /** @var array */
    protected $reference;

    /**
     * @param string $msgId
     * @param string $msgStr
     */
    public function __construct($msgId, $msgStr = null)
    {
        $this->msgId = $msgId;
        $this->msgStr = $msgStr;
        $this->msgStrPlurals = array();
        $this->flags = array();
        $this->translatorComments = array();
        $this->developerComments = array();
        $this->reference = array();
    }

    /**
     * @param string $msgId
     *
     * @return Entry
     */
    public function setMsgId($msgId)
    {
        $this->msgId = $msgId;

        return $this;
    }

    /**
     * @param string $msgStr
     *
     * @return Entry
     */
    public function setMsgStr($msgStr)
    {
        $this->msgStr = $msgStr;

        return $this;
    }

    /**
     * @param string $msgIdPlural
     *
     * @return Entry
     */
    public function setMsgIdPlural($msgIdPlural)
    {
        $this->msgIdPlural = $msgIdPlural;

        return $this;
    }

    /**
     * @param string $msgCtxt
     *
     * @return Entry
     */
    public function setMsgCtxt($msgCtxt)
    {
        $this->msgCtxt = $msgCtxt;

        return $this;
    }

    /**
     * @param null|Entry $previousEntry
     *
     * @return Entry
     */
    public function setPreviousEntry($previousEntry)
    {
        $this->previousEntry = $previousEntry;

        return $this;
    }

    /**
     * @param bool $obsolete
     *
     * @return Entry
     */
    public function setObsolete($obsolete)
    {
        $this->obsolete = $obsolete;

        return $this;
    }

    /**
     * @param array $flags
     *
     * @return Entry
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;

        return $this;
    }

    /**
     * @param array $translatorComments
     *
     * @return Entry
     */
    public function setTranslatorComments($translatorComments)
    {
        $this->translatorComments = $translatorComments;

        return $this;
    }

    /**
     * @param array $developerComments
     *
     * @return Entry
     */
    public function setDeveloperComments($developerComments)
    {
        $this->developerComments = $developerComments;

        return $this;
    }

    /**
     * @param array $reference
     *
     * @return Entry
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @param string[] $msgStrPlurals
     *
     * @return Entry
     */
    public function setMsgStrPlurals($msgStrPlurals)
    {
        $this->msgStrPlurals = $msgStrPlurals;

        return $this;
    }

    /**
     * @return string
     */
    public function getMsgId()
    {
        return $this->msgId;
    }

    /**
     * @return string
     */
    public function getMsgStr()
    {
        return $this->msgStr;
    }

    /**
     * @return string
     */
    public function getMsgIdPlural()
    {
        return $this->msgIdPlural;
    }

    /**
     * @return string|null
     */
    public function getMsgCtxt()
    {
        return $this->msgCtxt;
    }

    /**
     * @return null|Entry
     */
    public function getPreviousEntry()
    {
        return $this->previousEntry;
    }

    /**
     * @return bool
     */
    public function isObsolete()
    {
        return $this->obsolete === true;
    }

    /**
     * @return bool
     */
    public function isFuzzy()
    {
        return in_array('fuzzy', $this->getFlags(), true);
    }

    /**
     * @return bool
     */
    public function isPlural()
    {
        return $this->getMsgIdPlural() !== null || count($this->getMsgStrPlurals()) > 0;
    }

    /**
     * @return array
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return array
     */
    public function getTranslatorComments()
    {
        return $this->translatorComments;
    }

    /**
     * @return array
     */
    public function getDeveloperComments()
    {
        return $this->developerComments;
    }

    /**
     * @return array
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return string[]
     */
    public function getMsgStrPlurals()
    {
        return $this->msgStrPlurals;
    }
}
