<?php
// src/Services/FeedBuilder.php
declare(strict_types=1);

namespace App\Services;

final class FeedBuilder
{
    private string $root;
    private array $config;
    private array $env;

    public function __construct(array $config = [])
    {
        $this->root   = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
        $this->config = $config;
        // Try to read config/settings.php if nothing was passed in
        if (!$this->config) {
            $settings = $this->root . '/config/settings.php';
            if (is_file($settings)) {
                $cfg = require $settings;
                if (is_array($cfg)) $this->config = $cfg;
            }
        }
        // Parse .env for fallbacks
        $this->env = [];
        $envPath = $this->root . '/.env';
        if (is_file($envPath)) {
            $parsed = parse_ini_file($envPath, false, INI_SCANNER_RAW);
            if (is_array($parsed)) $this->env = $parsed;
        }
    }

    public function buildAll(): array
    {
        $posts  = $this->loadPosts();
        $latest = array_slice($posts, 0, 20);

        $rss  = $this->buildRss($latest);
        $atom = $this->buildAtom($latest);

        $rssPath  = $this->root . '/public/rss.xml';
        $atomPath = $this->root . '/public/atom.xml';

        if (!is_dir(dirname($rssPath)))  mkdir(dirname($rssPath), 0775, true);
        if (!is_dir(dirname($atomPath))) mkdir(dirname($atomPath), 0775, true);

        file_put_contents($rssPath,  $rss,  LOCK_EX);
        file_put_contents($atomPath, $atom, LOCK_EX);

        return ['rss' => $rssPath, 'atom' => $atomPath, 'count' => count($latest)];
    }

    /** ===== Helpers: config & env ===== */

    private function siteTitle(): string
    {
        // Prefer settings.php, else .env (SITE_NAME), else generic
        $t = (string)($this->config['site_name'] ?? '');
        if ($t !== '') return $t;
        foreach (['SITE_NAME','SITE_TITLE'] as $k) {
            $v = (string)($this->env[$k] ?? '');
            if ($v !== '') return $v;
        }
        return 'Site';
    }

    private function siteUrl(): string
    {
        // Prefer settings.php base_url, then common .env keys
        $b = (string)($this->config['base_url'] ?? '');
        if ($b === '') {
            foreach (['BASE_URL','APP_URL','SITE_URL'] as $k) {
                $v = (string)($this->env[$k] ?? '');
                if ($v !== '') { $b = $v; break; }
            }
        }
        $b = rtrim($b, '/');
        return $b !== '' ? $b : '';
    }

    private function siteLang(): string
    {
        $l = (string)($this->config['lang'] ?? '');
        if ($l !== '') return $l;
        $v = (string)($this->env['LANG'] ?? '');
        return $v !== '' ? $v : 'en';
    }

    /** ===== Data loading ===== */

    private function loadPosts(): array
    {
        $idx = $this->root . '/data/posts.json';
        $posts = [];
        if (is_file($idx)) {
            $json = json_decode((string)file_get_contents($idx), true);
            $posts = (array)($json['posts'] ?? []);
        } else {
            $dir = $this->root . '/data/posts';
            if (is_dir($dir)) {
                foreach (glob($dir.'/*.json') as $f) {
                    $j = json_decode((string)file_get_contents($f), true);
                    if (is_array($j)) $posts[] = $j;
                }
            }
        }
        // newest first by published_at (YYYY-MM-DD)
        usort($posts, function($a, $b) {
            return strcmp((string)($b['published_at'] ?? ''), (string)($a['published_at'] ?? ''));
        });
        return $posts;
    }

    /** ===== Date formatting ===== */

    private function rfc822(string $ymd): string
    {
        $ts = strtotime($ymd . ' 00:00:00 UTC') ?: time();
        return gmdate('D, d M Y H:i:s', $ts) . ' GMT';
    }

    private function iso8601(string $ymd): string
    {
        $ts = strtotime($ymd . ' 00:00:00 UTC') ?: time();
        return gmdate('c', $ts);
    }

    /** ===== Feed builders ===== */

    private function buildRss(array $posts): string
    {
        $title = $this->siteTitle();
        $link  = $this->siteUrl() ?: '/';
        $desc  = (string)($this->config['feed_description'] ?? 'Evidence-first articles about camel milk.');
        $lang  = $this->siteLang();

        $lastBuild = gmdate('D, d M Y H:i:s') . ' GMT'; // RSS 2.0 compatible date. :contentReference[oaicite:1]{index=1}

        $items = '';
        foreach ($posts as $p) {
            $ptitle = htmlspecialchars((string)($p['title'] ?? 'Article'), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $slug   = (string)($p['slug'] ?? '');
            $plink  = htmlspecialchars(($link !== '' ? $link : '') . '/' . rawurlencode($slug), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $pdate  = $this->rfc822((string)($p['published_at'] ?? gmdate('Y-m-d')));
            $guid   = $plink;
            $psumm  = htmlspecialchars((string)($p['summary'] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $items .= <<<XML
    <item>
      <title>{$ptitle}</title>
      <link>{$plink}</link>
      <guid isPermaLink="true">{$guid}</guid>
      <pubDate>{$pdate}</pubDate>
      <description>{$psumm}</description>
    </item>

XML;
        }

        // IMPORTANT: generator is now the public site name (no engine branding)
        $generator = $this->escapeXml($title);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>{$this->escapeXml($title)}</title>
    <link>{$this->escapeXml($link)}</link>
    <description>{$this->escapeXml($desc)}</description>
    <language>{$this->escapeXml($lang)}</language>
    <lastBuildDate>{$lastBuild}</lastBuildDate>
    <generator>{$generator}</generator>
{$items}  </channel>
</rss>
XML;
    }

    private function buildAtom(array $posts): string
    {
        $title = $this->siteTitle();
        $home  = $this->siteUrl() ?: '/';
        $self  = ($home !== '' ? $home : '') . '/atom.xml';
        $updated = gmdate('c'); // Atom 1.0 ISO 8601 datetime. :contentReference[oaicite:2]{index=2}

        $entries = '';
        foreach ($posts as $p) {
            $ptitle = $this->escapeXml((string)($p['title'] ?? 'Article'));
            $slug   = (string)($p['slug'] ?? '');
            $plink  = ($home !== '' ? $home : '') . '/' . rawurlencode($slug);
            $id     = $plink;
            $pupd   = $this->iso8601((string)($p['published_at'] ?? gmdate('Y-m-d')));
            $psumm  = $this->escapeXml((string)($p['summary'] ?? ''));

            $entries .= <<<XML
  <entry>
    <title>{$ptitle}</title>
    <link href="{$this->escapeXml($plink)}" rel="alternate"/>
    <id>{$this->escapeXml($id)}</id>
    <updated>{$pupd}</updated>
    <summary type="html">{$psumm}</summary>
  </entry>

XML;
        }

        // Atom <generator> value is free-text; we omit the "uri" to avoid leaking internals. :contentReference[oaicite:3]{index=3}
        $genText = $this->escapeXml($title);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>{$this->escapeXml($title)}</title>
  <link href="{$this->escapeXml($home)}" rel="alternate"/>
  <link href="{$this->escapeXml($self)}" rel="self"/>
  <updated>{$updated}</updated>
  <id>{$this->escapeXml($home)}</id>
  <generator>{$genText}</generator>
{$entries}</feed>
XML;
    }

    private function escapeXml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
