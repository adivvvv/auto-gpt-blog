<?php
// public/bootstrap.php
// shared bootstrap for CLI + web
// Optional composer autoload; then a tiny PSR-0-ish autoloader for /app classes.

$composer = __DIR__ . '/../vendor/autoload.php';
if (is_file($composer)) require $composer;

// map App\* => /app/*
spl_autoload_register(static function(string $class): void {
  if (str_starts_with($class, 'App\\')) {
    $rel = substr($class, 4);
    $f = __DIR__ . '/../app/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($f)) require $f;
  }
});

// Simple .env loader (KEY=VALUE per line)
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
  foreach (file($envFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    [$k, $v] = array_map('trim', array_pad(explode('=', $line, 2), 2, ''));
    if ($k !== '') putenv($k . '=' . $v);
  }
}
