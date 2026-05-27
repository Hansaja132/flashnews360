<?php
require __DIR__ . '/db.php';

$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$month = isset($_GET['month']) ? trim((string) $_GET['month']) : '';
$tag = isset($_GET['tag']) ? trim((string) $_GET['tag']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
  $page = 1;
}

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
  $month = '';
}

$limit = 10;
$offset = ($page - 1) * $limit;

$conditions = [];
$params = [];
if ($search !== '') {
  $conditions[] = '(title ILIKE ? OR summary ILIKE ?)';
  $like = '%' . $search . '%';
  $params[] = $like;
  $params[] = $like;
}
if ($month !== '') {
  $conditions[] = "to_char(pub_date, 'YYYY-MM') = ?";
  $params[] = $month;
}
if ($tag !== '') {
  $conditions[] = 'meta_tags ILIKE ?';
  $params[] = '%' . $tag . '%';
}

$whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

$countStmt = $conn->prepare('SELECT COUNT(*) FROM posts ' . $whereSql);
$countStmt->execute($params);
$totalPosts = (int) $countStmt->fetchColumn();

$stmt = $conn->prepare(
  'SELECT id, title, summary, image_url, pub_date, source_name, meta_tags '
  . 'FROM posts '
  . $whereSql . ' '
  . 'ORDER BY pub_date DESC NULLS LAST, id DESC '
  . 'LIMIT ? OFFSET ?'
);
$stmt->execute(array_merge($params, [$limit, $offset]));
$posts = $stmt->fetchAll();

$recentPosts = array_slice($posts, 0, 5);
$trendingPosts = array_slice($posts, 0, 3);
$popularPosts = array_slice($posts, 3, 3);
$hasMore = ($offset + count($posts)) < $totalPosts;

function escape_html(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function escape_url(string $value): string
{
  $value = trim($value);
  if ($value == '') {
    return '';
  }

  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_date($value): string
{
  if ($value === null || $value === '') {
    return '';
  }

  $timestamp = strtotime($value);
  if ($timestamp === false) {
    return escape_html((string) $value);
  }

  return date('M j, Y', $timestamp);
}

function month_label($value): string
{
  if ($value === null || $value === '') {
    return '';
  }

  $timestamp = strtotime($value);
  if ($timestamp === false) {
    return '';
  }

  return date('F Y', $timestamp);
}

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
$tags = extract_tags($tagsStmt->fetchAll());
$tagLimit = 10;
$visibleTags = array_slice($tags, 0, $tagLimit);
$hiddenTags = array_slice($tags, $tagLimit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FlashNews360 | Daily News Updates</title>
  <meta name="description"
    content="FlashNews360 publishes daily news updates. Discover the latest headlines and summaries from our automated news feed." />
  <meta name="robots" content="index,follow" />
  <meta name="theme-color" content="#f6f0e8" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="FlashNews360" />
  <meta property="og:title" content="FlashNews360 | Daily News Updates" />
  <meta property="og:description"
    content="FlashNews360 publishes daily news updates. Discover the latest headlines and summaries from our automated news feed." />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="FlashNews360 | Daily News Updates" />
  <meta name="twitter:description"
    content="FlashNews360 publishes daily news updates. Discover the latest headlines and summaries from our automated news feed." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600&display=swap"
    rel="stylesheet" />
  <link rel="canonical" href="/" />
  <link rel="stylesheet" href="styles/blog.css" />
  <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "NewsMediaOrganization",
        "name": "FlashNews360",
        "url": "https://example.com",
        "publishingPrinciples": "https://example.com/about"
      }
    </script>
  <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "FlashNews360",
        "url": "https://example.com",
        "publisher": {
          "@type": "NewsMediaOrganization",
          "name": "FlashNews360"
        }
      }
    </script>
</head>

<body>
  <header class="top-bar">
    <div class="container">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true"></div>
        <h1>FlashNews360</h1>
      </div>
      <div class="top-actions">
        <span>Live feed from your news database</span>
      </div>
    </div>
  </header>

  <main class="page">
    <section class="main-column">
      <div class="section-header">
        <h2>Latest Posts</h2>
        <p id="posts-status" class="status">
          <?php echo $posts ? escape_html(count($posts) . ' posts loaded') : 'No posts found.'; ?>
        </p>
      </div>
      <div class="posts-grid" id="posts-grid">
        <?php if (!$posts): ?>
          <div class="empty-state">
            <h3>No posts yet</h3>
            <p>Publish new posts in the database and refresh to see them here.</p>
          </div>
        <?php endif; ?>
        <?php foreach ($posts as $post): ?>
          <article class="post-card">
            <div class="post-media">
              <?php if (!empty($post['image_url'])): ?>
                <img src="<?php echo escape_url($post['image_url']); ?>"
                  alt="<?php echo escape_html($post['title'] ?? ''); ?>" />
              <?php endif; ?>
            </div>
            <div class="post-body">
              <div class="post-kicker">
                <span><?php echo escape_html($post['source_name'] ?? 'News'); ?></span>
              </div>
              <h3 class="post-title"><?php echo escape_html($post['title'] ?? ''); ?></h3>
              <p class="post-summary"><?php echo escape_html($post['summary'] ?? ''); ?></p>
              <div class="post-meta">
                <span><?php echo escape_html(format_date($post['pub_date'] ?? '')); ?></span>
                <span><?php echo escape_html($post['source_name'] ?? ''); ?></span>
              </div>
              <a class="post-link" href="pages/post.php?id=<?php echo escape_html((string) ($post['id'] ?? '')); ?>">
                Read more
                <span>→</span>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php if ($hasMore): ?>
        <div class="load-more">
          <a class="load-more-link"
            href="/?page=<?php echo escape_html((string) ($page + 1)); ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?><?php echo $month !== '' ? '&month=' . urlencode($month) : ''; ?><?php echo $tag !== '' ? '&tag=' . urlencode($tag) : ''; ?>">
            Load more posts
          </a>
          <p class="load-more-meta">Showing <?php echo escape_html((string) ($offset + count($posts))); ?> of
            <?php echo escape_html((string) $totalPosts); ?>
          </p>
        </div>
      <?php endif; ?>
    </section>

    <aside class="sidebar">
      <div class="widget search-widget">
        <h3>Search this blog</h3>
        <form class="search-form" action="/" method="get">
          <input type="search" name="q" placeholder="Search posts..." value="<?php echo escape_html($search); ?>" />
          <button type="submit">Search</button>
        </form>
      </div>

      <div class="widget">
        <h3>Months</h3>
        <ul class="widget-list">
          <?php if (!$months): ?>
            <li>No archive yet</li>
          <?php endif; ?>
          <?php foreach ($months as $item): ?>
            <?php $isActive = $month === ($item['month_key'] ?? ''); ?>
            <li>
              <a class="month-link<?php echo $isActive ? ' is-active' : ''; ?>"
                href="/?month=<?php echo escape_html((string) ($item['month_key'] ?? '')); ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?><?php echo $tag !== '' ? '&tag=' . urlencode($tag) : ''; ?>">
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
            <a class="tag<?php echo $tag === $tagItem ? ' is-active' : ''; ?>"
              href="/?tag=<?php echo urlencode($tagItem); ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?><?php echo $month !== '' ? '&month=' . urlencode($month) : ''; ?>">
              <?php echo escape_html($tagItem); ?>
            </a>
          <?php endforeach; ?>
          <?php foreach ($hiddenTags as $tagItem): ?>
            <a class="tag tag-hidden<?php echo $tag === $tagItem ? ' is-active' : ''; ?>"
              href="/?tag=<?php echo urlencode($tagItem); ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?><?php echo $month !== '' ? '&month=' . urlencode($month) : ''; ?>">
              <?php echo escape_html($tagItem); ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php if (count($hiddenTags) > 0): ?>
          <button class="tag-toggle" type="button" data-state="collapsed">Show more</button>
        <?php endif; ?>
      </div>

      <div class="widget">
        <h3>Recent</h3>
        <div class="mini-list">
          <?php foreach ($recentPosts as $post): ?>
            <a class="mini-post" href="pages/post.php?id=<?php echo escape_html((string) ($post['id'] ?? '')); ?>">
              <div class="mini-thumb">
                <?php if (!empty($post['image_url'])): ?>
                  <img src="<?php echo escape_url($post['image_url']); ?>"
                    alt="<?php echo escape_html($post['title'] ?? ''); ?>" />
                <?php endif; ?>
              </div>
              <div>
                <p><?php echo escape_html($post['title'] ?? ''); ?></p>
                <span><?php echo escape_html(format_date($post['pub_date'] ?? '')); ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="widget">
        <h3>Trending</h3>
        <div class="mini-list">
          <?php foreach ($trendingPosts as $post): ?>
            <a class="mini-post" href="pages/post.php?id=<?php echo escape_html((string) ($post['id'] ?? '')); ?>">
              <div class="mini-thumb">
                <?php if (!empty($post['image_url'])): ?>
                  <img src="<?php echo escape_url($post['image_url']); ?>"
                    alt="<?php echo escape_html($post['title'] ?? ''); ?>" />
                <?php endif; ?>
              </div>
              <div>
                <p><?php echo escape_html($post['title'] ?? ''); ?></p>
                <span><?php echo escape_html(format_date($post['pub_date'] ?? '')); ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="widget">
        <h3>Most Popular</h3>
        <div class="mini-list">
          <?php foreach ($popularPosts as $post): ?>
            <a class="mini-post" href="pages/post.php?id=<?php echo escape_html((string) ($post['id'] ?? '')); ?>">
              <div class="mini-thumb">
                <?php if (!empty($post['image_url'])): ?>
                  <img src="<?php echo escape_url($post['image_url']); ?>"
                    alt="<?php echo escape_html($post['title'] ?? ''); ?>" />
                <?php endif; ?>
              </div>
              <div>
                <p><?php echo escape_html($post['title'] ?? ''); ?></p>
                <span><?php echo escape_html(format_date($post['pub_date'] ?? '')); ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="widget ad-widget">
        <h3>Advertisement</h3>
        <div class="ad-space">Your ad code here</div>
      </div>
    </aside>
  </main>

  <footer class="footer">
    <p>FlashNews360 - handcrafted layout inspired by Newsim.</p>
  </footer>

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

</body>

</html>