====================================================================
Islands Book Trust Theme  (/ibt)
====================================================================

Developed for the Islands Book Trust by Ben Sheppard  
Tested with: WordPress 6.8 | WooCommerce 10.3 | PHP 8.4

--------------------------------------------------------------------
OVERVIEW
--------------------------------------------------------------------
The Islands Book Trust (IBT) theme is a custom WordPress block theme
built to support IBT’s publishing, reading, and event activities.
It provides Full Site Editing (FSE) support, WooCommerce integration,
and a clean, accessible design guided by WCAG AAA standards.

The theme is designed to work with the companion plugin
**IBT Customisation**, which adds WooCommerce product fields (Author,
ISBN, Pages, First Published), shared taxonomies, and event-management
features. Several templates and blocks rely on that plugin for full
functionality.

--------------------------------------------------------------------
FEATURES
--------------------------------------------------------------------
• Full Site Editing structure (WordPress 6.8+ block system).  
• Integrated WooCommerce styling for product archives and singles.  
• Responsive layout with accessible navigation and focus states.  
• Custom token-based colour palette (Stone / Ocean scheme).  
• Locally hosted open-source fonts (Source Serif 4 & Source Sans 3).  
• Optimised header with search and mini-cart support.  
• Minimal JavaScript footprint and AAA-targeted accessibility design.  

--------------------------------------------------------------------
STRUCTURE
--------------------------------------------------------------------
ibt/                                     - Theme root  
│
├─ /templates/                           - Core HTML templates  
│     ├─ archive-ibt_event.html          - Custom event archive (plugin-rendered)  
│     ├─ archive-product.html            - WooCommerce product archive  
│     ├─ single-product.html             - WooCommerce single product  
│     ├─ product-search-results.html     - Custom search results template  
│     ├─ coming-soon.html                - Site Maintenance Template  
│     ├─ order-confirmation.html         - Woo checkout confirmation  
│     ├─ page-cart.html / page-checkout.html / my-account.html  
│     ├─ taxonomy-product_attribute.html - Minimal Woo fallback (unused)  
│     └─ Standard WP templates (index, single, page, 404, etc.)  
│
├─ /parts/                               - Template parts  
│     ├─ header.html / footer.html       - Standard header and footer  
│     ├─ header-shop.html / footer-shop.html - Reduced versions for shop and checkout  
│     ├─ pre-footer.html                 - CTA and footer intro section  
│     └─ meta-schema.html                - JSON-LD structured data (Organization + WebSite)  
│
├─ /assets/                              - Static theme assets  
│     ├─ /css/    → ibt.css              - Compiled theme styles (cache-busted in dev)  
│     ├─ /js/     → ibt-header.js        - Header search and toggle logic  
│     └─ /fonts/                         - Local OFL fonts (subset WOFF2)  
│          ├─ /source-sans-3/            - Source Sans 3 (UI / headings)  
│          └─ /source-serif-4/           - Source Serif 4 + Italic (body / emphasis)  
│
├─ functions.php                         - Theme setup, enqueues, and helpers  
├─ theme.json                            - Tokens, global styles, and presets  
├─ style.css                             - Theme metadata and version header  
└─ README.txt                            - This document  

Version: See style.css header  

*Note:* `functions.php` includes a development cache-buster for CSS.  
Replace with standard versioning before production release.

--------------------------------------------------------------------
FONTS
--------------------------------------------------------------------
• Source Sans 3 (UI / headings), Source Serif 4 (body), and  
  Source Serif 4 Italic (quotes / emphasis).  
• Locally hosted variable fonts in WOFF2 format subset to Latin and  
  Latin Extended-A (English & Gaelic) for reduced payload.  
• Loaded with `font-display: swap` and metric overrides for stable  
  fallback and accessible rendering.

--------------------------------------------------------------------
LICENSING
--------------------------------------------------------------------
Copyright © 2025 Ben Sheppard.  
Developed for the Islands Book Trust and released under the GNU General
Public License v2 or later (GPL-2.0+).  

Full licence text: https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt  

Fonts  
• All OFL / SIL-licensed — see font folders for licence details.  

Icons:  
• Header search icon — Lucide Icons (MIT)  
• Additional icons — WooCommerce core  

--------------------------------------------------------------------
ACKNOWLEDGEMENT
--------------------------------------------------------------------
chatGPT credited as a non-legal co-author for advisory, code generation,
and educational support. Development was substantially accelerated and
improved by its assistance.
====================================================================
