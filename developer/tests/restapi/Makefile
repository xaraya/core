all:
	# sudo apt install default-jre-headless
	npm install @openapitools/openapi-generator-cli -g
	openapi-generator-cli generate -g php -i ../../../html/var/cache/api/openapi.json
	composer install

.PHONY: tests
tests:
	# phpunit OpenApiTest.php
	phpunit RestApiTest.php

.PHONY: clean
clean:
	rm -rf docs lib test vendor .openapi-generator
	find . -type f -delete
	git restore .

