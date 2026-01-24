<?php

namespace App\Payment;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SmsParser
{
    public static function parse(string $from, string $text, string $receivedStamp): ?array
    {
        if (! $provider = self::matchProvider($from)) {
            return null;
        }

        foreach ($provider->getMessageFormats() as $format) {
            if (preg_match($format, $text, $matches)) {
                $matches['status'] = 'approved';
                break;
            }
        }

        return [
            'provider' => $provider->getId(),
            'amount' => (string) str_replace(',', '', $matches['amount'] ?? '0'),
            'mobile' => $matches['mobile'] ?? '--',
            'trxid' => $matches['trxid'] ?? '--',
            'balance' => (string) str_replace(',', '', $matches['balance'] ?? '0'),
            'created_at' => self::parseDateTime($matches),
            'status' => $matches['status'] ?? 'review',
        ];
    }

    private static function matchProvider(string $from): ?PaymentDriver
    {
        $sender = trim($from);

        $paymentManager = app(PaymentManager::class);
        foreach ($paymentManager->getDrivers() as $driver) {
            if (in_array($sender, $driver->getAliases())) {
                return $driver;
            }
        }

        return null;
    }

    private static function parseDateTime(array $matches): string
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

        return now()->toDateTimeString();
    }
}
