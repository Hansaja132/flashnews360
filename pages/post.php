<?php
require __DIR__ . '/../db.php';

$postId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($postId <= 0) {
  http_response_code(400);
  exit('Invalid post id.');
}

$stmt = $conn->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
  http_response_code(404);
  exit('Post not found.');
}

$metaTags = $post['meta_tags'] ?? '';
$title = $post['title'] ?? '';
$summary = $post['summary'] ?? '';
$description = $post['description'] ?? '';
$imageUrl = $post['image_url'] ?? '';
$pubDate = $post['pub_date'] ?? '';
$sourceLink = $post['source_link'] ?? '';
$sourceName = $post['source_name'] ?? '';
$pageUrl = $post['page_url'] ?? '';

$recentStmt = $conn->prepare(
  'SELECT id, title, image_url, pub_date '
  . 'FROM posts '
  . 'WHERE id <> ? '
  . 'ORDER BY pub_date DESC NULLS LAST, id DESC '
  . 'LIMIT 5'
);
$recentStmt->execute([$postId]);
$recentPosts = $recentStmt->fetchAll();

$monthsStmt = $conn->query(
  "SELECT to_char(pub_date, 'YYYY-MM') AS month_key, "
  . "to_char(pub_date, 'FMMonth YYYY') AS month_label, "
  . "COUNT(*) AS total "
  . "FROM posts "
  . "WHERE pub_date IS NOT NULL "
  . "GROUP BY month_key, month_label "
  . "ORDER BY month_key DESC"
);
$months = $monthsStmt->fetchAll();

$tagsStmt = $conn->query('SELECT meta_tags FROM posts WHERE meta_tags IS NOT NULL ORDER BY id DESC LIMIT 200');
$tagRows = $tagsStmt->fetchAll();

function escape_html(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function escape_url(string $value): string
{
  $value = trim($value);
  if ($value === '') {
    return '';
  }

  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$metaTagsSafe = strip_tags($metaTags, '<meta><link>');

function extract_meta_tag_values(string $raw): array
{
  $tags = [];
  $raw = trim($raw);
  if ($raw === '') {
    return $tags;
  }

  if (stripos($raw, '<meta') !== false) {
    if (preg_match_all('/<meta\s+[^>]*>/i', $raw, $metaMatches)) {
      foreach ($metaMatches[0] as $metaTag) {
        $name = '';
        $content = '';
        if (preg_match('/\bname\s*=\s*["\']([^"\']+)["\']/i', $metaTag, $nameMatch)) {
          $name = strtolower(trim($nameMatch[1]));
        }
        if (preg_match('/\bcontent\s*=\s*["\']([^"\']+)["\']/i', $metaTag, $contentMatch)) {
          $content = trim($contentMatch[1]);
        }

        if ($content === '') {
          continue;
        }

        if (in_array($name, ['keywords', 'news_keywords', 'tags'], true)) {
          $tags = array_merge($tags, preg_split('/\s*[,;]\s*/', $content) ?: []);
        }
      }
    }
  }

  if (!$tags) {
    $tags = preg_split('/\s*[,;]\s*/', strip_tags($raw)) ?: [];
  }

  return $tags;
}

function normalize_tags(array $tags): array
{
  $unique = [];
  $ordered = [];
  foreach ($tags as $tag) {
    $tag = trim($tag);
    if ($tag === '') {
      continue;
    }
    $tag = preg_replace('/\s+/', ' ', $tag);
    if ($tag === null || $tag === '') {
      continue;
    }
    if (strlen($tag) > 40) {
      $tag = substr($tag, 0, 40) . '...';
    }
    $key = strtolower($tag);
    if (!isset($unique[$key])) {
      $unique[$key] = true;
      $ordered[] = $tag;
    }
  }

  sort($ordered, SORT_NATURAL | SORT_FLAG_CASE);

  return $ordered;
}

function extract_tags(array $posts): array
{
  $allTags = [];
  foreach ($posts as $post) {
    $raw = (string) ($post['meta_tags'] ?? '');
    if ($raw === '') {
      continue;
    }

    $allTags = array_merge($allTags, extract_meta_tag_values($raw));
  }

  return normalize_tags($allTags);
}

$tags = extract_tags($tagRows);
$tagLimit = 10;
$visibleTags = array_slice($tags, 0, $tagLimit);
$hiddenTags = array_slice($tags, $tagLimit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php echo $metaTagsSafe; ?>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo escape_html($title); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="../styles/blog.css" />
</head>
<script>(function (s) { s.dataset.zone = '10797249', s.src = 'https://izcle.com/vignette.min.js' })([document.documentElement, document.body].filter(Boolean).pop().appendChild(document.createElement('script')))</script>

<body>

  <header class="top-bar">
    <div class="container">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true"></div>
        <h1>FlashNews360</h1>
      </div>
      <div class="top-actions">
        <a class="post-back" href="../index.php">Back to home</a>
      </div>
    </div>
  </header>

  <main class="post-page">
    <article class="news-post">
      <header>
        <h1><?php echo escape_html($title); ?></h1>
        <div class="post-meta">
          <span><strong>Published:</strong> <?php echo escape_html($pubDate); ?></span>
          <span>
            <strong>Source:</strong>
            <a href="<?php echo escape_url($sourceLink); ?>" target="_blank"
              rel="noopener noreferrer"><?php echo escape_html($sourceName); ?></a>
          </span>
        </div>
      </header>


      <figure class="post-figure">
        <img src="<?php echo escape_url($imageUrl); ?>" alt="<?php echo escape_html($title); ?>" />
      </figure>


      <section class="post-section">
        <h2>Summary</h2>
        <p><?php echo escape_html($summary); ?></p>
      </section>

      <section class="post-section">
        <h2>Details</h2>
        <p><?php echo escape_html($description); ?></p>
      </section>

      <section class="post-section source-note">
        <p>
          This article was created from publicly available news feed data and organized for easier reading.
          Original source: <a href="<?php echo escape_url($pageUrl); ?>" target="_blank"
            rel="nofollow noopener noreferrer">Read full article</a>.
        </p>
      </section>

      <!-- Ad Space: Top Banner -->
      <div class="ad-space ad-top">
        <!-- Insert ad code here -->
      </div>

      <!-- Ad Space: In-article -->
      <div class="ad-space ad-middle">
        <!-- Insert ad code here -->
      </div>

      <!-- Ad Space: Bottom Banner -->
      <div class="ad-space ad-bottom">
        <!-- Insert ad code here -->
      </div>
      <a href="https://omg10.com/4/10797248" class="btn-primary" target="_blank" rel="noopener noreferrer">
        Click Here
      </a>
    </article>

    <aside class="sidebar">
      <div class="widget">
        <h3>Recent</h3>
        <div class="mini-list">
          <?php foreach ($recentPosts as $recent): ?>
            <a class="mini-post" href="post.php?id=<?php echo escape_html((string) ($recent['id'] ?? '')); ?>">
              <div class="mini-thumb">
                <?php if (!empty($recent['image_url'])): ?>
                  <img src="<?php echo escape_url($recent['image_url']); ?>"
                    alt="<?php echo escape_html($recent['title'] ?? ''); ?>" />
                <?php endif; ?>
              </div>
              <div>
                <p><?php echo escape_html($recent['title'] ?? ''); ?></p>
                <span><?php echo escape_html($recent['pub_date'] ?? ''); ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="widget">
        <h3>Months</h3>
        <ul class="widget-list">
          <?php if (!$months): ?>
            <li>No archive yet</li>
          <?php endif; ?>
          <?php foreach ($months as $item): ?>
            <li>
              <a class="month-link"
                href="../index.php?month=<?php echo escape_html((string) ($item['month_key'] ?? '')); ?>">
                <span><?php echo escape_html((string) ($item['month_label'] ?? '')); ?></span>
                <span class="count"><?php echo escape_html((string) ($item['total'] ?? 0)); ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="widget">
        <h3>Labels</h3>
        <div class="tag-list">
          <?php if (!$tags): ?>
            <span class="tag">News</span>
            <span class="tag">Updates</span>
          <?php endif; ?>
          <?php foreach ($visibleTags as $tagItem): ?>
            <a class="tag" href="../index.php?tag=<?php echo urlencode($tagItem); ?>">
              <?php echo escape_html($tagItem); ?>
            </a>
          <?php endforeach; ?>
          <?php foreach ($hiddenTags as $tagItem): ?>
            <a class="tag tag-hidden" href="../index.php?tag=<?php echo urlencode($tagItem); ?>">
              <?php echo escape_html($tagItem); ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php if (count($hiddenTags) > 0): ?>
          <button class="tag-toggle" type="button" data-state="collapsed">Show more</button>
        <?php endif; ?>
      </div>

      <div class="widget ad-widget">
        <h3>Advertisement</h3>
        <div class="ad-space">Your ad code here</div>
      </div>
    </aside>

  </main>

</body>

<script>
    (function () {
      var toggle = document.querySelector('.tag-toggle');
      if (!toggle) {
        return;
      }
      var hiddenTags = document.querySelectorAll('.tag-hidden');
      toggle.addEventListener('click', function () {
        var isCollapsed = toggle.dataset.state === 'collapsed';
        hiddenTags.forEach(function (tag) {
          tag.style.display = isCollapsed ? 'inline-flex' : 'none';
        });
        toggle.dataset.state = isCollapsed ? 'expanded' : 'collapsed';
        toggle.textContent = isCollapsed ? 'Show less' : 'Show more';
      });
    })();
</script>

</html>