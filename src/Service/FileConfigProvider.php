<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 21/02/2018
 * Time: 05:04 PM
 */

namespace App\Service;

/**
 * Class FileConfigProvider
 */
class FileConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var array
     */
    private $keys = [
        'certificate' => 'cert.pem',
        'logo' => 'logo.png',
        'companies' => 'empresas.json',
    ];

    /**
     * FileConfigProvider constructor.
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!isset($this->keys[$key])) {
            return '';
        }

        $path = $this->directory.DIRECTORY_SEPARATOR.$this->keys[$key];

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return '';
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function store($key, $value)
    {
        if (!isset($this->keys[$key])) {
            return false;
        }

        file_put_contents($this->directory.DIRECTORY_SEPARATOR.$this->keys[$key], $value);

        return true;
    }
}
