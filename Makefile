fix-cs:
	./vendor/bin/php-cs-fixer fix

test:
	./vendor/bin/php-cs-fixer fix --diff --dry-run
