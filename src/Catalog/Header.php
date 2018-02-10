<?php

namespace Sepia\PoParser\Catalog;

class Header
{
    /** @var array */
    protected $headers;

    /** @var int|null */
    protected $nPlurals;

    public function __construct(array $headers = array())
    {
        $this->setHeaders($headers);
    }

    public function getPluralFormsCount()
    {
        if ($this->nPlurals !== null) {
            return $this->nPlurals;
        }

        $header = $this->getHeaderValue('Plural-Forms');
        if ($header === null) {
            $this->nPlurals = 0;
            return $this->nPlurals;
        }

        $matches = array();
        if (preg_match('/nplurals=([0-9]+)/', $header, $matches) !== 1) {
            $this->nPlurals = 0;
            return $this->nPlurals;
        }

        $this->nPlurals = isset($matches[1]) ? (int)$matches[1] : 0;

        return $this->nPlurals;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function asArray()
    {
        return $this->headers;
    }

    /**
     * @param string $headerName
     *
     * @return string|null
     */
    protected function getHeaderValue($headerName)
    {
        $header = array_values(array_filter(
            $this->headers,
            function ($string) use ($headerName) {
                return preg_match('/'.$headerName.':(.*)/i', $string) == 1;
            }
        ));

        return count($header) ? $header[0] : null;
    }
}
