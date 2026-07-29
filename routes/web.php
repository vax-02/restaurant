<?php

use App\Http\Controllers\BuyController;
use App\Http\Controllers\DailyAvailabilityController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

 
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // CRUD de usuarios (solo administradores)
    Route::resource('users', UserController::class);
    Route::get('buys/all', [BuyController::class, 'all'])->name('buys.all');
    Route::put('buys/{buy}/assign-delivery', [BuyController::class, 'assignDelivery'])->name('buys.assign-delivery');
    Route::put('buys/{buy}/cancel', [BuyController::class, 'cancel'])->name('buys.cancel');
    Route::resource('buys', BuyController::class);
    Route::resource('deliveries', DeliveryController::class);
    Route::post('deliveries/{delivery}/regenerate-code', [DeliveryController::class, 'regenerateCode'])->name('deliveries.regenerate-code');


    Route::resource('products', ProductController::class);
    Route::post('products/toggle', [ProductController::class,'toggleAvailable'])->name('products.toggle');


    Route::get('availability', [DailyAvailabilityController::class,  'index'])
        ->name('availability.index');
    Route::post('availability', [DailyAvailabilityController::class, 'update'])
        ->name('availability.update');
    Route::post('availability/reset', [DailyAvailabilityController::class, 'resetToday'])
        ->name('availability.reset');
    Route::post('availability/{product}/stock', [DailyAvailabilityController::class, 'updateStock'])
        ->name('availability.stock');

    Route::middleware(['can:admin'])->group(function () {
    });
});