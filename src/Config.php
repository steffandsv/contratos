<?php

namespace App;

class Config
{
    public static function get($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}
