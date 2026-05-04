<?php
require __DIR__ . '/db.php';

$stmt = $conn->query('SELECT id, title, summary, image_url, pub_date, source_name FROM posts ORDER BY id DESC LIMIT 9');
$posts = $stmt->fetchAll();

$heroPost = $posts[0] ?? null;
$gridPosts = $heroPost ? array_slice($posts, 1) : [];

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
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FlashNews360 | Daily News Updates</title>
    <meta
      name="description"
      content="FlashNews360 publishes daily news updates. Discover the latest headlines and summaries from our automated news feed."
    />
    <meta name="robots" content="index,follow" />
    <meta name="theme-color" content="#f6f0e8" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="FlashNews360" />
    <meta property="og:title" content="FlashNews360 | Daily News Updates" />
    <meta
      property="og:description"
      content="FlashNews360 publishes daily news updates. Discover the latest headlines and summaries from our automated news feed."
    />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="FlashNews360 | Daily News Updates" />
    <meta
      name="twitter:description"
      content="FlashNews360 publishes daily news updates. Discover the latest headlines and summaries from our automated news feed."
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Space+Grotesk:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
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

    <section class="hero">
      <div class="hero-card" id="hero-card">
        <?php if ($heroPost): ?>
          <div class="hero-media">
            <?php if (!empty($heroPost['image_url'])): ?>
              <img src="<?php echo escape_url($heroPost['image_url']); ?>" alt="<?php echo escape_html($heroPost['title'] ?? ''); ?>" />
            <?php endif; ?>
          </div>
          <div class="hero-meta">
            <span><?php echo escape_html(format_date($heroPost['pub_date'] ?? '')); ?></span>
            <span><?php echo escape_html($heroPost['source_name'] ?? ''); ?></span>
          </div>
          <h2 class="hero-title"><?php echo escape_html($heroPost['title'] ?? ''); ?></h2>
          <p class="hero-summary"><?php echo escape_html($heroPost['summary'] ?? ''); ?></p>
          <a class="hero-link" href="pages/post.php?id=<?php echo escape_html((string) ($heroPost['id'] ?? '')); ?>">
            <span>→</span>
            Read full story
          </a>
        <?php else: ?>
          <h2 class="hero-title">No posts yet</h2>
          <p class="hero-summary">Publish new posts in the database and refresh to see them here.</p>
        <?php endif; ?>
      </div>
      <div class="hero-aside">
        <div class="hero-aside-card">
          <h2>Daily Pulse</h2>
          <p>
            Publish new posts in the database and refresh to see them here.
          </p>
        </div>
        <div class="hero-aside-card">
          <h2>Editor Notes</h2>
          <p>
            Add meta description and og:image tags to your page for better
            previews.
          </p>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="section-header">
        <h2>Latest Posts</h2>
        <p id="posts-status" class="status">
          <?php echo $posts ? escape_html(count($posts) . ' posts loaded') : 'No posts found.'; ?>
        </p>
      </div>
      <div class="posts-grid" id="posts-grid">
        <?php foreach ($gridPosts as $post): ?>
          <article class="post-card">
            <div class="post-media">
              <?php if (!empty($post['image_url'])): ?>
                <img src="<?php echo escape_url($post['image_url']); ?>" alt="<?php echo escape_html($post['title'] ?? ''); ?>" />
              <?php endif; ?>
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
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <footer class="footer">
      <p>FlashNews360 - handcrafted layout inspired by Newsim.</p>
    </footer>

  </body>
</html>
