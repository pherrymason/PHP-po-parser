<?php namespace Sepia\PoParser\Handler;

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
class FileHandler implements HandlerInterface
{
    /**
     * @var resource
     */
    protected $fileHandle;

    /**
     * @param string $filepath
     *
     * @throws \Exception
     */
    public function __construct($filepath)
    {
        if (file_exists($filepath) === false) {
            throw new \Exception('PoParser: Input File does not exists: "' . htmlspecialchars($filepath) . '"');
        } elseif (is_readable($filepath) === false) {
            throw new \Exception('PoParser: File is not readable: "' . htmlspecialchars($filepath) . '"');
        }

        $this->fileHandle = @fopen($filepath, "r");
        if ($this->fileHandle===false) {
            throw new \Exception('PoParser: Could not open file: "' . htmlspecialchars($filepath) . '"');
        }
    }

    /**
     * @return string
     */
    public function getNextLine()
    {
        return fgets($this->fileHandle);
    }

    /**
     * @return bool
     */
    public function ended()
    {
        return feof($this->fileHandle);
    }

    /**
     * @return bool
     */
    public function close()
    {
        return @fclose($this->fileHandle);
    }

    /**
     * @inheritdoc
     *
     * @param string $output
     * @param array  $params
     */
    public function save($output, $params)
    {
        $outputFilePath = isset($params['filepath']) ? $params['filepath'] : null;

        if ($outputFilePath) {
            $fileHandle = @fopen($params['filepath'], 'w');
            if ($fileHandle === false) {
                throw new \RuntimeException(
                    sprintf(
                        'Could not open filename "%s" for writing.',
                        $params['filepath']
                    )
                );
            }
        } else {
            $fileHandle = $this->fileHandle;
            if (is_resource($fileHandle) === false) {
                throw new \RuntimeException(
                    'No source file opened nor `filepath` parameter specified in FileHandler::save method.'
                );
            }
        }

        $bytesWritten = @fwrite($fileHandle, $output);
        if ($bytesWritten === false) {
            throw new \RuntimeException('Could not write data into file.');
        }

        if (isset($params['filepath'])) {
            @fclose($fileHandle);
        }
    }
}