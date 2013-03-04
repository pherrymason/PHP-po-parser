<?php

namespace PoParser;

class Entry
{
    protected $msgId;

    protected $msgIdPlural;

    protected $fuzzy = false;

    protected $obsolete = false;

    protected $translations = array();

    public function __construct($properties)
    {
        $this->msgId = $properties['msgid'];
        $this->msgIdPlural = $properties['msgid_plural'];
        $this->fuzzy = !empty($properties['fuzzy']);
        $this->obsolete = !empty($properties['obsolete']);
        $this->translations = $properties['msgstr'];
    }

    public function isFuzzy()
    {
        return $this->fuzzy;
    }

    public function getMsgId()
    {
        return $this->msgId;
    }

    public function getMsgIdPlural()
    {
        return $this->msgIdPlural;
    }

    public function isObsolete()
    {
        return $this->obsolete;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function getTranslation($index)
    {
        return (isset($this->translations[$index])) ? $this->translations[$index] : '';
    }

    public function isPlural()
    {
        return !empty($this->msgIdPlural);
    }
}
