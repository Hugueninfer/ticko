# Ticko

[![CI](https://github.com/Hugueninfer/ticko/actions/workflows/ci.yml/badge.svg)](https://github.com/Hugueninfer/ticko/actions/workflows/ci.yml)
![Cobertura](https://raw.githubusercontent.com/Hugueninfer/ticko/badges/coverage.svg)

Helpdesk multi-tenant com IA. Este repositório é um monorepo; a **fase 1** entrega a API,
a infraestrutura e os controles de qualidade. Web, mobile e o classificador de IA ainda são
esqueletos.

> 🇺🇸 English version: [README.md](README.md) (referência completa de arquitetura e endpoints)

## Subir tudo com um comando

Requisitos: Docker com Compose v2 e `make`. Não precisa de PHP, Composer nem Node na máquina.

```bash
make up
```

Na primeira vez, cria o `.env` com segredos gerados na hora; depois constrói as imagens, roda
as migrations e sobe tudo:

| URL | O quê |
| --- | --- |
| http://localhost:8080/docs | Swagger UI da API (OpenAPI 3) |
| http://localhost:8080/docs/openapi.json | Documento OpenAPI |
| http://localhost:8080/api/v1 | A API |
| http://localhost:8080/horizon | Painel das filas (só admin) |

Porta 8080 ocupada? Ajuste `WEB_PORT` (e `APP_URL`) no `.env`.

Tenant de demonstração (`acme`) com um usuário por papel; as senhas são aleatórias e
aparecem uma única vez:

```bash
make demo
```

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
| `make ci` | Tudo o que a CI roda |

## Estrutura

```
apps/api/            API Laravel 13 (PHP 8.4)              ← fase 1
apps/web/            SPA web                                (esqueleto)
apps/mobile/         App mobile                             (esqueleto)
services/classifier/ Classificador de tickets com IA        (esqueleto)
packages/api-client/ Tipos TypeScript gerados do OpenAPI
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
- CI no GitHub Actions: lint, Larastan, testes + cobertura, `composer audit`, gitleaks e build
  das imagens; permissões mínimas e actions fixadas por SHA.

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
- Containers não-root, somente leitura, sem capabilities; nada de segredos no repositório
  (chaves de IA só no `.env` local).

## Licença

Proprietária — todos os direitos reservados.
