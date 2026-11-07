<?php

namespace App\Payment;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SmsParser
{
    public static function parse(string $from, string $text, string $receivedStamp): ?array
    {
        $matches = [];
        $providers = self::matchProviders($from);

        foreach ($providers as $provider) {
            foreach ($provider->getMessageFormats() as $format) {
                if (preg_match($format, $text, $matches)) {
                    $matches['provider'] = $provider->getId();
                    $matches['status'] = 'approved';
                    goto finish;
                }
            }
        }

        // If no provider regex matched, check if this is a financial transaction SMS from bank or MFS
        if (self::looksLikeTransactionSms($text)) {
            $fallbackDriver = ! empty($providers) ? reset($providers) : (app(PaymentManager::class)->findProvider($from) ?? app(PaymentManager::class)->resolveProviderFromText(null, $from, $text));
            $matches['provider'] = $fallbackDriver?->getId() ?? $from;
            $matches['amount'] = self::extractAmount($text);
            $matches['balance'] = self::extractBalance($text);
            $matches['trxid'] = self::extractTrxId($text);
            $matches['status'] = 'review';
            goto finish;
        }

        return null;

        finish:

        $receiverAccount = $matches['receiver_account'] ?? self::extractReceiverAccount($text);
        $senderAccount = $matches['sender_account'] ?? null;

        // If sender_account is empty, use mobile if captured (e.g. from 018...)
        if (empty($senderAccount) && ! empty($matches['mobile']) && $matches['mobile'] !== '--') {
            $senderAccount = $matches['mobile'];
        }

        if (empty($senderAccount)) {
            $senderAccount = self::extractSenderAccount($text);
        }

        $trxId = $matches['trxid'] ?? self::extractTrxId($text);
        $providerKey = app(PaymentManager::class)->getDriverKey($matches['provider'] ?? $from);

        return [
            'provider' => $providerKey ?? $matches['provider'] ?? $from,
            'amount' => (string) str_replace(',', '', $matches['amount'] ?? '0'),
            'mobile' => $matches['mobile'] ?? $senderAccount ?? $receiverAccount ?? '--',
            'sender_account' => $senderAccount,
            'receiver_account' => $receiverAccount,
            'trxid' => $trxId,
            'balance' => (string) str_replace(',', '', $matches['balance'] ?? '0'),
            'received_at' => self::parseDateTime($matches) ?? Carbon::parse($receivedStamp)->toDateTimeString(),
            'status' => $matches['status'] ?? 'review',
        ];
    }

    public static function extractReceiverAccount(string $text): ?string
    {
        // 1. "your A/C#20539**0001 has been credited" or "AC 107***337 is credited" or "your A/C ***2219 credited"
        if (preg_match('/(?:your\s+)?(?:A\/C|AC|A\/C#|Account|Acct)\s*[:#]?\s*([0-9xX*]+)\s+(?:has\s+been\s+|is\s+)?credited/i', $text, $m)) {
            return trim($m[1]);
        }

        // 2. "credited to your A/C#20539**0001" or "credited to A/C 107***337"
        if (preg_match('/credited\s+(?:to\s+)?(?:your\s+)?(?:A\/C|AC|A\/C#|Account)?\s*[:#]?\s*([0-9xX*]+)/i', $text, $m)) {
            return trim($m[1]);
        }

        // 3. "Deposit ... into/to A/C 1503***4001" or "to 1503***4001"
        if (preg_match('/(?:Deposit|credited|transferred)\s+.+?(?:into|to)\s+(?:A\/C|AC|Account)?\s*[:#]?\s*([0-9xX*]{4,})/i', $text, $m)) {
            return trim($m[1]);
        }

        // 4. Standalone masked account with at least 4 chars (e.g. 1503***4001, ***2219, 20539**0001)
        if (preg_match('/\b(?:\d+[*xX]+\d+|[*xX]+\d{3,})\b/', $text, $m)) {
            return trim($m[0]);
        }

        // 5. Explicitly labeled A/C pattern
        if (preg_match('/(?:A\/C|AC|A\/C#|Account)\s*[:#]?\s*([0-9xX*]{4,})/i', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    public static function extractSenderAccount(string $text): ?string
    {
        // 1. "from 01815252733" or "from A/C 1234***567"
        if (preg_match('/from\s+(?:A\/C|AC|Account)?\s*[:#]?\s*([0-9xX*]{4,})/i', $text, $m)) {
            return trim($m[1]);
        }

        // 2. "Sender: 01815252733"
        if (preg_match('/Sender\s*[:#]?\s*([0-9xX*]{4,})/i', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    public static function extractTrxId(string $text): ?string
    {
        if (preg_match('/(?:TrxID|TxnID|Transaction ID|TXN NO|Ref|Reference(?: No)?)[:\s]+([A-Za-z0-9]+)/i', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    public static function extractAmount(string $text): string
    {
        if (preg_match('/(?:Tk\.?|BDT)\s*([\d,]+(?:\.\d{1,2})?)/i', $text, $m)) {
            return (string) str_replace(',', '', $m[1]);
        }

        if (preg_match('/([\d,]+(?:\.\d{1,2})?)\s*(?:Tk\.?|BDT)/i', $text, $m)) {
            return (string) str_replace(',', '', $m[1]);
        }

        return '0';
    }

    public static function extractBalance(string $text): string
    {
        if (preg_match('/Balance\s+(?:is\s+)?(?:Tk\.?|BDT)?\s*([\d,]+(?:\.\d{1,2})?)/i', $text, $m)) {
            return (string) str_replace(',', '', $m[1]);
        }

        return '0';
    }

    private static function looksLikeTransactionSms(string $text): bool
    {
        return (bool) preg_match('/(?:credited|received|deposit|deposited|payment|trxid|txnid|balance)\b/i', $text);
    }

    private static function matchProviders(string $from): array
    {
        $sender = trim($from);

        return collect(app(PaymentManager::class)->getDrivers())
            ->filter(fn ($driver) => $driver instanceof PaymentProvider && $driver->matches($sender))
            ->toArray();
    }

    private static function parseDateTime(array $matches): ?string
    {
        try {
            if (isset($matches['datetime']) && is_string($matches['datetime'])) {
                $dateString = (string) str_replace('/', '-', $matches['datetime']);

                try {
                    return Carbon::createFromFormat('d-m-Y H:i', $dateString)->toDateTimeString();
                } catch (\Throwable) {
                    return Carbon::parse($dateString)->toDateTimeString();
                }
            }

            if (isset($matches['date'], $matches['time']) && is_string($matches['date']) && is_string($matches['time'])) {
                $dateString = (string) str_replace('/', '-', $matches['date']);
                $fullDateTime = "{$dateString} {$matches['time']}";

                try {
                    return Carbon::createFromFormat('d-m-Y H:i', $fullDateTime)->toDateTimeString();
                } catch (\Throwable) {
                    return Carbon::parse($fullDateTime)->toDateTimeString();
                }
            }

            if (isset($matches['date']) && is_string($matches['date'])) {
                return Carbon::parse($matches['date'])->toDateTimeString();
            }

            if (isset($matches['trxid']) && is_string($matches['trxid'])) {
                try {
                    return Carbon::parse($matches['trxid'])->toDateTimeString();
                } catch (\Throwable) {
                    // Not a datetime string (expected for standard alpha-numeric transaction IDs)
                }
            }
        } catch (Exception $e) {
            Log::warning('Failed to parse SMS datetime', ['matches' => $matches]);
        }

        return null;
    }
}
