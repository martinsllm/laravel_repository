<?php

namespace App\Repositories;

use App\Contracts\CepRepository;
use App\DTO\CepDTO;
use Illuminate\Support\Facades\Log;

class CepFallback implements CepRepository
{
    public function __construct(private ViaCepRepository $viaCepRepository, private BrasilApiCepRepository $brasilApiCepRepository)
    {
    }

    public function find(string $cep): CepDTO|null
    {
        try {
            $data = $this->viaCepRepository->find($cep);

            if ($data) {
                return $data;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao buscar CEP na Via Cep: ' . $e->getMessage());
        }

        return $this->brasilApiCepRepository->find($cep);
    }
}