<?php

namespace App\Services;

class SmsParsingService
{
    private const PROVIDER_FORMATS = [
        'bKash' => [
            [
                'type' => 'sms1',
                'format' => '/Cash In Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+) successful\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
            ],
            [
                'type' => 'sms2',
                'format' => '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
            ],
            [
                'type' => 'sms3',
                'format' => '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Ref .*?Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
            ],
            [
                'type' => 'sms4',
                'format' => '/You have received payment Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\. Fee Tk (?<fee>[\d,]+\.\d{2})\. Balance Tk (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
            ],
            [
                'type' => 'sms5',
                'format' => '/You have received Tk (?<amount>[\d,]+\.\d{2}) from (?<mobile>\d+)\.Ref .*?TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/',
            ],
        ],
        'Nagad' => [
            [
                'type' => 'sms1',
                'format' => '/Cash In Received\.\s*Amount: Tk (?<amount>[\d,]+\.\d{2})\s*Uddokta: (?<mobile>\d+)\s*TxnID: (?<trxid>[A-Z0-9]+)\s*Balance: (?<balance>[\d,]+\.\d{2})\s*(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/',
            ],
            [
                'type' => 'sms2',
                'format' => '/Money Received\.\s*Amount: Tk (?<amount>[\d,]+\.\d{2})\s*Sender: (?<mobile>\d+)\s*Ref: (.+)\s*TxnID: (?<trxid>\w+)\s*Balance: Tk (?<balance>[\d,]+\.\d{2})\s*(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/',
            ],
        ],
        'Upay' => [
            [
                'type' => 'sms1',
                'format' => '/Cash In Received\.\s*Amount: Tk (?<amount>[\d,]+\.\d{2})\s*Uddokta: (?<mobile>\d+)\s*TxnID: (?<trxid>[A-Z0-9]+)\s*Balance: (?<balance>[\d,]+\.\d{2})\s*(?<date>\d{2}\/\d{2}\/\d{4}) (?<time>\d{2}:\d{2})/',
            ],
            [
                'type' => 'sms2',
                'format' => '/Tk\. (?<amount>[\d,]+\.\d{2}) has been received from (?<mobile>\d+)\. Ref-.*?Balance Tk\. (?<balance>[\d,]+\.\d{2})\. TrxID (?<trxid>\w+) at (?<datetime>\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})\./',
            ],
        ],
        'Rocket' => [
            [
                'type' => 'sms1',
                'format' => '/Tk(?<amount>[\d,]+(?:\.\d{1,2})?)\s+received\s+from\s+A\/C:(?:\*+)?(?<mobile>\d+)\s*Fee:Tk\.?(?<fee>[\d,]+(?:\.\d{1,2})?|0),?\s*Your\s*A\/C\s*Balance:\s*Tk(?<balance>[\d,]+(?:\.\d{1,2})?)\s*TxnId:(?<trxid>\d+)\s*Date:(?<datetime>\d{2}-[A-Z]{3}-\d{2}\s+\d{2}:\d{2}:\d{2}\s*(?:am|pm))/i',
            ],
            [
                'type' => 'sms2',
                'format' => '/Tk(?<amount>[\d,]+(?:\.\d{1,2})?)\s+credited\s+from\s+card\s+\**(?<mobile>\d+)\.?\s*Fee:Tk\.?(?<fee>[\d,]+(?:\.\d{1,2})?|0)\s*NetBal:Tk(?<balance>[\d,]+(?:\.\d{1,2})?)\s*TxnId:(?<trxid>\d+)\s*Date:(?<datetime>\d{2}-[A-Z]{3}-\d{2}\s+\d{2}:\d{2}:\d{2}\s*(?:am|pm))/i',
            ],
        ],
    ];

    private const PROVIDER_ALIASES = [
        'NAGAD' => 'Nagad',
        'Nagad' => 'Nagad',
        '01708403334' => 'Nagad',
        'bKash' => 'bKash',
        '16216' => 'Rocket',
        'upay' => 'Upay',
        'tap.' => 'Tap',
        '16269' => 'OkWallet',
        'IBBL .' => 'Cellfin',
        'IPAY' => 'Ipay',
        'iPAY' => 'Ipay',
        'PathaoPay' => 'Pathao Pay',
    ];

    public function parse(string $from, string $text, string $receivedStamp): ?array
    {
        $provider = $this->matchProvider($from);

        if (! $provider) {
            return null;
        }

        $matchedData = [
            'amount' => '0',
            'mobile' => '--',
            'trxid' => '--',
            'balance' => '0',
            'datetime' => $receivedStamp,
        ];

        if (isset(self::PROVIDER_FORMATS[$provider])) {
            foreach (self::PROVIDER_FORMATS[$provider] as $formatData) {
                if (preg_match($formatData['format'], $text, $matches)) {
                    $matchedData['amount'] = (string) str_replace(',', '', $matches['amount'] ?? '0');
                    $matchedData['mobile'] = $matches['mobile'] ?? '--';
                    $matchedData['trxid'] = $matches['trxid'] ?? '--';
                    $matchedData['balance'] = (string) str_replace(',', '', $matches['balance'] ?? '0');

                    $matchedData['datetime'] = $this->parseDateTime($matches);

                    return [
                        'provider' => $provider,
                        'amount' => $matchedData['amount'],
                        'mobile' => $matchedData['mobile'],
                        'trxid' => $matchedData['trxid'],
                        'balance' => $matchedData['balance'],
                        'datetime' => $matchedData['datetime'],
                        'status' => 'approved',
                    ];
                }
            }
        }

        return [
            'provider' => $provider,
            'amount' => $matchedData['amount'],
            'mobile' => $matchedData['mobile'],
            'trxid' => $matchedData['trxid'],
            'balance' => $matchedData['balance'],
            'datetime' => $matchedData['datetime'],
            'status' => 'review',
        ];
    }

    private function matchProvider(string $from): ?string
    {
        $sender = trim($from);

        foreach (self::PROVIDER_ALIASES as $alias => $provider) {
            if (strcasecmp($sender, $alias) === 0) {
                return $provider;
            }

            if (is_numeric($alias) && $sender === $alias) {
                return $provider;
            }
        }

        return null;
    }

    private function parseDateTime(array $matches): string
    {
        try {
            if (isset($matches['datetime'])) {
                $dateString = str_replace('/', '-', $matches['datetime']);

                return \Carbon\Carbon::createFromFormat('d-m-Y H:i', $dateString)->toDateTimeString();
            }

            if (isset($matches['date']) && isset($matches['time'])) {
                $dateString = str_replace('/', '-', $matches['date']);
                $fullDateTime = "{$dateString} {$matches['time']}";

                return \Carbon\Carbon::createFromFormat('d-m-Y H:i', $fullDateTime)->toDateTimeString();
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to parse SMS datetime', ['matches' => $matches]);
        }

        return now()->toDateTimeString();
    }
}
