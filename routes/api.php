<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CepController;

Route::get('/cep/{cep}', [CepController::class, 'show']);