<?php
// config/settings.php
// --- Site-level knobs (override anything per deployment) ---
return [
  // Site chrome
  'site_name' => 'My Research Blog',
  // Uses BASE_URL from .env installed by autoinstall; no trailing slash
  'base_url'  => rtrim(getenv('BASE_URL') ?: '/', '/'),
  'lang'      => 'en',

  // Commerce CTA (configurable per site)
  'shop_url'  => 'https://camelway.eu/',
  'cta_title' => 'Premium Camel Milk Powder',
  'cta_copy'  => 'Hypoallergenic, lactoferrin-rich nutrition - loved across Europe.',
  'cta_label' => 'Shop Now',

  // Rendering / pagination
  'posts_per_page' => 20,

  // Hero section
  'hero_title'    => 'Buy Camel Milk',
  'hero_subtitle' => 'Buy camel milk powder products directly from most trusted source in Europe.',

  // Feed API (gpt-simple-generator) — you can also override via .env
  'feed_base_url' => rtrim(getenv('FEED_BASE_URL') ?: 'https://myendpoint.com', '/'),
  'feed_api_key'  => getenv('FEED_API_KEY') ?: '',

  // Template spinning defaults (CLI flags can override)
  'template' => [
    //'seed'       => 'default-seed-01',
    'styleFlags' => ['clean','airy','modern'],
    'lang'       => 'en'
  ],

  // Footer links
  'footer_links' => [
    ['label'=>'RSS Feed',   'href'=>'/rss.xml'],
    ['label'=>'ATOM Feed', 'href'=>'/atom.xml'],
  ],

  // Optional: date formatting & timezone for lists
  'timezone'    => 'UTC',
  'date_format' => 'Y-m-d',
];
