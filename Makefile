DOCKER_COMPOSE = docker compose
EXEC_PHP = $(DOCKER_COMPOSE) exec -T php
EXEC_MYSQL = $(DOCKER_COMPOSE) exec mysql

.PHONY: docker-down docker-up docker-down-force

docker-up:
	$(DOCKER_COMPOSE) up -d --build

docker-down:
	docker compose down

docker-down-force:
	docker compose down -v

keys:
	$(EXEC_PHP) php artisan key:generate

database-drop-data:
	sudo rm -rf docker/db/data
	mkdir docker/db/data

set-env:
	$(EXEC_PHP) cp .env.example .env

set-overrides:
	cp docker-compose.override.yml.dist docker-compose.override.yml

init: set-overrides docker-up set-env
