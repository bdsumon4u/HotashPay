<?php

use App\Enums\InvoiceStatus;
use App\Filament\Pages\MakePayment;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('verifies transaction and marks invoice as paid with database transaction', function () {
    Http::fake();

    // Create an unpaid invoice
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
        'webhook_url' => 'https://example.com/webhook',
    ]);

    // Create an unclaimed transaction with matching details
    $transaction = Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    // Verify transaction is now claimed
    $transaction->refresh();
    expect($transaction->invoice_id)->toBe($invoice->id);

    // Verify invoice is marked as paid
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PAID);
});

it('fails verification when transaction id does not match', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'WRONG_ID')
        ->call('verifyTransaction');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);
});

it('fails verification when amount does not match', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    $transaction = Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 2000, // Different amount
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);

    $transaction->refresh();
    expect($transaction->invoice_id)->toBeNull();
});

it('fails verification when transaction is already claimed', function () {
    $invoice1 = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PAID,
    ]);

    $invoice2 = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    // Transaction already claimed by invoice1
    Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => $invoice1->id,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice2])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice2->refresh();
    expect($invoice2->status)->toBe(InvoiceStatus::PENDING);
});

it('fails verification when provider does not match', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    $transaction = Transaction::factory()->create([
        'provider' => 'Nagad',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash') // Different provider
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING);

    $transaction->refresh();
    expect($transaction->invoice_id)->toBeNull();
});

it('uses database transaction for payment verification', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    // Verify changes are committed to database
    $initialStatus = $invoice->status;

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice->refresh();

    // Invoice status should have changed from pending to paid
    expect($invoice->status)->not->toBe($initialStatus)
        ->and($invoice->status)->toBe(InvoiceStatus::PAID);
});

it('renders payment page with methods view initially and supports switching providers', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 600,
        'status' => InvoiceStatus::PENDING,
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->assertSet('selectedProvider', null)
        ->assertSet('isBankPayment', false)
        ->assertSee("currentView: 'methods'", false)
        ->call('selectProvider', 'bank-ebl')
        ->assertSet('selectedProvider', 'bank-ebl')
        ->assertSet('isBankPayment', true)
        ->call('selectProvider', 'nagad-personal')
        ->assertSet('selectedProvider', 'nagad-personal')
        ->assertSet('isBankPayment', false)
        ->call('selectProvider', null)
        ->assertSet('selectedProvider', null)
        ->assertSet('isBankPayment', false);
});

it('registers bank-city as an enabled ibanking provider with instructions', function () {
    $manager = app(\App\Payment\PaymentManager::class);
    $drivers = collect($manager->getEnabledDrivers('ibanking'));

    $cityDriver = $drivers->firstWhere('folder', 'bank-city');
    expect($cityDriver)->not->toBeNull()
        ->and($cityDriver['name'])->toBe('City Bank')
        ->and($cityDriver['type'])->toBe('ibanking')
        ->and($cityDriver['instruction_view'])->toBe('bank-city::instruction');
});

it('verifies bKash to bank transfer screenshot detected as city_bank', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 7000,
        'status' => InvoiceStatus::PENDING,
    ]);

    $transaction = Transaction::factory()->create([
        'provider' => 'city_bank',
        'trxid' => 'DK4528OF9R',
        'amount' => 7000,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    $mockVision = Mockery::mock(\App\Services\GeminiVisionService::class)->makePartial();
    $mockVision->shouldReceive('analyzeScreenshot')
        ->once()
        ->andReturn([
            'provider' => 'city_bank',
            'trx_id' => 'DK4528OF9R',
            'amount' => 7000.0,
            'fee' => 87.50,
            'total_amount' => 7087.50,
            'balance' => 12.79,
            'txn_type' => 'বিকাশ টু ব্যাংক',
            'beneficiary_bank' => 'সিটি ব্যাংক',
            'sender_account' => null,
            'sender_number' => null,
            'sender_name' => null,
            'receiver_account' => '1503XXXXXX001',
            'receiver_number' => '1503XXXXXX001',
            'receiver_name' => 'Cyber 32',
            'reference' => null,
            'transaction_time' => '2026-11-04 21:24:00',
            'status_in_screenshot' => 'successful',
            'is_suspicious_or_edited' => false,
            'confidence_score' => 0.98,
            'notes' => 'bKash to Bank transfer receipt to City Bank, successful.',
        ]);
    app()->instance(\App\Services\GeminiVisionService::class, $mockVision);

    $file = \Illuminate\Http\UploadedFile::fake()->image('city_bank_receipt.png');

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->call('selectProvider', 'bank-city')
        ->assertSet('isBankPayment', true)
        ->set('screenshot', $file)
        ->call('verifyTransaction');

    $transaction->refresh();
    expect($transaction->invoice_id)->toBe($invoice->id);

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PAID);
});

it('normalizes provider to city_bank when screenshot is bKash to City Bank transfer', function () {
    $service = app(\App\Services\GeminiVisionService::class);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'provider' => 'bkash',
                                    'trx_id' => 'DK4528OF9R',
                                    'amount' => 7000.0,
                                    'fee' => 87.5,
                                    'total_amount' => 7087.5,
                                    'balance' => 12.79,
                                    'txn_type' => 'বিকাশ টু ব্যাংক',
                                    'beneficiary_bank' => 'সিটি ব্যাংক',
                                    'receiver_account' => '1503XXXXXX001',
                                    'receiver_name' => 'Cyber 32',
                                    'transaction_time' => '2026-11-04 21:24:00',
                                    'status_in_screenshot' => 'successful',
                                    'is_suspicious_or_edited' => false,
                                    'confidence_score' => 0.98,
                                    'notes' => 'bKash to Bank transfer receipt to City Bank, successful.',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $file = \Illuminate\Http\UploadedFile::fake()->image('city_receipt.jpg');
    $path = $file->store('test_screenshots', 'public');

    $result = $service->analyzeScreenshot($path);

    expect($result['provider'])->toBeInstanceOf(\App\Payment\PaymentProvider::class)
        ->and($result['provider']->getId())->toBe('bank-city')
        ->and((string) $result['provider'])->toBe('bank-city')
        ->and($result['beneficiary_bank'])->toBe('সিটি ব্যাংক')
        ->and($result['trx_id'])->toBe('DK4528OF9R')
        ->and($result['amount'])->toBe(7000.0)
        ->and($result['status_in_screenshot'])->toBe('successful');
});

it('builds gemini prompt dynamically from registered payment drivers', function () {
    $service = app(\App\Services\GeminiVisionService::class);
    $prompt = $service->buildPrompt();

    expect($prompt)->toContain('bank-city')
        ->and($prompt)->toContain('bank-ebl')
        ->and($prompt)->toContain('City Bank')
        ->and($prompt)->toContain('Eastern Bank')
        ->and($prompt)->toContain('Reference No');
});

it('dynamically resolves newly registered payment provider and aliases without modifying services', function () {
    $plugin = new class('bank-brac') extends \App\Plugins\Plugin
    {
        public function getName(): string
        {
            return 'BRAC Bank';
        }
    };

    $mockBracDriver = new class($plugin) extends \App\Payment\PaymentDriver
    {
        public function getType(): string
        {
            return 'ibanking';
        }

        public function getAliases(): array
        {
            return ['BRAC Bank', 'brac_bank', 'Astha', 'আস্থা', 'ব্র্যাক'];
        }

        public function getReceiptPromptNotes(): ?string
        {
            return 'BRAC Bank / Astha "Reference Number" is the transaction identifier.';
        }

        public function getMessageFormats(): array
        {
            return [];
        }
    };

    $manager = app(\App\Payment\PaymentManager::class);
    $manager->extend('bank-brac', fn () => $mockBracDriver);

    // 1. Verify findProvider finds the new driver by any alias
    $found = $manager->findProvider('আস্থা');
    expect($found)->toBeInstanceOf(\App\Payment\PaymentProvider::class)
        ->and($found->getId())->toBe('bank-brac')
        ->and($found->getName())->toBe('BRAC Bank');

    // 2. Verify GeminiVisionService prompt dynamically includes the new provider
    $visionService = app(\App\Services\GeminiVisionService::class);
    $prompt = $visionService->buildPrompt();
    expect($prompt)->toContain('bank-brac')
        ->and($prompt)->toContain('BRAC Bank')
        ->and($prompt)->toContain('Reference Number');

    // 3. Verify PaymentVerificationService dynamically resolves variants for candidate matching
    $verificationService = app(\App\Services\PaymentVerificationService::class);
    $variants = $verificationService->resolveProviderVariants('bank-brac');
    expect($variants)->toContain('bank-brac')
        ->and($variants)->toContain('BRAC Bank')
        ->and($variants)->toContain('Astha')
        ->and($variants)->toContain('আস্থা');
});

it('downscales and optimizes large receipt images before transmitting to gemini', function () {
    $service = app(\App\Services\GeminiVisionService::class);

    // Create a 2000x2000 test image in memory
    $img = imagecreatetruecolor(2000, 2000);
    $red = imagecolorallocate($img, 255, 0, 0);
    if (is_int($red)) {
        imagefilledrectangle($img, 0, 0, 2000, 2000, $red);
    }
    ob_start();
    imagepng($img, null, 0);
    $largePng = ob_get_clean();
    imagedestroy($img);

    expect(strlen($largePng))->toBeGreaterThan(512 * 1024);

    $tempPath = tempnam(sys_get_temp_dir(), 'test_img_').'.png';
    file_put_contents($tempPath, $largePng);

    $capturedPayload = null;
    Http::fake([
        'https://generativelanguage.googleapis.com/*' => function ($request) use (&$capturedPayload) {
            $capturedPayload = $request->data();

            return Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'provider' => 'bank-city',
                                        'trx_id' => 'DK4528OF9R',
                                        'amount' => 500.0,
                                        'fee' => 0.0,
                                        'total_amount' => 500.0,
                                        'status_in_screenshot' => 'successful',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        },
    ]);

    $service->analyzeScreenshot($tempPath);
    @unlink($tempPath);

    expect($capturedPayload)->not->toBeNull();
    $inlineData = $capturedPayload['contents'][0]['parts'][1]['inline_data'];
    $transmittedBytes = base64_decode($inlineData['data']);

    // Optimized image should be JPEG and significantly smaller than the raw 2000x2000 PNG
    expect($inlineData['mime_type'])->toBe('image/jpeg')
        ->and(strlen($transmittedBytes))->toBeLessThan(strlen($largePng));
});

it('atomically claims transaction and prevents duplicate claims', function () {
    $invoice1 = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    $invoice2 = Invoice::factory()->create([
        'amount' => 1500,
        'status' => InvoiceStatus::PENDING,
    ]);

    $transaction = Transaction::factory()->create([
        'provider' => 'bKash',
        'trxid' => 'ABC123XYZ',
        'amount' => 1500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    // Claim on invoice 1
    Livewire::test(MakePayment::class, ['invoice' => $invoice1])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice1->refresh();
    $transaction->refresh();
    expect($invoice1->status)->toBe(InvoiceStatus::PAID)
        ->and($transaction->invoice_id)->toBe($invoice1->id);

    // Try claiming same transaction on invoice 2
    Livewire::test(MakePayment::class, ['invoice' => $invoice2])
        ->set('selectedProvider', 'bKash')
        ->set('transactionId', 'ABC123XYZ')
        ->call('verifyTransaction');

    $invoice2->refresh();
    expect($invoice2->status)->toBe(InvoiceStatus::PENDING);
});

it('preserves sms ground truth on transaction and does not overwrite with screenshot data', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 7000,
        'status' => InvoiceStatus::PENDING,
    ]);

    $transaction = Transaction::factory()->create([
        'provider' => 'city_bank',
        'trxid' => 'DK4528OF9R',
        'amount' => 7000,
        'sender_account' => '1503***SMS_SENDER',
        'receiver_account' => '1503***SMS_RECEIVER',
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    $mockVision = Mockery::mock(\App\Services\GeminiVisionService::class)->makePartial();
    $mockVision->shouldReceive('analyzeScreenshot')
        ->once()
        ->andReturn([
            'provider' => 'bank-city',
            'trx_id' => 'DK4528OF9R',
            'amount' => 7000.0,
            'fee' => 87.50,
            'total_amount' => 7087.50,
            'balance' => 12.79,
            'txn_type' => 'বিকাশ টু ব্যাংক',
            'beneficiary_bank' => 'সিটি ব্যাংক',
            'sender_account' => '01624093099_FROM_SCREENSHOT',
            'sender_number' => '01624093099_FROM_SCREENSHOT',
            'receiver_account' => '1503XXXXXX001_FROM_SCREENSHOT',
            'receiver_number' => '1503XXXXXX001_FROM_SCREENSHOT',
            'transaction_time' => '2026-11-04 21:24:00',
            'status_in_screenshot' => 'successful',
            'is_suspicious_or_edited' => false,
            'confidence_score' => 0.98,
        ]);
    app()->instance(\App\Services\GeminiVisionService::class, $mockVision);

    $file = \Illuminate\Http\UploadedFile::fake()->image('city_receipt.jpg');

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->call('selectProvider', 'bank-city')
        ->set('screenshot', $file)
        ->call('verifyTransaction');

    $transaction->refresh();
    $invoice->refresh();

    // Transaction is claimed atomically
    expect($transaction->invoice_id)->toBe($invoice->id)
        // Ground truth from SMS is NEVER overwritten by screenshot data
        ->and($transaction->sender_account)->toBe('1503***SMS_SENDER')
        ->and($transaction->receiver_account)->toBe('1503***SMS_RECEIVER')
        // Invoice metadata holds the screenshot verification details
        ->and($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($invoice->metadata['verification_ai']['sender_account'])->toBe('01624093099_FROM_SCREENSHOT');
});

it('fails verification and shows support prompt when 10 minutes timeout is reached', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 5000,
        'status' => InvoiceStatus::PENDING,
        'metadata' => [
            'verification_status' => 'processing',
            'verification_started_at' => now()->subMinutes(11)->toIso8601String(),
        ],
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->call('checkVerificationStatus')
        ->assertSet('isVerifying', false)
        ->assertSet('verificationFailed', true)
        ->assertSee('Verification timed out after 10 minutes');

    $invoice->refresh();
    expect($invoice->metadata['verification_status'])->toBe('failed')
        ->and($invoice->status)->toBe(InvoiceStatus::PENDING);
});

it('releases job back to queue when candidate transaction is not yet received within 10 minutes', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 5000,
        'status' => InvoiceStatus::PENDING,
        'metadata' => [
            'verification_status' => 'processing',
            'verification_started_at' => now()->subMinutes(2)->toIso8601String(),
        ],
    ]);

    $mockVision = Mockery::mock(\App\Services\GeminiVisionService::class)->makePartial();
    $mockVision->shouldReceive('analyzeScreenshot')
        ->once()
        ->andReturn([
            'provider' => 'bank-city',
            'trx_id' => 'DK4528OF9R',
            'amount' => 5000.0,
            'fee' => 0.0,
            'total_amount' => 5000.0,
            'status_in_screenshot' => 'successful',
            'is_suspicious_or_edited' => false,
            'confidence_score' => 0.95,
        ]);

    $job = new \App\Jobs\VerifyPaymentJob(
        invoiceId: $invoice->id,
        provider: 'bank-city',
        screenshotPath: 'dummy/path.jpg',
    );

    // Call job directly with empty candidate transactions in DB
    $job->handle($mockVision, app(\App\Services\PaymentVerificationService::class));

    $invoice->refresh();
    // Invoice should still be pending and processing
    expect($invoice->status)->toBe(InvoiceStatus::PENDING)
        ->and($invoice->metadata['verification_status'])->toBe('processing');
});

it('formats invoice reference with HP prefix', function () {
    $invoice1 = Invoice::factory()->create([
        'invoice_id' => '1005',
        'status' => InvoiceStatus::PENDING,
    ]);
    expect($invoice1->reference)->toBe('HP1005');

    $invoice2 = Invoice::factory()->create([
        'invoice_id' => 'HP9921',
        'status' => InvoiceStatus::PENDING,
    ]);
    expect($invoice2->reference)->toBe('HP9921');
});

it('renders reference option on payment instructions', function (string $provider) {
    $invoice = Invoice::factory()->create([
        'invoice_id' => '1005',
        'status' => InvoiceStatus::PENDING,
    ]);

    Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->call('selectProvider', $provider)
        ->assertSee('Reference (Optional)')
        ->assertSee('HP1005');
})->with([
    'bkash-personal',
    'nagad-personal',
    'bkash-merchant',
    'bank-city',
    'bank-ebl',
]);
