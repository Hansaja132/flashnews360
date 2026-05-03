const fs = require("fs");
const path = require("path");
const { Client } = require("basic-ftp");

const envFile = path.join(__dirname, "..", ".env");

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

const loadEnvFromFile = () => {
  const env = readEnvFile(envFile);
  Object.entries(env).forEach(([key, value]) => {
    if (process.env[key] === undefined) {
      process.env[key] = value;
    }
  });
};

const parseBool = (value) => {
  if (value === undefined) {
    return false;
  }
  return value === "true" || value === "1";
};

const pagesDir = path.join(__dirname, "..", "pages");

const ensureLocalPagesDir = () => {
  if (!fs.existsSync(pagesDir)) {
    fs.mkdirSync(pagesDir, { recursive: true });
  }
};

const main = async () => {
  loadEnvFromFile();

  const host = process.env.FTP_HOST;
  const user = process.env.FTP_USER;
  const password = process.env.FTP_PASS;
  const port = process.env.FTP_PORT ? Number(process.env.FTP_PORT) : 21;
  const secure = parseBool(process.env.FTP_SECURE);
  const remoteDir = process.env.FTP_REMOTE_PAGES_DIR || "pages";

  if (!host || !user || !password) {
    throw new Error("Missing FTP_HOST, FTP_USER, or FTP_PASS in environment.");
  }

  ensureLocalPagesDir();

  const client = new Client();
  client.ftp.verbose = false;

  try {
    await client.access({
      host,
      user,
      password,
      port,
      secure,
    });

    await client.cd(remoteDir);

    const entries = await client.list();
    const files = entries
      .filter((entry) => entry.isFile)
      .map((entry) => entry.name)
      .filter((name) => name.endsWith(".html"));

    if (!files.length) {
      console.log("No remote .html posts found to download.");
      return;
    }

    for (const fileName of files) {
      const localPath = path.join(pagesDir, fileName);
      await client.downloadTo(localPath, fileName);
      console.log(`Downloaded ${fileName}`);
    }
  } finally {
    client.close();
  }
};

main().catch((err) => {
  console.error(err.message || err);
  process.exit(1);
});
