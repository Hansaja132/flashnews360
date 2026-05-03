const fs = require("fs");
const path = require("path");

const rootDir = path.join(__dirname, "..");
const envFile = path.join(rootDir, ".env");

const readEnvFile = (filePath) => {
  if (!fs.existsSync(filePath)) {
    return {};
  }

  const lines = fs.readFileSync(filePath, "utf8").split(/\r?\n/);
  return lines.reduce((acc, line) => {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith("#")) {
      return acc;
    }

    const eqIndex = trimmed.indexOf("=");
    if (eqIndex === -1) {
      return acc;
    }

    const key = trimmed.slice(0, eqIndex).trim();
    let value = trimmed.slice(eqIndex + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }

    if (key) {
      acc[key] = value;
    }

    return acc;
  }, {});
};

const env = readEnvFile(envFile);
const baseUrl = env.BASE_URL;
if (!baseUrl || !/^https?:\/\//.test(baseUrl)) {
  console.error("BASE_URL is required in .env, e.g. https://flashnews360.gt.tc");
  process.exit(1);
}
const pagesDir = path.join(rootDir, "pages");
const pagesIndex = path.join(pagesDir, "index.json");
const sitemapFile = path.join(rootDir, "sitemap.xml");

const pages = JSON.parse(fs.readFileSync(pagesIndex, "utf8"));
const normalizedBase = baseUrl.replace(/\/$/, "");

const urls = ["/"]
  .concat(pages.map((page) => `/pages/${page.replace(/^pages\//, "")}`))
  .map((entry) => `${normalizedBase}${entry}`);

const getLastMod = (relativePath) => {
  const filePath = path.join(rootDir, relativePath);
  if (!fs.existsSync(filePath)) {
    return null;
  }
  return new Date(fs.statSync(filePath).mtime).toISOString();
};

const entries = urls
  .map((url) => {
    const pathName = url.replace(normalizedBase, "");
    const rel = pathName === "/" ? "index.html" : pathName.replace(/^\//, "");
    const lastmod = getLastMod(rel);

    return [
      "  <url>",
      `    <loc>${url}</loc>`,
      lastmod ? `    <lastmod>${lastmod}</lastmod>` : "",
      "  </url>",
    ]
      .filter(Boolean)
      .join("\n");
  })
  .join("\n");

const sitemap = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">\n${entries}\n</urlset>\n`;

fs.writeFileSync(sitemapFile, sitemap);
console.log(`Sitemap written to ${sitemapFile}`);
