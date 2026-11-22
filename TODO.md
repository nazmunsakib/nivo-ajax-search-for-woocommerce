# NivoSearch — Enterprise TODO

Source: static code read, 2026-11-21. Nothing here has been benchmarked or run in a browser.
Legend: [ ] open · P0 blocks enterprise use · P1 core enterprise features · P2 polish

## A. Claim verification (docs vs code)

| Claim | Status | Evidence |
|---|---|---|
| 300+ typo corrections | OK | 519 entries in `includes/data/typo-corrections.php` |
| Custom typo rules, free cap 10 | OK | `Typo_Manager::FREE_RULES_LIMIT = 10` |
| Variation SKU search | OK | `Search_Algorithm.php` JOIN + scoring |
| Search history, mobile overlay, GA4/GTM events | OK | `nivo-search.js` |
| WPML / Polylang scoping | OK (untested) | `Search_Algorithm.php` `apply_language_filter`, `fetch_terms` |
| Excluded product IDs | OK | `Nivo_Ajax_Search.php` option `nivo_search_excluded_products` |
| Add to cart (simple), variable links to product | OK | `nivo-search.js`, `format_search_result()` |
| Results page redirect to shop | OK, but default is ON | `Search_Results_Page.php`: default `'yes'`; readme says "when enabled" |
| **Synonym expansion** | **FALSE** | Session 6 log says `expand_synonyms()` exists. No code calls `includes/data/synonyms.php`. The file is dead data. |
| **Configurable relevance weights** | **PARTLY** | `rank_results()` reads `weight_*` args. CLAUDE.md (Session 19) says the admin weights feature has a gap. Check the preset UI. |
| "Index health" / index freshness | UNVERIFIED | Index only used as fuzzy fallback |
| Hidden / private products excluded | **MISSING** | Only `post_status = publish` checked. No `product_visibility` / catalog visibility handling. |
| Accessibility | **PARTIAL** | Some `aria-label` / `role="search"` / `aria-live`. No combobox/listbox/keyboard-nav verification. |

Correction to my earlier message: search already prefers `wc-ajax` over `admin-ajax.php`, so that is lighter than I said.

## B. P0 — correctness and trust

- [x] Respect catalog visibility + password-protected products in WP_Query and fuzzy paths (2026-11-21, lint only — test on a live store).
- [x] Visibility fix verified on the local site 2026-11-21: hidden, shop-only and password products excluded; "search only" included; typo query behaved the same.
- [x] Synonyms DECIDED + BUILT 2026-11-21: FREE. Built-in dictionary (single words) + up to 10 custom groups (Synonyms tab); Pro can raise the cap via `nivo_search_max_custom_synonyms`. Index path only (the old WP_Query path ignores them).
- [x] Relevance weights now configurable (Ranking tab, `Ranking_Settings`); the note below is the original finding. Relevance weights: VERIFIED NOT CONFIGURABLE. `rank_results()` reads `weight_*` args but no admin UI saves them and nothing passes them, so defaults always apply. Build the UI/filter or stop claiming it.
- [~] PHPUnit scaffold added (`tests/`, `phpunit.xml.dist`, `bin/install-wp-tests.sh`) with a visibility test. Run locally 2026-11-21: 5 tests / 31 assertions pass. Still to cover: indexer, typo manager, caching.
- [~] PHPCS (`phpcs.xml.dist`, security + compatibility rules first) and `.github/workflows/ci.yml` added. PHPCS passes locally (security + compat rules); CI not yet run on GitHub. Widen PHPCS rules later.
- [x] Rate limiting (`Rate_Limiter`, 120 req/min per IP, filter `nivo_search_rate_limit`, 429 + Retry-After) and soft nonce check for search so cached pages with expired nonces no longer 403 (filter `nivo_search_require_nonce` restores strict mode). Verified live 2026-11-21.

## C. P0 — scale

- [x] Make the indexed engine the primary path (2026-11-21, branch `perf/benchmark`): about 2 s to about 100 ms or less at 500k products, see section G.2. Follow-ups below.
- [x] Replace `LIKE '%term%'` with indexed prefix matching; benchmarked at 500k (section G.2).
- [ ] Index table: add FULLTEXT or prefix strategy, drop the row-per-token duplication where possible, store normalized fields.
- [x] Background batch indexing (2026-11-21): `Index_Rebuilder` (Action Scheduler, WP-Cron fallback; 20 s runs, resumable via `last_id`; progress notice on NivoSearch screens), starts automatically once per index version for admins, admin button goes background above 2,000 products, `wp nivo-search index rebuild|status`. Search stays on the standard path until it finishes. Verified: resume and Action Scheduler chaining on the 500k bench DB; 22 tests. Also verified on the dev site (1,030 products) 2026-11-21: `wp nivo-search index rebuild|status`, the WP-Cron fallback (filter `nivo_search_use_action_scheduler`), the auto-start after upgrade (job state showed `auto_started`), the admin button's background message and the progress notice in the browser, and that search keeps working before, during and after a rebuild. Bug found by this testing and fixed: the plugin clears all admin notices on its own screens, which hid the progress notice.
- [ ] Zero-downtime reindex (build into a shadow table, then swap).
- [x] Cache: object cache used when persistent (else transients); stock-status invalidation throttled to once per 30 s (`nivo_search_cache_bump_interval`); product saves still invalidate immediately. Per-product targeted invalidation not done (any product can appear in any query).
- [ ] Optional external engine adapter (Meilisearch / Elasticsearch / OpenSearch / Typesense).

## D. P1 — enterprise features

- [x] Relevance signals (2026-11-21): popularity (`total_sales`), rating, newness, in-stock; max points each, all default 0; Ranking tab. Applied to the candidate pool (100 when signals are on). Per-preset ranking rules NOT done (settings are global).
- [ ] Stemming / language-aware tokenization beyond accent folding.
- [~] Merchandising (2026-11-21): query rules (exact/starts/contains) to pin, bury and redirect (same-site only); Merchandising tab with product pickers; verified in the browser and live. NOT done: boost-by-amount rules, banners, per-category rules, rule scheduling, rule import/export.
- [ ] Filters / facets (category, price, attribute, availability).
- [ ] Analytics: unhide `Search_Analytics_Page`; zero-result report, CTR, search-to-cart/revenue.
- [x] REST API `nivo-search/v1`: `GET /search?q=&preset_id=` (public, rate limited), `GET /presets` (admins), `GET /presets/<id>` (public, sanitised settings). Tests + live smoke test 2026-11-21.
- [ ] Overridable templates (Phase 5.3).
- [ ] B2B: role/customer-group visibility and pricing; multi-currency; multisite check.
- [x] Core-search fallback when the engine throws (`nivo_search_engine_failed` action), tested.

## E. P1 — accessibility and compatibility

- [~] WCAG 2.1 AA — DONE and browser-verified 2026-11-21 (`assets/js/nivo-search-a11y.js`): combobox/listbox/option roles, `aria-activedescendant`, ArrowUp/Down/Home/End, Enter opens, Escape closes, separate polite status announcement, listbox no longer a live region. Active result now has a visible outline (found missing during the 2026-11-22 full check). STILL OPEN: mobile overlay keyboard/focus trap, focus management, contrast audit, screen-reader testing (VoiceOver/NVDA), full WCAG audit. Original item: combobox/listbox roles, `aria-activedescendant`, full keyboard nav, focus management in the mobile overlay, screen-reader announcements, contrast.
- [ ] Test with block themes, popular classic themes, Elementor/Divi, and caching plugins (WP Rocket, LiteSpeed).
- [ ] Verify WPML / Polylang with real installs.

## F. P2 — operations and compliance

- [ ] Index diagnostics / health dashboard; error logging.
- [ ] GDPR: retention and anonymization for search logs; privacy policy text.
- [ ] Preset import/export between environments.
- [ ] Docs and live admin preview polish (Phase 6).

## Suggested order

1. Visibility fix, synonyms/weights truth-check (small, high trust impact)
2. Tests + CI
3. Scale engine (C), then REST API
4. Relevance + merchandising, analytics, facets
5. Accessibility pass and compatibility matrix

## G. Benchmark findings (2026-11-21, synthetic catalog, local MySQL 8.4, PHP 8.2, one machine — indicative only)

Setup: raw-SQL products in a throwaway DB (title, short content, `_sku`, `_stock_status`, `_price` only; no variations or taxonomies). `Search_Algorithm::search()` called directly, WP object cache flushed before every run.

**Correction:** the first version of this section reported 0.5 to 180 ms. Those numbers were wrong: the runs reused WordPress's own query cache, so they measured cache hits. Cold numbers below.

| Products | Index rebuild | Any query on the primary WP_Query path (common word, multi-word, rare, SKU, no match) | Typo query (primary path, then fuzzy fallback) |
|---|---|---|---|
| 500k | not rebuilt (index still 100k) | about 2.0 s each | about 3.9 s |

- **The primary search does not scale.** Even an exact SKU match takes about 2 s at 500k, because the query scans titles and content with `LIKE '%term%'` and LEFT JOINs the postmeta and variation tables before applying `LIMIT`. Cold 10k and 100k numbers were not re-measured; the earlier ones are invalid.
- Index rebuild: 116 s for 100k products (about 860 products/s), single request. That measurement did not depend on the query cache.
- Fixed on branch `perf/benchmark`: multi-word queries used to be matched as one exact phrase (`LIKE '%premium hoodie%'`), so "premium hoodie" missed "Premium Red Hoodie". Every word must now match, in any order and field. Ranking also considered only the first N rows MySQL returned; it now ranks a candidate pool (max(40, 4 x limit), filter `nivo_search_candidate_pool`) ordered by title relevance in SQL, then cuts to the limit.
- Both fixes are covered by `tests/unit/test-search-multiword.php`; the fix did not change speed (2.0 s before and after at 500k).

Open items from this:
- [x] P0: indexed engine is the primary path (about 97 ms or less at 500k).
- [~] Fuzzy fallback bounded: index-friendly candidate prefixes, LIMIT 300; no-match 2 ms at 500k. Negative-result caching still open.
- [ ] Re-run the benchmark with realistic data (variations, categories, long descriptions) and keep the script in the repo (always flush the object cache between runs).

### G.2 Index-first engine results (500k synthetic products, cold cache, same machine)

| Query | Before (WP_Query LIKE scan) | After (index-first) |
|---|---|---|
| common word ("hoodie", about 5% of products) | about 2,010 ms | about 97 ms |
| two words ("premium hoodie") | about 1,990 ms | about 103 ms |
| rare ("hoodie 4321") | about 1,980 ms | about 7 ms |
| SKU exact ("SKU-004321") | about 1,970 ms | about 2 ms |
| typo (fuzzy fallback) | about 3,940 ms | about 94 ms |
| no match | about 2,030 ms | about 2 ms |

How: `Index_Query` drives the lookup from the longest word with indexed prefix lookups and checks other words with EXISTS probes; a covering index (`idx_cover`) answers them from the index alone; Search_Algorithm then loads the candidate products by ID and applies visibility, stock and exclusion rules. Used only after the index is rebuilt (option `nivo_search_index_version`); otherwise the old path runs. Fuzzy candidate lookup no longer uses a `%substring%` scan.

Costs and limits to know:
- Index size: 9.0M rows for 500k products (about 18 rows per product): about 456 MB data + 1.2 GB indexes in the benchmark. The old index was about 7 rows per product. Description words are capped at 30 per product (filter `nivo_search_index_content_token_limit`; `nivo_search_index_content` disables them).
- Full rebuild: 1,386 s for 500k products (about 360 products/s), in one process. Still needs background batching (below).
- Matching is by word start on the index path: "hood" finds "Hoodie", "oodie" does not. Turn off with option `nivo_search_use_index` (or filter `nivo_search_use_index_engine`).
- Existing sites keep the old, slow path until they rebuild the index. There is no automatic rebuild yet.
- Fuzzy no longer matches typos in the first two letters unless they are a swap of those two letters.
- Still unmeasured: realistic catalogs (variations, categories, long descriptions), concurrent load, MySQL 5.7/MariaDB, shared hosting.

New TODO items:
- [x] Background batch rebuild with resume + progress, auto-start after upgrade (see section C). Remaining: live progress bar in the admin card (only a notice today) and a non-destructive rebuild into a shadow table (search is on the standard path while rebuilding).
- [ ] Size/rows budget: consider dropping `idx_token` and the duplicate content rows, or moving description words into a smaller side table.
- [ ] Keep the benchmark script in the repo (flush the object cache between runs).

Follow-ups from the rebuild testing:
- [x] Search Index card now switches to a "Rebuilding in the background…" state after the button is clicked (page reloads show the real counts).
- [ ] With typo tolerance on, a mid-word query such as "oodie" still finds "Hoodie" through the typo/fuzzy fallback on the index path. Fine, but note that it is not an exact substring match.
- [ ] Action Scheduler async runs only fire on web requests: on a site with no traffic and DISABLE_WP_CRON, a rebuild waits for a visit or `wp action-scheduler run`.

## H. Free vs Pro decisions (2026-11-21, owner decision: "best free features, then Pro")

Goal: be one of the most powerful WooCommerce search plugins and beat FiboSearch. Free must be clearly stronger than FiboSearch free (which uses the standard WooCommerce engine) and win on features FiboSearch charges for. Free = generous, with limits (not locks); Pro = scale, insight, automation, developer tools.

FREE (built unless noted): index-first engine; word-by-word, typo, fuzzy and "Did you mean?"; SKU + variation SKU; **GTIN/UPC/EAN/ISBN search (built)**; **attribute search (built)**; **synonyms: built-in + 10 custom groups (built)**; add-to-cart, badges, unlimited presets; accessibility layer; core-search fallback; rate limiting.
FREE, still to build: simple ranking toggles ("Best sellers first", "Newest", "In stock first") in place of the raw weights; basic analytics (top 20 failed searches, last 30 days); up to 3 merchandising rules; stemming/plurals; facets (basic).
PRO (planned): unlimited merchandising rules + scheduling; custom ranking weights and boost values; full analytics (history, click-through, search-to-revenue, export); unlimited synonyms/typo rules; REST API, WP-CLI, template overrides; external engine adapter; B2B customer-group visibility; multi-vendor support.

Open: nothing on `main` is gated yet. Merchandising, ranking weights, REST API and WP-CLI are currently ungated in code; decide and gate them before the next release (never remove a free feature after shipping it).

## I. Full feature check, 2026-11-22 (dev site, 1,030 products + temporary test products, since removed)

PASS: 53 unit tests; PHPCS (0 errors, 13 warnings); PHP and JS syntax; all 9 admin screens load without PHP notices; AJAX and REST search (single word, multi-word, prefix, typo + "Did you mean?" + click, no match, SKU, GTIN full and prefix, attribute value, custom synonyms, visibility: hidden / shop-only / password); out-of-stock exclusion; legacy (no-index) path; merchandising pin / bury / redirect; results-page redirect to shop; REST status codes; rate limit (117 allowed, 13 blocked of 130); cache bump on save; core-search fallback; mobile overlay rendering, add-to-cart (item landed in the WooCommerce cart), search history, desktop dropdown and keyboard navigation; no new PHP/nginx errors, no new PHP-FPM crashes.
FOUND AND FIXED: (1) the "Excluded products" setting was ignored on the index path (WP_Query skips post__not_in when post__in is set); (2) the keyboard-active result had no visible highlight.
NOT TESTED: WPML/Polylang, other themes and page builders, variation-SKU search on real data (the dev catalog has no variation SKUs), large catalogs after these changes, screen readers, WP-Cron-less hosting, MySQL versions other than 8.4/CI's 8.0.

## J. Preset editor review, 2026-11-22

Found and fixed (branch `feat/preset-ux`):
- Old presets stored `search_in_attributes = 0` (a placeholder from before the feature existed), so attribute search looked OFF in the editor and stayed off for upgraded sites. Migration `2.0.8` switches it on once and removes the dead `search_in_gtin` / `enable_synonyms` keys (runs when the plugin version reaches 2.0.8; the dev site was migrated by hand).
- The fuzzy fallback ignored the "Search in ..." switches, so a field switched off could still match. It now searches only the preset's fields (categories/tags only when those results are on).
- Saving the editor accepted any number server-side. Now: results 1-50, min characters 1-5, delay 0-2000 ms, bar and results width 150-1400 px, bar height 30-100 px; an empty placeholder falls back to "Search products..." (it is also the input's accessible name).
Added: one-line help under every scope toggle; a status line (index on / building / not built, with a link to build it); a "Duplicate" link on the presets list.
Still open: preset templates ("Small store", "Large catalog", "Minimal"); grouping the Style tab into simple choices (light/dark/compact) with colours under Advanced; per-preset ranking choices; a "Where to use this preset" panel (shortcode, block, theme search replacement); live preview does not reflect scope toggles or the active index.

## K. Front-end layouts, 2026-11-22 (branch `feat/layouts`)

Added, per preset (Style & Layout tab, "Layout" section with visual choices and live preview):
- **Search bar styles:** Standard, Icon on the left, Rounded pill, With search button, Minimal underline.
- **Results layouts:** List (default), Grid of cards, Compact.
- Stored in the preset's general settings (`search_bar_layout`, `results_layout`); shortcode attributes `search_bar_layout="..."` and `results_layout="..."` override them; the Gutenberg block uses the preset. Values are whitelisted (they become CSS class names). The mobile overlay uses the same results layout. Old presets (numeric `search_bar_layout = 1`) map to "standard".
- Verified in the browser: all five bars, grid and compact on desktop, grid in the mobile overlay (2 columns), editor cards, instant live preview, saving through the real form, plain `[nivo_search id]` and the block output.
Not done: an "expand on click" icon-only bar for headers; per-layout text options (button label); a horizontal-scroll "carousel" results layout; grid card count setting; layout choice in the block's own sidebar; layouts for the category/tag result sections (they keep the list style).
Note: the Minimal bar ignores the preset's background colour (it is transparent by design).

## L. Icon-only search bar, 2026-11-22 (branch `feat/icon-search-layout`)

New search bar style **Icon only (opens on click)**: only a search icon is shown; clicking it opens the search box (and results) in a panel under the icon, right-aligned, so it suits headers. Closes on Escape (focus returns to the icon), a click outside, or another click on the icon; the icon is a real button (`aria-expanded`, Enter/Space work). On phones, focusing the input still opens the fullscreen overlay, and closing the overlay folds the panel away.
Verified in the browser: closed state (icon only), open, results under the box, Escape / outside click / keyboard reopen, mobile overlay hand-off (focus event), editor card and live preview. 68 tests.
Limits: the panel is as wide as the preset's bar width (max 92% of the screen); on a real phone the first tap on the icon opens the panel and focusing the box opens the overlay (not tested on a physical device); the results panel top assumes the bar height from the preset (set through the `--nivo-bar-h` variable).
