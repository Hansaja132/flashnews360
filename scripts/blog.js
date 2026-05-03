const POSTS_CONTAINER = document.querySelector("#posts-grid");
const HERO = document.querySelector("#hero-card");
const STATUS = document.querySelector("#posts-status");

const formatDate = (date) => {
  if (!date || Number.isNaN(date.getTime())) {
    return "";
  }
  return new Intl.DateTimeFormat("en-US", {
    month: "short",
    day: "2-digit",
    year: "numeric",
  }).format(date);
};

const toText = (value) => (value ? value.trim() : "");

const extractDate = (doc) => {
  const meta = doc.querySelector(
    'meta[property="article:published_time"], meta[name="date"], meta[name="published"], meta[name="pubdate"]'
  );
  let dateText = meta ? meta.getAttribute("content") : "";

  if (!dateText) {
    const timeEl = doc.querySelector("time[datetime]");
    if (timeEl) {
      dateText = timeEl.getAttribute("datetime") || timeEl.textContent;
    }
  }

  if (!dateText) {
    const strongEl = Array.from(doc.querySelectorAll("strong")).find((el) =>
      /published/i.test(el.textContent)
    );
    if (strongEl && strongEl.parentElement) {
      dateText = strongEl.parentElement.textContent.replace(/.*Published:\s*/i, "");
    }
  }

  const date = dateText ? new Date(dateText.trim()) : null;
  return date && !Number.isNaN(date.getTime()) ? date : null;
};

const extractSummary = (doc) => {
  const meta = doc.querySelector('meta[name="description"]');
  if (meta && meta.getAttribute("content")) {
    return meta.getAttribute("content");
  }
  const p = doc.querySelector("p");
  return p ? p.textContent : "";
};

const extractImage = (doc) => {
  const og = doc.querySelector('meta[property="og:image"]');
  if (og && og.getAttribute("content")) {
    return og.getAttribute("content");
  }
  const img = doc.querySelector("img");
  return img ? img.getAttribute("src") : "";
};

const normalizePath = (path) => {
  if (!path.startsWith("pages/")) {
    return `pages/${path}`;
  }
  return path;
};

const parseDirectoryListing = (html) => {
  const doc = new DOMParser().parseFromString(html, "text/html");
  const links = Array.from(doc.querySelectorAll("a"))
    .map((anchor) => anchor.getAttribute("href"))
    .filter((href) => href && href.endsWith(".html"))
    .map((href) => href.replace(/^\.\//, ""));
  return Array.from(new Set(links));
};

const fetchIndexFromDirectory = async () => {
  const response = await fetch("pages/", { cache: "no-store" });
  if (!response.ok) {
    throw new Error("Directory listing not available");
  }
  const html = await response.text();
  const links = parseDirectoryListing(html);
  return links.map(normalizePath);
};

const fetchIndexFromJson = async () => {
  const response = await fetch("pages/index.json", { cache: "no-store" });
  if (!response.ok) {
    return null;
  }
  return response.json();
};

const fetchPageList = async () => {
  const jsonIndex = await fetchIndexFromJson();
  if (Array.isArray(jsonIndex) && jsonIndex.length) {
    return jsonIndex.map(normalizePath);
  }
  return fetchIndexFromDirectory();
};

const fetchPageMeta = async (url) => {
  const response = await fetch(url, { cache: "no-store" });
  if (!response.ok) {
    throw new Error(`Failed to fetch ${url}`);
  }
  const html = await response.text();
  const doc = new DOMParser().parseFromString(html, "text/html");
  const title =
    toText(doc.querySelector('meta[property="og:title"]')?.getAttribute("content")) ||
    toText(doc.title) ||
    toText(doc.querySelector("h1")?.textContent) ||
    url;
  const summary = toText(extractSummary(doc));
  const image = toText(extractImage(doc));
  const date = extractDate(doc);

  return {
    url,
    title,
    summary,
    image,
    date,
  };
};

const renderHero = (post) => {
  if (!HERO || !post) {
    return;
  }
  HERO.innerHTML = `
    <div class="hero-media">
      ${post.image ? `<img src="${post.image}" alt="${post.title}" />` : ""}
    </div>
    <div class="hero-meta">
      <span>Featured Story</span>
      ${post.date ? `<span>${formatDate(post.date)}</span>` : ""}
    </div>
    <h2 class="hero-title">${post.title}</h2>
    <p class="hero-summary">${post.summary || "Fresh updates from the latest upload."}</p>
    <a class="hero-link" href="${post.url}">
      <span>&rarr;</span>
      Read the full story
    </a>
  `;
};

const renderPosts = (posts) => {
  if (!POSTS_CONTAINER) {
    return;
  }
  POSTS_CONTAINER.innerHTML = posts
    .map(
      (post) => `
        <article class="post-card">
          <div class="post-media">
            ${post.image ? `<img src="${post.image}" alt="${post.title}" />` : ""}
          </div>
          <h3 class="post-title">${post.title}</h3>
          <p class="post-summary">${post.summary || "Open to read the full update."}</p>
          <div class="post-meta">
            <span>${post.date ? formatDate(post.date) : ""}</span>
            <a class="post-link" href="${post.url}">Read more &rarr;</a>
          </div>
        </article>
      `
    )
    .join("");
};

const renderStatus = (message) => {
  if (STATUS) {
    STATUS.textContent = message;
  }
};

const loadPosts = async () => {
  try {
    renderStatus("Loading posts...");
    const pages = await fetchPageList();
    if (!pages.length) {
      renderStatus("No posts found in the pages folder.");
      return;
    }

    const metaList = await Promise.all(pages.map(fetchPageMeta));
    const sorted = metaList.sort((a, b) => {
      if (a.date && b.date) {
        return b.date.getTime() - a.date.getTime();
      }
      if (a.date) {
        return -1;
      }
      if (b.date) {
        return 1;
      }
      return a.url.localeCompare(b.url);
    });

    const [featured, ...rest] = sorted;
    renderHero(featured);
    renderPosts(rest.length ? rest : sorted);
    renderStatus("Showing latest posts.");
  } catch (error) {
    renderStatus(
      "Unable to list posts. If you are running locally, use a web server with directory listing or provide pages/index.json."
    );
  }
};

loadPosts();
