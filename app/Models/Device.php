<?php

namespace App\Models;

use App\Enums\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected function casts()
    {
        return [
            'status' => DeviceStatus::class,
        ];
    }
}
