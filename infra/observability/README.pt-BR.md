# Observabilidade

*[English](README.md)*

Traces, métricas e logs OpenTelemetry da API, dos workers da fila, do scheduler e do
classificador, coletados por um OpenTelemetry Collector e exibidos no Grafana. Opcional:

```bash
make up-obs        # a stack + Collector, Prometheus, Tempo, Loki e Grafana
make demo          # tenant demo (os tickets alimentam os painéis de SLA e do classificador)
open http://localhost:3001   # usuário admin, senha GRAFANA_ADMIN_PASSWORD do .env
```

Com `make up` a telemetria fica desligada (`TICKO_TELEMETRY=false`) e nada é enviado. Para
deixá-la sempre ligada, adicione `COMPOSE_PROFILES=obs` e `TICKO_TELEMETRY=true` ao `.env`.
Com o classificador também: `COMPOSE_PROFILES=ml,obs` e `CLASSIFIER_URL=http://classifier:8000`.

## Por que essa stack

Prometheus, Tempo e Loki são os backends nativos do Grafana: dá para ir de uma linha de log ao
trace (`trace_id`) e do trace aos logs sem plugin extra. Cada um roda num contêiner pequeno com
armazenamento local, o que basta para uma máquina. O Collector deixa as aplicações neutras
(só OTLP) e é o único ponto para limpar os dados antes de guardar.

## O que é instrumentado

- **API:** um span por requisição com o template da rota (nunca a URL); latência por método,
  rota e status.
- **Fila:** um span por job que **continua o trace da requisição** (`traceparent` no payload);
  duração por job e resultado (processado, reenfileirado, falhou).
- **Chamadas externas:** span com método, host e status; o `traceparent` só vai para o nosso
  classificador, nunca para provedores de IA.
- **IA:** span por chamada ao modelo com provedor, modelo e tokens (nunca prompt nem resposta);
  latência e erros; tokens e custo estimado **por tenant**; resultado das sugestões (pronta,
  "não sei", falha) e revisão dos atendentes (aprovada, editada, descartada + motivo).
- **Classificador:** o serviço Python continua o trace da fila; latência da chamada e da
  inferência, confiança por tarefa e rótulo, aplicado ou não, e correções dos atendentes.
- **Login:** falhas por tenant.
- **Scheduler, a cada minuto:** tickets abertos por prioridade, sem responsável e vencidos
  (1ª resposta e resolução) por tenant; backlog, espera e falhas da fila.
- **Logs:** toda linha de log do Laravel vai para o Loki com `trace_id`, depois da redação.

O **custo** é estimado: tokens informados pelo provedor (estimados no fake offline) × preço
por milhão de tokens em `ticko.ai.prices` (`TICKO_AI_PRICE_*`). Cada sugestão também guarda
tokens e custo.

No PHP-FPM cada requisição é um processo: as métricas saem como *delta* no fim da requisição
ou do job, e o processador `delta_to_cumulative` do Collector as converte nas séries
cumulativas que o Prometheus espera.

## Dashboards e alertas (como código)

Gerados por [`grafana/build_dashboards.py`](grafana/build_dashboards.py) (`make obs-dashboards`)
em `grafana/dashboards/*.json` e provisionados só leitura na pasta **Ticko** (a CI falha se o
JSON divergir do script): **Saúde da API**, **Fila (Horizon)**, **Tickets e SLA**, **Sugestões
de IA** e **Classificador**.

Alertas provisionados: pico de falhas de login (> 20 em 5 min), rajada de 403/404 (> 50 em
5 min, possível sondagem entre tenants), erros do modelo de IA acima de 20%, custo de IA acima
de 5 USD/hora e tickets com SLA estourado. Nenhum canal de notificação vem configurado;
adicione um contact point da sua equipe.

## Segurança

- **Login obrigatório:** sem acesso anônimo, sem basic auth, sem cadastro. A CI confere que
  chamadas anônimas e com basic auth recebem 401 e que `admin`/`admin` é recusado.
- **Sem senha padrão:** `GRAFANA_ADMIN_PASSWORD` é gerada pelo `make env` (40 caracteres
  aleatórios); sem ela a stack nem sobe.
- **Não exposto:** só o Grafana publica porta, em `127.0.0.1` (a CI confere). Collector,
  Prometheus, Tempo e Loki ficam na rede interna `obs`, sem portas.
- **Sem segredos nem dados pessoais:** o Laravel redige toda linha de log e atributo (senhas,
  tokens, cookies, chaves de API, APP_KEY, SQL com valores, texto do ticket, e-mail, CPF/CNPJ,
  cartão, telefone). Spans levam o template da rota, nunca URL, query, headers ou corpo; exceções
  só com o tipo. O Collector aplica uma segunda redação independente. Testes provam isso e a CI
  procura e-mails, CPFs e tokens no Loki.
- **Cardinalidade limitada:** atributos de métricas são templates de rota, enums e o slug do
  tenant; nunca usuário, ticket ou texto livre (um teste garante).
- **Imagens:** fixadas por versão e digest, todas sem root, filesystem só leitura, sem
  capabilities, `no-new-privileges`; analytics, checagem de atualizações e snapshots externos
  do Grafana desligados.
