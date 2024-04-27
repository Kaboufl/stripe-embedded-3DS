<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('checkout', 'checkout')
    ->middleware('auth')
    ->name('checkout');

Route::view('stripe_return', '3DScompleted')->name('stripe_return');

require __DIR__.'/auth.php';
