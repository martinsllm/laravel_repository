<?php

namespace App\Repositories;

use App\Contracts\CepRepository;
use App\DTO\CepDTO;
use Illuminate\Support\Facades\Http;

class ViaCepRepository implements CepRepository
{
    public function find(string $cep): CepDTO|null
    {
        $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");

        if ($response->status() !== 200) {
            $response->throw();
        }

        $data = $response->json();

        if ($data['erro'] ?? false) {
            return null;
        }

        return new CepDTO(
            str_replace('-', '', $data['cep']),
            $data['logradouro'],
            $data['bairro'],
            $data['localidade'],
            $data['uf'],
        );
    }
}
