<?php

use App\Models\Transaction;
use App\Plugins\BankEbl\BankEblDriver;
use App\Plugins\BankEbl\BankEblPlugin;
use App\Plugins\PluginLoader;

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

test('webhook processes bkash merchant sms with ref field', function () {
    $msTimestamp = 1772809460169;
    $text = 'You have received payment Tk 600.00 from 01815252733. Ref SUREBAZAR. Fee Tk 0.00. Balance Tk 13,990.10. TrxID DC61R98YS5 at 06/03/2026 21:04';

    $response = $this->post('/api/webhook', [
        'from' => 'bKash',
        'text' => $text,
        'receivedStamp' => $msTimestamp,
        'sim' => 1,
    ], [
        'User-Agent' => 'HT-HP-APP',
    ]);

    $response->assertSuccessful();
    $response->assertJson(['status' => true, 'message' => 'SMS processed successfully']);

    $this->assertDatabaseHas(Transaction::class, [
        'provider' => 'bkash-merchant',
        'amount' => '600.00',
        'mobile' => '01815252733',
        'trxid' => 'DC61R98YS5',
        'balance' => '13990.10',
        'status' => 'approved',
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

test('bank ebl driver matches account transfer sms format', function () {
    PluginLoader::register();

    $driver = new BankEblDriver(new BankEblPlugin('bank-ebl'));
    $text = 'AC 118***551 is credited with BDT 10000 as EBL Account Transfer on 05-MAR-26 02:40:16 PM Balance is BDT 26622 Thanks. EBL Helpline 16230';

    $matches = [];
    foreach ($driver->getMessageFormats() as $format) {
        if (preg_match($format, $text, $matches)) {
            break;
        }
    }

    expect($matches)->not->toBeEmpty()
        ->and($matches['amount'])->toBe('10000')
        ->and($matches['balance'])->toBe('26622')
        ->and($matches['trxid'])->toBe('05-MAR-26 02:40:16 PM');
});

test('bank ebl driver matches npsb fund transfer sms format', function () {
    PluginLoader::register();

    $driver = new BankEblDriver(new BankEblPlugin('bank-ebl'));
    $text = 'AC 118***551 is credited with BDT 600 as NPSB FUND TRANSFER on 06-MAR-26 10:16:20 AM Balance is BDT 27222 Thanks. EBL Helpline 16230';

    $matches = [];
    foreach ($driver->getMessageFormats() as $format) {
        if (preg_match($format, $text, $matches)) {
            break;
        }
    }

    expect($matches)->not->toBeEmpty()
        ->and($matches['amount'])->toBe('600')
        ->and($matches['balance'])->toBe('27222')
        ->and($matches['trxid'])->toBe('06-MAR-26 10:16:20 AM');
});

test('sms parser extracts receiver and sender accounts from bank and mfs sms', function () {
    // 1. EBL SMS
    $eblText = 'AC 107***337 is credited with BDT 38100 as BEFTN on 05-NOV-26 06:04:00 PM Balance is BDT 50000 Thanks. EBL Helpline 16230';
    $eblParsed = \App\Payment\SmsParser::parse('EBL', $eblText, now()->toDateTimeString());
    expect($eblParsed)->not->toBeNull()
        ->and($eblParsed['receiver_account'])->toBe('107***337')
        ->and($eblParsed['amount'])->toBe('38100');

    // 2. City Bank SMS
    $cityText = 'A/C 1503***4001 is credited with BDT 25,400.00 on 05-NOV-26 04:23:00 PM Balance is BDT 100000';
    $cityParsed = \App\Payment\SmsParser::parse('City Bank', $cityText, now()->toDateTimeString());
    expect($cityParsed)->not->toBeNull()
        ->and($cityParsed['receiver_account'])->toBe('1503***4001')
        ->and($cityParsed['amount'])->toBe('25400.00');

    // 3. BRAC Bank SMS
    $bracText = 'Your BRAC Bank A/C#20539**0001 has been credited with Tk 7,320.83 on 05-Nov-2026';
    $bracParsed = \App\Payment\SmsParser::parse('BRAC Bank', $bracText, now()->toDateTimeString());
    expect($bracParsed)->not->toBeNull()
        ->and($bracParsed['receiver_account'])->toBe('20539**0001')
        ->and($bracParsed['amount'])->toBe('7320.83');

    // 4. DBBL SMS
    $dbblText = 'Dear Sir, your A/C ***2219 credited (Fund Transfer) by Tk. 6,500.00';
    $dbblParsed = \App\Payment\SmsParser::parse('DBBL', $dbblText, now()->toDateTimeString());
    expect($dbblParsed)->not->toBeNull()
        ->and($dbblParsed['receiver_account'])->toBe('***2219')
        ->and($dbblParsed['amount'])->toBe('6500.00');

    // 5. bKash SMS
    $bkashText = 'You have received payment Tk 600.00 from 01815252733. Fee Tk 0.00. Balance Tk 13990.10. TrxID DC61R98YS5 at 06/03/2026 21:04';
    $bkashParsed = \App\Payment\SmsParser::parse('bKash', $bkashText, now()->toDateTimeString());
    expect($bkashParsed)->not->toBeNull()
        ->and($bkashParsed['sender_account'])->toBe('01815252733')
        ->and($bkashParsed['trxid'])->toBe('DC61R98YS5');
});
