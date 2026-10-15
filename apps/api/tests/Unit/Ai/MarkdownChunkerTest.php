<?php

declare(strict_types=1);

use App\Services\Knowledge\MarkdownChunker;

it('splits by heading and turns Markdown into plain text', function (): void {
    $chunks = (new MarkdownChunker)->chunk('Entregas', <<<'MD'
        Introdução **importante** sobre o [rastreio](https://example.com).

        ## Prazos
        - Sul: 3 dias
        - Norte: `10 dias`

        ### Vazio

        ## Contato <b>agora</b>
        > Fale conosco.
        MD);

    expect(array_map(fn ($c) => [$c->ordinal, $c->heading, $c->content], $chunks))->toBe([
        [1, 'Entregas', 'Introdução importante sobre o rastreio.'],
        [2, 'Prazos', "• Sul: 3 dias\n• Norte: 10 dias"],
        [3, 'Contato agora', 'Fale conosco.'],
    ])->and($chunks[0]->embeddingInput('Entregas'))->toBe("Entregas › Entregas\nIntrodução importante sobre o rastreio.");
});

it('keeps passages near the target size with overlap', function (): void {
    $paragraph = str_repeat('Palavra de teste repetida para encher o texto. ', 20);
    $chunks = (new MarkdownChunker)->chunk('Longo', "## Seção\n\n".implode("\n\n", array_fill(0, 6, $paragraph)));

    expect(count($chunks))->toBeGreaterThan(1);

    foreach ($chunks as $chunk) {
        expect($chunk->tokens)->toBeLessThanOrEqual(MarkdownChunker::TARGET_TOKENS + MarkdownChunker::OVERLAP_TOKENS + 5);
    }

    expect($chunks[1]->content)->toStartWith('…');
});

it('cuts run-on text without sentences', function (): void {
    $chunks = (new MarkdownChunker)->chunk('Sem pontos', str_repeat('a', 3000));

    expect(count($chunks))->toBeGreaterThanOrEqual(3)->and(MarkdownChunker::estimateTokens(''))->toBe(1);
});
