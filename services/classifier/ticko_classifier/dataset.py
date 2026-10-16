"""Synthetic, fully fictional pt-BR support tickets with labels.

How it works (see README "Dataset"):

* Each category has sub-topics; each sub-topic has a base priority, a few
  subject lines and several phrasings of the problem ("templates").
* A ticket = optional greeting + problem + optional detail + urgency cue +
  sentiment cue + optional closing, with slots filled from fictional values
  (products, order numbers, dates, amounts) and realistic noise (no
  accents, lowercase, typos, chat abbreviations, emoji).
* Labels follow fixed rules: the category comes from the sub-topic; the
  priority is the sub-topic's base priority moved up by an urgency cue or
  down by a calm cue; the sentiment comes from the sentiment cue.
* The split is by phrasing, not by row: every problem template and every
  cue phrase belongs to exactly one of train / validation / test, so test
  tickets use wording the models never saw. Scores on the test split are
  therefore a fairer (if still optimistic) estimate than a random split.

Everything is generated from a fixed seed: the same command always writes
the same files. No real person, order, document or card appears anywhere.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import random
import unicodedata
from dataclasses import dataclass
from pathlib import Path

from .labels import PRIORITIES, ticket_text

SEED = 20261016
SPLITS = ("train", "validation", "test")


@dataclass(frozen=True)
class Topic:
    category: str
    name: str
    priority: str
    subjects: tuple[str, ...]
    problems: tuple[str, ...]
    details: tuple[str, ...] = ()


TOPICS: tuple[Topic, ...] = (
    # ------------------------------------------------------------------ entrega
    Topic(
        "entrega",
        "atraso",
        "normal",
        ("Pedido {order} atrasado", "Meu pedido ainda não chegou", "Entrega fora do prazo", "Cadê meu pedido {order}?"),
        (
            "Comprei {product} no dia {date} e até agora não recebi.",
            "O prazo de entrega do pedido {order} venceu {days} dias atrás e nada.",
            "Meu pedido {order} deveria ter chegado na semana passada.",
            "Já passou da data prevista e o {product} não chegou aqui em casa.",
            "Fiz a compra há {days} dias e a entrega ainda não aconteceu.",
            "A previsão era até {date}, mas o pedido segue sem chegar.",
            "Estou esperando o {product} faz {days} dias, o prazo já estourou.",
            "O site dizia entrega em 5 dias úteis e já são {days}.",
            "Até hoje não recebi o pedido {order}, comprado em {date}.",
            "O pedido {order} continua como 'a caminho' e já passou do prazo.",
        ),
        ("Moro em {city}.", "Paguei frete expresso.", "O endereço está correto no cadastro."),
    ),
    Topic(
        "entrega",
        "rastreio",
        "normal",
        ("Rastreio parado", "Rastreamento não atualiza", "Código de rastreio sem movimentação", "Pedido {order} parado na transportadora"),
        (
            "O rastreio do pedido {order} está parado há {days} dias.",
            "O código de rastreamento não mostra nenhuma atualização desde {date}.",
            "Meu pacote ficou travado em 'em trânsito' no centro de distribuição.",
            "A transportadora não atualiza o rastreio do {product} faz {days} dias.",
            "O rastreamento diz 'objeto postado' e não sai disso.",
            "Consultei o rastreio hoje e continua no mesmo status de {date}.",
            "O código de rastreio que recebi diz que não existe.",
            "O status da entrega está congelado em 'saiu para entrega' há {days} dias.",
            "Desde {date} o pedido {order} não se mexe no rastreio.",
            "A última movimentação do pacote foi há {days} dias.",
        ),
        ("O código começa com BR.", "Já tentei no site da transportadora."),
    ),
    Topic(
        "entrega",
        "avaria",
        "high",
        ("Produto chegou quebrado", "Caixa amassada e produto danificado", "Pedido {order} veio avariado", "Recebi o {product} danificado"),
        (
            "O {product} chegou com a tela trincada.",
            "A caixa veio toda amassada e o {product} está quebrado.",
            "Abri a encomenda e o {product} estava danificado.",
            "O pedido {order} chegou molhado e o produto estragou.",
            "Recebi o {product} com uma peça faltando e arranhado.",
            "O vidro do {product} veio estilhaçado dentro da caixa.",
            "A embalagem chegou rasgada e o {product} sem funcionar.",
            "O {product} veio amassado na lateral, dá pra ver que caiu.",
            "Chegou tudo quebrado, o {product} não tem conserto.",
            "O produto do pedido {order} chegou com defeito de transporte.",
        ),
        ("Tenho fotos da embalagem.", "Recusei? Não, aceitei sem perceber."),
    ),
    Topic(
        "entrega",
        "endereco",
        "normal",
        ("Mudar endereço de entrega", "Endereço errado no pedido", "Alterar local de entrega do {order}", "Entrega no endereço antigo"),
        (
            "Preciso alterar o endereço de entrega do pedido {order}.",
            "Coloquei o endereço errado na compra do {product}.",
            "Mudei de casa e o pedido {order} vai para o endereço antigo.",
            "Dá pra trocar o endereço de entrega? Errei o número da casa.",
            "O pedido saiu com o CEP errado, como corrijo?",
            "Quero receber o {product} no trabalho em vez de em casa.",
            "Esqueci o complemento do endereço no pedido {order}.",
            "O entregador disse que o endereço não existe.",
            "Gostaria de redirecionar a entrega para {city}.",
            "O pedido {order} foi enviado para um endereço desatualizado.",
        ),
        (),
    ),
    Topic(
        "entrega",
        "extraviado",
        "high",
        ("Pedido entregue mas não recebi", "Consta entregue e não chegou", "Pedido {order} extraviado", "Encomenda sumiu"),
        (
            "O rastreio diz que o pedido {order} foi entregue, mas não recebi nada.",
            "Consta como entregue em {date} e ninguém aqui recebeu.",
            "Minha encomenda sumiu, a transportadora diz que entregou.",
            "O pedido foi marcado como entregue para outra pessoa.",
            "Recebi a notificação de entrega do {product} mas não tem nada na portaria.",
            "O pacote foi dado como entregue e o porteiro não viu nada.",
            "Disseram que entregaram o pedido {order}, mas é mentira, não chegou.",
            "A entrega aparece concluída e o {product} não está aqui.",
            "Meu pedido foi extraviado no caminho, segundo o rastreio.",
            "O status mudou para entregue sem eu receber nada.",
        ),
        (),
    ),
    # ---------------------------------------------------------------- pagamento
    Topic(
        "pagamento",
        "duplicidade",
        "high",
        ("Cobrança duplicada", "Fui cobrado duas vezes", "Valor debitado em dobro", "Cobrança em duplicidade no cartão"),
        (
            "Fui cobrado duas vezes pelo pedido {order} no cartão.",
            "Apareceram duas cobranças de {amount} na minha fatura.",
            "O pagamento do {product} foi debitado em duplicidade.",
            "Paguei uma vez e o banco mostra o valor de {amount} duas vezes.",
            "Minha fatura veio com o pedido {order} cobrado em dobro.",
            "Tem uma cobrança repetida de {amount} no meu extrato.",
            "O Pix saiu duas vezes da minha conta para o mesmo pedido.",
            "Vocês cobraram o pedido {order} duas vezes no meu cartão.",
            "O valor de {amount} foi descontado em duplicidade.",
            "Na fatura aparecem duas compras iguais do {product}.",
        ),
        ("O cartão termina em {card_end}.",),
    ),
    Topic(
        "pagamento",
        "estorno",
        "normal",
        ("Estorno não caiu", "Reembolso pendente", "Quando cai meu estorno?", "Estorno do pedido {order}"),
        (
            "Devolvi o {product} há {days} dias e o estorno ainda não apareceu.",
            "O reembolso de {amount} prometido não caiu na fatura.",
            "Cancelei o pedido {order} e não recebi o dinheiro de volta.",
            "Ainda não vi o estorno da devolução na minha conta.",
            "Disseram que o estorno seria em até 2 faturas e já passou.",
            "O valor do pedido cancelado não voltou para o cartão.",
            "Quando vou receber o reembolso do pedido {order}?",
            "O estorno do Pix não caiu na minha conta.",
            "Fiz a devolução em {date} e o reembolso não saiu.",
            "Estou aguardando o estorno de {amount} desde {date}.",
        ),
        (),
    ),
    Topic(
        "pagamento",
        "parcelamento",
        "low",
        ("Dúvida sobre parcelamento", "Posso parcelar?", "Formas de pagamento", "Parcelamento sem juros"),
        (
            "Queria saber em quantas vezes posso parcelar o {product}.",
            "O parcelamento é sem juros no cartão?",
            "Vocês aceitam boleto parcelado?",
            "Qual o valor mínimo para parcelar em 10 vezes?",
            "Posso pagar metade no Pix e metade no cartão?",
            "Gostaria de entender as formas de pagamento disponíveis.",
            "O desconto no Pix vale para o {product}?",
            "Dá pra parcelar compras abaixo de {amount}?",
            "Quantos dias o boleto demora para compensar?",
            "Aceitam cartão de débito?",
        ),
        (),
    ),
    Topic(
        "pagamento",
        "recusado",
        "normal",
        ("Pagamento recusado", "Cartão não passa", "Erro ao pagar", "Compra não finaliza"),
        (
            "Meu cartão foi recusado ao tentar comprar o {product}.",
            "O pagamento não conclui, aparece erro no final do checkout.",
            "Tentei pagar com Pix e o código expirou na hora.",
            "A compra não finaliza, dá erro de pagamento.",
            "O site recusa meu cartão mas ele tem limite.",
            "Não consigo pagar o pedido, o botão de confirmar não funciona.",
            "O boleto gerado está com valor diferente do carrinho.",
            "Aparece 'transação não autorizada' toda vez.",
            "O pagamento ficou em análise há {days} dias.",
            "Meu pedido foi cancelado por falta de pagamento, mas eu paguei.",
        ),
        (),
    ),
    # ------------------------------------------------------------ troca_devolucao
    Topic(
        "troca_devolucao",
        "tamanho",
        "low",
        ("Troca de tamanho", "Trocar por outro número", "Quero trocar o tamanho", "Troca do {product}"),
        (
            "O {product} ficou pequeno, quero trocar por um número maior.",
            "Comprei tamanho M e preciso do G.",
            "O tênis ficou apertado, dá pra trocar pelo 42?",
            "Gostaria de trocar a cor do {product}.",
            "A calça ficou grande, quero um tamanho menor.",
            "Como faço para trocar o {product} por outro tamanho?",
            "Recebi o tamanho certo mas ficou largo, posso trocar?",
            "Quero trocar o {product} por outro modelo do mesmo preço.",
            "Preciso de um número menor, o {product} sobrou no pé.",
            "Dá pra trocar o vestido por outro tamanho?",
        ),
        (),
    ),
    Topic(
        "troca_devolucao",
        "arrependimento",
        "normal",
        ("Quero devolver", "Devolução do pedido {order}", "Desistência da compra", "Como devolvo o produto?"),
        (
            "Me arrependi da compra do {product} e quero devolver.",
            "Quero devolver o pedido {order}, não gostei.",
            "Recebi o {product} ontem e quero desistir da compra.",
            "Como faço a devolução dentro dos 7 dias?",
            "O produto não é o que eu esperava, quero o dinheiro de volta.",
            "Gostaria de cancelar e devolver o {product}.",
            "Não vou usar o {product}, como devolvo?",
            "Quero exercer meu direito de arrependimento no pedido {order}.",
            "Comprei por engano, quero devolver.",
            "A cor é diferente da foto, quero devolver.",
        ),
        (),
    ),
    Topic(
        "troca_devolucao",
        "etiqueta",
        "normal",
        ("Etiqueta de devolução", "Não consigo gerar a etiqueta", "Código de postagem", "Postagem da devolução"),
        (
            "Não consigo gerar a etiqueta de devolução no site.",
            "O código de postagem da devolução não chegou no meu e-mail.",
            "A agência dos Correios recusou meu código de devolução.",
            "Onde encontro a etiqueta para devolver o {product}?",
            "O código de postagem expirou antes de eu enviar.",
            "A etiqueta de troca está dando erro ao abrir.",
            "Preciso de um novo código para postar o pedido {order}.",
            "Já postei a devolução, como acompanho?",
            "Não tenho impressora, posso devolver sem etiqueta impressa?",
            "O link da etiqueta de devolução está quebrado.",
        ),
        (),
    ),
    # -------------------------------------------------------------- conta_acesso
    Topic(
        "conta_acesso",
        "senha",
        "normal",
        ("Esqueci minha senha", "Não consigo entrar", "Problema para fazer login", "Redefinir senha"),
        (
            "Esqueci a senha e o e-mail de recuperação não chega.",
            "Não consigo entrar na minha conta, diz senha inválida.",
            "O link de redefinir senha está expirado.",
            "Tento fazer login e volta para a tela inicial.",
            "Minha senha não é aceita mesmo depois de trocar.",
            "O site não reconhece meu e-mail no login.",
            "Fiquei bloqueado depois de errar a senha.",
            "Não recebo o e-mail para criar uma nova senha.",
            "O aplicativo não deixa eu entrar na conta.",
            "Troquei a senha e continuo sem acesso.",
        ),
        (),
    ),
    Topic(
        "conta_acesso",
        "codigo",
        "normal",
        ("Código de verificação não chega", "SMS não chega", "Não recebo o código", "Verificação em duas etapas"),
        (
            "O código de verificação por SMS não chega no meu celular.",
            "Peço o código de acesso e nunca recebo.",
            "O código de verificação chega depois de expirar.",
            "Troquei de número e não consigo receber o código.",
            "O e-mail com o código de confirmação não chega.",
            "A verificação em duas etapas não funciona.",
            "O código de seis dígitos sempre dá inválido.",
            "Não consigo validar minha conta sem o SMS.",
            "Já pedi o código várias vezes e nada.",
            "O código de segurança vai para um número antigo.",
        ),
        (),
    ),
    Topic(
        "conta_acesso",
        "invadida",
        "urgent",
        ("Conta invadida", "Compras que não fiz", "Alguém acessou minha conta", "Minha conta foi hackeada"),
        (
            "Alguém entrou na minha conta e fez uma compra que não reconheço.",
            "Recebi aviso de login de outro estado e não fui eu.",
            "Mudaram o e-mail da minha conta sem minha autorização.",
            "Tem um pedido de {amount} na minha conta que não fiz.",
            "Acho que minha conta foi invadida, a senha foi trocada.",
            "Apareceram endereços de entrega desconhecidos no meu cadastro.",
            "Fizeram compras no meu nome usando meu cartão salvo.",
            "Minha conta foi hackeada e não consigo mais acessar.",
            "Recebi confirmação de um pedido {order} que não é meu.",
            "Usaram meus pontos de fidelidade sem eu saber.",
        ),
        (),
    ),
    Topic(
        "conta_acesso",
        "dados",
        "low",
        ("Atualizar cadastro", "Mudar e-mail da conta", "Excluir minha conta", "Alterar dados pessoais"),
        (
            "Quero atualizar meu telefone no cadastro.",
            "Como altero o e-mail da minha conta?",
            "Gostaria de excluir minha conta e meus dados.",
            "Meu nome está escrito errado no cadastro.",
            "Como desativo as notificações por e-mail?",
            "Quero mudar o endereço principal da conta.",
            "Preciso atualizar meus dados para emitir nota.",
            "Como baixo uma cópia dos meus dados?",
            "Quero parar de receber propaganda.",
            "Dá pra juntar duas contas que eu tenho?",
        ),
        (),
    ),
    # ------------------------------------------------------------------ produto
    Topic(
        "produto",
        "defeito",
        "high",
        ("Produto com defeito", "{product} parou de funcionar", "Defeito de fábrica", "Problema no {product}"),
        (
            "O {product} parou de funcionar depois de {days} dias de uso.",
            "O {product} não liga de jeito nenhum.",
            "A bateria do {product} não segura carga.",
            "O {product} esquenta muito e desliga sozinho.",
            "O {product} veio com defeito de fábrica.",
            "Um dos lados do fone não funciona.",
            "A tela do {product} ficou com manchas.",
            "O {product} faz um barulho estranho quando liga.",
            "O botão do {product} quebrou com pouco uso.",
            "O {product} reinicia sozinho toda hora.",
        ),
        ("Está dentro da garantia.", "Já testei em outra tomada."),
    ),
    Topic(
        "produto",
        "duvida",
        "low",
        ("Dúvida sobre o produto", "Especificações do {product}", "O {product} é compatível?", "Informação de produto"),
        (
            "O {product} é compatível com celular Android?",
            "Qual a voltagem do {product}?",
            "O {product} vem com manual em português?",
            "Quanto tempo de garantia tem o {product}?",
            "O {product} serve para uso profissional?",
            "Qual o tamanho exato do {product}?",
            "Vocês vão ter o {product} em outra cor?",
            "O {product} acompanha carregador?",
            "Quando o {product} volta ao estoque?",
            "O {product} tem certificação do Inmetro?",
        ),
        (),
    ),
    Topic(
        "produto",
        "diferente",
        "normal",
        ("Produto diferente do anunciado", "Veio errado", "Recebi outro produto", "Item trocado no pedido {order}"),
        (
            "Recebi um produto diferente do que comprei.",
            "O {product} que chegou não é o modelo do anúncio.",
            "Pedi a cor azul e veio vermelho.",
            "Veio um item a menos no pedido {order}.",
            "O produto é de outra marca, não a anunciada.",
            "A descrição dizia 128 GB e veio 64 GB.",
            "Chegou o {product} de outra pessoa no meu pedido.",
            "O kit veio incompleto, falta o carregador.",
            "As fotos do site não batem com o que recebi.",
            "Mandaram o tamanho errado do {product}.",
        ),
        (),
    ),
    # -------------------------------------------------------------------- outro
    Topic(
        "outro",
        "nota_fiscal",
        "low",
        ("Nota fiscal", "Segunda via da nota", "Preciso da NF", "Nota fiscal do pedido {order}"),
        (
            "Preciso da nota fiscal do pedido {order}.",
            "A nota fiscal não veio junto com o produto.",
            "Como emito a segunda via da nota?",
            "A nota saiu com o CNPJ errado.",
            "Preciso da nota em nome da empresa.",
            "Onde baixo a nota fiscal da compra?",
            "A nota fiscal está com o endereço errado.",
            "Não recebi o XML da nota.",
            "A nota foi emitida com valor diferente.",
            "Preciso da nota para reembolso da empresa.",
        ),
        (),
    ),
    Topic(
        "outro",
        "sugestao",
        "low",
        ("Sugestão", "Elogio ao atendimento", "Ideia para a loja", "Feedback"),
        (
            "Queria sugerir que vocês vendessem {product} em outras cores.",
            "Só passando para elogiar o atendimento da última compra.",
            "Seria ótimo ter retirada na loja física.",
            "O aplicativo poderia ter modo escuro.",
            "Gostei muito da embalagem nova de vocês.",
            "Vocês poderiam ter um programa de fidelidade.",
            "Sugiro mostrar o prazo de entrega antes do checkout.",
            "Adorei a promoção da semana passada.",
            "Seria legal poder agendar a entrega.",
            "Fica a sugestão de aceitar mais carteiras digitais.",
        ),
        (),
    ),
    Topic(
        "outro",
        "parceria",
        "low",
        ("Parceria", "Proposta comercial", "Quero revender", "Trabalhe conosco"),
        (
            "Tenho uma loja e queria revender os produtos de vocês.",
            "Gostaria de propor uma parceria de divulgação.",
            "Como faço para ser fornecedor da loja?",
            "Vocês têm vagas de emprego?",
            "Sou influenciador e queria uma parceria.",
            "Queria saber sobre venda no atacado.",
            "Vocês fazem vendas corporativas?",
            "Como me cadastro como afiliado?",
            "Tenho interesse em ser representante na minha cidade.",
            "Vocês patrocinam eventos?",
        ),
        (),
    ),
)

# Cues that move the priority. Each list is split between train/val/test.
URGENT_CUES: tuple[str, ...] = (
    "É urgente!",
    "Preciso disso para amanhã.",
    "Viajo na sexta e preciso resolver antes.",
    "É um presente de aniversário para depois de amanhã.",
    "Por favor, resolvam com urgência.",
    "Estou sem o produto e preciso dele para trabalhar.",
    "Não posso esperar mais.",
    "Preciso de uma solução hoje.",
    "Isso está me causando prejuízo.",
    "Tenho um evento no fim de semana.",
    "Preciso com máxima prioridade.",
    "É caso de urgência.",
    "Preciso resolver ainda hoje, por favor.",
    "Meu filho precisa disso para a escola amanhã.",
    "O prazo para mim é amanhã cedo.",
    "Vou precisar disso em poucas horas.",
    "Estou desesperado com isso.",
    "Resolvam o quanto antes!",
)
CALM_CUES: tuple[str, ...] = (
    "Sem pressa.",
    "Quando puderem, me respondam.",
    "Não é nada urgente.",
    "Pode ser com calma.",
    "Fico no aguardo, sem urgência.",
    "Respondam quando der.",
    "Não tenho pressa nenhuma.",
    "Só uma dúvida rápida, sem urgência.",
    "Tranquilo, pode ser na semana que vem.",
    "Quando tiverem um tempinho.",
    "Nada urgente, só queria saber.",
    "Sem correria por aqui.",
)
NEGATIVE_CUES: tuple[str, ...] = (
    "Estou muito insatisfeito.",
    "Isso é um absurdo!",
    "Péssimo atendimento.",
    "Já é a terceira vez que reclamo.",
    "Que decepção com essa loja.",
    "Estou indignado.",
    "Nunca mais compro aqui.",
    "Vou reclamar no Procon.",
    "Ninguém resolve nada.",
    "Estou cansado de esperar resposta.",
    "Que falta de respeito com o cliente.",
    "Experiência horrível.",
    "Estou furioso com isso.",
    "Lamentável o serviço de vocês.",
    "Me sinto enganado.",
    "Ridículo isso.",
    "Vou postar nas redes sociais.",
    "Pior compra que já fiz.",
)
POSITIVE_CUES: tuple[str, ...] = (
    "Obrigado desde já!",
    "Adoro a loja de vocês.",
    "Vocês sempre me atendem muito bem.",
    "Agradeço a atenção.",
    "Parabéns pelo atendimento.",
    "Sou cliente fiel e muito satisfeita.",
    "Muito obrigada pela ajuda!",
    "Gosto muito dos produtos de vocês.",
    "Atendimento nota dez como sempre.",
    "Fico grato pela ajuda.",
    "Vocês são ótimos!",
    "Agradeço muito a paciência.",
    "Confio muito na loja.",
    "Que bom poder contar com vocês.",
    "Amei a última compra.",
    "Obrigadão!",
)
GREETINGS: tuple[str, ...] = ("Olá,", "Oi,", "Bom dia,", "Boa tarde,", "Boa noite,", "Oi, tudo bem?", "Olá, equipe!", "")
CLOSINGS: tuple[str, ...] = ("Aguardo retorno.", "Fico no aguardo.", "Att.", "Abraço.", "", "", "")

PRODUCTS = (
    "fone de ouvido",
    "tênis de corrida",
    "celular",
    "notebook",
    "liquidificador",
    "smartwatch",
    "cafeteira",
    "jaqueta",
    "mochila",
    "air fryer",
    "teclado",
    "carregador",
    "ventilador",
    "relógio",
    "vestido",
    "monitor",
)
CITIES = ("Curitiba", "Recife", "Belo Horizonte", "Porto Alegre", "Salvador", "Goiânia", "Manaus", "Florianópolis")
EMOJI = {"negative": ("😡", "😤", "😠", "👎"), "positive": ("😊", "🙏", "💙", "❤️"), "neutral": ("", "")}
ABBREVIATIONS = (("você", "vc"), ("porque", "pq"), ("que", "q"), ("também", "tb"), ("está", "tá"), ("para", "pra"), ("não", "n"))


def _partition(items: tuple[str, ...], salt: str) -> dict[str, list[str]]:
    """Deterministic 70/15/15 split of a phrase list (at least one phrase per split)."""
    ordered = sorted(items, key=lambda item: hashlib.sha256(f"{salt}:{item}".encode()).hexdigest())
    n = len(ordered)
    n_test = max(1, round(n * 0.15))
    n_val = max(1, round(n * 0.15))
    return {"test": ordered[:n_test], "validation": ordered[n_test : n_test + n_val], "train": ordered[n_test + n_val :]}


def _shift(priority: str, delta: int) -> str:
    index = max(0, min(len(PRIORITIES) - 1, PRIORITIES.index(priority) + delta))
    return PRIORITIES[index]


def _strip_accents(text: str) -> str:
    return "".join(c for c in unicodedata.normalize("NFD", text) if unicodedata.category(c) != "Mn")


def _typo(text: str, rng: random.Random) -> str:
    words = text.split(" ")
    candidates = [i for i, w in enumerate(words) if len(w) > 4 and w.isalpha()]
    if not candidates:
        return text
    i = rng.choice(candidates)
    w = words[i]
    j = rng.randrange(1, len(w) - 1)
    words[i] = w[:j] + w[j + 1] + w[j] + w[j + 2 :]
    return " ".join(words)


def _noise(text: str, rng: random.Random) -> str:
    if rng.random() < 0.25:
        text = _strip_accents(text)
    if rng.random() < 0.2:
        text = text.lower()
    if rng.random() < 0.25:
        for full, short in ABBREVIATIONS:
            if rng.random() < 0.5:
                text = text.replace(f" {full} ", f" {short} ")
    if rng.random() < 0.2:
        text = _typo(text, rng)
    return text


def _fill(template: str, rng: random.Random) -> str:
    return template.format(
        order=f"#{rng.randint(10000, 99999)}",
        product=rng.choice(PRODUCTS),
        date=f"{rng.randint(1, 28):02d}/{rng.randint(1, 12):02d}",
        days=rng.randint(3, 25),
        amount=f"R$ {rng.randint(30, 2500)},{rng.randint(0, 99):02d}",
        city=rng.choice(CITIES),
        card_end=f"{rng.randint(0, 9999):04d}",
    )


def generate(per_split: dict[str, int] | None = None, seed: int = SEED) -> dict[str, list[dict[str, str]]]:
    """Generates the three splits. Returns {split: [ {id, subject, body, text, category, priority, sentiment} ]}."""
    per_split = per_split or {"train": 2400, "validation": 500, "test": 500}
    rng = random.Random(seed)  # noqa: S311 - reproducible data generation, not security

    problems = {t.name: _partition(t.problems, t.name) for t in TOPICS}
    subjects = {t.name: _partition(t.subjects, f"subject:{t.name}") for t in TOPICS}
    urgent, calm = _partition(URGENT_CUES, "urgent"), _partition(CALM_CUES, "calm")
    negative, positive = _partition(NEGATIVE_CUES, "negative"), _partition(POSITIVE_CUES, "positive")

    data: dict[str, list[dict[str, str]]] = {}

    for split in SPLITS:
        rows = []
        for i in range(per_split[split]):
            topic = TOPICS[i % len(TOPICS)] if i < len(TOPICS) else rng.choice(TOPICS)
            urgency = rng.choices(("up", "none", "down"), weights=(0.3, 0.45, 0.25))[0]
            sentiment = rng.choices(("negative", "neutral", "positive"), weights=(0.35, 0.4, 0.25))[0]

            priority = topic.priority
            parts = [rng.choice(GREETINGS), _fill(rng.choice(problems[topic.name][split]), rng)]
            if topic.details and rng.random() < 0.4:
                parts.append(_fill(rng.choice(topic.details), rng))
            if urgency == "up":
                parts.append(rng.choice(urgent[split]))
                priority = _shift(priority, +1)
            elif urgency == "down":
                parts.append(rng.choice(calm[split]))
                priority = _shift(priority, -1)
            if sentiment == "negative":
                parts.append(rng.choice(negative[split]))
            elif sentiment == "positive":
                parts.append(rng.choice(positive[split]))
            if rng.random() < 0.3:
                parts.append(rng.choice(EMOJI[sentiment]))
            parts.append(rng.choice(CLOSINGS))

            body = _noise(" ".join(p for p in parts if p), rng)
            subject = _fill(rng.choice(subjects[topic.name][split]), rng)
            rows.append(
                {
                    "id": f"{split}-{i:05d}",
                    "subject": subject,
                    "body": body,
                    "text": ticket_text(subject, body),
                    "category": topic.category,
                    "priority": priority,
                    "sentiment": sentiment,
                }
            )
        rng.shuffle(rows)
        data[split] = rows

    return data


def write(directory: Path, data: dict[str, list[dict[str, str]]]) -> None:
    directory.mkdir(parents=True, exist_ok=True)
    for split, rows in data.items():
        with (directory / f"{split}.jsonl").open("w", encoding="utf-8") as handle:
            for row in rows:
                handle.write(json.dumps(row, ensure_ascii=False) + "\n")


def read(path: Path) -> list[dict[str, str]]:
    with path.open(encoding="utf-8") as handle:
        return [json.loads(line) for line in handle if line.strip()]


def main() -> None:
    parser = argparse.ArgumentParser(description="Generate the synthetic ticket dataset.")
    parser.add_argument("--out", type=Path, default=Path(__file__).resolve().parent.parent / "data")
    args = parser.parse_args()
    data = generate()
    write(args.out, data)
    for split, rows in data.items():
        print(f"{split}: {len(rows)} tickets")


if __name__ == "__main__":
    main()
