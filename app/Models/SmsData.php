<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsData extends Model
{
    protected $table = 'sms_data';

    protected $fillable = [
        'entry_type',
        'sim',
        'payment_method',
        'mobile_number',
        'transaction_id',
        'amount',
        'balance',
        'message',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_REVIEW = 'review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';
}
