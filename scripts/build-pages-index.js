const fs = require("fs");
const path = require("path");

const pagesDir = path.join(__dirname, "..", "pages");
const outputFile = path.join(pagesDir, "index.json");

const pages = fs
  .readdirSync(pagesDir, { withFileTypes: true })
  .filter((entry) => entry.isFile())
  .map((entry) => entry.name)
  .filter((name) => name.endsWith(".html"))
  .sort();

fs.writeFileSync(outputFile, JSON.stringify(pages, null, 2));
console.log(`Wrote ${pages.length} entries to ${outputFile}`);
