<?php

namespace Sepia\PoParser\Catalog;

class Header
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $value;

    /**
     * @param string $id
     * @param string $value
     */
    public function __construct($id, $value)
    {
        $this->id = $id;
        $this->value = $value;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
