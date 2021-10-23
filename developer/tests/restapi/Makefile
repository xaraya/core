all:
	# sudo apt install default-jre-headless
	npm update @openapitools/openapi-generator-cli -g
	openapi-generator-cli generate -g php -i ../../../html/var/cache/api/openapi.json
	composer install

.PHONY: tests
tests:
	phpunit OpenApiTest.php

.PHONY: clean
clean:
	rm -rf docs lib test vendor .openapi-generator
	find . -type f -delete
	git restore .

