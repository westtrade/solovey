#!/usr/bin/env python3
"""Снять контент kursk-news.net в JSON + локальные картинки.

Запуск: python3 scripts/fetch_snapshot.py [кол-во статей]
Результат: .scratch/snapshot.json, public/media/<id>/*.jpg
"""
import json
import os
import re
import sys
import html as htmllib
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from urllib.parse import urljoin, urlparse
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parent.parent
SCRATCH = ROOT / ".scratch"
MEDIA = ROOT / "public" / "media"
BASE = "https://kursk-news.net"
UA = {"User-Agent": "Mozilla/5.0 (compatible; SoloveyClone/1.0)"}
LIMIT = int(sys.argv[1]) if len(sys.argv) > 1 else 200

CATS = {
    "society": "Общество",
    "incident": "Происшествия",
    "politics": "Политика",
    "economy": "Экономика",
    "sport": "Спорт",
    "culture": "Культура",
    "other": "Разное",
}
FEED_TITLES = {
    "all": "Лента",
    "hot": "Важно",
    "official": "Официально",
    "top": "Топ",
    "video": "Видео",
    "smi": "СМИ",
    "tg": "Паблики",
    "opinions": "Мнения",
    "longread": "Лонгриды",
}


def fetch(url, binary=False):
    for _ in range(3):
        try:
            with urlopen(Request(url, headers=UA), timeout=30) as r:
                data = r.read()
                return data if binary else data.decode("cp1251", "replace")
        except Exception as exc:  # noqa: BLE001
            last = exc
    raise RuntimeError(f"fail {url}: {last}")


def strip_tags(s):
    s = re.sub(r"<br\s*/?>", " ", s)
    s = re.sub(r"<[^>]+>", "", s)
    return re.sub(r"\s+", " ", htmllib.unescape(s)).strip()


def save_image(url, dest):
    if dest.exists() and dest.stat().st_size > 0:
        return True
    try:
        data = fetch(url, binary=True)
    except Exception:  # noqa: BLE001
        return False
    if len(data) < 1200:
        return False
    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_bytes(data)
    return True


IMG_TAG = re.compile(r"<picture\b.*?</picture>|<img\b[^>]*>", re.S | re.I)


def localize(block, art_id, index, alt):
    m = re.search(r'<img[^>]*\bsrc="([^"]+)"', block)
    if not m:
        return None
    src = urljoin(BASE, m.group(1))
    ext = os.path.splitext(urlparse(src).path)[1].lower() or ".jpg"
    name = f"{index:02d}{ext}"
    if not save_image(src, MEDIA / str(art_id) / name):
        return None
    return name


def clean_body(body, art_id, title):
    """Оставить p/em/strong/blockquote/ul/li/h2/h3 и картинки → /media/..."""
    body = re.sub(r"<script\b.*?</script>|<style\b.*?</style>", "", body, flags=re.S | re.I)
    out = []
    images = []
    pos = 0
    for m in IMG_TAG.finditer(body):
        out.append(body[pos:m.start()])
        name = localize(m.group(0), art_id, len(images) + 1, title)
        if name:
            images.append(name)
            out.append(f'<figure class="news-figure"><img src="/media/{art_id}/{name}" alt="{htmllib.escape(title)}" loading="lazy"></figure>')
        pos = m.end()
    out.append(body[pos:])
    body = "".join(out)

    # оставить только безопасные теги
    body = re.sub(r"<(?!p\b|/p\b|em\b|/em\b|strong\b|/strong\b|b\b|/b\b|i\b|/i\b|blockquote\b|/blockquote\b|ul\b|/ul\b|ol\b|/ol\b|li\b|/li\b|h2\b|/h2\b|h3\b|/h3\b|figure\b|/figure\b|img\b|br\s*/?)[^>]+>", "", body)
    body = re.sub(r"\s(?:style|class|id|width|height|itemprop|pubdate|target|rel|srcset|sizes|fetchpriority|decoding|loading)=\"[^\"]*\"", "", body)
    body = re.sub(r"<img\b(?![^>]*class=\"news-figure-img\")", '<img', body)
    body = re.sub(r"<(p|li)>\s*</\1>", "", body)
    body = re.sub(r"&nbsp;", " ", body)
    body = re.sub(r"\n{2,}", "\n", body)
    return body.strip(), images


def parse_article(url):
    try:
        page = fetch(url)
    except Exception:  # noqa: BLE001
        return None
    art_id = int(re.search(r"/(\d+)\.html$", url).group(1))
    cat = urlparse(url).path.strip("/").split("/")[0]

    h1 = re.search(r'<h1 class="title"[^>]*>(.*?)</h1>', page, re.S)
    if not h1:
        return None
    title = strip_tags(h1.group(1))

    tm = re.search(r'<time datetime="([^"]+)"[^>]*>(.*?)</time>', page, re.S)
    iso, human = (tm.group(1), strip_tags(tm.group(2))) if tm else ("", "")

    bc = re.search(r'<div class="breadcrumbs">(.*?)</div>', page, re.S)
    feed = ""
    if bc:
        a = re.search(r'href="[^"]*/([a-z]+)"', bc.group(1))
        feed = a.group(1) if a else ""

    src = re.search(r'class="llink source-link"[^>]*>(.*?)</span>', page, re.S)
    source = strip_tags(src.group(1)) if src else ""

    body = ""
    m = re.search(r'<div itemprop="articleBody">(.*?)</div>\s*</div>', page, re.S)
    if m:
        body = m.group(1)
    else:
        m = re.search(r'<div class="news_c">(.*?)<div class="source news-author"', page, re.S)
        body = m.group(1) if m else ""

    body, images = clean_body(body, art_id, title)
    text = strip_tags(body)
    lead = text[:320].rsplit(" ", 1)[0] + "…" if len(text) > 320 else text

    return {
        "id": art_id,
        "url": url,
        "category": cat,
        "feed": feed,
        "title": title,
        "published_at": iso,
        "display_date": human,
        "source": source,
        "body": body,
        "text": text,
        "lead": lead,
        "images": [f"/media/{art_id}/{n}" for n in images],
        "cover": f"/media/{art_id}/{images[0]}" if images else "",
    }


def parse_feed_rows(url, feed_key):
    """Строки правой колонки «Лента новостей» и списков карточек."""
    page = fetch(url)
    rows = []
    for block in re.findall(r'<li class="list-item">(.*?)</li>', page, re.S):
        tm = re.search(r'<time datetime="([^"]+)"[^>]*>(.*?)</time>', block, re.S)
        if not tm:
            continue
        link = re.search(r'<a href="(https://kursk-news\.net/[^"]+)"', block)
        title = re.search(r'<div class="mes-info">.*?<a[^>]*>(.*?)</a>', block, re.S)
        if not title:
            title = re.search(r'<div class="post_title">.*?<a[^>]*>(.*?)</a>', block, re.S)
        inline = re.search(r'class="inline-mes2"[^>]*>(.*?)</div>', block, re.S)
        src = re.search(r'class="llink source-link"[^>]*>(.*?)</span>', block, re.S)
        rows.append({
            "feed": feed_key,
            "published_at": tm.group(1),
            "title": strip_tags(title.group(1)) if title else strip_tags(inline.group(1)) if inline else "",
            "url": link.group(1) if link else "",
            "inline": bool(inline),
            "source": strip_tags(src.group(1)) if src else "",
        })
    return rows


def main():
    SCRATCH.mkdir(exist_ok=True)
    rss = fetch(f"{BASE}/rss.xml")
    urls = [u.strip() for u in re.findall(r"<link>(https://kursk-news\.net/[^<]+)</link>", rss)][:LIMIT]
    print("rss:", len(urls))

    with ThreadPoolExecutor(max_workers=8) as pool:
        articles = [a for a in pool.map(parse_article, urls) if a]
    print("articles:", len(articles))

    feed_rows = []
    for key in ["all", "hot", "official", "top", "video", "smi", "tg", "opinions", "longread"]:
        try:
            rows = parse_feed_rows(f"{BASE}/{key}", key)
            feed_rows += rows
            print("feed", key, len(rows))
        except Exception as exc:  # noqa: BLE001
            print("feed fail", key, exc)

    for cat in CATS:
        try:
            rows = parse_feed_rows(f"{BASE}/{cat}", "all")
            feed_rows += rows
            print("cat", cat, len(rows))
        except Exception as exc:  # noqa: BLE001
            print("cat fail", cat, exc)

    payload = {
        "categories": CATS,
        "feeds": FEED_TITLES,
        "articles": articles,
        "feed_rows": feed_rows,
    }
    out = SCRATCH / "snapshot.json"
    out.write_text(json.dumps(payload, ensure_ascii=False, indent=1), encoding="utf-8")
    print("wrote", out, out.stat().st_size // 1024, "KB")


if __name__ == "__main__":
    main()
