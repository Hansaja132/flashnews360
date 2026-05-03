# InfinityFree Automation (index.json + sitemap.xml)

These steps automate `pages/index.json` and `sitemap.xml` updates on InfinityFree cPanel by running the Node scripts after new posts are uploaded.

## Option A: Use Make.com (no server-side runtime needed)

This is the simplest and most reliable on InfinityFree (static hosting).

1. In your Make.com scenario, add a **Run JavaScript** module (or any module that can transform data).
2. When your scenario uploads a new post file to `/pages/`, also:
   - Download `/pages/index.json`.
   - Parse it as JSON.
   - Append the new filename (example: `post_2026-05-03T15_10_00.000+05_30.html`).
   - Sort the array if you want chronological order.
   - Upload the updated JSON back to `/pages/index.json`.
3. Also rebuild `sitemap.xml` in Make.com:
   - Download `/pages/index.json`.
   - Build a list of URLs based on your `BASE_URL`.
   - Write a new `sitemap.xml` string.
   - Upload it to `/sitemap.xml`.

This replaces the Node scripts with Make.com logic and works on static hosting.

## Option B: Use a remote runner (GitHub Actions or another VPS)

InfinityFree does not allow running Node scripts on each request, so run them elsewhere and upload the results.

1. Put the project in a GitHub repo.
2. Create a GitHub Action that runs on a schedule or on push:
   - `node scripts/build-pages-index.js`
   - `node scripts/build-sitemap.js`
3. Upload the generated `pages/index.json` and `sitemap.xml` to InfinityFree using FTP (actions like `SamKirkland/FTP-Deploy-Action`).
4. Trigger the workflow when new posts are added (from Make.com or any webhook).

## Notes

- Running these scripts on every page load is not possible on InfinityFree because it is static hosting (no Node runtime per request).
- The most reliable method is to update the JSON and sitemap at upload time.
