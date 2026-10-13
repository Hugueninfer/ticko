.DEFAULT_GOAL := help
COMPOSE := docker compose
DEV := $(COMPOSE) --profile tools run --rm dev
WEB_PORT = $(or $(shell grep -s '^WEB_PORT=' .env | cut -d= -f2),8080)

.PHONY: help env up down logs demo test coverage lint fix stan docs audit gitleaks ci

help: ## Show available targets
	@grep -E '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

env: ## Create .env with generated secrets (once)
	@./scripts/make-env.sh

up: env ## Build and start the whole stack (http://localhost:$WEB_PORT, default 8080)
	$(COMPOSE) up -d --build --wait
	@echo "API:  http://localhost:$(WEB_PORT)/api/v1"
	@echo "Docs: http://localhost:$(WEB_PORT)/docs"

down: ## Stop the stack
	$(COMPOSE) down

logs: ## Follow logs
	$(COMPOSE) logs -f

demo: ## Seed a demo tenant and print its credentials
	$(COMPOSE) exec api php artisan ticko:demo

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
