# InfinityFree Automation (database-backed sitemap.xml)

Posts are stored in MySQL, so `pages/index.json` and FTP sync are no longer used.
The sitemap is generated from the database using the Node script.

## Option A: Run sitemap generation offsite

InfinityFree does not allow Node on the server, so generate `sitemap.xml` elsewhere and upload it.

1. Put the project in a GitHub repo.
2. Create a GitHub Action that runs on a schedule or on push:
   - `node scripts/build-sitemap.js`
3. Upload the generated `sitemap.xml` to InfinityFree (FTP or your preferred deploy step).

## Option B: Use Make.com

1. In your Make.com scenario, add a **Run JavaScript** module.
2. Set `BASE_URL` and DB credentials from your Make.com secrets.
3. Run `node scripts/build-sitemap.js` in the module and upload `sitemap.xml`.

## Notes

- `scripts/build-sitemap.js` reads DB credentials from `.env`.
- Posts are resolved to `POST_PATH` (default: `/pages/post.php?id=`).
