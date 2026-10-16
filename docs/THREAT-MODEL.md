# Threat model (STRIDE)

*[Português](THREAT-MODEL.pt-BR.md)*

Scope: the three flows that matter most — **signing in**, **a customer's ticket** (create, read,
reply, attach) and **the AI reply suggestion**. Assets: tenant data (tickets, messages, images,
customers' personal data), credentials and tokens, AI provider keys, and the customers' trust
(nothing must be sent in the store's name without a person). Trust boundaries: browser/phone →
Caddy → API; API ↔ Postgres (RLS); queue → AI providers and Expo (internet); queue → classifier
(internal). Each row is a threat, the control and where it is proven.

## 1. Sign-in (web session and mobile token)

| STRIDE | Threat | Control | Proof |
| --- | --- | --- | --- |
| **S**poofing | Password guessing, credential stuffing | Argon2id, 12+ characters, breached passwords refused (HIBP); rate limits per account and IP; failed sign-ins alert in Grafana | `PasswordTest`, `RateLimitTest`, alert rule `ticko-login-failures` |
| **S** | Stolen session or token | httpOnly session cookie + CSRF for the SPA; expiring, tenant-bound mobile tokens in the Keychain/Keystore; revoked on sign-out, deactivation, role or password change | `SessionAuthTest`, `TokenAuthTest`, mobile tests |
| **T**ampering | Switching tenant with the `X-Tenant` header after login | The token's or session's tenant always wins; the header is only used to log in | `TenantIsolationTest` |
| **R**epudiation | "I never signed in" | Failed sign-ins are counted per tenant and logged without the e-mail | `TelemetryTest` |
| **I**nformation disclosure | User enumeration | One generic error and constant cost whether the tenant, e-mail or password is wrong | `SessionAuthTest` ("does not reveal…") |
| **D**enial of service | Brute force exhausting CPU (Argon2id) | Per-IP and per-account rate limits before hashing | `RateLimitTest` |
| **E**levation of privilege | Demo shortcut in production | `/auth/demo` answers 404 unless `TICKO_DEMO_LOGIN=true`, never in production | `DemoLoginTest` |

## 2. A customer's ticket

| STRIDE | Threat | Control | Proof |
| --- | --- | --- | --- |
| **S** | Acting as another customer | Policies on every action; customers see only their own tickets | role matrices in `TicketTest` |
| **T** | Reading or changing another tenant's ticket by id | Global scope + **forced RLS** + composite FKs + non-privileged DB role; foreign ids answer 404 | `TenantIsolationTest` (every route), `RowLevelSecurityTest` |
| **T** | Malicious image upload (polyglot, EXIF) | Type from content, size and pixel limits, re-encoded, random names, private disk, 5-minute signed URLs with `nosniff` and sandbox CSP | `AttachmentTest`, `ImageSanitizerTest` |
| **T** | Stored XSS in messages | Text-only rendering; lint forbids `dangerouslySetInnerHTML`; strict CSP | web tests, e2e CSP check |
| **R** | Disputed classification or change | Automatic classifications and agents' corrections in append-only `audit_events` (DB trigger) | `ClassifyTicketTest`, `CorrectClassificationTest` |
| **I** | Ticket text or personal data in logs, traces or push | Push carries only the reference; telemetry redacted twice (Laravel + Collector); classifier receives masked text and never logs it | `TicketPushTest`, `TelemetryTest`, `RedactorTest`, CI Loki search |
| **I** | Internal notes reaching the customer | Internal notes only on the staff channel and staff responses | `TicketMessageTest`, `BroadcastingTest` |
| **D** | Flooding the API or uploads | Per-user rate limits with `Retry-After`; upload size limits | `RateLimitTest` |
| **E** | Customer setting priority or assignee | Form requests authorize before validating; staff-only fields | `TicketTest` |

## 3. The AI reply suggestion

| STRIDE | Threat | Control | Proof |
| --- | --- | --- | --- |
| **S** | The AI answering customers as the store | Drafts are only sent when an agent approves them; an architecture test forbids the AI code from posting messages | `ArchitectureTest`, `AiSuggestionTest`, e2e |
| **T** | Prompt injection in the ticket or an article ("ignore your rules…") | Rules only in the system prompt; untrusted text inside per-request nonce delimiters; no tools; JSON output with citations of provided passages only; the classifier strips instruction-like text | `ReplyDrafterTest`, classifier injection eval (576 pairs, CI) |
| **T** | Hallucinated answers | Answer only above a retrieval confidence; must cite sources; otherwise "I don't know" | `AiSuggestionTest` |
| **R** | "The AI said it" | Who requested, approved, edited or discarded (with reason) each draft is stored | `AiSuggestionTest` |
| **I** | Another tenant's articles or staff-only articles in a prompt | Retrieval filtered by tenant (plus RLS), published and customer-visible articles only | `KnowledgeSearchTest` |
| **I** | Personal data sent to the provider | Minimal data (subject, messages, first name); PII masked for the classifier; free-tier privacy note for Gemini | `ReplyDrafterTest`, `ClassifyTicketTest` |
| **I** | Provider keys leaking | Keys only in `.env`, sent in headers (not URLs), never logged; gitleaks and Semgrep in CI | `ProvidersTest`, `RedactorTest` |
| **D** | Cost explosion or provider outage | Per-user rate limit on suggestions; timeouts; failures shown with a retry; cost per tenant and alerts (errors > 20 %, cost > 5 USD/h) | `AiSuggestionTest`, alert rules |
| **E** | Model output used as code or HTML | Output parsed as JSON, citations validated, rendered as text; classifier labels from closed enums | `ReplyDrafterTest`, `ClassifyTicketTest`, web tests |

## Residual risks

- Synthetic training data: the classifier's real-world accuracy must be validated with agents'
  corrections before relying on it.
- Prompt injection can still degrade a draft's quality; the human approval step is the control.
- The demo runs over HTTP on localhost; production needs TLS at the edge and a secret manager.
- Push notifications on Android need FCM credentials and were not tested on a real device.
