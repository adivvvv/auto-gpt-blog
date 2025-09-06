<?php require __DIR__.'/partial-icons.php'; require __DIR__.'/partial-header.php';
// app/Templates/article.php
// Simple article template (used by Router).
/** @var array $post loaded by Router */
$title   = $post['title']   ?? 'Article';
$summary = $post['summary'] ?? '';
$body    = $post['body']    ?? ''; // prefer pre-rendered HTML
$md      = $post['body_markdown'] ?? '';
$tags    = $post['tags']    ?? [];
$pmids   = $post['pmids']   ?? [];
$faqs    = $post['faqs'] ?? ($post['faq'] ?? []);
$escape  = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');

// Minimal Markdown → HTML if needed
if ($body === '' && $md !== '') {
  $h = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $md);
  $parts = preg_split("/\n{2,}/", $h);
  $parts = array_map(fn($p) => (str_starts_with(trim($p), '<h2>') ? $p : '<p>'.$escape(trim($p)).'</p>'), $parts);
  $body = implode("\n", $parts);
}
?>
<!doctype html>
<html lang="<?=htmlspecialchars($config['lang'] ?? 'en')?>">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=htmlspecialchars($title)?> — <?=htmlspecialchars($config['site_name'] ?? 'CamelWay')?></title>
  <meta name="description" content="<?=htmlspecialchars($summary)?>">
  <link rel="canonical" href="<?=htmlspecialchars(($config['base_url'] ?? '/').'/'.($post['slug'] ?? ''))?>">
  <link rel="stylesheet" href="/assets/tailwind.css">
</head>
<body class="<?=$prefix?>-body">
  <main class="<?=$prefix?>-container">
    <?php require __DIR__.'/partial-cta.php'; ?>

    <article class="<?=$prefix?>-article">
      <header class="<?=$prefix?>-article-header">
        <h1 class="<?=$prefix?>-article-title"><?=htmlspecialchars($title)?></h1>
        <?php if ($summary): ?><p class="<?=$prefix?>-article-summary"><?=htmlspecialchars($summary)?></p><?php endif; ?>
        <?php if (!empty($tags)): ?>
          <p class="<?=$prefix?>-article-tags">
            <?php foreach ($tags as $t): ?><span class="<?=$prefix?>-tag"><?=(function_exists('icon') ? icon('tag') : '#')?> <?=htmlspecialchars($t)?></span><?php endforeach; ?>
          </p>
        <?php endif; ?>
      </header>

      <div class="<?=$prefix?>-prose"><?=$body?></div>

      <?php if (!empty($faqs) && is_array($faqs)): ?>
      <section class="<?=$prefix?>-section">
        <h2 class="<?=$prefix?>-section-title">FAQ</h2>
        <div class="<?=$prefix?>-prose">
          <?php foreach ($faqs as $f): 
            $q = $f['question'] ?? '';
            $a = $f['answer'] ?? '';
            if ($q === '' || $a === '') continue; ?>
            <details style="margin: .5rem 0">
              <summary><strong><?=$escape($q)?></strong></summary>
              <p><?=$escape($a)?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php if (!empty($pmids) && is_array($pmids)): ?>
      <section class="<?=$prefix?>-refs">
        <h2 class="<?=$prefix?>-section-title">Referenced studies</h2>
        <ol class="<?=$prefix?>-refs-list">
          <?php foreach ($pmids as $pm): ?>
            <li><a href="<?= 'https://pubmed.ncbi.nlm.nih.gov/'.urlencode((string)$pm).'/' ?>">PMID: <?=htmlspecialchars((string)$pm)?></a></li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endif; ?>

      <p class="<?=$prefix?>-disclaimer">Educational content. Not medical advice.</p>
    </article>

    <?php require __DIR__.'/partial-cta.php'; ?>
  </main>
  <?php require __DIR__.'/partial-footer.php'; ?>
</body>
</html>
