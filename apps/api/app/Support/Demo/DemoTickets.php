<?php

declare(strict_types=1);

namespace App\Support\Demo;

use App\Enums\TicketPriority as P;
use App\Enums\TicketStatus as S;

/**
 * The demo tenant's tickets ("Loja Aurora", an online fashion store).
 * Everything is fictional: people, orders, amounts and addresses.
 *
 * Each ticket: subject, description, requester, category (null = left to
 * the classifier), priority, final status, opened N hours ago, assignee,
 * conversation [author, body, minutes after opening, internal?], what
 * happens to the AI suggestion, and an agent's correction of the
 * classification, if any.
 *
 * Suggestion: approve | edit | discard:<reason> | review (ready, not
 * reviewed yet) | insufficient (the knowledge base has no answer) | failed
 * (provider timeout) | null.
 */
final class DemoTickets
{
    /** Customers: key => [name, e-mail]. "customer" is the one who can sign in on the app. */
    public const array CUSTOMERS = [
        'customer' => ['Mariana Costa', 'customer@acme.test'],
        'joao' => ['João Pereira', 'joao.pereira@acme.test'],
        'beatriz' => ['Beatriz Lima', 'beatriz.lima@acme.test'],
        'rafael' => ['Rafael Souza', 'rafael.souza@acme.test'],
        'camila' => ['Camila Rocha', 'camila.rocha@acme.test'],
        'lucas' => ['Lucas Almeida', 'lucas.almeida@acme.test'],
    ];

    /** Staff: key => [name, e-mail, role]. */
    public const array STAFF = [
        'admin' => ['Helena Duarte', 'admin@acme.test', 'admin'],
        'agent' => ['Ana Souza', 'agent@acme.test', 'agent'],
        'caio' => ['Caio Freitas', 'caio.freitas@acme.test', 'agent'],
    ];

    /** @var array<string, string> name => description */
    public const array CATEGORIES = [
        'Entrega' => 'Atrasos, rastreio parado, endereço errado ou produto avariado.',
        'Pagamento' => 'Cobranças, estornos e parcelamento.',
        'Troca e devolução' => 'Trocas de tamanho, devoluções e reembolsos.',
        'Conta e acesso' => 'Login, senha e dados cadastrais.',
        'Produto' => 'Dúvidas sobre tamanho, material, defeitos e disponibilidade.',
        'Outro' => 'Elogios, sugestões e assuntos gerais.',
    ];

    /**
     * @return list<array{
     *     subject: string, description: string, requester: string, category: string|null, priority: P,
     *     status: S, hours: int, assignee: string|null, messages: list<array{string, string, int, bool}>,
     *     suggestion: string|null, correct: array<string, string|null>
     * }>
     */
    public static function all(): array
    {
        return [
            // --- Needs attention now: overdue or close to the deadline -------------
            self::t('Cobrança em duplicidade no cartão de crédito', 'Fui cobrado duas vezes pelo pedido #88190 de R$ 349,90. Preciso do estorno de uma das cobranças.',
                'joao', 'Pagamento', P::Urgent, S::Open, 2, 'agent', suggestion: 'review'),
            self::t('Pedido #88213 não chegou e o rastreio parou há 6 dias', 'O rastreio está parado em "Em trânsito" desde o dia 06/10.',
                'customer', 'Entrega', P::High, S::Open, 1, 'agent'),
            self::t('O código de verificação não chega e não consigo entrar', 'Já tentei três vezes e o SMS não chega.',
                'beatriz', 'Conta e acesso', P::High, S::Open, 30, null, suggestion: 'insufficient'),
            self::t('Rastreamento sem atualização e preciso para o casamento no sábado', 'Comprei o vestido azul (pedido #88250) e o rastreamento não muda desde terça. É para um casamento no sábado, estou preocupada.',
                'camila', null, P::Normal, S::Open, 9, 'caio', suggestion: 'approve'),
            self::t('Paguei o boleto e o pedido continua aguardando pagamento', 'Paguei anteontem e preciso do produto para sexta. Estou bem chateada.',
                'rafael', null, P::Normal, S::Open, 1, null, correct: ['priority' => 'urgent']),
            self::t('Cliente irritado: terceira cobrança do frete', 'Já é a terceira vez que cobram o frete do pedido #88302. Quero falar com alguém agora.',
                'lucas', 'Pagamento', P::Urgent, S::Open, 3, null, suggestion: 'failed'),

            // --- Waiting on the customer (pending) -----------------------------------
            self::t('Estorno da devolução ainda não caiu na fatura', 'Devolvi o produto há 15 dias e o estorno não apareceu.',
                'customer', 'Pagamento', P::High, S::Pending, 6, 'agent', [
                    ['agent', 'Oi! Já pedimos o estorno à operadora; ele aparece em até 2 faturas.', 20, false],
                ], suggestion: null),
            self::t('Quero trocar o tênis por um número maior', 'O 41 ficou apertado, queria o 42.',
                'joao', 'Troca e devolução', P::Normal, S::Pending, 20, 'caio', suggestion: 'edit'),
            self::t('Caixa chegou amassada, mas o produto está perfeito', 'Só para avisar, a embalagem veio bem danificada.',
                'beatriz', 'Entrega', P::Low, S::Pending, 50, 'agent', [
                    ['agent', 'Obrigada pelo aviso, Beatriz! Vamos registrar com a transportadora.', 90, false],
                    ['agent', 'Abrir ocorrência com a transportadora (embalagem).', 95, true],
                ]),
            self::t('Como altero o endereço de entrega de um pedido já pago?', 'Mudei de apartamento ontem.',
                'rafael', 'Entrega', P::Normal, S::Pending, 3, 'caio', suggestion: 'discard:wrong_information'),

            // --- Open, on time ----------------------------------------------------------
            self::t('Adorei o atendimento, só queria trocar a cor', 'A blusa veio perfeita, mas prefiro a azul. Obrigada pela ajuda!',
                'camila', null, P::Normal, S::Open, 2, null),
            self::t('Cupom PRIMEIRA10 não é aplicado no carrinho', 'Aparece "cupom inválido".',
                'lucas', 'Pagamento', P::Low, S::Open, 4, null, suggestion: 'discard:outdated_source'),
            self::t('Posso parcelar em 10 vezes sem juros?', 'Queria comprar o casaco de lã e dividir no cartão.',
                'customer', null, P::Low, S::Open, 5, null, suggestion: 'review'),
            self::t('A jaqueta veio com o zíper quebrado', 'Recebi hoje e o zíper não fecha. Dá para trocar por outra igual?',
                'joao', null, P::Normal, S::Open, 7, 'agent', correct: ['category' => 'Produto']),
            self::t('Esqueci a senha e o e-mail de recuperação não chega', 'Tentei recuperar a senha duas vezes.',
                'beatriz', null, P::Normal, S::Open, 2, null, suggestion: 'approve'),
            self::t('Vocês têm loja física em Curitiba?', 'Queria experimentar antes de comprar.',
                'rafael', null, P::Low, S::Open, 6, null, suggestion: 'insufficient'),
            self::t('O tamanho M da calça jeans serve para quem veste 40?', 'Não achei a tabela de medidas no site.',
                'camila', 'Produto', P::Low, S::Open, 10, null),
            self::t('Atualizar o CPF cadastrado na minha conta', 'Digitei errado no cadastro e não consigo editar.',
                'lucas', null, P::Normal, S::Open, 12, 'caio', correct: ['sentiment' => 'negative']),

            // --- Resolved and closed -----------------------------------------------
            self::t('Reembolso do pedido cancelado', 'Cancelei o pedido #88001 e quero saber quando o dinheiro volta.',
                'customer', 'Pagamento', P::Normal, S::Resolved, 72, 'agent', [
                    ['agent', 'Oi, Mariana! O reembolso foi solicitado e aparece em até 5 dias úteis.', 45, false],
                    ['customer', 'Caiu hoje, obrigada!', 60 * 30, false],
                ]),
            self::t('Troca de tamanho do vestido floral', 'O P ficou pequeno, gostaria do M.',
                'beatriz', 'Troca e devolução', P::Normal, S::Resolved, 96, 'caio', [
                    ['caio', 'Claro! Enviamos o código de postagem por e-mail.', 30, false],
                    ['beatriz', 'Recebi o novo, ficou ótimo.', 60 * 48, false],
                ], suggestion: 'approve'),
            self::t('Não consigo mudar a senha pelo app', 'O botão de salvar fica cinza.',
                'rafael', 'Conta e acesso', P::Normal, S::Closed, 140, 'agent', [
                    ['agent', 'Corrigimos o problema na versão 2.3 do app. Pode atualizar?', 120, false],
                    ['rafael', 'Atualizei e funcionou.', 60 * 20, false],
                ]),
            self::t('Pedido entregue no vizinho', 'O entregador deixou com o vizinho sem avisar.',
                'joao', 'Entrega', P::High, S::Closed, 160, 'caio', [
                    ['caio', 'Sentimos muito, João! Já registramos a reclamação com a transportadora.', 50, false],
                ]),
            self::t('Elogio para a equipe de atendimento', 'Fui muito bem atendida na última troca, parabéns!',
                'camila', 'Outro', P::Low, S::Resolved, 30, 'agent', [
                    ['agent', 'Que bom saber, Camila! Vamos repassar para a equipe.', 120, false],
                ]),
            self::t('Desconto não aplicado na Black Friday', 'O preço no carrinho estava sem o desconto anunciado.',
                'lucas', 'Pagamento', P::Normal, S::Resolved, 200, 'agent', [
                    ['agent', 'Estornamos a diferença de R$ 40,00 no seu cartão.', 180, false],
                ], suggestion: 'discard:tone'),
            self::t('Atraso na entrega para o interior de Minas', 'Meu pedido para Poços de Caldas está atrasado 4 dias.',
                'customer', 'Entrega', P::Normal, S::Closed, 300, 'caio', [
                    ['caio', 'O pedido foi entregue hoje pela manhã. Obrigado pela paciência!', 60 * 26, false],
                ]),
        ];
    }

    /**
     * @param  list<array{string, string, int, bool}>  $messages
     * @param  array<string, string|null>  $correct
     * @return array{subject: string, description: string, requester: string, category: string|null, priority: P, status: S, hours: int, assignee: string|null, messages: list<array{string, string, int, bool}>, suggestion: string|null, correct: array<string, string|null>}
     */
    private static function t(
        string $subject,
        string $description,
        string $requester,
        ?string $category,
        P $priority,
        S $status,
        int $hours,
        ?string $assignee,
        array $messages = [],
        ?string $suggestion = null,
        array $correct = [],
    ): array {
        return [
            'subject' => $subject, 'description' => $description, 'requester' => $requester, 'category' => $category,
            'priority' => $priority, 'status' => $status, 'hours' => $hours, 'assignee' => $assignee,
            'messages' => $messages, 'suggestion' => $suggestion, 'correct' => $correct,
        ];
    }
}
