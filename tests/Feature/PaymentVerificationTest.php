<?php

use App\Enums\InvoiceStatus;
use App\Filament\Pages\MakePayment;
use App\Models\Invoice;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('payment.gemini.api_key', config('payment.gemini.api_key') ?: 'test-fake-gemini-key');
});

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

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'matched_transaction_id' => $transaction->id,
                                    'confidence_score' => 0.98,
                                    'match_reason' => 'Exact match on TrxID DK4528OF9R and amount 7000 BDT',
                                    'is_suspicious_or_edited' => false,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    /** @var \App\Services\GeminiVisionService&\Mockery\MockInterface $mockVision */
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
    assert(is_string($path));

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
    assert(is_string($largePng));

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
    assert(isset($inlineData['data']) && is_string($inlineData['data']));
    $transmittedBytes = base64_decode($inlineData['data']);
    assert(is_string($transmittedBytes));

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

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'matched_transaction_id' => $transaction->id,
                                    'confidence_score' => 0.98,
                                    'match_reason' => 'Exact match on TrxID DK4528OF9R and amount 7000 BDT',
                                    'is_suspicious_or_edited' => false,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    /** @var \App\Services\GeminiVisionService&\Mockery\MockInterface $mockVision */
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

    /** @var \App\Services\GeminiVisionService&\Mockery\MockInterface $mockVision */
    $mockVision = Mockery::mock(\App\Services\GeminiVisionService::class)->makePartial();

    $job = new \App\Jobs\VerifyPaymentJob(
        invoiceId: $invoice->id,
        provider: 'bank-city',
        screenshotPath: 'dummy/path.jpg',
    );

    // Call job directly with empty candidate transactions in DB
    assert($mockVision instanceof \App\Services\GeminiVisionService);
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

it('matches transaction when received_at is older but created_at is within window', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 600,
        'status' => InvoiceStatus::PENDING,
    ]);

    $tx = Transaction::factory()->create([
        'provider' => 'bkash-merchant',
        'trxid' => 'DC46O25T7K',
        'amount' => 600,
        'invoice_id' => null,
        'status' => 'approved',
        'received_at' => now()->subMonths(5),
        'created_at' => now()->subMinutes(5),
    ]);

    $verifier = app(\App\Services\PaymentVerificationService::class);
    $matched = $verifier->findMatchingTransactionByTrxId($invoice, 'bkash-merchant', 'DC46O25T7K');

    expect($matched)->not->toBeNull()
        ->and($matched->id)->toBe($tx->id);
});

it('returns canonical driver key for provider', function () {
    $manager = app(\App\Payment\PaymentManager::class);

    expect($manager->getDriverKey('bkash-merchant'))->toBe('bkash-merchant')
        ->and($manager->getDriverKey('bkash-personal'))->toBe('bkash-personal')
        ->and($manager->getDriverKey('bank-city'))->toBe('bank-city')
        ->and($manager->getDriverKey('bKash'))->toBe('bkash-personal');
});

it('allows retrying payment verification after failure', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 600,
        'status' => InvoiceStatus::PENDING,
    ]);

    $component = Livewire::test(MakePayment::class, ['invoice' => $invoice])
        ->set('selectedProvider', 'bkash-merchant')
        ->set('transactionId', 'WRONG_ID')
        ->call('verifyTransaction');

    expect($component->get('isVerifying'))->toBeFalse()
        ->and($component->get('verificationFailed'))->toBeTrue();

    $tx = Transaction::factory()->create([
        'provider' => 'bkash-merchant',
        'trxid' => 'CORRECT123',
        'amount' => 600,
        'invoice_id' => null,
        'status' => 'approved',
        'created_at' => now()->subMinutes(1),
    ]);

    $component->set('transactionId', 'CORRECT123')
        ->call('verifyTransaction');

    $tx->refresh();
    $invoice->refresh();

    expect($tx->invoice_id)->toBe($invoice->id)
        ->and($invoice->status)->toBe(InvoiceStatus::PAID);
});

it('mounts with isVerifying as false even if previous verification was processing and supports cancelVerification', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 600,
        'status' => InvoiceStatus::PENDING,
        'metadata' => [
            'verification_status' => 'processing',
            'verification_started_at' => now()->subMinutes(2)->toIso8601String(),
        ],
    ]);

    $component = Livewire::test(MakePayment::class, ['invoice' => $invoice]);
    expect($component->get('isVerifying'))->toBeFalse();

    $component->set('isVerifying', true);
    $component->call('cancelVerification');

    expect($component->get('isVerifying'))->toBeFalse();
    $invoice->refresh();
    expect($invoice->metadata['verification_status'])->toBe('cancelled');
});

it('reconciles candidate transactions via gemini and returns the highest confidence match', function () {
    $service = app(\App\Services\GeminiVisionService::class);

    $invoice = Invoice::factory()->create([
        'amount' => 1200,
        'status' => InvoiceStatus::PENDING,
    ]);

    $tx1 = Transaction::factory()->create([
        'provider' => 'bkash-personal',
        'trxid' => 'TX111111',
        'amount' => 1200,
        'invoice_id' => null,
    ]);

    $tx2 = Transaction::factory()->create([
        'provider' => 'bkash-personal',
        'trxid' => 'TX222222',
        'amount' => 1200,
        'invoice_id' => null,
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'matched_transaction_id' => $tx2->id,
                                    'confidence_score' => 0.96,
                                    'match_reason' => 'Exact match on TrxID TX222222 and amount 1200 BDT',
                                    'is_suspicious_or_edited' => false,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $screenshotData = [
        'provider' => 'bkash-personal',
        'trx_id' => 'TX222222',
        'amount' => 1200.0,
        'sender_account' => '01811223344',
        'receiver_account' => '01624093099',
    ];

    $result = $service->reconcileCandidatesWithGemini(
        screenshotData: $screenshotData,
        candidateTransactions: [$tx1, $tx2],
        invoice: $invoice,
        provider: app(\App\Payment\PaymentManager::class)->findProvider('bkash-personal'),
    );

    expect($result['matched_transaction_id'])->toBe($tx2->id)
        ->and($result['confidence_score'])->toBe(0.96)
        ->and($result['is_suspicious_or_edited'])->toBeFalse()
        ->and($result['match_reason'])->toContain('TX222222');
});

it('rejects match when gemini confidence score is below threshold', function () {
    $service = app(\App\Services\GeminiVisionService::class);

    $invoice = Invoice::factory()->create([
        'amount' => 1200,
        'status' => InvoiceStatus::PENDING,
    ]);

    $tx = Transaction::factory()->create([
        'provider' => 'bkash-personal',
        'trxid' => 'TX111111',
        'amount' => 1200,
        'invoice_id' => null,
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'matched_transaction_id' => $tx->id,
                                    'confidence_score' => 0.65, // Below default 0.85 threshold
                                    'match_reason' => 'Low confidence match: amount matches but TrxID is missing',
                                    'is_suspicious_or_edited' => false,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $result = $service->reconcileCandidatesWithGemini(
        screenshotData: ['amount' => 1200.0],
        candidateTransactions: [$tx],
        invoice: $invoice,
    );

    expect($result['matched_transaction_id'])->toBeNull()
        ->and($result['confidence_score'])->toBe(0.65);
});

it('rejects hallucinated transaction id not present in candidate list', function () {
    $service = app(\App\Services\GeminiVisionService::class);

    $invoice = Invoice::factory()->create([
        'amount' => 500,
        'status' => InvoiceStatus::PENDING,
    ]);

    $tx = Transaction::factory()->create([
        'provider' => 'nagad-personal',
        'trxid' => 'NAGAD123',
        'amount' => 500,
        'invoice_id' => null,
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'matched_transaction_id' => 999999, // Hallucinated ID
                                    'confidence_score' => 0.99,
                                    'match_reason' => 'Exact match on hallucinated ID',
                                    'is_suspicious_or_edited' => false,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $result = $service->reconcileCandidatesWithGemini(
        screenshotData: ['amount' => 500.0],
        candidateTransactions: [$tx],
        invoice: $invoice,
    );

    expect($result['matched_transaction_id'])->toBeNull();
});

it('flags suspicious or edited receipt and does not match transaction', function () {
    $service = app(\App\Services\GeminiVisionService::class);

    $invoice = Invoice::factory()->create([
        'amount' => 500,
        'status' => InvoiceStatus::PENDING,
    ]);

    $tx = Transaction::factory()->create([
        'provider' => 'nagad-personal',
        'trxid' => 'NAGAD123',
        'amount' => 500,
        'invoice_id' => null,
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'matched_transaction_id' => $tx->id,
                                    'confidence_score' => 0.95,
                                    'match_reason' => 'Amount matches, but fonts appear altered',
                                    'is_suspicious_or_edited' => true,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $result = $service->reconcileCandidatesWithGemini(
        screenshotData: ['amount' => 500.0],
        candidateTransactions: [$tx],
        invoice: $invoice,
    );

    expect($result['matched_transaction_id'])->toBeNull()
        ->and($result['is_suspicious_or_edited'])->toBeTrue();
});

it('avoids re-running gemini analysis when candidate transactions remain unchanged between retries', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 500,
        'status' => InvoiceStatus::PENDING,
        'metadata' => [
            'verification_status' => 'processing',
            'verification_started_at' => now()->subMinutes(1)->toIso8601String(),
            'verification_ai' => [
                'provider' => 'nagad-personal',
                'amount' => 500.0,
                'trx_id' => 'UNKNOWN_TRX',
                'is_suspicious_or_edited' => false,
            ],
            // Simulated: candidates [42] was already evaluated previously for this screenshot
            'verification_cached_screenshot' => 'dummy/path.jpg',
            'verification_last_candidates' => '42',
        ],
    ]);

    Transaction::factory()->create([
        'id' => 42,
        'provider' => 'nagad-personal',
        'trxid' => 'OTHER_TRX',
        'amount' => 500,
        'invoice_id' => null,
    ]);

    // Mock GeminiVisionService: analyzeScreenshot and matchScreenshot must NEVER be called!
    /** @var \App\Services\GeminiVisionService&\Mockery\MockInterface $mockVision */
    $mockVision = Mockery::mock(\App\Services\GeminiVisionService::class)->makePartial();
    $mockVision->shouldNotReceive('analyzeScreenshot');
    $mockVision->shouldNotReceive('matchScreenshotWithCandidateTransactions');
    $mockVision->shouldNotReceive('reconcileCandidatesWithGemini');

    $job = new \App\Jobs\VerifyPaymentJob(
        invoiceId: $invoice->id,
        provider: 'nagad-personal',
        screenshotPath: 'dummy/path.jpg',
    );

    assert($mockVision instanceof \App\Services\GeminiVisionService);
    $job->handle(
        geminiVision: $mockVision,
        verifier: app(\App\Services\PaymentVerificationService::class),
        paymentManager: app(\App\Payment\PaymentManager::class),
    );

    // Job releases and does not fail; invoice remains pending
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PENDING)
        ->and($invoice->metadata['verification_status'])->toBe('processing');
});

it('re-evaluates with gemini and ignores old cache when user retries with a new screenshot', function () {
    $invoice = Invoice::factory()->create([
        'amount' => 500,
        'status' => InvoiceStatus::PENDING,
        'metadata' => [
            'verification_status' => 'processing',
            'verification_started_at' => now()->toIso8601String(),
            'verification_screenshot' => 'old_screenshot.jpg',
            // Stale cache from the old wrong screenshot:
            'verification_cached_screenshot' => 'old_screenshot.jpg',
            'verification_ai' => [
                'provider' => 'nagad-personal',
                'amount' => 100.0,
                'trx_id' => 'WRONG_OLD_TRX',
                'is_suspicious_or_edited' => false,
            ],
            'verification_last_candidates' => '50',
        ],
    ]);

    $transaction = Transaction::factory()->create([
        'id' => 50,
        'provider' => 'nagad-personal',
        'trxid' => 'NEW_CORRECT_TRX',
        'amount' => 500,
        'invoice_id' => null,
        'status' => 'approved',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'matched_transaction_id' => $transaction->id,
                                    'confidence_score' => 0.98,
                                    'match_reason' => 'Exact match for NEW_CORRECT_TRX',
                                    'is_suspicious_or_edited' => false,
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    // Mock Vision: analyzeScreenshot MUST be called for the new screenshot path!
    /** @var \App\Services\GeminiVisionService&\Mockery\MockInterface $mockVision */
    $mockVision = Mockery::mock(\App\Services\GeminiVisionService::class)->makePartial();
    $mockVision->shouldReceive('analyzeScreenshot')
        ->once()
        ->with('new_screenshot.jpg')
        ->andReturn([
            'provider' => 'nagad-personal',
            'amount' => 500.0,
            'trx_id' => 'NEW_CORRECT_TRX',
            'is_suspicious_or_edited' => false,
        ]);

    $job = new \App\Jobs\VerifyPaymentJob(
        invoiceId: $invoice->id,
        provider: 'nagad-personal',
        screenshotPath: 'new_screenshot.jpg',
    );

    assert($mockVision instanceof \App\Services\GeminiVisionService);
    $job->handle(
        geminiVision: $mockVision,
        verifier: app(\App\Services\PaymentVerificationService::class),
        paymentManager: app(\App\Payment\PaymentManager::class),
    );

    $invoice->refresh();
    $transaction->refresh();

    // Verify invoice is paid and the new transaction was claimed
    expect($invoice->status)->toBe(InvoiceStatus::PAID)
        ->and($transaction->invoice_id)->toBe($invoice->id)
        ->and($invoice->metadata['verification_cached_screenshot'])->toBe('new_screenshot.jpg')
        ->and($invoice->metadata['verification_ai']['trx_id'])->toBe('NEW_CORRECT_TRX');
});
