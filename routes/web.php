<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\DaftraWebhookController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', LocaleController::class)->name('locale');

Route::post('/webhooks/daftra', DaftraWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.daftra');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/login/code', [LoginOtpController::class, 'create'])->name('login.otp');
    Route::post('/login/code', [LoginOtpController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/login/code/resend', [LoginOtpController::class, 'resend'])->middleware('throttle:3,1')->name('login.otp.resend');
});

// Storefront: public and client-facing. Data always comes from the local database.
Route::get('/', StorefrontController::class)->name('home');
Route::post('/vehicle', [VehicleController::class, 'store'])->name('vehicle.store');
Route::delete('/vehicle', [VehicleController::class, 'destroy'])->name('vehicle.destroy');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', Portal\DashboardController::class)->name('dashboard');
    Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)->middleware('throttle:30,1')->name('invoices.pdf');

    Route::middleware('role:client')->group(function () {
        Route::get('/invoices', [Portal\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [Portal\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/payments', Portal\PaymentController::class)->name('payments.index');
        Route::get('/account', Portal\AccountController::class)->name('account');

        Route::get('/cart', [Portal\CartController::class, 'show'])->name('cart.show');
        Route::get('/cart/summary', [Portal\CartController::class, 'summary'])->name('cart.summary');
        Route::post('/cart/items', [Portal\CartController::class, 'store'])->name('cart.items.store');
        Route::patch('/cart/items/{item}', [Portal\CartController::class, 'update'])->name('cart.items.update');
        Route::delete('/cart/items/{item}', [Portal\CartController::class, 'destroy'])->name('cart.items.destroy');
        Route::post('/cart/submit', [Portal\CartController::class, 'submit'])->name('cart.submit');

        Route::get('/orders', [Portal\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Portal\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/cancel', [Portal\OrderController::class, 'cancel'])->name('orders.cancel');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', Admin\DashboardController::class)->name('dashboard');
        Route::resource('products', Admin\ProductController::class)->only(['index', 'edit', 'update']);
        Route::resource('brands', Admin\BrandController::class)->except('show');
        Route::resource('car-models', Admin\CarModelController::class)->except('show');
        Route::resource('tags', Admin\TagController::class)->except('show');
        Route::resource('categories', Admin\CategoryController::class)->except('show');
        Route::get('/clients', [Admin\ClientController::class, 'index'])->name('clients.index');
        Route::get('/clients/{client}', [Admin\ClientController::class, 'show'])->name('clients.show');
        Route::get('/invoices', [Admin\InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [Admin\InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/payments', Admin\PaymentController::class)->name('payments.index');
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
        Route::get('/orders/{order}/invoices', [Admin\OrderController::class, 'invoices'])->name('orders.invoices');
        Route::get('/webhooks', Admin\WebhookController::class)->name('webhooks.index');
        Route::get('/sync', [Admin\SyncController::class, 'index'])->name('sync.index');
        Route::post('/sync', [Admin\SyncController::class, 'store'])->name('sync.store');
        Route::post('/sync/test', [Admin\SyncController::class, 'test'])->name('sync.test');
        Route::resource('users', Admin\UserController::class)->except('show');
        Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});
