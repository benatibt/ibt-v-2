<?php
/**
 * IBT Navigation State Engine
 * ------------------------------------------------------------
 * Adds `data-ibt-state="active|ancestor"` to menu items inside
 * the desktop header navigation. This powers all header tab
 * highlighting without relying on WordPress’s built-in current
 * menu item classes (inconsistent under block navigation).
 *
 * HOW IT WORKS
 * - Parses the already-rendered Navigation block HTML.
 * - Extracts all <a> tags with class wp-block-navigation-item__content.
 * - Extracts all <li class="has-child"> submenu parents.
 * - Normalises the current path from REQUEST_URI.
 * - Determines:
 *       active   = exact path match,
 *       ancestor = real prefix match (/more/team → /more),
 *       ancestor = any parent <li> with a matching child.
 * - Special IBT rule: “Books” tab acts as section root for all /shop URLs.
 *
 * OUTPUT
 * Injects data-ibt-state="active" or "ancestor" back into the opening <a>
 * tag or the parent <li>. CSS reads these attributes to colour the tabs.
 *
 * ALIAS RULES
 * `ibt_nav_apply_alias()` exists for future IA changes (e.g. redirecting
 * /library/archive → /library). Keep all mapping rules inside that
 * function only. Currently no aliases are enabled.
 *
 * DEBUGGING
 * Several debug_log lines exist but are commented out. They can be enabled
 * during development or troubleshooting. Leaving them in place is cheap
 * and improves maintainability.
 */


defined( 'ABSPATH' ) || exit;

// Hook that fires the code
add_filter( 'render_block', 'ibt_nav_states_render', 10, 2 );

// *************************************************************************************

/**
 * Entry point — orchestrator
 */
function ibt_nav_states_render( $content, $block ) {

    // Limit to navigation blocks
    if ( empty( $block['blockName'] ) || $block['blockName'] !== 'core/navigation' ) {
        return $content;
    }

    // Limit to desktop header navigation only
    $class = $block['attrs']['className'] ?? '';
    if ( strpos( $class, 'ibt-header-nav-desktop' ) === false ) {
        return $content;
    }

    // ----------------------------------------------
    // INITIALISE STATE BAG
    // ----------------------------------------------

    $state = [
        'content'      => $content,               // raw HTML to manipulate
        'current_path' => ibt_nav_detect_current_path(),
        'items'        => [],                     // flat list of anchors (<a>)
        'parents'      => [],                     // list of <li class="has-child"> parents
    ];

    // ----------------------------------------------
    // PROCESSING PIPELINE
    // ----------------------------------------------

    $state['current_path'] = ibt_nav_apply_alias( $state['current_path'] );

    $state = ibt_nav_extract_items( $state );

    $state = ibt_nav_detect_states( $state );

    $state = ibt_nav_apply_states( $state );

    // DEBUG: Dump full navigation state. !!! WILL FILL LOG UP VERY QUICKLY !!!
    // error_log("IBT NAV DEBUG — FINAL STATE DUMP:\n" . print_r($state, true));

    // ----------------------------------------------
    // RETURN FINAL HTML
    // ----------------------------------------------

    return $state['content'];
}

// *************************************************************************************

/**
 * 1. Detect current request path, normalised
 */
function ibt_nav_detect_current_path() {

    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url( $request_uri, PHP_URL_PATH ) ?? '/';

    $path = strtolower( rtrim( $path, '/' ) );
    if ( $path === '' ) {
        $path = '/';
    }

    return $path;
}

// *************************************************************************************

/**
 * 2. Apply section aliases
 *
 * ----------------------------------------------
 * This function rewrites certain request paths
 * so that navigation tab highlighting works even
 * when CPTs or posts use a different URL base
 * from their archive.
 *
 * Current aliases:
 *   • /news-article/* → /news/*
 *
 * This allows:
 *   /news                 → exact match
 *   /news-article/post    → ancestor of News
 */
function ibt_nav_apply_alias( $path ) {

    // Define alias rules: FROM => TO
    $aliases = [
        '/news-article' => '/news',
    ];

    foreach ( $aliases as $from => $to ) {

        // Exact alias match
        if ( $path === $from ) {
            return $to;
        }

        // Prefix match (e.g. /news-article/foo → /news/foo)
        if ( str_starts_with( $path, $from . '/' ) ) {

            // Extract the remainder of the path after the alias source
            $rest = substr( $path, strlen( $from ) );

            // Build new path: /news + /foo  → /news/foo
            $new = rtrim( $to . $rest, '/' );

            return $new === '' ? '/' : $new;
        }
    }

    // No alias applied
    return $path;
}

// *************************************************************************************

/**
 * 3. Extract items
 *    - <a class="wp-block-navigation-item__content">
 *    - <li class="has-child"> parents
 */
function ibt_nav_extract_items( $state ) {

    $content = $state['content'];
    $items   = [];
    $parents = [];

    // =====================================================================
    // 1. FIND ALL SUBMENU PARENTS:
    //    “Find every <li> element that has the class has-child,
    //     and capture the ENTIRE <li> ... </li> block.”
    //
    //    This gives us the containers for dropdowns such as "More…".
    // =====================================================================
        if ( preg_match_all(
            '/<li[^>]*\bhas-child\b[^>]*>.*?<ul[^>]*wp-block-navigation__submenu-container[^>]*>.*?<\/ul>\s*<\/li>/is',
            $content,
            $matches
        ) ) {

        foreach ( $matches[0] as $i => $li_block ) {
            // Extract just the opening <li ...> tag (stable for replacement)
            preg_match( '/<li[^>]*>/i', $li_block, $open_tag );

            $parents[] = [
                'full'  => $li_block,     // full block, still used for detecting children
                'open'  => $open_tag[0],   // only the opener is used for replacement
                'index' => $i,
            ];
        }
    }

    // =====================================================================
    // 2. FIND ALL NAVIGATION ANCHORS:
    //    “Find every <a> tag whose class list contains
    //     wp-block-navigation-item__content.”
    //
    //    These are the actual clickable menu entries (top-level and submenu).
    // =====================================================================
    if ( preg_match_all(
        '/<a[^>]*class="[^"]*\bwp-block-navigation-item__content\b[^"]*"[^>]*>/i',
        $content,
        $a_matches
    ) ) {

        foreach ( $a_matches[0] as $a_tag ) {

            // -------------------------------------------------------------
            // 2A. Extract the href
            //     “Find the href="…" attribute inside the <a> tag.”
            // -------------------------------------------------------------
            if ( ! preg_match( '/href="([^"]+)"/i', $a_tag, $m ) ) {
                continue; // anchor with no href — skip
            }

            $href = $m[1];

            // Ignore external links
            $is_internal =
                str_starts_with( $href, '/' ) ||
                str_starts_with( $href, home_url( '/' ) );

            if ( ! $is_internal ) {
                continue;
            }

            // Normalise path
            $path = parse_url( $href, PHP_URL_PATH ) ?? '/';
            $path = strtolower( rtrim( $path, '/' ) );
            if ( $path === '' ) {
                $path = '/';
            }

            // -------------------------------------------------------------
            // 2B. DETECT THE PARENT LI (IF THIS ANCHOR LIVES IN A SUBMENU)
            //
            //     "Search for the <a> tag inside each full <li has-child> block.
            //      If the <a> is found within that block, record that LI as
            //      the parent of this anchor."
            // -------------------------------------------------------------
            $parent_index = null;

            foreach ( $parents as $p_index => $p ) {
                // Simple string search — fast and reliable
                if ( strpos( $p['full'], $a_tag ) !== false ) {
                    $parent_index = $p_index;
                    break;
                }
            }

            // -------------------------------------------------------------
            // 2C. Store the extracted item
            // -------------------------------------------------------------
            $items[] = [
                'full'         => $a_tag,         // the exact <a ...>
                'href'         => $href,
                'path'         => $path,          // normalised version
                'state'        => null,           // will be set later
                'parent_index' => $parent_index,  // null = top-level
            ];
        }
    }

    // Save into state bag
    $state['items']   = $items;
    $state['parents'] = $parents;

    /*
    // DEBUG: extraction summary
    error_log('IBT NAV DEBUG — extract_items: '
        . count($parents) . ' parents, '
        . count($items) . ' items');
    */

    return $state;
}

// *************************************************************************************

/**
 * 4. Determine active, ancestors, virtual ancestors
 */
function ibt_nav_detect_states( $state ) {

    $current_path = $state['current_path'];
    $items        = $state['items'];
    $parents      = $state['parents'];

    // ---------------------------------------------------------------------
    // HIGH-LEVEL NOTE:
    // We modify the local copies ($items, $parents) and assign them
    // back into $state at the end. This keeps mutation explicit.
    // ---------------------------------------------------------------------


    // =====================================================================
    // 1. ACTIVE MATCH
    //
    //    “For each menu item, check if its path exactly equals the
    //     current browser path. If so, mark it as 'active'.”
    //
    //    Only one active is expected, but the code allows multiple
    //    without failing.
    // =====================================================================
    foreach ( $items as &$item ) {
        if ( $item['path'] === $current_path ) {
            $item['state'] = 'active';
        }
    }
    unset( $item ); // safety: break reference


    // =====================================================================
    // 2. ANCESTOR MATCH (PURE PREFIX)
    // =====================================================================
    //
    // “If the current path starts with item.path + '/', then the item is an
    //  ancestor. Example:
    //
    //      item.path = /more
    //      current   = /more/team
    //
    //  → mark item as 'ancestor'.
    //
    // This does NOT use aliasing; IBT shop logic comes afterwards.
    //
    foreach ( $items as &$item ) {

        // Skip homepage, because "/" is a prefix of everything
        if ( $item['path'] === '/' ) {
            continue;
        }

        // Don’t override exact matches
        if ( $item['state'] === 'active' ) {
            continue;
        }

        // Pure prefix: /foo/bar matches /foo
        if ( str_starts_with( $current_path, $item['path'] . '/' ) ) {
            $item['state'] = 'ancestor';
        }
    }
    unset( $item );


    // =====================================================================
    // 2B. IBT SPECIAL CASE — SHOP SECTION ROOT LOGIC
    // =====================================================================
    //
    // Books tab lives at:          /shop/category/books
    // Shop section root lives at:  /shop
    //
    // Correct behaviour:
    //   • exact active match ONLY when current = /shop/category/books
    //   • ancestor when current = /shop or /shop/*
    //
    // No other menu item is affected.
    //
    foreach ( $items as &$item ) {

        if ( $item['path'] === '/shop/category/books' ) {

            // If it's NOT the exact active match
            if ( $item['state'] !== 'active' ) {

                // /shop or anything inside /shop/... should mark Books as ancestor
                if ( $current_path === '/shop' || str_starts_with( $current_path, '/shop/' ) ) {
                    $item['state'] = 'ancestor';
                }
            }
        }
    }
    unset( $item );



    // =====================================================================
    // 3. SECTION ROOT MAPPINGS (ALIASES)
    //
    //    Example rule:
    //        '/shop/category/books'  →  '/shop'
    //
    //    “If current path begins with the ALIAS source, mark the menu
    //     item that matches the ALIAS TARGET as 'ancestor'.”
    //
    //    This lets us support IA oddities cleanly.
    // =====================================================================
    $section_roots = [
        '/shop' => '/shop/category/books',
    ];

    foreach ( $section_roots as $root => $menu_path ) {

        // Does the current path live under the real root?
        if ( $current_path === $root || str_starts_with( $current_path, $root . '/' ) ) {

            // Find the menu item whose path equals the menu_path
            foreach ( $items as &$item ) {
                if ( $item['path'] === $menu_path ) {
                    if ( $item['state'] !== 'active' ) {
                        $item['state'] = 'ancestor';
                    }
                }
            }
            unset( $item );
        }
    }


    // =====================================================================
    // 4. VIRTUAL ANCESTOR FOR PARENT <li> ELEMENTS
    //
    //    “A parent <li class='has-child'> becomes an ancestor if
    //     ANY of its child <a> items are active or ancestor.”
    //
    //    This is how the top-level menu “More…” gets highlighted.
    // =====================================================================
    foreach ( $parents as $p_index => &$parent_li ) {

        // Look for items whose parent_index matches this LI
        foreach ( $items as $item ) {
            if (
                $item['parent_index'] === $p_index &&
                ($item['state'] === 'active' || $item['state'] === 'ancestor')
            ) {
                $parent_li['state'] = 'ancestor';
                break; // stop scanning children
            }
        }
    }
    unset( $parent_li );


    // ---------------------------------------------------------------------
    // Save back into state
    // ---------------------------------------------------------------------
    $state['items']   = $items;
    $state['parents'] = $parents;


    // DEBUG: state assignment summary
    $active_count = 0;
    $ancestor_count = 0;
    foreach ($items as $it) {
        if ($it['state'] === 'active')   $active_count++;
        if ($it['state'] === 'ancestor') $ancestor_count++;
    }

    // error_log("IBT NAV DEBUG — detect_states: active=$active_count ancestor=$ancestor_count");

    return $state;
}

// *************************************************************************************

/**
 * 5. Inject data-ibt-state back into HTML
 */
function ibt_nav_apply_states( $state ) {

    $content = $state['content'];
    $items   = $state['items'];
    $parents = $state['parents'];

    // =====================================================================
    // 1. APPLY DATA ATTRIBUTES TO <a> TAGS
    //
    //    “For each menu item:
    //       If it has a state (‘active’ or ‘ancestor’),
    //       replace its <a ...> opening tag with the same tag
    //       plus data-ibt-state="...".
    //
    //     This does NOT modify the closing </a>, only the opener.”
    // =====================================================================

    foreach ( $items as $item ) {

        if ( empty( $item['state'] ) ) {
            continue;   // skip items with no state
        }

        $old = $item['full'];

        // -------------------------------------------------------------
        // Build the new tag:
        //     If data-ibt-state already exists (it shouldn't),
        //     replace it. Otherwise inject before '>'.
        // -------------------------------------------------------------
        if ( preg_match( '/\sdata-ibt-state="/i', $old ) ) {

            // Replace existing
            $new = preg_replace(
                '/data-ibt-state="[^"]*"/i',
                'data-ibt-state="' . $item['state'] . '"',
                $old,
                1
            );

        } else {

            // Inject new attribute before close bracket
            $new = preg_replace(
                '/>$/',
                ' data-ibt-state="' . $item['state'] . '">',
                $old,
                1
            );
        }

        // Apply replacement in the global HTML
        $content = str_replace( $old, $new, $content );
    }


    // =====================================================================
    // 2. APPLY DATA ATTRIBUTES TO <li class="has-child"> PARENTS
    //
    //    NEW BEHAVIOUR:
    //    - We now REPLACE ONLY THE <li ...> OPENER, not the full block.
    //    - This avoids the fragile “full text match” problem.
    // =====================================================================

    foreach ( $parents as $parent ) {

        if ( empty( $parent['state'] ) ) {
            continue; // highlight only ancestor parents
        }

        // The opener we extracted during parsing
        $old_open = $parent['open'];

        // Build new opener with data-ibt-state
        $new_open = preg_replace(
            '/<li\b([^>]*)>/i',
            '<li$1 data-ibt-state="ancestor">',
            $old_open,
            1
        );

        // DEBUG
        // error_log('IBT NAV DEBUG — parent opener replaced');

        // Replace only the opener, safe and reliable
        $content = str_replace( $old_open, $new_open, $content );
    }



    // Save the updated content back into state
    $state['content'] = $content;
    return $state;
}