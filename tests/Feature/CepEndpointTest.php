<?php

namespace Tests\Feature;

use App\Contracts\CepRepository;
use App\Repositories\BrasilApiCepRepository;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CepEndpointTest extends TestCase
{
    public function test_show_returns_address_from_the_active_repository(): void
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

        $response = $this->getJson('/api/cep/01001000');

        $response->assertOk();
        $response->assertExactJson([
            'cep' => '01001000',
            'logradouro' => 'Praça da Sé',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
        ]);

        Http::assertSentCount(1);
    }

    public function test_show_accepts_a_cep_with_hyphen(): void
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

        $response = $this->getJson('/api/cep/01001-000');

        $response->assertOk();
        $response->assertExactJson([
            'cep' => '01001000',
            'logradouro' => 'Praça da Sé',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
        ]);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://viacep.com.br/ws/01001000/json/';
        });
    }

    public function test_show_returns_422_when_cep_does_not_have_eight_digits(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $response = $this->getJson('/api/cep/123');

        $response->assertStatus(422);
        $response->assertExactJson(['error' => 'CEP inválido']);
        Http::assertNothingSent();
    }

    public function test_show_returns_404_when_repository_does_not_find_the_cep(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://viacep.com.br/ws/01001000/json/' => Http::response([
                'erro' => true,
            ], 200),
            'https://brasilapi.com.br/api/cep/v2/01001000' => Http::response([
                'message' => 'CEP não encontrado',
            ], 404),
        ]);

        $response = $this->getJson('/api/cep/01001000');

        $response->assertNotFound();
        $response->assertExactJson(['error' => 'CEP não encontrado']);
        Http::assertSentCount(2);
    }

    public function test_show_returns_the_same_json_when_the_binding_changes(): void
    {
        $this->app->singleton(CepRepository::class, BrasilApiCepRepository::class);

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

        $response = $this->getJson('/api/cep/01001000');

        $response->assertOk();
        $response->assertExactJson([
            'cep' => '01001000',
            'logradouro' => 'Praça da Sé',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://brasilapi.com.br/api/cep/v2/01001000';
        });
    }
}
