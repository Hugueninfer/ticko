# Modelo de ameaças (STRIDE)

*[English](THREAT-MODEL.md)*

Escopo: os três fluxos que mais importam — **login**, **o ticket do cliente** (abrir, ler,
responder, anexar) e **a sugestão de resposta da IA**. Ativos: dados de cada tenant (tickets,
mensagens, imagens, dados pessoais dos clientes), credenciais e tokens, chaves dos provedores de
IA e a confiança do cliente (nada é enviado em nome da loja sem uma pessoa). Fronteiras de
confiança: navegador/celular → Caddy → API; API ↔ Postgres (RLS); fila → provedores de IA e
Expo (internet); fila → classificador (interno). O detalhe de cada linha, com o teste que prova
o controle, está na [versão em inglês](THREAT-MODEL.md).

## 1. Login

- **Falsificação (S):** senha adivinhada → Argon2id, mínimo de 12 caracteres, senhas vazadas
  recusadas, rate limit por conta e por IP, alerta de pico de falhas no Grafana. Sessão ou token
  roubado → cookie httpOnly com CSRF; token do app com validade, preso ao tenant, no
  Keychain/Keystore, revogado ao sair, desativar ou trocar papel/senha.
- **Adulteração (T):** trocar de tenant pelo header `X-Tenant` → o tenant do token/sessão sempre vence.
- **Repúdio (R):** falhas de login contadas e registradas por tenant, sem o e-mail.
- **Divulgação (I):** enumeração de usuários → erro genérico e custo constante.
- **Negação de serviço (D):** força bruta → rate limit antes do hash.
- **Elevação (E):** atalho de demo em produção → `/auth/demo` responde 404 fora do modo demo e nunca em produção.

## 2. O ticket do cliente

- **S:** agir como outro cliente → policies em toda ação; cliente só vê os próprios tickets.
- **T:** ler ou alterar ticket de outro tenant → global scope + **RLS forçado** + FKs compostas +
  papel do banco sem privilégios; ids de outro tenant respondem 404 (testado em todas as rotas).
  Upload malicioso → tipo pelo conteúdo, limites, re-encode, nome aleatório, disco privado, URL
  assinada de 5 minutos. XSS → conteúdo só como texto e CSP estrita.
- **R:** classificação ou correção contestada → `audit_events` somente inserção (trigger no banco).
- **I:** texto do ticket ou dados pessoais em logs, traces ou push → push só com a referência;
  telemetria redigida duas vezes; o classificador recebe texto mascarado e nunca o registra.
  Notas internas nunca chegam ao cliente.
- **D:** excesso de requisições → rate limit com `Retry-After`; limites de upload.
- **E:** cliente definindo prioridade ou responsável → autorização antes da validação.

## 3. A sugestão da IA

- **S:** a IA respondendo como a loja → rascunho só sai com aprovação do atendente; um teste de
  arquitetura impede o código de IA de postar mensagens.
- **T:** prompt injection no ticket ou em artigo → regras só no prompt de sistema, texto não
  confiável entre delimitadores com nonce, sem ferramentas, saída JSON com citações apenas das
  fontes fornecidas; o classificador remove frases de instrução (576 ataques testados na CI).
  Alucinação → só responde acima da confiança da busca e citando fontes; senão, "não sei".
- **R:** quem pediu, aprovou, editou ou descartou (com motivo) cada rascunho fica registrado.
- **I:** artigos de outro tenant ou internos no prompt → busca filtrada por tenant (além do RLS)
  e só artigos publicados e visíveis ao cliente. Dados pessoais ao provedor → dados mínimos.
  Chaves vazando → só no `.env`, em headers, nunca em log; gitleaks e Semgrep na CI.
- **D:** custo explodindo ou provedor fora → rate limit, timeouts, erro com "tentar de novo",
  custo por tenant e alertas (erro > 20 %, custo > 5 USD/h).
- **E:** saída do modelo usada como código/HTML → JSON validado, renderizado como texto;
  rótulos do classificador de enums fechados.

## Riscos residuais

- Dados de treino sintéticos: validar a precisão do classificador com as correções reais.
- Prompt injection ainda pode piorar um rascunho; a aprovação humana é o controle.
- O demo roda em HTTP no localhost; produção precisa de TLS e de um gerenciador de segredos.
- Push no Android precisa de credenciais FCM e não foi testado em aparelho real.
