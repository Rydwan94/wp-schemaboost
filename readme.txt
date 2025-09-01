=== SchemaBoost Lite ===
Contributors: your-name
Tags: schema, json-ld, rich results, woo, faq, howto, localbusiness, organization
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 0.6.4
Requires PHP: 7.4
License: Proprietary
License URI: https://example.com

Add fast, clean Schema.org JSON‑LD to your site: Article, FAQ, and WooCommerce Product. Unlock PRO for HowTo, LocalBusiness, Organization, and an on‑page inspector for admins.

== Description ==

SchemaBoost Lite helps your content surface as Rich Results in Google by outputting well‑formed JSON‑LD.

Free features:

- Article schema for single posts (author, dates, publisher, image).
- FAQ schema (global list configured in settings).
- WooCommerce Product schema (name, description, images, offers, ratings) — optional.

PRO features:

- HowTo schema (steps, totalTime) with a simple UI.
- LocalBusiness schema (type, address, opening hours, geo, sameAs). Output site‑wide or homepage.
- Organization (no location) for businesses without a physical address (homepage).
- On‑page Schema Inspector for admins to quickly see detected JSON‑LD.

Security and coding standards:

- Inputs are sanitized and outputs escaped per WordPress standards.
- Nonces and capabilities verified for admin actions.

== Installation ==

1. Upload the `schema-boost-lite` directory to `/wp-content/plugins/`.
2. Activate the plugin through the ‘Plugins’ screen in WordPress or via WP‑CLI: `wp plugin activate schema-boost-lite`.
3. Go to Settings → SchemaBoost Lite and configure.

== Frequently Asked Questions ==

= Does this conflict with other SEO plugins? =
It generally does not. Avoid duplicating the same schema types from multiple plugins on the same URL.

= Is WooCommerce required? =
Only if you enable Product schema. Otherwise it remains inactive.

= Where do I manage PRO features? =
Go to Settings → SchemaBoost Lite → PRO sections (HowTo, LocalBusiness, Organization).

== Screenshots ==
1. Settings page with Article/FAQ/Product/PRO sections
2. LocalBusiness form with hours and address
3. On‑page Schema Inspector (PRO)

== Changelog ==

= 0.6.4 =
* Improved LocalBusiness UI (clear toggles, spacing).
* Added Organization (no location) schema (PRO, homepage).
* Fixed Woo Product checkbox event and form name.

== Upgrade Notice ==

= 0.6.4 =
UI polish and new Organization schema (PRO). Review settings after update.
