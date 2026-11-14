<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Sales API routes
Route::middleware('api')->prefix('v1/orders')->group(function () {
    // API endpoints can be defined here
});
