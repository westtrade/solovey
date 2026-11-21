-- Схема «Соловья». Применяется scripts/migrate: DROP + CREATE, потому что
-- база демонстрационная и восстанавливается из снимка kursk-news.net.

DROP TABLE IF EXISTS article_tags;
DROP TABLE IF EXISTS feed_items;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS sources;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS pages;

CREATE TABLE categories (
	id        INTEGER PRIMARY KEY AUTOINCREMENT,
	slug      TEXT NOT NULL UNIQUE,
	title     TEXT NOT NULL,
	lead      TEXT NOT NULL DEFAULT '',
	accent    TEXT NOT NULL DEFAULT 'slate',
	sort      INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE sources (
	id    INTEGER PRIMARY KEY AUTOINCREMENT,
	slug  TEXT NOT NULL UNIQUE,
	title TEXT NOT NULL,
	kind  TEXT NOT NULL DEFAULT 'media'
);

CREATE TABLE articles (
	id           INTEGER PRIMARY KEY,
	category_id  INTEGER NOT NULL REFERENCES categories (id),
	source_id    INTEGER REFERENCES sources (id),
	title        TEXT NOT NULL,
	lead         TEXT NOT NULL DEFAULT '',
	body         TEXT NOT NULL DEFAULT '',
	cover        TEXT NOT NULL DEFAULT '',
	published_at TEXT NOT NULL,
	views        INTEGER NOT NULL DEFAULT 0,
	reading_time INTEGER NOT NULL DEFAULT 1,
	is_top       INTEGER NOT NULL DEFAULT 0,
	is_important INTEGER NOT NULL DEFAULT 0,
	is_video     INTEGER NOT NULL DEFAULT 0,
	is_longread  INTEGER NOT NULL DEFAULT 0,
	is_opinion   INTEGER NOT NULL DEFAULT 0,
	feed_key     TEXT NOT NULL DEFAULT 'all'
);

CREATE INDEX articles_published ON articles (published_at DESC);
CREATE INDEX articles_category ON articles (category_id, published_at DESC);
CREATE INDEX articles_feed ON articles (feed_key, published_at DESC);
CREATE INDEX articles_top ON articles (is_top, published_at DESC);

CREATE TABLE tags (
	id    INTEGER PRIMARY KEY AUTOINCREMENT,
	slug  TEXT NOT NULL UNIQUE,
	title TEXT NOT NULL
);

CREATE TABLE article_tags (
	article_id INTEGER NOT NULL REFERENCES articles (id) ON DELETE CASCADE,
	tag_id     INTEGER NOT NULL REFERENCES tags (id) ON DELETE CASCADE,
	PRIMARY KEY (article_id, tag_id)
);

CREATE INDEX article_tags_tag ON article_tags (tag_id, article_id);

-- Лента «Сейчас в Курске»: оперативные сообщения и посты пабликов живут
-- отдельной сущностью, а не абзацем внутри новости.
CREATE TABLE feed_items (
	id           INTEGER PRIMARY KEY AUTOINCREMENT,
	kind         TEXT NOT NULL DEFAULT 'news',
	title        TEXT NOT NULL DEFAULT '',
	body         TEXT NOT NULL DEFAULT '',
	source_id    INTEGER REFERENCES sources (id),
	article_id   INTEGER REFERENCES articles (id),
	published_at TEXT NOT NULL,
	pinned       INTEGER NOT NULL DEFAULT 0,
	severity     TEXT NOT NULL DEFAULT 'normal'
);

CREATE INDEX feed_items_published ON feed_items (published_at DESC);
CREATE INDEX feed_items_kind ON feed_items (kind, published_at DESC);

CREATE TABLE pages (
	slug       TEXT PRIMARY KEY,
	title      TEXT NOT NULL,
	lead       TEXT NOT NULL DEFAULT '',
	body       TEXT NOT NULL DEFAULT '',
	updated_at TEXT NOT NULL DEFAULT ''
);
