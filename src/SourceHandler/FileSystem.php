<?php

namespace Sepia\PoParser\SourceHandler;

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
 */
class FileSystem implements SourceHandler
{
    /** @var resource */
    protected $fileHandle;

    /** @var string */
    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->fileHandle = null;
    }

    /**
     * @throws \Exception
     */
    protected function openFile()
    {
        if ($this->fileHandle !== null) {
            return;
        }

        if (file_exists($this->filePath) === false) {
            throw new \Exception('Parser: Input File does not exists: "' . htmlspecialchars($this->filePath) . '"');
        }

        if (is_readable($this->filePath) === false) {
            throw new \Exception('Parser: File is not readable: "' . htmlspecialchars($this->filePath) . '"');
        }

        $this->fileHandle = @fopen($this->filePath, 'rb');
        if ($this->fileHandle===false) {
            throw new \Exception('Parser: Could not open file: "' . htmlspecialchars($this->filePath) . '"');
        }
    }

    /**
     * @return bool|string
     * @throws \Exception
     */
    public function getNextLine()
    {
        $this->openFile();

        return fgets($this->fileHandle);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function ended()
    {
        $this->openFile();

        return feof($this->fileHandle);
    }

    public function close()
    {
        if ($this->fileHandle === null) {
            return true;
        }

        return @fclose($this->fileHandle);
    }

    /**
     * @param $output
     * @param $filePath
     *
     * @return bool
     * @throws \Exception
     */
    public function save($output)
    {
        $result = file_put_contents($this->filePath, $output);
        if ($result === false) {
            throw new \Exception('Could not write into file '.htmlspecialchars($this->filePath));
        }

        return true;
    }
}