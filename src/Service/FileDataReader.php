<?php

namespace App\Service;

class FileDataReader
{
    /**
     * @var string
     */
    private $directory;

    /**
     * FileDataReader constructor.
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function getContents($filename): ?string
    {

        $path = $this->directory.DIRECTORY_SEPARATOR.$filename;

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return '';
    }

}