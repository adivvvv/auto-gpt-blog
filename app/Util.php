<?php declare(strict_types=1);
// app/Util.php
// Utility functions for config, slugs, index management.

namespace App;

final class Util
{
    public static function env(string $name, ?string $default = null): string
    {
        $v = getenv($name);
        return ($v === false || $v === null) ? ($default ?? '') : $v;
    }

    /** Merge config/settings.php with .env overrides. */
    public static function loadConfig(): array
    {
        $cfg = require __DIR__ . '/../config/settings.php';
        if ($b = self::env('BASE_URL'))       $cfg['base_url']      = rtrim($b, '/');
        if ($k = self::env('FEED_API_KEY'))   $cfg['feed_api_key']  = $k;
        if ($u = self::env('FEED_BASE_URL'))  $cfg['feed_base_url'] = rtrim($u, '/');
        if ($l = self::env('BLOG_LANG'))      $cfg['lang']          = $l;
        return $cfg;
    }

    public static function slugify(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/i', '-', $s) ?: '';
        $s = trim($s, '-');
        return $s !== '' ? $s : 'post';
    }

    /** Ensure slug is unique in data/posts.json; append -2/-3 if needed. */
    public static function uniqueSlug(string $slug, array $index): string
    {
        $exists = [];
        foreach (($index['posts'] ?? []) as $p) {
            if (!empty($p['slug'])) $exists[$p['slug']] = true;
        }
        if (!isset($exists[$slug])) return $slug;
        $n = 2;
        while (isset($exists[$slug . '-' . $n])) $n++;
        return $slug . '-' . $n;
    }

    public static function postsIndexPath(): string { return __DIR__ . '/../data/posts.json'; }
    public static function postsDir(): string       { return __DIR__ . '/../data/posts'; }

    public static function loadIndex(): array
    {
        $f = self::postsIndexPath();
        if (!is_file($f)) return ['posts'=>[]];
        $j = json_decode((string)file_get_contents($f), true);
        return is_array($j) ? $j : ['posts'=>[]];
    }

    public static function saveIndex(array $idx): void
    {
        @mkdir(dirname(self::postsIndexPath()), 0775, true);
        file_put_contents(self::postsIndexPath(), json_encode($idx, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    public static function savePost(string $slug, array $post): void
    {
        @mkdir(self::postsDir(), 0775, true);
        $path = self::postsDir() . '/' . $slug . '.json';
        file_put_contents($path, json_encode($post, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    public static function nowIso(): string { return gmdate('Y-m-d'); }
}