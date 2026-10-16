# Guia de demonstração (5–10 minutos)

*[English](DEMO.md)*

Tudo aqui é fictício: a "Loja Aurora", os clientes, os pedidos e os valores.

## Preparação (uma vez; ~5 minutos na primeira)

```bash
make up-obs          # o app + Grafana (rode make up-ml antes para o classificador real)
make demo            # mostra as senhas de admin, atendente e cliente
```

Abra http://localhost:8080 — a tela de login tem os botões **Atendente** e **Admin**. Grafana:
http://localhost:3001 (usuário `admin`, senha `GRAFANA_ADMIN_PASSWORD` do `.env`). Para começar
de novo: `make demo-reset`.

## Roteiro

**1. A fila (1 min).** Entre como **Atendente** (Ana Souza). São 25 tickets abertos, aguardando
cliente, resolvidos e fechados, com o prazo de SLA — alguns já **atrasados** (em vermelho).
Use as abas (*Meus*, *Não atribuídos*, *Todos*, *Atrasados*) e os filtros.

![Fila](screenshots/02-queue.png)

**2. Um rascunho da IA, com fontes (2 min).** Abra *"Cobrança em duplicidade no cartão de
crédito"*. À direita, a IA escreveu uma resposta a partir da base de conhecimento: cada
afirmação cita uma fonte (`[1]`, `[2]`); clique para ver o trecho exato. A barra mostra a
confiança. **Nada é enviado sem aprovação** — *Aprovar e enviar*, *Editar* ou *Descartar* (com
motivo). À esquerda, o card **Classificação da IA** mostra o que o classificador sugeriu e aplicou.

![Rascunho da IA](screenshots/03-ticket-ai-draft.png)

**3. "Não sei" (30 s).** Abra *"Vocês têm loja física em Curitiba?"*. A base não tem a resposta:
a IA diz que não sabe, lista os artigos mais próximos e permite sinalizar a lacuna — nunca inventa.

![Não sei](screenshots/04-ticket-dont-know.png)

**4. Corrigindo o classificador (1 min).** Abra *"A jaqueta veio com o zíper quebrado"*. A IA
sugeriu uma categoria e um atendente corrigiu para *Produto*. O campo corrigido perde o selo
**IA**, a IA nunca mais o altera e a correção fica auditada (e vira dado de treino). Experimente
**Corrigir** em *"Adorei o atendimento, só queria trocar a cor"*.

![Classificação corrigida](screenshots/05-ticket-corrected.png)

**5. Base de conhecimento e busca (1 min).** *Base de conhecimento*: artigos para clientes ou só
para a equipe, com o status de indexação. *Playground de busca*: digite "meu pedido está
atrasado" e veja exatamente os trechos e as notas que a IA receberia (texto + semântica).

![Base de conhecimento](screenshots/06-knowledge-base.png)
![Playground](screenshots/07-search-playground.png)

**6. Admin (30 s).** Saia e entre como **Admin**: volume, mediana da 1ª resposta, cumprimento de
SLA; equipe e categorias com metas de SLA por prioridade.

![Dashboard do admin](screenshots/08-admin-dashboard.png)

**7. Observabilidade (2 min).** No Grafana, pasta **Ticko**:
- *API health*: latência p50/p95 por rota, erros, rajadas de 403/404, logs redigidos com link para o trace;
- *AI suggestions*: taxa de "não sei", aprovações e descartes, tokens e custo estimado por tenant;
- *Tickets and SLA*: abertos, sem responsável e vencidos por tenant;
- *Classifier* e *Queue*. Há cinco alertas provisionados (falhas de login, rajadas de 403/404,
  erros e custo de IA, SLA estourado).

Com `make up-ml`, abra um ticket pelo app e siga **um único trace** no Tempo, da requisição na API
até o classificador em Python, passando pela fila.

![Dashboard da API](screenshots/09-grafana-api.png)
![Dashboard da IA](screenshots/10-grafana-ai.png)
![Dashboard de SLA](screenshots/11-grafana-sla.png)

**8. O lado do cliente (opcional, 2 min).** `make up-lan`, depois `make mobile-dev` e leia o QR
code com o Expo Go (ou use um emulador Android, veja o README). Entre como a cliente demo
(Mariana Costa): os tickets dela, um ticket novo com foto, respostas ao vivo e notificações push.

## Gerando os screenshots de novo

```bash
make up-obs && make demo && make screenshots
```

O app mobile não é capturado automaticamente (precisa de um aparelho ou emulador).
