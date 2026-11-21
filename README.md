# Соловей — новости Курска и области

Редизайн и переосмысление [kursk-news.net](https://kursk-news.net/): та же
структура страниц и те же ссылки, но быстрее, доступнее и с нормальной
навигацией. Снимок контента снят с оригинала, вёрстка написана с нуля.

Тип репозитория: монолит
Фреймворк: bolt

- **Архитектурная модель оригинала, разбор блоков и план улучшений** —
  [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- **Развёртывание (nginx, Apache, права, кэш)** —
  [`docs/DEPLOY.md`](docs/DEPLOY.md)

## Стек

| Слой | Что |
|---|---|
| Рендер | Bolt: Plates-шаблоны, `urlpatterns`, `bolt://ui-kit/seo`, `bolt://ui-kit/pagination`, спрайт иконок, `/convert-image` |
| Модель | CakePHP ORM 5.4: таблицы, сущности с геттерами, finder'ы |
| База | SQLite (`database/solovey.sqlite`) |
| Интерфейс | Tailwind 4, Alpine.js 3, htmx 2, Vite 8 |
| Данные | снимок `kursk-news.net` → `database/snapshot.json` → сид |

## Быстрый старт

```bash
make install     # composer install + npm install
make db-reset    # схема + данные из снимка
make build       # Tailwind и JS в public/build
make serve       # http://127.0.0.1:8090
```

Разработка с HMR: `make dev` (Vite на 5183 + PHP-сервер на 8090).

## Структура

```
config.php              маршруты, иконки, пресеты картинок, SEO, Vite
public/index.php        точка входа приложения
public/convert-image.php ресайз и конвертация по запросу
app/pages/              маршрут → страница: home, feed, category, tag, source,
                        search, article, page, error
app/components/         layout (main, header, footer, alert, live),
                        news/listing, ui/section-head, ui/breadcrumbs
app/data/               выборки и презентация: queries, present, cards, nav,
                        feeds (RSS, sitemap, robots)
app/support/helpers.php хелперы шаблонов: icon_svg, article_card, fail, …
app/client/             styles.css (Tailwind 4 + @theme), main.ts (Alpine, htmx)
app/icons/              31 SVG-иконка → спрайт
src/Model/Table|Entity  CakePHP ORM
src/Support/            Database (SQLite), AppError, ErrorCodes, Slug
database/               schema.sql, snapshot.json, pages.php, solovey.sqlite
scripts/                все исполняемые сценарии (Makefile только вызывает их)
docs/                   архитектура и развёртывание
```

## Страницы

| URL | Что показывает |
|---|---|
| `/` | главная: лид за сутки, рубрики, свежее, «Важное» |
| `/lenta` `/hot` `/official` `/top` `/video` `/smi` `/tg` `/opinions` `/longread` | ленты |
| `/society` `/incident` `/politics` `/economy` `/sport` `/culture` `/other` | рубрики |
| `/<cat>/<y>/<m>/<d>/<id>.html` | карточка новости |
| `/tema/<slug>` | тема |
| `/istochnik/<slug>` | источник |
| `/poisk?q=…` | поиск |
| `/o-nas` `/kontakty` `/redakciya` `/privacy` `/pravila` | информационные страницы |
| `/rss.xml` `/sitemap.xml` `/robots.txt` | служебные |

## Данные

Выборки живут в `app/data/queries.php` и возвращают готовые массивы, поэтому
шаблоны не знают про сущности:

```php
articles_page(limit: 12, offset: 0, feed: 'hot');   // items + total
feed_loader(categoryId: 3);                          // контракт пагинации Bolt
article_find(886665);                                // карточка или null
articles_related($article, 3);                       // по тегам, иначе по рубрике
live_stream(14);                                     // алерты + свежее
```

Карточка рендерится функцией `article_card($article, 'hero')` с вариантами
`hero`, `default`, `row`, `plain` — один шаблон на все ленты.

## Ошибки

Символьные коды — в `src/Support/ErrorCodes.php`, проверка — `make errors-check`.
Страница получает ошибку так:

```php
$article = article_find($id) ?? fail('article_not_found', ['id' => $id]);
```

`fail()` выставляет HTTP-статус и рендерит `app/pages/error.php`. Клиентский
конверт для API — `{"error":{"code":…,"message":…}}` (`AppError::toArray()`).

## Скрипты

Все — в `scripts/`, запускаются через `make`:

| Цель | Скрипт |
|---|---|
| `make install` | `scripts/install` |
| `make serve` / `make dev` | `scripts/serve`, `scripts/dev` |
| `make build` | `scripts/build` |
| `make db-migrate` / `make db-seed` / `make db-reset` | `scripts/migrate`, `scripts/seed` |
| `make check` | `scripts/check` (+ `errors_check.php`) |
| `make icons` | `bolt icons` через `scripts/php` |
| `make media` | `scripts/optimize_media.php` |
| `make snapshot` | `scripts/fetch_snapshot.py` |

`scripts/php` — обёртка над системным PHP с подключением `pdo_sqlite` и
`sqlite3` из `tools/php-ext/`: в этом окружении они не собраны в бинарь.

## Лицензия и происхождение текстов

Тексты, заголовки и фотографии принадлежат kursk-news.net и указанным в
материалах источникам. Репозиторий — учебный редизайн: контент взят снимком
для демонстрации вёрстки и модели данных.
