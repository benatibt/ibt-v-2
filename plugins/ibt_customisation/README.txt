====================================================================
IBT Customisation Plugin  (/ibt_customisation)
====================================================================

Developed for the Islands Book Trust by Ben Sheppard  
Tested with: WordPress 6.8 | WooCommerce 10.3 | PHP 8.4  
Requires: WooCommerce (active)

--------------------------------------------------------------------
OVERVIEW
--------------------------------------------------------------------
The IBT Customisation plugin extends WordPress and WooCommerce for
use on the Islands Book Trust website.

It adds custom fields, post types, taxonomies, and output logic to
integrate books, library articles, and shared topics within a unified
content framework.

It also provides a lightweight, fully custom **Events** subsystem with
list views, archives, and single-event displays — replacing heavier
third-party calendar plugins. Data structures remain compatible with
future iCal / Google Calendar export.

--------------------------------------------------------------------
FEATURES
--------------------------------------------------------------------
• **Events system**  
  – Shortcode for inserting event lists on any page:  
        [ibt_events_list n="3"]  
  – Field shortcode for use in block templates:  
        [ibt_event_field key="..."]  
  – Archive view provided by a PHP-rendered dynamic block.  
  – Single-event view via block template and shortcodes.  
  – Google Maps button using latitude/longitude or Plus Codes.  
  – Metabox-based admin interface for events and venues.  

• **WooCommerce product extensions**  
  – Adds custom Author, ISBN, Pages, and First Published fields to Products.  
  – Outputs data in product loops and the single-product page.  
  – Fields appear in the Additional Information table when present.

• **Content model enhancements**  
  – Registers a “Library” Custom Post Type for non-commercial articles.  
  – Registers a shared hierarchical “Topic” taxonomy used by:  
        • WooCommerce Products (Books)  
        • Library CPT  
        • Standard Posts (optional future use)
  – Default posts retitled to "News" in admin.

• **Search improvements (Relevanssi)**  
  – Front-end search results are ordered by relevance.

--------------------------------------------------------------------
STRUCTURE
--------------------------------------------------------------------
ibt_customisation.php                    – Main plugin loader  
│
├─ /includes/                           – Supporting modules  
│    ├─ register-taxonomy-types.php     – Registers Library CPT and Topic taxonomy  
│    ├─ author-isbn-fields.php          – Adds Author, ISBN, Pages, First Published  
│    ├─ ibt-utilities.php               – Misc utilities (search ordering, etc.)
│
│    ├─ /events/                        – Event subsystem  
│         ├─ ibt-events-core.php            – Registers CPTs and venue CPT  
│         ├─ ibt-events-admin.php           – Admin metaboxes  
│         ├─ ibt-events-helpers.php         – Utility, date, and field retrieval  
│         ├─ ibt-events-display-field.php   – Renders individual event fields  
│         ├─ ibt-events-display-list.php    – Renders event list (shortcode view)  
│         ├─ ibt-events-shortcodes.php      – Registers shortcodes and routes calls  
│
├─ /blocks/                             – Block definitions  
│    ├─ /events-archive-php/  
│         ├─ block-register.php         – Registers dynamic block from PHP  
│         ├─ render.php                 – PHP render callback for event archive  
│
├─ /css/                                – Stylesheets  
│    ├─ ibt-events-admin.css            – Backend metabox formatting  

*Note:* The events archive block uses a simplified registration pattern
where `block-register.php` directly registers the render callback from
PHP without a full `block.json` / JS build. This is fully
production-safe under WordPress 6.8 and avoids Gutenberg build
dependencies.

--------------------------------------------------------------------
LICENSING
--------------------------------------------------------------------
Copyright © 2025 Ben Sheppard.
Developed for the Islands Book Trust and released under the GNU
General Public License v2 or later (GPL-2.0+).

Full license text:  
https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt

The author retains copyright to the original source code and may
reuse portions of this work in other projects under different licences.

--------------------------------------------------------------------
ACKNOWLEDGEMENT
--------------------------------------------------------------------
ChatGPT credited as a non-legal co-author for advisory, code generation,
and educational support. Development was substantially accelerated and
improved by its assistance.
====================================================================
