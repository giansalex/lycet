<?php

namespace App\Service;

class EnvConfigProvider implements ConfigProviderInterface
{
    public function get($key)
    {
       return getenv($key);
    }

    public function store($key, $value)
    {
        putenv($key.'='.$value);
    }
}