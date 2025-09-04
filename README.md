# auto-gpt-blog

Text-only, fast blog that **pulls unique templates + articles** from your feed service.

## What this repo does
- Calls **/template_bundle** to fetch a unique design and writes it into `app/Templates` + `public/assets`.
- Calls **/generate** to fetch articles and stores them to `data/posts/*.json`, keeping an index at `data/posts.json`.
- Serves pages through a small front controller (`public/index.php`).

No build steps, no external fonts, no tracking. Pure PHP + inline CSS.

---

## Quick start

```bash
# 1) Clone
git clone https://github.com/adivvvv/auto-gpt-blog.git
cd auto-gpt-blog

# 2) Configure
cp .env.example .env
# edit .env => FEED_BASE_URL, FEED_API_KEY, BASE_URL
# edit config/settings.php => site_name, shop_url, etc.

# 3) Pull a unique template (choose your seed/flags)
php bin/template-install --lang=en --seed="desert-oliva-01" --flags="airy,serifish,boxed"

# 4) Generate your first article
php bin/article-generate --lang=en

# 5) Run locally (PHP built-in server)
php -S 127.0.0.1:8080 -t public