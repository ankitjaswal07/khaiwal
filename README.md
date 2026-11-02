# Khaiwal — WordPress theme

A classic WordPress theme for browsing and publishing game results. It uses PHP templates and standard WordPress APIs—no React, build step, external CDN, or Node runtime is required in production. The yellow accent is `#ffd800`.

## Install

1. Put this directory in `wp-content/themes/khaiwal`, or zip it as a folder and upload it under **Appearance → Themes → Add New**. Exclude `.git`, `.wp-playground`, and `node_modules` if they exist.
2. Activate **Khaiwal**. On activation the theme creates any missing `Games` posts for the 36 catalog games, plus `/chart/` and `/contact/` pages if those paths do not already exist. Existing posts and pages are not overwritten; if a path is already occupied, create a page at another URL and assign the **Game directory** or **Contact** page template. The default navigation uses an explicitly assigned template page first. On an otherwise untouched installation only, three English sample journal posts are added.
3. Set the site title, logo and timezone in WordPress. Dates for results are interpreted in **Asia/Kolkata**. The default navigation is Home, Chart, Contact and WordPress Login; a menu assigned to **Primary navigation** can replace it. Login opens in a new tab.
4. Add a featured image to a WordPress post to display it in the journal carousel. Existing page/post content, shortcodes, standard WordPress hooks and SEO plugins remain available. Existing content on the Chart page is shown after the directory; content on the Contact page replaces the built-in form, so a form plugin may be used instead. The built-in form sends via `wp_mail()` to the WordPress administrator email; configure your host's mail delivery before using it. It applies a basic one-message-per-minute limit per IP address.

On activation game pages are available at `/games/{game-slug}/`. They are normal public WordPress posts with standard permalinks and title tags. No existing root-level game URLs are changed or redirected; if migrating from an established site, map the old URLs deliberately with your existing redirect/SEO plugin after testing. If permalinks do not work after installation, save **Settings → Permalinks** once.

## Publish and import results

- Go to **Game results → Publish a result**. Select a game, enter a valid date and a two-digit result (`00`–`99`), and publish the post. A published entry overrides the bundled snapshot for the same game and date.
- For bulk updates use **Game results → Import CSV**. The CSV must have exactly these header names (column order may vary):

  ```csv
  game_slug,date,result
  delhi-bazar,2026-11-01,30
  madhupuri,2026-11-01,09
  ```

  The game slug is the last part of the game URL. Dates are `YYYY-MM-DD`; preserve leading zeroes. Invalid rows are skipped; published matches are updated, not duplicated. The importer requires permission to publish posts and to edit any existing result it updates. Uploads are capped at 2 MB / 5,000 rows per request. Files over the row limit are rejected before any row is imported; on hosts with low PHP time limits, use smaller batches.
- Add more games under **Games**. A game can have a usual IST result time, Hindi name, description and featured image. The usual result time is informational; it does not schedule or generate a result.

Results and games are stored as WordPress posts and remain in the database if the theme is switched. The theme registers their post types, so reactivate it (or migrate the registration to a site plugin) to manage them after a theme change.

## Data provenance and freshness

`data/games.json` contains a **dated snapshot from A1-Satta**, collected **2 November 2026**, covering the available October 2026 records and the published November 2026 records at that time for all 36 games listed on its chart page. Sources are linked from the site and game pages. Missing values on the reference are represented by a dash; they are not predictions. Some reference charts did not provide an October 31 value. As with any third-party source, verify values before publication.

This is **not a live feed** and does not automatically scrape or publish future results. An editor must add or import verified newer numbers. The interface shows the current and preceding India-time date/month. The selector includes bundled archive months and all months with editor-published results for that game. If a current value has not been published, it is shown as unavailable. The snapshot date is shown in the interface so old data is not presented as current.

## Language and accessibility

The English/Hindi switch translates the theme interface and the sample journal summaries and remembers the visitor's choice in local storage. WordPress-authored post/page bodies and custom menu labels are not automatically machine-translated; use a multilingual plugin or translated content for those. Core templates and the four default navigation links are in English by default.

GSAP + ScrollTrigger animate only the hero; AOS handles separate section reveals; Swiper powers the journal carousel. Libraries and fonts are locally hosted and enqueued with WordPress, with common existing plugin handles reused when registered. Motion is disabled when `prefers-reduced-motion` is set. Content remains available without JavaScript; the carousel has a scrollable fallback on small screens. Images are optimized WebP and journal images lazy-load.

## Development / QA

The theme works on WordPress 6.2+ and PHP 7.4+. The preview used WordPress Playground with WordPress 6.8 / PHP 8.3. Test the homepage, Chart, Contact, a game, a blog post, login, keyboard navigation, language persistence, filters, carousel and result publishing on your target host. The theme does not change WordPress or plugin data beyond the non-destructive activation seed described above.
