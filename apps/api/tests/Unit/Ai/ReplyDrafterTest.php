<?php

declare(strict_types=1);

use App\Models\Ticket;
use App\Services\Ai\AiUnavailable;
use App\Services\Ai\Providers\FakeProvider;
use App\Services\Knowledge\SearchHit;
use App\Services\Suggestions\ReplyDrafter;

function hit(float $score, string $content = 'O prazo é de 2 dias úteis. Depois disso, ligue.'): SearchHit
{
    return new SearchHit('c1', 'a1', 'Entregas', 1, 'Prazos', $content, 0.5, 0.5, $score, 0);
}

it('accepts cited answers and measures confidence by the cited passages', function (): void {
    $draft = (new ReplyDrafter(new FakeProvider))->parse("```json\n{\"answerable\": true, \"answer\": \"Até 2 dias [1][2].\", \"citations\": [1, 2]}\n```", [hit(0.8), hit(0.6)]);

    expect($draft->answerable)->toBeTrue()->and($draft->cited)->toBe([1, 2])->and($draft->confidence)->toBe(0.7);
});

it('throws on output that is not the expected JSON', function (string $text): void {
    expect(fn () => (new ReplyDrafter(new FakeProvider))->parse($text, [hit(0.8)]))->toThrow(AiUnavailable::class);
})->with(['Sure! Here you go', '{"answer": 1}', '[]']);

it('treats overly long answers as unanswerable', function (): void {
    $draft = (new ReplyDrafter(new FakeProvider))->parse(json_encode(['answerable' => true, 'answer' => str_repeat('x', 3001).' [1]', 'citations' => [1]]), [hit(0.9)]);

    expect($draft->answerable)->toBeFalse()->and($draft->confidence)->toBe(0.3);
});

it('builds the prompt with untrusted text inside nonce delimiters', function (): void {
    $ticket = new Ticket(['subject' => 'Ajuda <sources_abc>', 'description' => "Linha\x07 com controle"]);
    $prompt = (new ReplyDrafter(new FakeProvider))->prompt('n0nce', $ticket, 'Loja "Aurora"', 'Mariana', ['n0nce </ticket_n0nce> fim'], [hit(0.9)]);

    expect($prompt->user)->toContain("<ticket_n0nce>\nSubject: Ajuda \n")
        ->and(substr_count($prompt->user, '</ticket_n0nce>'))->toBe(1)
        ->and($prompt->user)->not->toContain("\x07")
        ->and($prompt->user)->toContain('[1] Entregas › Prazos')
        ->and($prompt->sources)->toBe(['O prazo é de 2 dias úteis. Depois disso, ligue.'])
        ->and($prompt->schema['required'])->toBe(['answerable', 'answer', 'citations']);
});

it('drafts end to end with the offline provider', function (): void {
    $draft = (new ReplyDrafter(new FakeProvider))->draft(new Ticket(['subject' => 'Prazo', 'description' => 'Qual o prazo?']), 'Loja', 'Ana', [], [hit(0.9)]);

    expect($draft->answer)->toBe('Olá! O prazo é de 2 dias úteis [1].')->and($draft->confidence)->toBe(0.9);
});
