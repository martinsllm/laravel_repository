<?php

use Illuminate\Support\Facades\Route;
use App\Http\CepController;

Route::get('/cep/{cep}', [CepController::class, 'show']);