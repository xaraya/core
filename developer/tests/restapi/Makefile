all:
	# sudo apt install default-jre-headless
	npm update @openapitools/openapi-generator-cli -g
	cp .gitignore .gitignore.orig
	openapi-generator-cli generate -g php -i ../../../html/var/cache/api/openapi.json
	composer install

.PHONY: tests
tests:
	phpunit OpenApiTest.php

.PHONY: clean
clean:
	cp Makefile ..
	cp RestApiTest.php ..
	cp .gitignore.orig ..
	rm -rf docs lib test vendor .openapi-generator
	find . -type f -delete
	mv ../Makefile .
	mv ../RestApiTest.php .
	mv ../.gitignore.orig .gitignore

