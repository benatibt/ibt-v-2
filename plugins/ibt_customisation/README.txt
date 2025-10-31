====================================================================
IBT Customisation Plugin  (/ibt_customisation)
====================================================================

Developed for the Islands Book Trust by Ben Sheppard  
Tested with: WordPress 6.8 | WooCommerce 10.3
Requires: WooCommerce (active)

--------------------------------------------------------------------
OVERVIEW
--------------------------------------------------------------------
The IBT Customisation plugin extends WordPress and WooCommerce for
use on the Islands Book Trust website.

It adds custom fields, post types, taxonomies, and output logic to
integrate books, library articles, and shared topics within a unified
content framework.

It also provides a fully custom Events system with list views,
archives, and single-event displays, replacing heavier third-party
calendar plugins. Data structure is compatible with future iCal /
Google Calendar feed generation.

--------------------------------------------------------------------
FEATURES
--------------------------------------------------------------------
• **Events system**  
  – Shortcode for grid insertion on any page.  
  – Archive view provided by a PHP-rendered dynamic block.  
  – Single-event view via block template and shortcodes.  
  – Google Maps button using latitude/longitude or Plus Codes.  
  – Metabox-based admin interface for events and venues.  

• **WooCommerce product extensions**  
  – Adds custom Author and ISBN fields to Products.  
  – Outputs these fields in product loops and single-product views.  

• **Content model enhancements**  
  – Registers a “Library” Custom Post Type for non-commercial articles.  
  – Registers a shared hierarchical “Topic” taxonomy used by:  
    • WooCommerce Products (Books)  
    • Library CPT  
    • Standard Posts (optional future use).  

Requires WooCommerce to be active.

--------------------------------------------------------------------
STRUCTURE
--------------------------------------------------------------------
ibt_customisation.php               - Main plugin loader  
│
├─ /includes/                       - Supporting modules  
│    ├─ register-taxonomy-types.php     - Registers Library CPT and Topic taxonomy  
│    ├─ author-isbn-fields.php          - Adds Author and ISBN fields to WooCommerce  
│    ├─ /events/                        - Event subsystem  
│         ├─ ibt-customisation-events-core.php        - Registers CPTs and venues  
│         ├─ ibt-customisation-events-metabox.php     - Admin metaboxes  
│         ├─ ibt-customisation-events-helpers.php     - Utility and date functions  
│         ├─ ibt-customisation-events-display-shortcode.php - Shortcode output  
│
├─ /blocks/                         - Block definitions  
│    ├─ /events-archive-php/  
│         ├─ block-register.php     - Registers dynamic block from block.json metadata  
│         ├─ render.php             - PHP render callback for events archive  
│
├─ /css/                            - Stylesheets  
     ├─ ibt-events-admin.css        - Formating of metabox back end


  *Note:* The events archive block uses a **simplified registration**
  pattern where `block-register.php` directly registers the render
  callback from PHP without a full `block.json`–based JS build.
  This approach is production-safe under WordPress 6.8+ and avoids
  Gutenberg dev dependencies, but it differs from the canonical
  “block metadata” workflow.

--------------------------------------------------------------------
LICENSING
--------------------------------------------------------------------
Copyright © 2025 Benjamin Sheppard.  
Developed for the Islands Book Trust and released under the  
GNU General Public License v2 or later (GPL-2.0+).  

Full license text: https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt

The author retains copyright to the original source code and may reuse
portions of this work in other projects under different licences.

--------------------------------------------------------------------
ACKNOWLEDGEMENT
--------------------------------------------------------------------
chatGPT credited as a non-legal co-author for advisory, code-generation,
and educational support. Development was substantially accelerated by
its assistance.

====================================================================
