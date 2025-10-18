Phase 0 — Setup & Foundation

    X 0.1 Reference & Folder

    X 0.2 Download Twenty Twenty-Five for reference.

    X 0.3 Create /wp-content/themes/ibt.

    X 0.4 Add fresh style.css header (Theme Name: IBT Theme).

    X 0.5 Add functions.php with:

        X add_theme_support( 'title-tag' )

        X add_theme_support( 'editor-styles' )

        X add_theme_support( 'woocommerce' )

        X enqueue style.css + future assets/css/ibt.css.

    0.6 X Minimal TT5 Files to Copy

        X theme.json (root only, delete styles/)

        X templates/index.html

        X templates/page.html

        X templates/single.html

        X templates/archive.html

        X templates/404.html

        X parts/header.html

        X parts/footer.html

    0.7 X Prune & Merge

        X Remove all style variations, demo patterns, gradients.

        X Merge good settings from v1 theme.json (spacing, widths, typography, temp AAA palette).

    0.8 Git

        x Initialise Git in ibt folder.

        N/A Create branch v2-foundation.

        N/A Commit minimal scaffold.

Phase 1 — Core Structure

    1.1 Templates

        X Strip TT5 design blocks → keep skeleton only.

    1.2 Adapt/create:

        X1.2.1 index.html

        X1.2.2 page.html

        X1.2.3 single.html

        X1.2.4 archive.html

        X1.2.5 front-page.html

        X1.2.6 404.html

    1.3 WooCommerce:

        X1.3.1 single-product.html

        X1.3.2 archive-product.html

        X1.3.3 checkout.html

    1.4 Template Parts

        X 1.4.1 Minimal header.html (site title + nav).

        X 1.4.2 Minimal footer.html (copyright placeholder).

    1.5 Custom Types & Taxonomies

        X1.5.1 Register Library CPT (library) with archive.

        X1.5.2 Register shared Topic taxonomy for product + library.

        X1.5.3 Keep Woo Product Tag private (internal only).

        X1.5.4 theme.json Tokens - Spacing scale & content/wide widths.

    X1.6 Temporary AAA-safe palette.

    X1.7 Base typography (≈1.2 rem body, generous line-height).

Phase 2 — Functionality

    2.1 Dev Plugins:
        WooCommerce
        Woo Payments (testing mode)
        The Events Calendar - Free version for listings only
        MailPoet (Free)
       X Create Block Theme - Simplifies pattern export. Dev only

    2.2 WooCommerce flow:

        Shop, cart, checkout pages.
        
        Books - verify core physical product checkout flow

        Membership product (one-off, manual renewal).

        Event product type for advance-pay events.

    2.3 Library & Topic:

        2.3.1 Create single-library.html (unstyled single.html clone to start)

        2.3.2 Library archive template (custom query loop).

        2.3.3 Topic tag archive shows Products + Library only.

    2.4 News:

        Default Posts with Categories.

        Optional custom Category archive template.

    2.4a Add cookie/consent notice if analytics or marketing cookies used

    2.4b E-mail Call to Action

        Decide mailing-list provider (MailPoet likely – free tier OK for dev, check extent of their branding).

        Configure plugin: create list, enable double-opt-in, add privacy/consent checkbox.

        Create a minimal subscribe pattern (can be reused in sidebar, footer, and around site).

        Add GDPR text + link to privacy policy.

        Test double-opt-in end to end

    2.4c - Review and update refund, privacy and T&C for new site logic

    2.4d - Design and implement functional footer
    
    2.5 Library Rail Implementation

        2.5.1 Create parts/sidebar-library.html template part and insert into single-library.html.
        
        2.5.2 Apply two-column CSS grid layout in ibt.css
            Hide below breakpoint
            Consider CTA at end below breakpoint

        2.5.3 Build SSR blocks inside the ibt-customisation plugin for:
            Related Books by Topic (now possible because Topic terms exist).
            Upcoming Events.
            Featured Products.
            "Join IBT" & mailing list subscribe CTA.
            Add caching/fallback logic.

Phase 3 — Design & Branding

    3.1 Brand Integration

        Update colours & fonts in theme.json when brand ready.

        Swap placeholder tokens for final palette.

        Add custom patterns (hero, product card, library teaser).

        Style MailPoet form for AAA contrast, visible focus states, and clear success/error messages.

        AAA Contrast Testing
            Normal text AAA ≥ 7:1
            Large text AAA ≥ 4.5:1

        A11y Tools:
            WebAIM Contrast Checker https://webaim.org/resources/contrastchecker/
            Contrast Ratio https://contrast-ratio.com/
            Chrome/Edge DevTools → Accessibility panel
            Chrome Lighthouse audit

    3.2 Custom CSS

        Add to assets/css/ibt.css only for grid/flex tweaks, cross-browser fixes, print styles.
        Keep colour/spacing/typography in theme.json wherever possible.
        Responsive images & basic font optimisation (subset/preload); quick Lighthouse performance pass.

Phase 4 - Testing / QA

    4.0 Clean up theme for release
        Remove cache buster from functions.php

    4.1 Clean WP Install for QA

    4.2 Add production plugins
        WooCommerce
        Woo Payments
        The Events Calendar (free version for listings only)
        MailPoet (Free? Paid may be req'd depending on branding)
        Yoast or Rank Math (SEO).
        Google Product Feed (WooCommerce → Google Merchant integration)
        Solid Security
        Hostinger caching & CDN

    4.3 Tag v1.0.0 in Git for theme & plugin.

    4.4 Export as ZIP and install on a fresh Hostinger instance

    4.5 Populate with sample content.

    4.5a Verify XML sitemaps, metadata, and robots.txt via SEO plugin.

    4.5b Final keyboard-only navigation and screen-reader pass headings/landmarks check.
        Test WooCommerce Flow
        Test double-opt-in for subscribe
        Check each post type, search type and tag type for correct behaviour
        Responsive images & basic font optimisation (subset/preload); quick Lighthouse performance pass.

    4.6 If result==flawless 
        Complete content migration
        Add forwards for Shopify URLs
        Promote to production
    else 
        fix in sandbox
        goto start of phase 4

Optional refinements for later
    Automated membership renewals (Woo Subscriptions or Paid Memberships Pro).
    Event booking plugin if attendance grows.
    Split library into sub-categories and improve search - Content growth likely driver 
