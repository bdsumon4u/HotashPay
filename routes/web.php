<?php

use App\Filament\Pages\MakePayment;
use App\Http\Controllers\PluginImageController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => User::query()->doesntExist(),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';

Route::get('hotash/plugins/{plugin}/image', PluginImageController::class)->name('plugins.image');
Route::get('payment/{invoice:ulid}', MakePayment::class)->name('invoices.pay');
