<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Transaction;
use App\Payment\SmsParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handleSms(Request $request): JsonResponse
    {
        // $webhook = $request->query('webhook');

        // if (blank($webhook)) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'System is under maintenance. Please try again later.',
        //     ], 400);
        // }

        // $setting = $this->getWebhookSetting($webhook);

        // if (! $setting) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Invalid Webhook',
        //     ], 401);
        // }

        // $this->handleDeviceConnection($request);

        // $userAgent = $request->header('User-Agent');

        $request->merge(json_decode('{"from":"bKash","text":"You have received payment Tk 500.00 from 01783110247. Fee Tk 0.00. Balance Tk 9,452.20. TrxID DAM6CTT7RW at 22/01/2026 01:54","sentStamp":1769025241000,"receivedStamp":1769025248417,"sim":"sim1"}', true));

        // if ($userAgent === 'mh-piprapay-api-key') {
        return $this->processSmsNotification($request);
        // }

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

        Device::query()->updateOrCreate($deviceData, [
            'd_status' => $request->input('connection_status', 'Connected'),
        ]);
    }

    private function processSmsNotification(Request $request): JsonResponse
    {
        $from = $request->input('from', '');
        $text = $request->input('text', '');
        $sentStamp = $request->input('sentStamp', '');
        $receivedStamp = $request->input('receivedStamp', now()->toDateTimeString());
        $sim = $this->normalizeSim($decoded['sim'] ?? $request->input('sim', 1));

        if ($parsingResult = SmsParser::parse($from, $text, $receivedStamp)) {
            Transaction::create([
                'entry_type' => 'automatic',
                'sim' => $sim,
                'message' => $text,
                ...$parsingResult,
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
            default => $sim,
        };
    }
}
