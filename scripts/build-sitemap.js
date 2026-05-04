const fs = require("fs");
const path = require("path");
const { Client } = require("pg");

const rootDir = path.join(__dirname, "..");
const envFile = path.join(rootDir, ".env");
const sitemapFile = path.join(rootDir, "sitemap.xml");

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
const postPath = env.POST_PATH || "/pages/post.php?id=";

if (!baseUrl || !/^https?:\/\//.test(baseUrl)) {
  console.error("BASE_URL is required in .env, e.g. https://flashnews360.gt.tc");
  process.exit(1);
}

const dbHost = env.DB_HOST || "127.0.0.1";
const dbPort = env.DB_PORT ? Number(env.DB_PORT) : 5432;
const dbName = env.DB_NAME;
const dbUser = env.DB_USER;
const dbPass = env.DB_PASS || "";
const dbSsl = env.DB_SSL || "require";

if (!dbName || !dbUser) {
  console.error("DB_NAME and DB_USER are required in .env");
  process.exit(1);
}

const normalizedBase = baseUrl.replace(/\/$/, "");
const normalizedPostPath = postPath.startsWith("/") ? postPath : `/${postPath}`;

const formatLastMod = (value) => {
  if (!value) {
    return "";
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return "";
  }
  return date.toISOString();
};

const main = async () => {
  const sslEnabled = dbSsl !== "disable" && dbSsl !== "false" && dbSsl !== "0";
  const client = new Client({
    host: dbHost,
    port: dbPort,
    user: dbUser,
    password: dbPass,
    database: dbName,
    ssl: sslEnabled ? { rejectUnauthorized: false } : false,
  });

  await client.connect();

  try {
    const result = await client.query("SELECT id, pub_date FROM posts ORDER BY id DESC");
    const rows = result.rows || [];
    const urls = ["/"]
      .concat(
        rows.map((row) => {
          return `${normalizedPostPath}${row.id}`;
        })
      )
      .map((entry) => `${normalizedBase}${entry}`);

    const entries = urls
      .map((url, index) => {
        const lastmod = index === 0 ? "" : formatLastMod(rows[index - 1]?.pub_date);

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
  } finally {
    await client.end();
  }
};

main().catch((err) => {
  console.error(err.message || err);
  process.exit(1);
});
