.DEFAULT_GOAL := help
COMPOSE := docker compose
DEV := $(COMPOSE) --profile tools run --rm dev
WEB_PORT = $(or $(shell grep -s '^WEB_PORT=' .env | cut -d= -f2),8080)
GRAFANA_PORT = $(or $(shell grep -s '^GRAFANA_PORT=' .env | cut -d= -f2),3001)
# This computer's address on the local network (Linux, then macOS).
LAN_IP = $(or $(shell hostname -I 2>/dev/null | awk '{print $$1}'),$(shell ipconfig getifaddr en0 2>/dev/null),127.0.0.1)

PHONY_CLASSIFIER := classifier-image classifier-model classifier-data classifier-train classifier-eval classifier-test classifier-lock
.PHONY: $(PHONY_CLASSIFIER) help env up up-ml up-obs screenshots demo-reset backup backup-verify semgrep trivy obs-dashboards e2e-obs up-lan down logs demo kb-reindex test coverage lint fix stan docs audit gitleaks ci web-install web-dev web-test e2e mobile-env mobile-dev mobile-test

help: ## Show available targets
	@grep -E '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-17s\033[0m %s\n", $$1, $$2}'

env: ## Create .env with generated secrets (once)
	@./scripts/make-env.sh

up: env ## Build and start the whole stack (http://localhost:$WEB_PORT, default 8080)
	$(COMPOSE) up -d --build --wait
	@echo "API:  http://localhost:$(WEB_PORT)/api/v1"
	@echo "Docs: http://localhost:$(WEB_PORT)/docs"

up-lan: env ## Like up, but reachable from phones on your Wi-Fi (only WEB_PORT; Postgres/Redis stay internal)
	WEB_BIND=0.0.0.0 $(COMPOSE) up -d --build --wait
	@echo "Phones on your network: http://$(LAN_IP):$(WEB_PORT)  (run 'make up' to go back to localhost only)"

up-ml: env ## Like up, plus the ticket classifier (first build downloads ~1 GB: PyTorch CPU + base model)
	CLASSIFIER_URL=http://classifier:8000 $(COMPOSE) --profile ml up -d --build --wait
	@echo "Classifier on (internal network only). New tickets get category, priority and sentiment."

up-obs: env ## Like up, plus observability: Grafana on http://localhost:3001 (admin password in .env)
	TICKO_TELEMETRY=true $(COMPOSE) --profile obs up -d --build --wait
	@echo "Grafana: http://localhost:$(GRAFANA_PORT)  user: admin  password: GRAFANA_ADMIN_PASSWORD in .env"

screenshots: web-install ## Regenerate docs/screenshots from the running demo (make up-obs && make demo first)
	E2E_BASE_URL=http://localhost:$(WEB_PORT) GRAFANA_URL=http://localhost:$(GRAFANA_PORT) \
		GRAFANA_PASSWORD=$$(grep '^GRAFANA_ADMIN_PASSWORD=' .env | cut -d= -f2-) \
		corepack pnpm --filter @ticko/web e2e --project docs

obs-dashboards: ## Regenerate the Grafana dashboards (JSON) from infra/observability/grafana/build_dashboards.py
	python3 infra/observability/grafana/build_dashboards.py

e2e-obs: web-install ## Check Grafana (login required, every panel has data); run after make up-obs, make demo and make e2e
	GRAFANA_URL=http://localhost:$(GRAFANA_PORT) GRAFANA_PASSWORD=$$(grep '^GRAFANA_ADMIN_PASSWORD=' .env | cut -d= -f2-) \
		corepack pnpm --filter @ticko/web e2e --project observability

down: ## Stop the stack
	$(COMPOSE) --profile ml --profile obs down

logs: ## Follow logs
	$(COMPOSE) logs -f

demo: ## Seed (or complete) the demo tenant "Loja Aurora" and print the credentials of new users
	$(COMPOSE) exec api php artisan ticko:demo

demo-reset: ## Delete the demo tenant and seed it again from scratch (prints new passwords)
	$(COMPOSE) exec api php artisan ticko:demo --fresh

kb-reindex: ## Index every published knowledge-base article again (after changing TICKO_AI_EMBEDDING_PROVIDER)
	$(COMPOSE) exec api php artisan ticko:kb:reindex

UV_IMAGE = ghcr.io/astral-sh/uv:0.9.5-python3.12-bookworm-slim@sha256:f4a1d5fb780441a485a8e3f88b30344ab858ed8acde3cc35f1ef59516b253ec8
CLASSIFIER_DEV = docker run --rm -v "$(CURDIR)/services/classifier:/src" -v ticko-classifier-models:/models ticko-classifier:dev

classifier-image:
	docker build -q --target dev --build-arg UID=$$(id -u) --build-arg GID=$$(id -g) -t ticko-classifier:dev services/classifier >/dev/null

classifier-model: classifier-image ## Download the pinned base model (SHA-256 checked) into a Docker volume
	$(CLASSIFIER_DEV) python -m ticko_classifier.fetch_model --dir /models/bertimbau-base

classifier-data: classifier-image ## Generate the synthetic dataset again (deterministic)
	$(CLASSIFIER_DEV) python -m ticko_classifier.dataset

classifier-train: classifier-model ## Train the baseline and the LoRA model on the CPU (LoRA takes ~25 min)
	$(CLASSIFIER_DEV) sh -c 'python -m ticko_classifier.baseline && python -m ticko_classifier.lora'

classifier-eval: classifier-model ## Evaluate both models and enforce thresholds.json (writes reports/)
	$(CLASSIFIER_DEV) sh -c 'python -m ticko_classifier.evaluate --model baseline --check --write && python -m ticko_classifier.evaluate --model lora --check --write'

classifier-test: classifier-image ## Lint and test the classifier service (coverage gate) and audit its dependencies
	$(CLASSIFIER_DEV) sh -c 'ruff check . && ruff format --check . && pytest --cov && ./audit.sh'

classifier-lock: ## Re-lock the classifier dependencies with hashes (after editing requirements*.in)
	docker run --rm -u $$(id -u):$$(id -g) -e UV_CACHE_DIR=/tmp/uv -v "$(CURDIR)/services/classifier:/src" -w /src --entrypoint sh $(UV_IMAGE) ./lock.sh

backup: env ## Encrypted database backup into backups/ (AES-256, BACKUP_PASSPHRASE from .env)
	./scripts/backup.sh

backup-verify: env ## Back up, restore into a scratch database and compare every table's row count
	./scripts/backup-verify.sh

vendor: env
	$(DEV) composer install

test: vendor ## Run the test suite
	$(DEV) composer test

coverage: vendor ## Run tests with coverage (fails under 80%)
	$(DEV) composer test:coverage

lint: vendor ## Check code style (Pint)
	$(DEV) composer lint

fix: vendor ## Fix code style
	$(DEV) composer fix

stan: vendor ## Static analysis (Larastan)
	$(DEV) composer stan

docs: vendor ## Regenerate the OpenAPI document
	$(DEV) composer docs

audit: vendor ## Check dependencies for known vulnerabilities
	$(DEV) composer audit

gitleaks: ## Scan the repository for secrets
	docker run --rm -v "$(CURDIR):/repo" zricethezav/gitleaks@sha256:c00b6bd0aeb3071cbcb79009cb16a60dd9e0a7c60e2be9ab65d25e6bc8abbb7f git /repo --config /repo/.gitleaks.toml --redact

SEMGREP_IMAGE = semgrep/semgrep@sha256:529ee8a277ec8adc5b534d7c74eea0a47e9de21d62852b6ba7ac6ba9566845c3
TRIVY_IMAGE = aquasec/trivy@sha256:2416d1f74045b57274df1a340eee6b510cacd73c66a6ccb8da092b117eb7d222

semgrep: ## SAST for the PHP API (Semgrep PHP and secrets rules)
	docker run --rm -v "$(CURDIR):/src" -w /src $(SEMGREP_IMAGE) semgrep scan --config p/php --config p/secrets --metrics=off --error \
		apps/api/app apps/api/config apps/api/routes apps/api/bootstrap

trivy: ## Scan the built images for fixable HIGH/CRITICAL vulnerabilities (after make up / up-ml)
	@for image in ticko-api:local ticko-web:local ticko-postgres:local $$(docker image inspect ticko-classifier:local >/dev/null 2>&1 && echo ticko-classifier:local); do \
		echo "== $$image"; \
		docker run --rm -v /var/run/docker.sock:/var/run/docker.sock -v "$(CURDIR)/.trivyignore.yaml:/ignore.yaml:ro" $(TRIVY_IMAGE) \
			image --quiet --scanners vuln --severity CRITICAL,HIGH --ignore-unfixed --ignorefile /ignore.yaml --exit-code 1 $$image || exit 1; \
	done

ci: lint stan coverage audit gitleaks semgrep web-test mobile-test classifier-test ## Everything CI runs without the Docker stack (then: make up, make e2e, make trivy)

web-install: ## Install the web workspace (Node 22 + pnpm via corepack)
	corepack pnpm install

web-dev: web-install ## Run the web app with Vite on http://localhost:5173 (proxies the API on WEB_PORT)
	TICKO_API_URL=http://localhost:$(WEB_PORT) corepack pnpm --filter @ticko/web dev

web-test: web-install ## Lint, type-check and test the web app and API client
	corepack pnpm lint && corepack pnpm --filter '!@ticko/mobile' -r typecheck && corepack pnpm --filter '!@ticko/mobile' -r test:coverage

e2e: web-install ## End-to-end tests against the running stack (make up && make demo first)
	E2E_BASE_URL=http://localhost:$(WEB_PORT) corepack pnpm --filter @ticko/web e2e --project chromium

mobile-env: ## Create apps/mobile/.env pointing the app at this computer (once)
	@test -f apps/mobile/.env && echo "apps/mobile/.env already exists; leaving it untouched." || \
		(sed -e "s|^EXPO_PUBLIC_API_URL=.*|EXPO_PUBLIC_API_URL=http://$(LAN_IP):$(WEB_PORT)|" apps/mobile/.env.example > apps/mobile/.env && \
		echo "Created apps/mobile/.env with EXPO_PUBLIC_API_URL=http://$(LAN_IP):$(WEB_PORT)")

mobile-dev: web-install mobile-env ## Start Expo for the mobile app (scan the QR code with Expo Go; needs make up-lan)
	corepack pnpm --filter @ticko/mobile start

mobile-test: web-install ## Lint, type-check and test the mobile app (coverage gate)
	corepack pnpm exec eslint apps/mobile && corepack pnpm exec prettier --check apps/mobile
	corepack pnpm --filter @ticko/mobile typecheck && corepack pnpm --filter @ticko/mobile test:coverage
