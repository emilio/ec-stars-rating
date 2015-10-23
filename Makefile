PHPCS := ./vendor/squizlabs/php_codesniffer/scripts/phpcs
PHPCFB := ./vendor/squizlabs/php_codesniffer/scripts/phpcbf

# TODO: test JS

.PHONY: lint
lint:
	$(PHPCS) -s --standard=test/phpcs/ruleset.xml $(wildcard *.php)

# This is the lint but without warnings, that unfortunately are common
.PHONY: tests
tests:
	$(PHPCS) -n --standard=test/phpcs/ruleset.xml $(wildcard *.php)

.PHONY: autofix
autofix:
	$(PHPCFB) --standard=test/phpcs/ruleset.xml $(wildcard *.php)
