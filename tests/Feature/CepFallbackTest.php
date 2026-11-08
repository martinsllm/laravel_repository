<?php

namespace Tests\Feature;

use App\Repositories\BrasilApiCepRepository;
use App\Repositories\CepFallback;
use App\Repositories\ViaCepRepository;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CepFallbackTest extends TestCase
{
    private const VIA_CEP_URL = 'https://viacep.com.br/ws/01001000/json/';

    private const BRASIL_API_URL = 'https://brasilapi.com.br/api/cep/v2/01001000';

    public function test_find_returns_viacep_address_without_calling_brasil_api(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            self::VIA_CEP_URL => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ], 200),
        ]);

        $result = $this->fallback()->find('01001000');

        $this->assertNotNull($result);
        $this->assertSame('Praça da Sé', $result->logradouro);
        $this->assertSame('01001000', $result->cep);
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->url() === self::BRASIL_API_URL);
    }

    public function test_find_uses_brasil_api_when_viacep_does_not_find_the_cep(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            self::VIA_CEP_URL => Http::response(['erro' => true], 200),
            self::BRASIL_API_URL => Http::response([
                'cep' => '01001000',
                'street' => 'Rua Direita',
                'neighborhood' => 'Sé',
                'city' => 'São Paulo',
                'state' => 'SP',
            ], 200),
        ]);

        $result = $this->fallback()->find('01001000');

        $this->assertNotNull($result);
        $this->assertSame('Rua Direita', $result->logradouro);
        $this->assertSame('São Paulo', $result->cidade);
        $this->assertSame('SP', $result->uf);
        Http::assertSentCount(2);
    }

    public function test_find_uses_brasil_api_when_viacep_fails(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            self::VIA_CEP_URL => Http::response(['message' => 'erro'], 500),
            self::BRASIL_API_URL => Http::response([
                'cep' => '01001000',
                'street' => 'Rua Direita',
                'neighborhood' => 'Sé',
                'city' => 'São Paulo',
                'state' => 'SP',
            ], 200),
        ]);

        $result = $this->fallback()->find('01001000');

        $this->assertNotNull($result);
        $this->assertSame('Rua Direita', $result->logradouro);
        Http::assertSentCount(2);
    }

    public function test_find_returns_null_when_both_apis_do_not_find_the_cep(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            self::VIA_CEP_URL => Http::response(['erro' => true], 200),
            self::BRASIL_API_URL => Http::response(['message' => 'CEP não encontrado'], 404),
        ]);

        $result = $this->fallback()->find('01001000');

        $this->assertNull($result);
        Http::assertSentCount(2);
    }

    public function test_find_throws_when_both_apis_fail(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            self::VIA_CEP_URL => Http::response(['message' => 'erro'], 500),
            self::BRASIL_API_URL => Http::response(['message' => 'erro'], 500),
        ]);

        $this->expectException(RequestException::class);

        $this->fallback()->find('01001000');
    }

    private function fallback(): CepFallback
    {
        return new CepFallback(new ViaCepRepository(), new BrasilApiCepRepository());
    }
}
