<?php

namespace App\Repositories;

use App\Contracts\CepRepository;
use App\DTO\CepDTO;
use Illuminate\Support\Facades\Http;

class BrasilApiCepRepository implements CepRepository
{
    public function find(string $cep): CepDTO|null
    {
        $response = Http::timeout(5)->get("https://brasilapi.com.br/api/cep/v2/{$cep}");

        if($response->status() === 404) {
            return null;
        }

        if ($response->status() !== 200) {
            $response->throw();
        }

        $data = $response->json();

        return new CepDTO(
            $data['cep'],
            $data['street'],
            $data['neighborhood'],
            $data['city'],
            $data['state'],
        );
    }
}
