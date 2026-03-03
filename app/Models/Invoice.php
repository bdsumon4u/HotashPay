<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Casts\UlidBinaryCast;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

class Invoice extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (Invoice $invoice): void {
            $invoice->ulid = mb_strtolower(Str::ulid()->toString());
        });
    }

    protected function casts(): array
    {
        return [
            'ulid' => UlidBinaryCast::class,
            'status' => InvoiceStatus::class,
            'metadata' => 'array',
            'amount' => MoneyCast::class,
        ];
    }

    public function resolveRouteBinding(mixed $value, mixed $field = null): Model
    {
        if ($field === 'ulid' && Str::isUlid($value)) {
            $value = Ulid::fromString($value)->toBinary();
        }

        $record = parent::resolveRouteBinding($value, $field);

        throw_unless($record, (new ModelNotFoundException)->setModel(self::class, [$value]));

        return $record;
    }

    public function paymentUrl(): Attribute
    {
        return Attribute::get(
            fn (): string => route('invoices.pay', ['invoice' => $this->ulid]),
        );
    }

    public function redirectUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->attributes['redirect_url'] ?? url('/payment-successful'),
        );
    }

    public function cancelUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->attributes['cancel_url'] ?? url('/payment-cancelled'),
        );
    }

    public function webhookUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->attributes['webhook_url'] ?? url('api/payment-webhook'),
        );
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
