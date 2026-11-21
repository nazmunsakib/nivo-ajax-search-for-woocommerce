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
- [ ] Decide on synonyms: implement (Pro-gated) or delete `synonyms.php`. CLAUDE.md note already corrected.
- [ ] Relevance weights: VERIFIED NOT CONFIGURABLE. `rank_results()` reads `weight_*` args but no admin UI saves them and nothing passes them, so defaults always apply. Build the UI/filter or stop claiming it.
- [~] PHPUnit scaffold added (`tests/`, `phpunit.xml.dist`, `bin/install-wp-tests.sh`) with a visibility test. Run locally 2026-11-21: 5 tests / 31 assertions pass. Still to cover: indexer, typo manager, caching.
- [~] PHPCS (`phpcs.xml.dist`, security + compatibility rules first) and `.github/workflows/ci.yml` added. PHPCS passes locally (security + compat rules); CI not yet run on GitHub. Widen PHPCS rules later.
- [x] Rate limiting (`Rate_Limiter`, 120 req/min per IP, filter `nivo_search_rate_limit`, 429 + Retry-After) and soft nonce check for search so cached pages with expired nonces no longer 403 (filter `nivo_search_require_nonce` restores strict mode). Verified live 2026-11-21.

## C. P0 — scale

- [ ] Make the indexed engine the primary path (not only the zero-result fallback).
- [ ] Replace `LIKE '%term%'` with indexed or full-text matching; benchmark at 10k / 100k / 500k products.
- [ ] Index table: add FULLTEXT or prefix strategy, drop the row-per-token duplication where possible, store normalized fields.
- [ ] Background batch indexing (Action Scheduler or cron) with resume; WP-CLI `wp nivo index rebuild`; no admin-ajax timeouts.
- [ ] Zero-downtime reindex (build into a shadow table, then swap).
- [ ] Cache: use the object cache when available; replace the single global version bump (`invalidate_search_cache`) with targeted invalidation.
- [ ] Optional external engine adapter (Meilisearch / Elasticsearch / OpenSearch / Typesense).

## D. P1 — enterprise features

- [ ] Relevance signals: sales, popularity, rating, recency; per-preset ranking rules.
- [ ] Stemming / language-aware tokenization beyond accent folding.
- [ ] Merchandising: pin/boost/bury products, query rules, query redirects, banners.
- [ ] Filters / facets (category, price, attribute, availability).
- [ ] Analytics: unhide `Search_Analytics_Page`; zero-result report, CTR, search-to-cart/revenue.
- [ ] REST API: search and presets endpoints (Phase 5.1/5.2).
- [ ] Overridable templates (Phase 5.3).
- [ ] B2B: role/customer-group visibility and pricing; multi-currency; multisite check.
- [ ] Fallback to core WooCommerce search if the engine fails.

## E. P1 — accessibility and compatibility

- [ ] WCAG 2.1 AA: combobox/listbox roles, `aria-activedescendant`, full keyboard nav, focus management in the mobile overlay, screen-reader announcements, contrast.
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

Setup: raw-SQL products in a throwaway DB, title/SKU/stock meta only (no variations, no taxonomies), `Search_Algorithm::search()` called directly, no result cache.

| Products | Index rebuild | Common word | SKU exact | Multi-word ("premium hoodie") | No match |
|---|---|---|---|---|---|
| 10k | 11.5 s | 0.5 ms | 1.8 ms | 16.8 ms | 10.2 ms |
| 100k | 116 s (about 860 products/s) | 2.6 ms | 2.2 ms | 180 ms | 88 ms |
| 500k | not rebuilt (index still 100k) | 1.8 ms | 2.0 ms | 164 ms | 83 ms |

- The plain `LIKE '%term%'` path is fast at these sizes because `LIMIT 10` stops early, so scaling is NOT where the first read suggested. Real catalogs with descriptions, variations and taxonomies are heavier — re-measure with realistic data.
- **P0 correctness bug: multi-word queries are matched as ONE exact phrase** (`post_title LIKE '%premium hoodie%'`, `Search_Algorithm::search_where`). "premium hoodie" does not match "Premium Red Hoodie", and "hoodie red" does not match "Red Hoodie". With fuzzy ON, such queries are only rescued by the index fallback, which cost about 2.0 s per search at 500k products (7 to 15 SQL queries).
- No-match queries trigger the fuzzy fallback every time: about 2.0 s at 500k, 83 ms at 100k.
- Ranking is applied after `LIMIT`: results are re-ordered by relevance only among the first N rows MySQL returned, not among all matches. Verify and fix.
- Index rebuild is single-pass (about 20 minutes for 1M products at the measured rate) and runs inside one request.

New TODO items from this:
- [ ] P0: tokenize multi-word queries (every word must match, any order, across title/SKU/content) instead of one phrase.
- [ ] P0: rank across all matches, not only the first N rows.
- [ ] Bound the cost of the fuzzy fallback at scale (cap candidates, cache negative results, timeout).
- [ ] Re-run the benchmark with realistic data (variations, categories, long descriptions) and keep the script in the repo.
