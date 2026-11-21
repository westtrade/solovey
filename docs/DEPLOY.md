# Развёртывание

Проект — обычный PHP-сайт: точка входа `public/index.php`, картинки —
`public/convert-image.php`, статика — `public/`. Никаких очередей и демонов,
база — файл SQLite.

## Требования

| Что | Версия | Зачем |
|---|---|---|
| PHP | 8.3+ | ядро Bolt, CakePHP ORM 5 |
| Расширения | `pdo_sqlite`, `gd`, `mbstring`, `json` | база, ресайз картинок |
| Node.js | 20+ | только сборка ассетов, на сервере не нужен |
| Веб-сервер | nginx или Apache | отдаёт статику, остальное — в PHP |

`pdo_sqlite` обязателен: без него `Database::locator()` упадёт с
`database_schema_missing`.

## Сборка

```bash
make install     # composer install + npm install
make db-reset    # схема + данные
make build       # public/build: CSS и JS
make check       # синтаксис, маршруты, иконки, коды ошибок
```

## nginx

```nginx
server {
    listen 80;
    server_name solovey.example;
    root /srv/solovey/public;
    index index.php;

    # Картинки по запросу: путь берётся из REQUEST_URI, query — параметры.
    location ^~ /convert-image/ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/convert-image.php;
        fastcgi_param SCRIPT_NAME /convert-image.php;
        fastcgi_param REQUEST_URI $request_uri;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    # Кэш конвертера неизменяем: имя файла содержит хеш параметров.
    location ^~ /assets/cache/ {
        add_header Cache-Control "public, max-age=31536000, immutable";
    }

    location ^~ /build/ {
        add_header Cache-Control "public, max-age=31536000, immutable";
    }
}
```

## Apache

В `public/.htaccess`:

```apache
RewriteEngine On

# только растровые расширения, svg/php/html → 403
RewriteCond %{REQUEST_URI} !\.(jpe?g|png|gif|webp|avif)$ [NC]
RewriteRule ^convert-image/ - [F,L]

RewriteRule ^convert-image/ /convert-image.php [L,QSA]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

## Права

Запись нужна только трём каталогам:

```
var/               сессии и спрайт иконок
public/assets/cache/   кэш конвертера
database/          файл SQLite и WAL
```

`public/media/` и `public/build/` — только чтение.

## Обновление данных

```bash
make snapshot    # снять свежий снимок kursk-news.net
make media       # уменьшить новые исходники картинок
make db-reset    # пересобрать базу
make build       # пересобрать ассеты, если менялись шаблоны/стили
```

## Переменные окружения

| Переменная | По умолчанию | Что делает |
|---|---|---|
| `APP_URL` | — | абсолютные ссылки в RSS и sitemap |
| `PORT` | `8090` | порт `make serve` |
| `VITE_PORT` | `5183` | порт dev-сервера Vite |
