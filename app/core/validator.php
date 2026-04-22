<?php
// File: /app/Core/Validator.php

namespace App\Core;

class Validator
{
    public static function hour($value): bool
    {
        return preg_match('/^(?:[1-9]|1[0-9]|2[0-4])\.00$/', $value);
    }

    public static function load($value): bool
    {
        return is_numeric($value) && $value >= 0 && $value <= 10;
    }

    public static function fault($value): bool
    {
        return in_array($value, ['LS','OS','FO','BF'], true);
    }
}
