# Khaiwal — WordPress theme

A classic, responsive WordPress theme for browsing and publishing game results. It uses PHP templates and standard WordPress APIs—no React, build step, external CDN, or Node runtime is required in production. The yellow accent is `#ffd800`.

## Install or update

1. Put this directory in `wp-content/themes/khaiwal`, or zip it as a folder and upload it under **Appearance → Themes → Add New**. Exclude `.git`, `.wp-playground`, and `node_modules` if present. Back up the WordPress database and files before replacing a theme on an existing site.
2. Activate **Khaiwal**. It creates missing `Games` posts for the 14 selected games, plus `/chart/` and `/contact/` pages if those paths do not already exist. Existing posts and pages are not overwritten. If a path is occupied, create a page at another URL and assign the **Game directory** or **Contact** page template; the navigation uses an explicitly assigned template page first. On an otherwise untouched installation only, three English sample journal posts are added.
3. Set the site title, logo and WordPress timezone. Result dates and game schedules use **Asia/Kolkata (IST)** regardless of the WordPress timezone. The default navigation is Home, Chart, Contact and WordPress Login; a menu assigned to **Primary navigation** can replace it. Login opens in a new tab.
4. Add featured images to WordPress posts to display them in the journal carousel. Existing page/post content, shortcodes, standard WordPress hooks and SEO plugins remain available. Existing content on the Chart page is shown after the directory; content on the Contact page replaces the built-in form, so a form plugin may be used instead. The built-in form sends via `wp_mail()` to the WordPress administrator email; configure host mail/SMTP before using it. It applies a basic one-message-per-minute limit per IP address.
5. If links do not work after installation, save **Settings → Permalinks** once. Clear page and asset caches after updating the theme. For the dashboard to refresh, allow same-origin WordPress REST requests and do not cache `/wp-json/khaiwal/v1/dashboard`.

Game pages use `/games/{game-slug}/` on a pretty-permalink installation. No existing root-level URLs are changed or redirected; when migrating from an established site, map old URLs deliberately with your redirect/SEO plugin after testing.

### Selected lineup and times

The selected list appears on the homepage and Chart page. The supplied times take precedence over any different times on the reference source. Times are informational and do not guarantee result publication.

| Game | Hindi | IST | Game slug |
| --- | --- | --- | --- |
| Model Town | मॉडल टाऊन | 12:40 PM | `model-town` |
| Sadar Bazar | सदर बाजार | 1:25 PM | `sadar-bazar` |
| Gwalior | ग्वालियर | 2:25 PM | `gwalior` |
| Delhi Bazar | दिल्ली बाजार | 2:50 PM | `delhi-bazar` |
| Punjab Matka | पंजाब मटका | 3:40 PM | `punjab-matka` |
| Shri Ganesh | श्री गणेश | 4:25 PM | `shri-ganesh` |
| Agra | आगरा | 5:20 PM | `agra` |
| Faridabad | फरीदाबाद | 5:55 PM | `faridabad` |
| Alwar | अलवर | 7:25 PM | `alwar` |
| HR Matka | HR मटका | 8:15 PM | `hr-matka` |
| Ghaziabad | गाज़ियाबाद | 9:20 PM | `gaziabad` |
| HUDA City | हुडा सिटी | 10:40 PM | `huda-city` |
| Gali | गली | 11:25 PM | `gali` |
| Disawar | दिसावर | 1:00 AM | `disawer` |

The spelling of the `gaziabad` and `disawer` slugs preserves existing links. A one-time versioned upgrade creates missing selected games and updates names, Hindi labels and times only where they still match the previous theme defaults or were blank. Editor-customized details are preserved; review them after upgrading if the supplied schedule must be enforced. Previous game posts, their results and direct links are retained, but non-selected games are excluded from the active lineup and the WordPress core game sitemap and receive a core `noindex` directive. SEO plugins may have separate sitemap settings. New custom game posts do not automatically join this curated lineup; to change the lineup, update `data/games.json` and publish matching Game posts (or implement a versioned migration).

## Homepage dashboard, full chart and FAQs

- **Current game** is the most recently scheduled game in IST; **recent result** is the most recent earlier scheduled game with an available result; **next game** is the next scheduled game. The 1:00 AM Disawar event uses its actual IST calendar date. Missing numbers show as a dash or awaiting status, never a prediction.
- With JavaScript, the countdown ticks every second and the dashboard, 14 result cards and currently selected chart month check this site's same-origin WordPress REST endpoint on load, about every 60 seconds while visible, at schedule boundaries, and when the tab becomes visible again. Published/imported entries then appear without a reload. If refresh fails the last rendered values remain visible with a warning. Without JavaScript, the server-rendered page and GET filters still work; reload to refresh the values and countdown.
- The homepage full chart filters by game, month and year (2000–2100). It displays only available records and is scrollable by touch, mouse and keyboard. Game pages offer the current/previous month and months with bundled or editor-published entries.
- FAQs use native HTML disclosures and work without JavaScript. Developers can replace the bilingual question/answer arrays using the `khaiwal_home_faqs` WordPress filter.

## Publish and import results

- Verify each result against its source. Go to **Game results → Publish a result**, select a listed game, enter the IST date and a two-digit result (`00`–`99`), then publish the post. A published entry overrides the bundled snapshot for the same game and date.
- For bulk updates use **Game results → Import CSV**. The CSV must contain these header names (column order may vary):

  ```csv
  game_slug,date,result
  delhi-bazar,2026-11-01,30
  faridabad,2026-11-01,77
  ```

  The slug is the last part of the game URL. Dates are `YYYY-MM-DD`; preserve leading zeroes. Invalid or unlisted games and invalid rows are skipped; published matches are updated, not duplicated. The importer requires permission to publish posts and edit any existing result it updates. Uploads are capped at 2 MB / 5,000 rows per request. Files over the row limit are rejected before any row is imported; on hosts with low PHP time limits, use smaller batches.
- Under **Games**, an editor can update a listed game's Hindi name and usual IST result time. Use `h:mm AM` or `h:mm PM` (for example `2:50 PM`) so it participates in the dashboard schedule. Editing the time does not schedule or generate a result.

Results and games are stored as WordPress posts and remain in the database if the theme is switched. The theme registers their post types, so reactivate it (or migrate the registration to a site plugin) to manage them after a theme change.

## Data provenance and freshness

`data/games.json` contains a **dated A1-Satta snapshot collected 2 November 2026**, including available October 2026 history and values published at collection time for the ten selected games found on the reference. Individual sources are linked from their game pages. Model Town, Punjab Matka, HR Matka and HUDA City were not found under those names on A1-Satta at collection time, so their snapshot results are empty. HR Matka is not assumed to be the differently scheduled HR Satta. Editors can publish independently verified numbers for these games. Missing values on the reference are dashes; they are not predictions. Some reference charts did not provide an October 31 value.

The snapshot is **not a live feed**: the theme does not scrape A1-Satta or automatically import or publish future results. An editor must verify and add or import newer numbers. The automatic browser refresh only retrieves data already available in this WordPress site. The snapshot date is displayed so archived data is not presented as a live source.

## Language and accessibility

The English/Hindi switch translates the theme interface and the sample journal summaries and remembers the visitor's choice in local storage. WordPress-authored post/page bodies and custom menu labels are not automatically machine-translated; use a multilingual plugin or translated content for those. Core templates and the four default navigation links are in English by default.

GSAP + ScrollTrigger animate only the hero; AOS handles separate section reveals; Swiper powers the journal carousel. Libraries and fonts are locally hosted and enqueued with WordPress, with common existing plugin handles reused when registered. Motion is disabled when `prefers-reduced-motion` is set. Content remains available without JavaScript; the carousel has a scrollable fallback on small screens. Images are optimized WebP and journal images lazy-load.

## Development / QA

The theme works on WordPress 6.2+ and PHP 7.4+. The preview used WordPress Playground with WordPress 6.8 / PHP 8.3. Test the homepage, Chart, Contact, a game, a blog post, login, keyboard navigation, language persistence, filters, carousel, IST midnight rollover and result publishing on your target host. The theme does not change WordPress or plugin data beyond the non-destructive activation seed and lineup upgrade described above.
