# PHP Code Intelligence Tool - Multi-Version Testing

.PHONY: help test test-all test-82 test-83 test-84 benchmark clean phpstan code-quality

# Colors
BLUE := \033[36m
GREEN := \033[32m
RED := \033[31m
YELLOW := \033[33m
NC := \033[0m

help: ## Show help
	@echo "$(BLUE)PHP Code Intelligence Tool - Multi-Version Testing$(NC)"
	@echo "=================================================="
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-15s$(NC) %s\n", $$1, $$2}'

test: ## Test with current PHP version
	@echo "$(YELLOW)Testing with current PHP version...$(NC)"
	composer test
	php bin/php-code-intel --version

# Helper function to install dependencies and test
define test_version
	@echo "$(YELLOW)Installing dependencies with Composer...$(NC)"
	@docker run --rm -v $(PWD):/app composer:latest install --no-interaction
	@echo "$(YELLOW)Testing with PHP $(1)...$(NC)"
	@docker run --rm -v $(PWD):/app -w /app php:$(1)-cli sh -c "php vendor/bin/phpunit && php bin/php-code-intel --version && php bin/php-code-intel find-usages \"TestFixtures\\\\BasicSymbols\\\\SimpleClass\" --path=tests/fixtures/BasicSymbols/ --format=json | head -5"
endef

test-82: ## Test with PHP 8.2
	$(call test_version,8.2)

test-83: ## Test with PHP 8.3
	$(call test_version,8.3)

test-84: ## Test with PHP 8.4
	$(call test_version,8.4)

test-all: ## Test all supported PHP versions (8.2, 8.3, 8.4)
	@echo "$(BLUE)Testing all supported PHP versions...$(NC)"
	@echo ""
	@$(MAKE) test-82 || echo "$(RED)❌ PHP 8.2 failed$(NC)"
	@echo ""
	@$(MAKE) test-83 || echo "$(RED)❌ PHP 8.3 failed$(NC)"
	@echo ""
	@$(MAKE) test-84 || echo "$(RED)❌ PHP 8.4 failed$(NC)"
	@echo ""
	@echo "$(GREEN)✅ Multi-version testing complete!$(NC)"

benchmark: ## Benchmark performance across supported PHP versions
	@echo "$(BLUE)Performance Benchmark$(NC)"
	@echo "===================="
	@echo "$(YELLOW)PHP 8.2:$(NC)"
	@docker run --rm -v $(PWD):/app -w /app php:8.2-cli-alpine sh -c "composer install --no-interaction && time php bin/php-code-intel index tests/fixtures/ >/dev/null"
	@echo "$(YELLOW)PHP 8.3:$(NC)"
	@docker run --rm -v $(PWD):/app -w /app php:8.3-cli-alpine sh -c "composer install --no-interaction && time php bin/php-code-intel index tests/fixtures/ >/dev/null"
	@echo "$(YELLOW)PHP 8.4:$(NC)"
	@docker run --rm -v $(PWD):/app -w /app php:8.4-cli-alpine sh -c "composer install --no-interaction && time php bin/php-code-intel index tests/fixtures/ >/dev/null"

matrix: ## Show compatibility matrix
	@echo "$(BLUE)PHP Compatibility Matrix$(NC)"
	@echo "========================="
	@echo "| Version | Tests | CLI   | Symbol Finding |"
	@echo "|---------|-------|-------|----------------|"
	@for version in 8.2 8.3 8.4; do \
		echo -n "| $$version     |"; \
		if docker run --rm -v $(PWD):/app -w /app php:$$version-cli-alpine sh -c "composer install --no-interaction >/dev/null 2>&1 && php vendor/bin/phpunit >/dev/null 2>&1"; then \
			echo -n " $(GREEN)✅$(NC)    |"; \
		else \
			echo -n " $(RED)❌$(NC)    |"; \
		fi; \
		if docker run --rm -v $(PWD):/app -w /app php:$$version-cli-alpine sh -c "composer install --no-interaction >/dev/null 2>&1 && php bin/php-code-intel --version >/dev/null 2>&1"; then \
			echo -n " $(GREEN)✅$(NC)    |"; \
		else \
			echo -n " $(RED)❌$(NC)    |"; \
		fi; \
		if docker run --rm -v $(PWD):/app -w /app php:$$version-cli-alpine sh -c "composer install --no-interaction >/dev/null 2>&1 && php bin/php-code-intel find-usages 'TestFixtures\\BasicSymbols\\SimpleClass' --path=tests/fixtures/BasicSymbols/ --format=json | grep -q SimpleClass 2>/dev/null"; then \
			echo " $(GREEN)✅$(NC)             |"; \
		else \
			echo " $(RED)❌$(NC)             |"; \
		fi; \
	done

quick: test-84 ## Quick test with latest PHP only

clean: ## Clean up
	@echo "$(YELLOW)Cleaning up...$(NC)"
	docker system prune -f

install-deps: ## Install Composer dependencies for testing
	@echo "$(YELLOW)Installing dependencies...$(NC)"
	composer install

phpstan: ## Run PHPStan static analysis
	@echo "$(YELLOW)Running PHPStan static analysis...$(NC)"
	composer phpstan

phpstan-baseline: ## Generate PHPStan baseline
	@echo "$(YELLOW)Generating PHPStan baseline...$(NC)"
	composer phpstan-baseline

code-quality: ## Run all code quality checks (PHPStan + Tests)
	@echo "$(BLUE)Running code quality checks...$(NC)"
	@echo ""
	@$(MAKE) phpstan || echo "$(RED)❌ PHPStan failed$(NC)"
	@echo ""
	@$(MAKE) test || echo "$(RED)❌ Tests failed$(NC)"
	@echo ""
	@echo "$(GREEN)✅ Code quality checks complete!$(NC)"