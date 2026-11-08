<?php

namespace App\DTO;

class CepDTO
{
    public function __construct(
        public readonly string $cep,
        public readonly string $logradouro,
        public readonly string $bairro,
        public readonly string $cidade,
        public readonly string $uf,
    ) {}

    public function toArray(): array
    {
        return [
            'cep' => $this->cep,
            'logradouro' => $this->logradouro,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'uf' => $this->uf,
        ];
    }
}