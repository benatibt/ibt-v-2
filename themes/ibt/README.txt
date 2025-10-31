====================================================================
Islands Book Trust Theme  (/ibt)
====================================================================

Developed for the Islands Book Trust by Ben Sheppard  
Tested with: WordPress 6.8 | Compatible with WooCommerce 10.3+

--------------------------------------------------------------------
OVERVIEW
--------------------------------------------------------------------
The Islands Book Trust (IBT) theme is a custom WordPress block theme
built to support IBT’s publishing, reading, and event activities.
It provides Full Site Editing (FSE) support, WooCommerce integration,
and a clean, accessible design guided by WCAG AAA/AA standards.

The theme is designed to work with the companion plugin
**IBT Customisation**, which adds WooCommerce product fields
(Author, ISBN), shared taxonomies, and event management features.
Several theme templates and blocks rely on that plugin for full
functionality.

--------------------------------------------------------------------
FEATURES
--------------------------------------------------------------------
• Full Site Editing structure (WordPress 6.8+ block system).  
• Integrated WooCommerce styling for product archives and singles.  
• Responsive layout with accessible navigation and focus states.  
• Custom token-based colour palette (Stone/Ocean scheme).  
• Locally hosted open-source fonts (Source Serif 4 & Source Sans 3).  
• Optimised header with search and mini-cart support.  
• Minimal JavaScript footprint and AAA-targeted accessibility design.  

--------------------------------------------------------------------
STRUCTURE
--------------------------------------------------------------------
ibt/                                     - Theme root  
│
├─ /templates/                           - Core HTML templates  
│     ├─ archive-product.html            - Woo product archive  
│     ├─ archive-ibt_event.html          - Custom event archive (via plugin)  
│     ├─ single-product.html             - Woo single product  
│     ├─ taxonomy-product_attribute.html - Minimal Woo fallback (unused)  
│     └─ … (standard WordPress templates)  
│
├─ /parts/                         - Template parts (header, footer, etc.)  
│     ├─ header.html  
│     ├─ footer.html  
│     └─ meta-schema.html  – Structured data (JSON-LD for Organization + WebSite)  
│
├─ /assets/                        - Static theme assets  
│     ├─ /css/  → ibt.css          - Compiled theme styles (cache-busted in dev)  
│     ├─ /js/   → ibt-header.js    - Header search and toggle logic  
│     └─ /fonts/                   - Local font files (OFL-licensed)  
│
├─ functions.php                   - Theme setup, enqueue, and helper filters  
├─ theme.json                      - Tokens, global styles, and presets  
├─ style.css                       - Theme metadata and version header  
└─ README.txt                      - This document  

Version: See style.css header  

*Note:* `functions.php` includes a development cache-buster for CSS.
Replace with standard versioning before production release.

--------------------------------------------------------------------
LICENSING
--------------------------------------------------------------------
Copyright © 2025 Ben Sheppard.  
Developed for the Islands Book Trust and released under the  
GNU General Public License v2 or later (GPL-2.0+).  

Full licence text: https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt  

Fonts:  
• Source Sans 3 and Source Serif 4 — Open Font License (OFL/SIL)  
Icons:  
• Header search icon — Lucide Icons (MIT)  
• Additional icons — WooCommerce core  

--------------------------------------------------------------------
ACKNOWLEDGEMENT
--------------------------------------------------------------------
chatGPT credited as a non-legal co-author for advisory, code-generation,
and educational support. Development was substantially accelerated by
its assistance.

====================================================================
