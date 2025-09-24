.PHONY: lint phpcs

lint:
	phplint application/ library/
phpcs:
	phpcs
