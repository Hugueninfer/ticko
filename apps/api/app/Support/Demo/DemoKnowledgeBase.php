<?php

declare(strict_types=1);

namespace App\Support\Demo;

use App\Enums\ArticleVisibility;

/**
 * Knowledge-base articles of the demo store ("Loja Aurora"), matching the
 * demo tickets so reply suggestions have something to cite.
 */
final class DemoKnowledgeBase
{
    /**
     * Title, category name (or null), visibility, publish, Markdown body.
     *
     * @return list<array{string, string|null, ArticleVisibility, bool, string}>
     */
    public static function articles(): array
    {
        return [
            ['Política de entregas e prazos', 'Entrega', ArticleVisibility::Customers, true, <<<'MD'
                ## 1. Prazos por região
                Sul e Sudeste: 3 a 5 dias úteis após a confirmação do pagamento. Demais regiões: 5 a 10 dias úteis.

                ## 2. Como acompanhar
                O código de rastreio é enviado por e-mail e aparece em Meus pedidos assim que a nota fiscal é emitida.

                ## 3. Rastreio sem atualização
                Se o rastreamento não for atualizado por mais de 5 dias úteis, abrimos uma investigação junto à transportadora. O prazo de resposta da transportadora é de até 2 dias úteis.

                - Informamos o cliente sobre a investigação no mesmo dia.
                - Se o cliente tiver urgência, veja o artigo Reenvio e reembolso por atraso.

                ## 4. Entrega em endereço errado
                Pedidos já despachados não podem mudar de endereço. Se a entrega falhar, o pedido volta para o nosso centro de distribuição e é reenviado sem custo para o endereço correto.
                MD],
            ['Reenvio e reembolso por atraso', 'Entrega', ArticleVisibility::Customers, true, <<<'MD'
                ## Quando oferecer
                Em atrasos confirmados pela transportadora, ofereça ao cliente reenvio expresso sem custo ou reembolso integral, à escolha dele.

                ## Reenvio expresso
                O reenvio expresso chega em até 2 dias úteis nas capitais do Sul e Sudeste. O pedido original, se chegar, pode ser recusado na entrega.

                ## Reembolso
                O reembolso integral é feito na mesma forma de pagamento: no cartão aparece em até 2 faturas; no Pix, em até 1 dia útil.
                MD],
            ['Trocas e devoluções em até 30 dias', 'Troca e devolução', ArticleVisibility::Customers, true, <<<'MD'
                ## Prazo
                A troca ou devolução pode ser pedida em até 30 dias após o recebimento, com o produto sem uso e na embalagem original.

                ## Como devolver
                Para devolver, gere a etiqueta em Meus pedidos e leve o pacote a uma agência dos Correios. A postagem é gratuita.

                ## Troca de tamanho
                A troca de tamanho é feita assim que o produto devolvido chega ao nosso centro de distribuição, sujeita a estoque.
                MD],
            ['Cobrança em duplicidade: como estornar', 'Pagamento', ArticleVisibility::Customers, true, <<<'MD'
                ## O que acontece
                Às vezes a operadora do cartão mostra duas autorizações para o mesmo pedido. Uma delas é uma pré-autorização e some sozinha em até 5 dias úteis.

                ## Quando estornamos
                Se as duas cobranças aparecerem na fatura fechada, pedimos o estorno à operadora no mesmo dia. O valor aparece em até 2 faturas.
                MD],
            ['Recuperar acesso à conta', 'Conta e acesso', ArticleVisibility::Customers, true, <<<'MD'
                ## Código de verificação
                O código de verificação é enviado por SMS e por e-mail e vale por 10 minutos. Confira a caixa de spam antes de pedir outro.

                ## Sem acesso ao celular
                Sem acesso ao número cadastrado, o cliente pode trocar o telefone pela opção Atualizar telefone, confirmando o e-mail da conta.
                MD],
            ['Formas de pagamento e parcelamento', 'Pagamento', ArticleVisibility::Customers, true, <<<'MD'
                ## Formas aceitas
                Aceitamos cartão de crédito, Pix e boleto. O boleto vence em 2 dias úteis.

                ## Parcelamento
                Compras acima de R$ 100 podem ser parceladas em até 10 vezes sem juros no cartão de crédito.
                MD],
            ['Aprovação interna de estornos', 'Pagamento', ArticleVisibility::Internal, true, <<<'MD'
                ## Alçadas
                Estornos até R$ 500 são aprovados pelo próprio atendente. Acima disso, peça aprovação ao financeiro no canal interno.
                MD],
            ['Cupons e promoções vigentes', null, ArticleVisibility::Customers, false, <<<'MD'
                ## PRIMEIRA10
                10% de desconto na primeira compra, acima de R$ 150. Não vale com outras promoções.
                MD],
        ];
    }
}
