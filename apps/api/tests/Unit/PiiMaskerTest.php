<?php

declare(strict_types=1);

use App\Support\Privacy\PiiMasker;

it('masks personal data before it leaves the API', function (string $text, string $expected): void {
    expect(PiiMasker::mask($text))->toBe($expected);
})->with([
    'cpf' => ['meu cpf é 123.456.789-09', 'meu cpf é [CPF]'],
    'cpf digits' => ['cpf 12345678909', 'cpf [CPF]'],
    'cnpj' => ['empresa 12.345.678/0001-95', 'empresa [CNPJ]'],
    'card' => ['cartão 4111 1111 1111 1111', 'cartão [CARTAO]'],
    'card digits' => ['cartão 5555444433331111', 'cartão [CARTAO]'],
    'e-mail' => ['escreve pra fulana.tal@example.com', 'escreve pra [EMAIL]'],
    'phone' => ['liga no (11) 98765-4321', 'liga no [TELEFONE]'],
    'phone with country code' => ['whats +55 11 98765 4321', 'whats [TELEFONE]'],
]);

it('keeps order numbers, amounts and dates', function (): void {
    $text = 'Pedido #88213 de R$ 249,90 em 06/10, protocolo 2026.';

    expect(PiiMasker::mask($text))->toBe($text);
});
