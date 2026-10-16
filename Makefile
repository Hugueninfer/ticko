.DEFAULT_GOAL := help
COMPOSE := docker compose
DEV := $(COMPOSE) --profile tools run --rm dev
WEB_PORT = $(or $(shell grep -s '^WEB_PORT=' .env | cut -d= -f2),8080)
# This computer's address on the local network (Linux, then macOS).
LAN_IP = $(or $(shell hostname -I 2>/dev/null | awk '{print $$1}'),$(shell ipconfig getifaddr en0 2>/dev/null),127.0.0.1)

PHONY_CLASSIFIER := classifier-image classifier-model classifier-data classifier-train classifier-eval classifier-test classifier-lock
.PHONY: $(PHONY_CLASSIFIER) help env up up-ml up-lan down logs demo kb-reindex test coverage lint fix stan docs audit gitleaks ci web-install web-dev web-test e2e mobile-env mobile-dev mobile-test

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

down: ## Stop the stack
	$(COMPOSE) --profile ml down

logs: ## Follow logs
	$(COMPOSE) logs -f

demo: ## Seed a demo tenant and print its credentials
	$(COMPOSE) exec api php artisan ticko:demo

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
	docker run --rm -v "$(CURDIR):/repo" zricethezav/gitleaks:v8.30.1 git /repo --config /repo/.gitleaks.toml --redact

ci: lint stan coverage audit gitleaks ## Everything CI runs

web-install: ## Install the web workspace (Node 22 + pnpm via corepack)
	corepack pnpm install

web-dev: web-install ## Run the web app with Vite on http://localhost:5173 (proxies the API on WEB_PORT)
	TICKO_API_URL=http://localhost:$(WEB_PORT) corepack pnpm --filter @ticko/web dev

web-test: web-install ## Lint, type-check and test the web app and API client
	corepack pnpm lint && corepack pnpm --filter '!@ticko/mobile' -r typecheck && corepack pnpm --filter '!@ticko/mobile' -r test:coverage

e2e: web-install ## End-to-end tests against the running stack (make up && make demo first)
	E2E_BASE_URL=http://localhost:$(WEB_PORT) corepack pnpm --filter @ticko/web e2e

mobile-env: ## Create apps/mobile/.env pointing the app at this computer (once)
	@test -f apps/mobile/.env && echo "apps/mobile/.env already exists; leaving it untouched." || \
		(sed -e "s|^EXPO_PUBLIC_API_URL=.*|EXPO_PUBLIC_API_URL=http://$(LAN_IP):$(WEB_PORT)|" apps/mobile/.env.example > apps/mobile/.env && \
		echo "Created apps/mobile/.env with EXPO_PUBLIC_API_URL=http://$(LAN_IP):$(WEB_PORT)")

mobile-dev: web-install mobile-env ## Start Expo for the mobile app (scan the QR code with Expo Go; needs make up-lan)
	corepack pnpm --filter @ticko/mobile start

mobile-test: web-install ## Lint, type-check and test the mobile app (coverage gate)
	corepack pnpm exec eslint apps/mobile && corepack pnpm exec prettier --check apps/mobile
	corepack pnpm --filter @ticko/mobile typecheck && corepack pnpm --filter @ticko/mobile test:coverage
