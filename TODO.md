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
- [ ] Verify the visibility fix with a hidden, a "shop only" and a password-protected product (normal + fuzzy/typo search).
- [ ] Decide on synonyms: implement (Pro-gated) or delete `synonyms.php`. CLAUDE.md note already corrected.
- [ ] Verify relevance weights are saved and used end to end; fix or remove the setting.
- [ ] Add PHPUnit (WP test suite) covering search, indexer, typo manager, caching.
- [ ] Add PHPCS (WPCS) and a CI workflow (lint + tests, PHP 7.4–8.3, a WP/WC matrix).
- [ ] Rate limiting / abuse protection on the public search endpoint; handle nonce expiry on CDN-cached pages.

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
