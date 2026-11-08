# Repository pattern com busca de CEP

Estudo do repository pattern no Laravel. O exemplo busca CEP em duas APIs públicas, ViaCEP e BrasilAPI. Cada uma devolve um JSON diferente. O controller chama `CepRepository` e recebe um `CepDTO`, sem saber qual API respondeu.

## Como o padrão aparece aqui

`CepRepository` é a pergunta da aplicação: dado um CEP de 8 dígitos, qual é o endereço? Quem responde são três classes.

| Classe | Papel |
|---|---|
| `ViaCepRepository` | Chama a ViaCEP e traduz o JSON dela para `CepDTO`. CEP inexistente vem como HTTP 200 com `erro: true` e vira `null`. |
| `BrasilApiCepRepository` | Chama a BrasilAPI v2 e faz a mesma tradução. CEP inexistente é HTTP 404 e também vira `null`. |
| `CepFallback` | Implementa o mesmo contrato. Tenta a ViaCEP e, se ela devolver `null` ou falhar, tenta a BrasilAPI. |

O container, no `AppServiceProvider`, entrega `CepFallback` sempre que alguém pede `CepRepository`. Trocar a fonte é mudar esse bind. O controller continua igual.

`CepDTO` é o formato único da resposta: `cep`, `logradouro`, `bairro`, `cidade` e `uf`. Campos como `localidade`, `street` e `state` ficam dentro de cada repositório.

## Endpoint

`GET /api/cep/{cep}`

O CEP pode vir com máscara. A rota fica só com os dígitos antes de chamar o repositório.

| Situação | Status |
|---|---|
| Endereço encontrado | 200, com as cinco chaves do DTO |
| Menos ou mais de 8 dígitos | 422 |
| Nenhuma das APIs encontrou o CEP | 404 |
| As duas APIs falharam | 500 |

Exemplo:

```http
GET /api/cep/01001000
```

```json
{
  "cep": "01001000",
  "logradouro": "Praça da Sé",
  "bairro": "Sé",
  "cidade": "São Paulo",
  "uf": "SP"
}
```

## Subir o projeto

Requisitos: PHP 8.3 e Composer.

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

A URL local fica `http://127.0.0.1:8000/api/cep/01001000`.

## Testes

Os testes não chamam a internet. Cada um usa `Http::fake()`.

```sh
php artisan test
```
