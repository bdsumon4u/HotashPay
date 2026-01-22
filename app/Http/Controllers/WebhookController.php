<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\SmsData;
use App\Services\SmsParsingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(private SmsParsingService $smsParser) {}

    public function handleSms(Request $request): JsonResponse
    {
        $webhook = $request->query('webhook');

        if (blank($webhook)) {
            return response()->json([
                'status' => false,
                'message' => 'System is under maintenance. Please try again later.',
            ], 400);
        }

        $setting = $this->getWebhookSetting($webhook);

        if (! $setting) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Webhook',
            ], 401);
        }

        $this->handleDeviceConnection($request);

        $userAgent = $request->header('User-Agent');

        if ($userAgent === 'mh-piprapay-api-key') {
            return $this->processSmsNotification($request);
        }

        return response()->json([
            'status' => true,
            'message' => 'Webhook received',
        ]);
    }

    private function getWebhookSetting(string $webhook): ?array
    {
        // Retrieve webhook setting from your Settings model/table
        // This is a placeholder - adjust based on your actual settings storage
        return cache()->remember(
            "webhook:{$webhook}",
            now()->addHours(24),
            fn () => $this->fetchWebhookFromDatabase($webhook)
        );
    }

    private function fetchWebhookFromDatabase(string $webhook): ?array
    {
        // TODO: Replace with your actual Settings query
        // return Setting::where('webhook', $webhook)->first()?->toArray();
        return ['status' => true];
    }

    private function handleDeviceConnection(Request $request): void
    {
        if (! $request->filled(['d_model', 'd_brand', 'd_version', 'd_api_level'])) {
            return;
        }

        $deviceData = [
            'd_model' => $request->input('d_model'),
            'd_brand' => $request->input('d_brand'),
            'd_version' => $request->input('d_version'),
            'd_api_level' => $request->input('d_api_level'),
        ];

        $device = Device::where($deviceData)->first();

        $status = $request->input('connection_status', 'Connected');

        if ($device) {
            $device->update([
                'd_status' => $status,
                'created_at' => now(),
            ]);
        } else {
            Device::create([
                'd_id' => rand(),
                'created_at' => now(),
                'd_status' => 'Connected',
                ...$deviceData,
            ]);
        }
    }

    private function processSmsNotification(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $decoded = json_decode($payload, true) ?? [];

        $from = $decoded['from'] ?? $request->input('from', '');
        $text = $decoded['text'] ?? $request->input('text', '');
        $sentStamp = $decoded['sentStamp'] ?? $request->input('sentStamp', '');
        $receivedStamp = $decoded['receivedStamp'] ?? $request->input('receivedStamp', now()->toDateTimeString());
        $sim = $decoded['sim'] ?? $request->input('sim', 1);

        $sim = $this->normalizeSim($sim);

        $parsingResult = $this->smsParser->parse($from, $text, $receivedStamp);

        if ($parsingResult) {
            SmsData::create([
                'entry_type' => 'automatic',
                'sim' => $sim,
                'payment_method' => $parsingResult['provider'],
                'mobile_number' => $parsingResult['mobile'],
                'transaction_id' => $parsingResult['trxid'],
                'amount' => $parsingResult['amount'],
                'balance' => $parsingResult['balance'],
                'message' => $text,
                'status' => $parsingResult['status'],
                'created_at' => $parsingResult['datetime'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'SMS processed successfully',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to parse SMS',
        ], 422);
    }

    private function normalizeSim(int|string $sim): string
    {
        return match ($sim) {
            1, '1' => 'sim1',
            2, '2' => 'sim2',
            default => "sim{$sim}",
        };
    }
}
