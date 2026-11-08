<?php

namespace Tests\Feature;

use App\Repositories\ViaCepRepository;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViaCepRepositoryTest extends TestCase
{
    public function test_find_returns_successful_when_cep_is_found(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ], 200),
        ]);

        $repository = new ViaCepRepository();
        $cep = '01001000';
        $result = $repository->find($cep);

        $this->assertNotNull($result);
        $this->assertEquals('Praça da Sé', $result->logradouro);
        $this->assertEquals('Sé', $result->bairro);
        $this->assertEquals('São Paulo', $result->cidade);
        $this->assertEquals('SP', $result->uf);
        $this->assertEquals('01001000', $result->cep);
        $this->assertSame(
            ['cep', 'logradouro', 'bairro', 'cidade', 'uf'],
            array_keys($result->toArray()),
        );

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://viacep.com.br/ws/01001000/json/';
        });
    }

    public function test_find_returns_null_when_cep_is_not_found(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response([
                'erro' => true,
            ], 200),
        ]);

        $repository = new ViaCepRepository();
        $cep = '01001000';
        $result = $repository->find($cep);

        $this->assertNull($result);
    }

    public function test_find_throws_when_viacep_returns_server_error(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response(['message' => 'erro'], 500),
        ]);

        $repository = new ViaCepRepository();

        $this->expectException(RequestException::class);

        $repository->find('01001000');
    }
}
