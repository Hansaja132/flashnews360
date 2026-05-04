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
    rel="stylesheet"
  />
  <link rel="stylesheet" href="../styles/blog.css" />
</head>
<script>(function(s){s.dataset.zone='10797249',s.src='https://izcle.com/vignette.min.js'})([document.documentElement, document.body].filter(Boolean).pop().appendChild(document.createElement('script')))</script>
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

  <main class="post-layout">
  <article class="news-post">
    <header>
      <h1><?php echo escape_html($title); ?></h1>
      <div class="post-meta">
        <span><strong>Published:</strong> <?php echo escape_html($pubDate); ?></span>
        <span>
          <strong>Source:</strong>
          <a href="<?php echo escape_url($sourceLink); ?>" target="_blank" rel="noopener noreferrer"><?php echo escape_html($sourceName); ?></a>
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
        Original source: <a href="<?php echo escape_url($pageUrl); ?>" target="_blank" rel="nofollow noopener noreferrer">Read full article</a>.
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

  </main>

</body>
</html>
