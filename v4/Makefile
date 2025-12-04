.PHONY: help install setup migrate seed run test clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install PHP dependencies
	composer install

setup: ## Setup the application (copy .env, create directories)
	@if [ ! -f .env ]; then cp .env.example .env; echo ".env file created"; fi
	@mkdir -p storage/cache storage/logs storage/uploads
	@chmod -R 775 storage
	@echo "Setup complete! Please configure your .env file."

migrate: ## Run database migrations
	php scripts/migrate.php

seed: ## Seed the database with sample data
	php scripts/seed.php

run: ## Start the development server
	php -S localhost:8000 -t public

test: ## Run tests
	@echo "Running tests..."
	@if [ -d "tests" ]; then vendor/bin/phpunit; else echo "No tests found"; fi

clean: ## Clean cache and logs
	rm -rf storage/cache/* storage/logs/*
	@echo "Cache and logs cleaned"

reset: clean ## Reset the application (clean + fresh install)
	rm -rf vendor composer.lock
	make install
	make setup
