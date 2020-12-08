<?php

namespace App\Service;

class EnvConfigProvider implements ConfigProviderInterface
{
    public function get($key)
    {
       return $_ENV[$key] ?? getenv($key);
    }

    public function store($key, $value)
    {
        putenv($key.'='.$value);
    }
}