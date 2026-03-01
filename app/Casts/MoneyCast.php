<?php

namespace App\Casts;

final class MoneyCast
{
    public function get($model, string $key, $value, array $attributes)
    {
        return $value / 100;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return (int) round($value * 100);
    }
}