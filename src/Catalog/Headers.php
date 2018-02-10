<?php

namespace Sepia\PoParser\Catalog;

class Headers
{
    /** @var Header[] */
    protected $headers;

    /** @var int|null */
    protected $nPlurals;

    /**
     * Headers constructor.
     *
     * @param Header[] $headers
     */
    public function __construct(array $headers = array())
    {
        foreach ($headers as $header) {
            $this->headers[$header->getId()] = $header;
        }
    }

    /**
     * @param string $headerKey
     *
     * @return bool
     */
    public function has($headerKey)
    {
        return isset($this->headers[$headerKey]);
    }

    /**
     * @param string $headerKey
     *
     * @return Header|null
     */
    public function get($headerKey)
    {
        if (!$this->has($headerKey)) {
            return null;
        }

        return $this->headers[$headerKey];
    }

    /**
     * @return Header[]
     */
    public function all()
    {
        return $this->headers;
    }

    /**
     * @param Header $header
     */
    public function set(Header $header)
    {
        $this->headers[$header->getId()] = $header;
    }

    /**
     * @return int
     */
    public function getPluralFormsCount()
    {
        if ($this->nPlurals !== null) {
            return $this->nPlurals;
        }

        $key = 'Plural-Forms';
        if (!$this->has($key)) {
            $this->nPlurals = 0;
            return $this->nPlurals;
        }

        $header = $this->get('Plural-Forms');

        $matches = array();
        if (preg_match('/nplurals=([0-9]+)/', $header->getValue(), $matches) !== 1) {
            $this->nPlurals = 0;
            return $this->nPlurals;
        }

        $this->nPlurals = isset($matches[1]) ? (int)$matches[1] : 0;

        return $this->nPlurals;
    }

    /**
     * @param string $headerName
     *
     * @return string|null
     */
/*    protected function getHeaderValue($headerName)
    {
        $header = array_values(array_filter(
            $this->headers,
            function ($string) use ($headerName) {
                return preg_match('/'.$headerName.':(.*)/i', $string) == 1;
            }
        ));

        return count($header) ? $header[0] : null;
    }
*/
}
