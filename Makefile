# Соловей — новости Курска и области.
#
# Один раннер: цели ниже — однострочные вызовы скриптов из scripts/.
# Флаги передаются через ARGS: make build ARGS="--mode development"

.DEFAULT_GOAL := help
.PHONY: help install serve dev build db-migrate db-seed db-reset check errors-check icons media snapshot clean

help: ## показать список целей
	@grep -E '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) | sed 's/:.*## /\t— /'

install: ## установить зависимости (composer + npm)
	./scripts/install $(ARGS)

serve: ## запустить сайт на 127.0.0.1:8090
	./scripts/serve $(ARGS)

dev: ## Vite с HMR + PHP-сервер
	./scripts/dev $(ARGS)

build: ## собрать клиентские ассеты
	./scripts/build $(ARGS)

db-migrate: ## создать схему базы
	./scripts/migrate $(ARGS)

db-seed: ## наполнить базу из снимка
	./scripts/seed $(ARGS)

db-reset: ## пересоздать базу с нуля
	./scripts/migrate $(ARGS)
	./scripts/seed $(ARGS)

check: ## синтаксис, маршруты, иконки, коды ошибок
	./scripts/check $(ARGS)

errors-check: ## проверить реестр кодов ошибок
	./scripts/errors_check.php $(ARGS)

icons: ## собрать спрайт иконок
	./scripts/php vendor/bin/bolt icons $(ARGS)

media: ## уменьшить исходники картинок
	./scripts/optimize_media.php $(ARGS)

snapshot: ## снять свежий снимок kursk-news.net
	./scripts/fetch_snapshot.py $(ARGS)

clean: ## удалить сборочные артефакты
	rm -rf public/build var/sprite.svg var/cache
