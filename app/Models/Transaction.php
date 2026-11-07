<?php

namespace App\Models;

use App\Payment\PaymentManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'entry_type',
        'sim',
        'message',
        'provider',
        'amount',
        'mobile',
        'sender_account',
        'receiver_account',
        'trxid',
        'balance',
        'received_at',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (Transaction $transaction) {
            if ($transaction->provider) {
                $driverKey = app(PaymentManager::class)->getDriverKey($transaction->provider);
                if ($driverKey) {
                    $transaction->provider = $driverKey;
                }
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
