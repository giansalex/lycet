<?php

namespace App\Service;

class EnvConfigProvider implements ConfigProviderInterface
{
    public function get($key)
    {
       return $_ENV[$key];
    }

    public function store($key, $value)
    {
        putenv($key.'='.$value);
    }
}