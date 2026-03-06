<?php

namespace App\Http\Controllers;

use App\Enums\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Transaction;
use App\Payment\SmsParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        info('webhook received', $request->all());
        // $webhook = $request->query('webhook');

        // if (blank($webhook)) {
        //     info('webhook blank');
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'System is under maintenance. Please try again later.',
        //     ], 400);
        // }

        // $setting = $this->getWebhookSetting($webhook);

        // if (! $setting) {
        //     info('no settings');
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Invalid Webhook',
        //     ], 401);
        // }

        $this->handleDeviceConnection($request);

        $userAgent = $request->header('User-Agent');
        info('user agent: '.$userAgent);

        if ($userAgent === 'HT-HP-APP') {
            return $this->processSmsNotification($request);
        }

        info('webhook received');

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
        if ($request->has($keys = ['model', 'brand', 'version', 'api_level'])) {
            info('before device update or create');
            Device::query()->updateOrCreate($request->only($keys), [
                'status' => $request->input('connection_status', DeviceStatus::CONNECTED),
            ]);
            info('after device update or create');
        }
    }

    private function processSmsNotification(Request $request): JsonResponse
    {
        $from = $request->input('from', '');
        $text = $request->input('text', '');
        $receivedStamp = $this->normalizeReceivedStamp($request->input('receivedStamp', now()->toDateTimeString()));

        $sim = $this->normalizeSim($request->input('sim', 1));

        info('processing');

        if (! $parsed = SmsParser::parse($from, $text, $receivedStamp)) {
            info('not parsed');

            return response()->json(['status' => false, 'message' => 'Failed to parse SMS'], 422);
        }

        info('before transaction');
        Transaction::create([
            'entry_type' => 'automatic',
            'sim' => $sim,
            'message' => $text,
            ...$parsed,
        ]);
        info('after transaction');

        return response()->json(['status' => true, 'message' => 'SMS processed successfully']);
    }

    private function normalizeReceivedStamp(mixed $receivedStamp): string
    {
        if (is_numeric($receivedStamp)) {
            return Carbon::createFromTimestampMs((int) $receivedStamp)->toDateTimeString();
        }

        return Carbon::parse((string) $receivedStamp)->toDateTimeString();
    }

    private function normalizeSim(int|string $sim): string
    {
        return match ($sim) {
            1, '1' => 'sim1',
            2, '2' => 'sim2',
            default => (string) $sim,
        };
    }
}
