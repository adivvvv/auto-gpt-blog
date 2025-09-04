<?php declare(strict_types=1);
// public/index.php
// Simple blog front controller (router).

use App\Util;

require __DIR__ . '/bootstrap.php';

$cfg  = Util::loadConfig();
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri  = '/' . ltrim($uri, '/');
$base = $cfg['base_url'] ?? '';

if ($base && str_starts_with($uri, $base)) {
  $uri = '/' . ltrim(substr($uri, strlen($base)), '/');
}

// Discover template files (installed by template-install)
$tplDir  = __DIR__ . '/../app/Templates';
$home    = $tplDir . '/home.php';
$article = $tplDir . '/article.php';
$css     = __DIR__ . '/assets/tailwind.css';

if (!is_file($home) || !is_file($article) || !is_file($css)) {
  http_response_code(500);
  echo "<h1>Template not installed</h1><p>Run <code>php bin/template-install</code> first.</p>";
  exit;
}

// HOME
if ($uri === '/' || $uri === '') {
  $config = $cfg; // expose to template
  include $home;
  exit;
}

// ARTICLE (slug path)
$slug = trim($uri, '/');
$postFile = __DIR__ . '/../data/posts/' . $slug . '.json';
if (is_file($postFile)) {
  $post   = json_decode((string)file_get_contents($postFile), true) ?: [];
  $config = $cfg;
  include $article;
  exit;
}

// NOT FOUND
http_response_code(404);
echo "<h1>Not Found</h1><p>No article at: " . htmlspecialchars($slug) . "</p>";
