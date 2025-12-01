# PartOps Makefile
# Helper commands for development and deployment

.PHONY: help setup install migrate seed test run clean

help:
	@echo "PartOps - Available commands:"
	@echo "  make setup    - Initial project setup (install + migrate + seed)"
	@echo "  make install  - Install PHP dependencies via Composer"
	@echo "  make migrate  - Run database migrations"
	@echo "  make seed     - Seed database with sample data"
	@echo "  make test     - Run tests"
	@echo "  make run      - Start development server"
	@echo "  make clean    - Clean cache and logs"

setup: install migrate seed
	@echo "Setup complete! Run 'make run' to start the server."

install:
	@echo "Installing dependencies..."
	composer install

migrate:
	@echo "Running migrations..."
	@php scripts/migrate.php

seed:
	@echo "Seeding database..."
	@php scripts/seed.php

test:
	@echo "Running tests..."
	@vendor/bin/phpunit tests/

run:
	@echo "Starting development server on http://localhost:8000"
	@php -S localhost:8000 -t public/

clean:
	@echo "Cleaning cache and logs..."
	@rm -rf storage/cache/*
	@rm -rf storage/logs/*
	@echo "Clean complete."
