<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 21/02/2018
 * Time: 05:02 PM
 */

namespace App\Service;

/**
 * Interface ConfigProviderInterface
 */
interface ConfigProviderInterface
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function store($key, $value);
}