<?php

namespace App\Payment;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SmsParser
{
    public static function parse(string $from, string $text, string $receivedStamp): ?array
    {
        if (! $providers = self::matchProviders($from)) {
            return null;
        }

        foreach ($providers as $provider) {
            foreach ($provider->getMessageFormats() as $format) {
                if (preg_match($format, $text, $matches)) {
                    $matches['provider'] = $provider->getId();
                    $matches['status'] = 'approved';
                    goto finish;
                }
            }
        }

        finish:

        return [
            'provider' => $matches['provider'] ?? $from,
            'amount' => (string) str_replace(',', '', $matches['amount'] ?? '0'),
            'mobile' => $matches['mobile'] ?? '--',
            'trxid' => $matches['trxid'] ?? null,
            'balance' => (string) str_replace(',', '', $matches['balance'] ?? '0'),
            'received_at' => self::parseDateTime($matches) ?? $receivedStamp,
            'status' => $matches['status'] ?? 'review',
        ];
    }

    private static function matchProviders(string $from): array
    {
        $sender = trim($from);

        return collect(app(PaymentManager::class)->getDrivers())
            ->filter(fn ($driver) => in_array($sender, $driver->getAliases()))
            ->toArray();
    }

    private static function parseDateTime(array $matches): ?string
    {
        try {
            if (isset($matches['datetime'])) {
                $dateString = str_replace('/', '-', $matches['datetime']);

                return Carbon::createFromFormat('d-m-Y H:i', $dateString)->toDateTimeString();
            }

            if (isset($matches['date']) && isset($matches['time'])) {
                $dateString = str_replace('/', '-', $matches['date']);
                $fullDateTime = "{$dateString} {$matches['time']}";

                return Carbon::createFromFormat('d-m-Y H:i', $fullDateTime)->toDateTimeString();
            }
        } catch (Exception $e) {
            Log::warning('Failed to parse SMS datetime', ['matches' => $matches]);
        }

        return null;
    }
}
