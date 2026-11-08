<?php

namespace App\Contracts;

use App\DTO\CepDTO;

interface CepRepository
{
    public function find(string $cep): CepDTO|null;
}
