# Ticko

[![CI](https://github.com/Hugueninfer/ticko/actions/workflows/ci.yml/badge.svg)](https://github.com/Hugueninfer/ticko/actions/workflows/ci.yml)
![Cobertura](https://raw.githubusercontent.com/Hugueninfer/ticko/badges/coverage.svg)

Helpdesk multi-tenant com IA. Este repositório é um monorepo: a **fase 1** entregou a API,
a infraestrutura e os controles de qualidade; a **fase 2**, o app web do atendente e do admin
e o cliente tipado da API; a **fase 3**, o app mobile do cliente (Expo), as notificações push
e as atualizações em tempo real (Laravel Reverb); a **fase 4**, a base de conhecimento, a busca
(RAG) e as sugestões de resposta da IA que o atendente sempre aprova; a **fase 5**, o
classificador de tickets (categoria, prioridade e sentimento) — um BERTimbau + LoRA treinado
em dados sintéticos, servido numa rede interna.

> 🇺🇸 English version: [README.md](README.md) (referência completa de arquitetura e endpoints)

## Subir tudo com um comando

Requisitos: Docker com Compose v2 e `make`. Para subir a stack não precisa de PHP, Composer nem
Node na máquina (Node 22 só é necessário para desenvolver o web fora do Docker).

```bash
make up
```

Na primeira vez, cria o `.env` com segredos gerados na hora; depois constrói as imagens, roda
as migrations e sobe tudo:

| URL | O quê |
| --- | --- |
| http://localhost:8080 | O app web (atendentes e admins) |
| http://localhost:8080/docs | Swagger UI da API (OpenAPI 3) |
| http://localhost:8080/docs/openapi.json | Documento OpenAPI |
| http://localhost:8080/api/v1 | A API |
| http://localhost:8080/horizon | Painel das filas (só admin) |

Porta 8080 ocupada? Ajuste `WEB_PORT` (e `APP_URL`) no `.env`.

Tenant de demonstração (`acme`, "Loja Aurora") com um usuário por papel, categorias e
tickets; as senhas são aleatórias e aparecem uma única vez:

```bash
make demo
```

Com `TICKO_DEMO_LOGIN=true` (padrão no `.env.example`; nunca vale em produção) a tela de login
mostra os atalhos **Atendente** e **Admin** para entrar na demo com um clique.

Tenant real: `docker compose exec api php artisan ticko:tenant:create <slug> "<Nome>" --admin-email=<email>`.

### Comandos de desenvolvimento

| Comando | Faz |
| --- | --- |
| `make test` | Roda os testes Pest (banco `ticko_testing`) |
| `make coverage` | Testes com cobertura; falha abaixo de 80% |
| `make lint` / `make fix` | Pint (verificar / corrigir) |
| `make stan` | Larastan no nível `max` |
| `make audit` | `composer audit` |
| `make gitleaks` | Procura segredos no histórico do git |
| `make docs` | Regera `apps/api/resources/openapi/openapi.json` com o Scribe |
| `make ci` | Tudo o que a CI roda para a API |
| `make web-dev` | App web com hot reload em http://localhost:5173 (Node 22 + corepack) |
| `make web-test` | ESLint, Prettier, `tsc` e Vitest com cobertura (web + cliente da API) |
| `make e2e` | Playwright contra a stack rodando (antes: `make up && make demo`) |
| `make up-lan` | A stack acessível pelo celular no seu Wi-Fi (só a porta do web) |
| `make mobile-dev` | Expo para o app mobile (leia o QR code com o Expo Go) |
| `make mobile-test` | ESLint, Prettier, `tsc` e Jest com cobertura (mobile) |
| `make kb-reindex` | Reindexa todos os artigos publicados (depois de trocar o provedor de embeddings) |
| `make up-ml` | A stack mais o classificador de tickets (o primeiro build baixa ~1 GB) |
| `make classifier-test` / `classifier-eval` | Classificador: lint, testes, pip-audit / avaliações de F1 e de prompt injection |
| `make classifier-train` | Retreina o baseline e o LoRA na CPU (~26 min) |

## Estrutura

```
apps/api/            API Laravel 13 (PHP 8.4)              ← fase 1
apps/web/            App web: React + Vite                  ← fase 2
apps/mobile/         App do cliente: React Native + Expo    ← fase 3
services/classifier/ Classificador: FastAPI + BERTimbau/LoRA ← fase 5
packages/api-client/ Cliente tipado gerado do OpenAPI (web + mobile)
infra/               Dockerfile, Caddy, init do Postgres
```

## Arquitetura (resumo)

- **Containers:** `web` (Caddy, única porta publicada, em `127.0.0.1`), `api` (PHP-FPM),
  `queue` (Horizon), `migrate` (roda antes), `postgres` (17 + pgvector) e `redis`. Postgres e
  Redis ficam numa rede interna, sem porta publicada.
- **Camadas da API:** controllers finos → actions (um caso de uso cada) / services; Form
  Requests (autorizam antes de validar); API Resources; Policies; `strict_types` em todo arquivo.
- **Multi-tenant:** todo registro tem `tenant_id`. Isolamento em duas camadas: trait
  `BelongsToTenant` (global scope que não retorna nada sem tenant) **e** Row-Level Security no
  Postgres (com `FORCE`), lendo `app.tenant_id`, que o middleware `ResolveTenant` define a cada
  requisição. O papel do banco usado pela aplicação não é superusuário nem tem `BYPASSRLS`.
  Chaves estrangeiras compostas impedem relacionar registros de tenants diferentes.
- **Origem do tenant:** token bearer → sessão (definida no login) → URL assinada →
  cabeçalho `X-Tenant` (slug, usado no login). A fonte mais forte sempre vence.
- **App web:** React 19 + TypeScript estrito + Vite, TanStack Query, React Router, Tailwind
  com componentes no estilo shadcn/ui, React Hook Form + Zod e react-i18next (**pt-BR por
  padrão**, inglês). Temas claro e escuro com os tokens do design: teal para ações humanas,
  violeta só para o que vem da IA. Telas: login, fila (Meus / Não atribuídos / Todos /
  Atrasados, filtros, busca, paginação), detalhe do ticket (cliente, conversa com notas
  internas e imagens, painel da IA como "em breve"), equipe, categorias e SLA, e dashboard.
- **Mesma origem:** no Docker o Caddy serve o app em `/` e manda `/api`, `/sanctum`, `/docs`,
  `/horizon` e `/up` para o Laravel; no `make web-dev` o Vite faz proxy dessas rotas. Login por
  cookie de sessão do Sanctum com CSRF; nenhum token fica no navegador e não existe CORS com
  credenciais.
- **App mobile (cliente):** Expo SDK 56, Expo Router, TypeScript estrito, TanStack Query,
  React Hook Form + Zod, mesmo i18n (pt-BR/inglês), tokens e fonte Geist do web, claro e
  escuro. Telas: login (atalho "cliente demo" só onde a API permite), meus tickets (status
  colorido, última mensagem), novo ticket (texto, foto da câmera ou galeria, assunto
  opcional), conversa (chat, linha do tempo do status, fotos) e perfil com preferências de
  notificação; estados vazio, carregando, erro e sem conexão. Token Sanctum com validade no
  `expo-secure-store`. Como rodar no celular: [apps/mobile/README.pt-BR.md](apps/mobile/README.pt-BR.md).
- **Tempo real:** Reverb num container próprio, em rede interna; o Caddy repassa só o
  WebSocket (`/app`) na mesma origem, sem porta nova. Eventos em **canais privados**
  (`tenant.{tenant}.tickets.{ticket}` para o solicitante e a equipe; `tenant.{tenant}.staff` só
  para a equipe), autorizados em `routes/channels.php` por tenant e pela policy do ticket. O
  evento leva **só ids**; o app busca o resto pela API. Notas internas só vão para o canal da
  equipe. O web do atendente atualiza a fila e as conversas ao vivo.
- **Push:** quando a equipe responde ou muda o status, um job na fila envia a notificação aos
  aparelhos do cliente pela API de push do Expo, **sem conteúdo do ticket** (só "Nova resposta
  no ticket #4F2K9Z"), no idioma do aparelho e respeitando as preferências. O aparelho fica
  ligado ao token que o registrou: sair do app (revogar o token) desliga o push.
- **Base de conhecimento e IA (RAG):** artigos em Markdown ("interno e cliente" ou "só
  equipe"). Ao publicar, um job divide o texto em trechos (~320 tokens, 40 de sobreposição) e
  gera embeddings de 768 dimensões (pgvector, HNSW), com progresso e erro na lista e a aba
  "Como a IA vê" no editor. A busca é híbrida (full-text em português + cosseno no pgvector,
  com peso ou fusão por ranking), sempre filtrada por tenant (além do RLS), publicação e modelo
  de embedding. O **playground** mostra exatamente o contexto que a IA recebe.
- **Sugestão de resposta:** o atendente pede, um job busca nos artigos visíveis ao cliente e,
  só se o melhor trecho passar de `TICKO_AI_MIN_CONFIDENCE`, pede ao modelo um JSON com
  citações obrigatórias (`[1]`). Sem base, com citação inventada ou modelo sem resposta, o
  painel diz **"não sei"**, mostra os artigos mais próximos e permite sinalizar a lacuna.
  **A IA nunca responde sozinha:** só a aprovação do atendente (com ou sem edição) envia; os
  marcadores de fonte são removidos; o descarte pede motivo. Um teste de arquitetura impede
  o código de IA de postar mensagens. Contra prompt injection: regras na instrução de sistema,
  ticket e trechos como dados entre delimitadores com nonce aleatório, sem ferramentas, saída
  validada, e só o mínimo do ticket vai ao modelo (sem e-mail ou dados de conta).
- **Provedores** (`.env`): `TICKO_AI_TEXT_PROVIDER` = `fake` (padrão, offline) | `gemini` |
  `groq` | `ollama`; `TICKO_AI_EMBEDDING_PROVIDER` = `fake` | `gemini` | `ollama` (o Groq não
  tem embeddings). Chaves (`GEMINI_API_KEY`, `GROQ_API_KEY`) só no `.env` local. O `fake`
  faz o demo, os testes e a CI funcionarem sem chave, mas a relevância dele é só lexical.
  Trocar o provedor de embeddings pede `make kb-reindex`. **Privacidade:** no plano gratuito
  do Gemini o Google pode usar os dados enviados; para dados reais use chave paga ou Ollama.
- **Classificação de tickets:** um job classifica cada ticket novo em **categoria**,
  **prioridade** e **sentimento do cliente** com um BERTimbau ajustado com LoRA em dados
  sintéticos 100% fictícios. A IA só preenche **o que ninguém escolheu** e só acima de
  `CLASSIFIER_MIN_CONFIDENCE` (0,6); abaixo disso a sugestão fica guardada e visível, mas não é
  aplicada (mudar a prioridade recalcula o SLA). O atendente vê o **selo "IA"** em cada campo e
  o card *Classificação da IA*; **Corrigir** define os valores de vez. As correções vão para
  `classification_corrections` e tudo fica em `audit_events` (somente inserção). Desligado por
  padrão: `make up-ml` liga. Relatório completo (dataset, baseline × LoRA, F1 por classe, tempo
  de treino na CPU) em [services/classifier/README.pt-BR.md](services/classifier/README.pt-BR.md).
- **SLA:** cada ticket recebe prazos de 1ª resposta e de resolução ao ser aberto (metas da
  categoria ou padrão por prioridade em `config/ticko.php`, em horário corrido). `?overdue=1`
  lista os atrasados; `GET /api/v1/metrics` (só admin) alimenta o dashboard.

### Papéis

| | cliente | atendente | admin |
| --- | --- | --- | --- |
| Tickets | só os seus; abrir, fechar/reabrir, responder, anexar | todos; qualquer status, atribuir, notas internas | igual ao atendente |
| Categorias | listar | listar | gerenciar |
| Equipe | — | listar | listar, criar, mudar papel, desativar |
| Horizon | — | — | sim (fora do `local`, só e-mails liberados) |

## Testes e qualidade

- Pest (unit, feature e arquitetura), cobertura mínima de **80%**.
- Isolamento de tenant testado em **todo** endpoint de recurso (precisa responder `404` sem
  alterar nada); um meta-teste falha se uma rota nova ficar de fora. RLS testado com SQL puro.
- Autorização por papel; rate limit; upload (EXIF, arquivo falso, payload anexado,
  dimensões, URL assinada expirada/adulterada).
- Teste de arquitetura que falha se uma action de controller da API não chamar `authorize()`.
- Larastan no nível `max`, Pint, gitleaks.
- Web: ESLint (type-checked, hooks, acessibilidade), Prettier, `tsc` estrito, Vitest + Testing
  Library (cobertura mínima de 80%) e Playwright contra a stack Docker (login, fila, responder e
  nota interna, páginas de admin, categorias, equipe), que também falha com qualquer violação
  de CSP.
- Mobile: ESLint (type-checked, hooks; proíbe AsyncStorage e `console`), Prettier, `tsc`
  estrito, Jest + React Native Testing Library com API falsa (cobertura mínima de 80% das
  linhas) e build do bundle Android com configuração de produção.
- Base de conhecimento e IA: Pest com as APIs dos provedores falsas, chunking, busca híbrida
  (isolamento com artigos iguais em dois tenants, rascunho/arquivado/"só equipe" fora,
  modelo de embedding diferente ignorado), sugestão (citação obrigatória, "não sei" sem
  chamar o modelo, delimitadores contra injection) e aprovação; Vitest em todos os estados
  do painel; Playwright indexando, buscando e aprovando um rascunho.
- Push e tempo real: Pest com a API do Expo falsa (texto sem conteúdo, preferências, aparelho
  desregistrado, isolamento de tenant dos aparelhos) e autorização dos canais por tenant e ticket.
- CI no GitHub Actions: lint, Larastan, testes + cobertura, `composer audit`, gitleaks, build
  das imagens, lint/tipos/testes/build/`pnpm audit` do web, o job do mobile e os testes E2E;
  permissões mínimas e actions fixadas por SHA.

## Segurança

Detalhes em [README.md#security](README.md#security) e como reportar falhas em
[SECURITY.md](SECURITY.md). Pontos principais:

- Isolamento: global scope + RLS forçado + FKs compostas + papel do banco sem privilégios.
- Autenticação Sanctum: cookie de sessão para a SPA (com CSRF) e token com validade para o
  mobile, vinculado ao tenant e revogado ao desativar o usuário ou mudar o papel.
- Senhas com Argon2id, mínimo de 12 caracteres, recusadas se aparecem em vazamentos (HIBP).
- Sem enumeração de usuário: login com erro genérico e custo constante.
- Rate limit no login (por conta e por IP), na API e nos uploads.
- Upload: tipo detectado pelo conteúdo, re-encode (remove EXIF/GPS), nome aleatório, disco
  privado, URL assinada de 5 minutos.
- Erros no formato RFC 7807, sem detalhes internos fora do modo debug.
- App web com CSP estrita (só scripts, estilos, fontes, imagens e requisições da própria
  origem; nada inline; sem iframe), cookie de sessão httpOnly, conteúdo de usuário sempre
  renderizado como texto (o lint proíbe `dangerouslySetInnerHTML`), nenhum segredo em
  variáveis `VITE_*`; o controle por papel no front é só navegação, quem autoriza é a API.
- App mobile: token só no `expo-secure-store` (Keychain/Keystore, só neste aparelho), com
  validade e revogado ao sair; HTTPS obrigatório fora do dev (HTTP só para endereço da rede
  local em dev); deep links e notificações só aceitam id de ticket válido e a API revalida o
  acesso; foto re-codificada no celular (sem EXIF/GPS) e de novo na API; nenhum segredo em
  `EXPO_PUBLIC_*`; nada de dados pessoais em log.
- IA: só artigos publicados e visíveis ao cliente do próprio tenant; citações conferidas;
  confiança baixa vira "não sei"; nada sai sem o atendente; texto não confiável delimitado;
  chaves só no `.env`; chamadas ao provedor só pelos jobs da fila (a fila tem saída para a
  internet; Postgres e Redis continuam isolados).
- Tempo real e push: só canais privados, autorizados por tenant e ticket; eventos com ids,
  nunca conteúdo; push só com a referência do ticket. A API de publicação do Reverb não fica
  exposta e os clientes não enviam eventos entre si.
- Classificador: só rede interna (sem porta e sem internet) com token de serviço; dados
  pessoais mascarados antes do envio; texto do ticket nunca vai para log; saída validada contra
  enums fechados e exibida só como texto; modelo base fixado por revisão e SHA-256, só
  safetensors (sem pickle); dependências Python com hash e `pip-audit`; avaliações de F1 e de
  prompt injection na CI; imagem mínima, não-root, fixada por digest.
- Containers não-root, somente leitura, sem capabilities; nada de segredos no repositório
  (chaves de IA só no `.env` local).

## Licença

Proprietária — todos os direitos reservados.
