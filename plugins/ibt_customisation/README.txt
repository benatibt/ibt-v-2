====================================================================
IBT Customisation Plugin ( /ibt_customisation )
====================================================================

Developed for Islands Book Trust by Benjamin Sheppard
WordPress 6.8+  |  WooCommerce required

--------------------------------------------------------------------
OVERVIEW
--------------------------------------------------------------------
The IBT Customisation plugin extends WordPress and WooCommerce
for use on the Islands Book Trust website.

It adds custom fields, post types, taxonomies, and output logic to
integrate books, library articles, and shared topics within a unified
content framework.

--------------------------------------------------------------------
FEATURES
--------------------------------------------------------------------
• Adds custom WooCommerce product fields:
  – Author
  – ISBN

• Registers the “Library” custom post type for non-commercial articles
  and archive content.

• Registers a shared hierarchical “Topic” taxonomy used by:
  – WooCommerce Products (Books)
  – Library custom post type
  – Posts (optional future use)

• Outputs Author and ISBN fields in product loops and single-product
  templates, and exposes template hooks for use in block templates
  or custom theme parts.

Requires WooCommerce to be active.

--------------------------------------------------------------------
STRUCTURE
--------------------------------------------------------------------
ibt_customisation.php      - Main plugin file and loader
/includes/                 - Supporting modules
  ├─ product-fields.php     - Adds Author and ISBN fields
  ├─ product-display.php    - Outputs field data and defines hooks
  ├─ register-taxonomy-types.php - Registers “Library” CPT and shared “Topic” taxonomy

--------------------------------------------------------------------
LICENSING
--------------------------------------------------------------------
Copyright © 2025 Benjamin Sheppard.
Developed for Islands Book Trust and provided under the
GNU General Public License v2 or later (GPL-2.0+).

Full licence text: https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt

The author retains copyright to the original source code and
may reuse portions of this work in other projects under
different licences.

====================================================================

