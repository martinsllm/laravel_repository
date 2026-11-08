<?php

namespace App\Http\Controllers;

use App\Contracts\CepRepository;

class CepController extends Controller
{
    public function __construct(private CepRepository $cepRepository)
    {
    }

    public function show(string $cep)
    {
        $cep = preg_replace('/\D/', '', $cep);

        if(!preg_match('/^\d{8}$/', $cep)) {
            return response()->json(['error' => 'CEP inválido'], 422);
        }

        $data = $this->cepRepository->find($cep);

        if (!$data) {
            return response()->json(['error' => 'CEP não encontrado'], 404);
        }

        return response()->json($data->toArray());
    }
}
