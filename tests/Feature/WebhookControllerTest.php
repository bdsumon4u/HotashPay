<?php

use App\Models\Transaction;

test('webhook processes sms notification with milliseconds timestamp from bkash', function () {
    $msTimestamp = 1772809460169; // milliseconds timestamp

    // This message format matches bKash merchant provider's format
    $text = 'You have received payment Tk 600.00 from 01815252733. Fee Tk 0.00. Balance Tk 13990.10. TrxID DC61R98YS5 at 06/03/2026 21:04';

    $response = $this->post('/api/webhook', [
        'from' => 'bKash', // This matches the bkash-merchant provider alias
        'text' => $text,
        'sentStamp' => '',
        'receivedStamp' => $msTimestamp,
        'sim' => 1,
        'model' => 'SM-G950F',
        'brand' => 'samsung',
        'version' => '9',
        'api_level' => 28,
        'connection_status' => 'connected',
    ], [
        'User-Agent' => 'HT-HP-APP',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['status' => true, 'message' => 'SMS processed successfully']);

    $this->assertDatabaseHas(Transaction::class, [
        'entry_type' => 'automatic',
        'sim' => 'sim1',
        'provider' => 'bkash-merchant',
        'amount' => '600.00',
        'mobile' => '01815252733',
        'trxid' => 'DC61R98YS5',
        'balance' => '13990.10',
    ]);
});

test('webhook returns 422 when sms cannot be parsed', function () {
    $msTimestamp = 1772809460169;
    $text = 'Invalid SMS format that does not match any provider';

    $response = $this->post('/api/webhook', [
        'from' => 'UnknownSender',
        'text' => $text,
        'receivedStamp' => $msTimestamp,
        'sim' => 1,
    ], [
        'User-Agent' => 'HT-HP-APP',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['status' => false, 'message' => 'Failed to parse SMS']);
});
