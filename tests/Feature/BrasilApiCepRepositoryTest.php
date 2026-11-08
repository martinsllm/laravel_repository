<?php

namespace Tests\Feature;

use App\Repositories\BrasilApiCepRepository;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrasilApiCepRepositoryTest extends TestCase
{
    public function test_find_returns_successful_when_cep_is_found(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/01001000' => Http::response([
                'cep' => '01001000',
                'street' => 'Praça da Sé',
                'neighborhood' => 'Sé',
                'city' => 'São Paulo',
                'state' => 'SP',
                'service' => 'open-cep',
            ], 200),
        ]);

        $repository = new BrasilApiCepRepository();
        $result = $repository->find('01001000');

        $this->assertNotNull($result);
        $this->assertEquals('01001000', $result->cep);
        $this->assertEquals('Praça da Sé', $result->logradouro);
        $this->assertEquals('Sé', $result->bairro);
        $this->assertEquals('São Paulo', $result->cidade);
        $this->assertEquals('SP', $result->uf);
        $this->assertSame(
            ['cep', 'logradouro', 'bairro', 'cidade', 'uf'],
            array_keys($result->toArray()),
        );

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://brasilapi.com.br/api/cep/v2/01001000';
        });
    }

    public function test_find_returns_null_when_cep_is_not_found(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/01001000' => Http::response([
                'message' => 'CEP não encontrado',
            ], 404),
        ]);

        $repository = new BrasilApiCepRepository();
        $result = $repository->find('01001000');

        $this->assertNull($result);
    }

    public function test_find_throws_when_brasil_api_returns_server_error(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/01001000' => Http::response(['message' => 'erro'], 500),
        ]);

        $repository = new BrasilApiCepRepository();

        $this->expectException(RequestException::class);

        $repository->find('01001000');
    }
}
