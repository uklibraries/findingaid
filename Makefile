COMPOSE_DEV = docker compose -f docker-compose.yml -f docker-compose.dev.override.yml

.PHONY: help dev build down test lint lint-fix check logs test-watch lint-watch findingaid-sh web-sh sample

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

dev: ## Start development environment
	$(COMPOSE_DEV) up -d

build: ## Build containers
	$(COMPOSE_DEV) build

down: ## Stop all containers
	$(COMPOSE_DEV) down

test: ## Run PHPUnit tests
	$(COMPOSE_DEV) exec findingaid /opt/findingaid/vendor/bin/phpunit -c /opt/findingaid/phpunit.xml /opt/findingaid/tests

lint: ## Run PHP_CodeSniffer (PSR-12)
	$(COMPOSE_DEV) exec findingaid /opt/findingaid/vendor/bin/phpcs -w --exclude=Generic.Files.LineLength --standard=PSR12 /opt/findingaid/tests /opt/findingaid/app

lint-fix: ## Auto-fix PHP_CodeSniffer violations (PSR-12)
	$(COMPOSE_DEV) exec findingaid /opt/findingaid/vendor/bin/phpcbf --exclude=Generic.Files.LineLength --standard=PSR12 /opt/findingaid/tests /opt/findingaid/app

check: ## Run linter and tests reports
	make lint
	make test

logs: ## Tail container logs
	$(COMPOSE_DEV) logs -f

test-watch: ## Run tests on each file change (requires: watchexec)
	watchexec -w app -w public -w tests --no-process-group 'make test'

lint-watch: ## Run linter on each file change (requires: watchexec)
	watchexec -w app -w tests --no-process-group 'make lint'

sample: ## Download and extract sample finding aid data (~1GB)
	wget -O xml.tar.gz https://solrindex.uky.edu/fa/findingaid/xml.tar.gz
	tar zxf xml.tar.gz
	rm xml.tar.gz

findingaid-sh: ## Shell into the findingaid container
	$(COMPOSE_DEV) exec findingaid sh

web-sh: ## Shell into the web container
	$(COMPOSE_DEV) exec web sh
