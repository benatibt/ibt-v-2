<?php
/**
 * IBT Navigation States
 * ---------------------
 * Marks up navigation markup with data-ibt-state attributes so the
 * card-index style header can highlight active / ancestor sections.
 *
 * Scope:
 * - Applies only to the desktop header navigation block.
 * - Adds data-ibt-state="active" or "ancestor" to <a> elements.
 * - Adds data-ibt-state="ancestor" to <li class="... has-child ...">
 *   when any submenu item beneath it is active/ancestor (e.g. "More").
 */

defined( 'ABSPATH' ) || exit;

/**
 * Filter: add data-ibt-state attributes to the desktop navigation block.
 *
 * @param string $content Rendered block HTML.
 * @param array  $block   Block data.
 *
 * @return string
 */
function ibt_nav_states_render( $content, $block ) {
    // Only care about navigation blocks.
    if ( empty( $block['blockName'] ) || $block['blockName'] !== 'core/navigation' ) {
        return $content;
    }

    // Restrict to the desktop header navigation only.
    $class = $block['attrs']['className'] ?? '';
    if ( strpos( $class, 'ibt-header-nav-desktop' ) === false ) {
        return $content;
    }

    // ---------------------------------------------------------------------
    // 1. Determine current request path (/shop, /more/about, /)
    // ---------------------------------------------------------------------
    $request_uri   = $_SERVER['REQUEST_URI'] ?? '/';
    $original_path = parse_url( $request_uri, PHP_URL_PATH ) ?? '/';
    $original_path = strtolower( rtrim( $original_path, '/' ) );

    if ( $original_path === '' ) {
        $original_path = '/';
    }

    $current_path = $original_path;

    // ---------------------------------------------------------------------
    // 2. Gather all internal nav anchors (<a class="...wp-block-navigation-item__content...">)
    // ---------------------------------------------------------------------
    if ( ! preg_match_all( '/<a[^>]*>/i', $content, $all_a_tags ) ) {
        return $content;
    }

    $anchors = [];

    foreach ( $all_a_tags[0] as $tag ) {
        // Only navigation item content anchors.
        if ( ! preg_match( '/class="[^"]*\bwp-block-navigation-item__content\b[^"]*"/i', $tag ) ) {
            continue;
        }

        if ( ! preg_match( '/href="([^"]+)"/i', $tag, $m ) ) {
            continue;
        }

        $href = $m[1];

        // Only handle internal URLs.
        $is_internal = str_starts_with( $href, '/' ) || str_starts_with( $href, home_url( '/' ) );
        if ( ! $is_internal ) {
            continue;
        }

        $link_path = parse_url( $href, PHP_URL_PATH ) ?? '/';
        $link_path = strtolower( rtrim( $link_path, '/' ) );
        if ( $link_path === '' ) {
            $link_path = '/';
        }

        $anchors[] = [
            'full' => $tag,   // full <a ...> tag as rendered
            'href' => $href,
            'path' => $link_path,
        ];
    }

    // Section root mappings: real section root => menu link path
    $section_roots = [
        '/shop' => '/shop/category/books',
    ];

    if ( empty( $anchors ) ) {
        return $content;
    }

    // Helper: add or replace data-ibt-state="X" on an opening <a ...> tag.
    $add_state_to_anchor = static function ( string $tag, string $state ) {
        if ( preg_match( '/\sdata-ibt-state="/i', $tag ) ) {
            // Replace existing value.
            return preg_replace(
                '/data-ibt-state="[^"]*"/i',
                'data-ibt-state="' . $state . '"',
                $tag,
                1
            );
        }

        // Inject new attribute just before closing angle bracket.
        return preg_replace( '/>$/', ' data-ibt-state="' . $state . '">', $tag, 1 );
    };

    $state = 'none';

    // ---------------------------------------------------------------------
    // 3. Exact match → active
    // ---------------------------------------------------------------------
    foreach ( $anchors as $a ) {
        if ( $a['path'] === $current_path ) {
            $content = str_replace(
                $a['full'],
                $add_state_to_anchor( $a['full'], 'active' ),
                $content
            );
            $state = 'active';
            break; // One active match is enough.
        }
    }

    // ---------------------------------------------------------------------
    // 4. Ancestor match (exact prefix OR mapped section root)
    // ---------------------------------------------------------------------

    foreach ( $anchors as $a ) {

        if ( $a['path'] === '/' || $a['path'] === '' ) {
            continue;
        }

        $anchor_path = $a['path'];

        // 4A: Exact prefix match: /foo/... matches /foo
        if ( str_starts_with( $current_path, $anchor_path . '/' ) ) {
            $content = str_replace(
                $a['full'],
                $add_state_to_anchor( $a['full'], 'ancestor' ),
                $content
            );
            // IMPORTANT: do NOT break — multiple ancestors possible
            continue;
        }

        // 4B: Section-root mapping: current /shop/... belongs to section /shop
        foreach ( $section_roots as $root => $menu_path ) {

            if (
                $anchor_path === $menu_path &&
                $current_path === $root || str_starts_with( $current_path, $root . '/' )
            ) {
                $content = str_replace(
                    $a['full'],
                    $add_state_to_anchor( $a['full'], 'ancestor' ),
                    $content
                );
                break 2;
            }
        }
    }



    // ---------------------------------------------------------------------
    // 5. Virtual ancestor at <li> level for submenu parents (e.g. "More")
    //
    // For any <li ... has-child ...> that contains an anchor marked
    // data-ibt-state="active" or "ancestor" in its submenu, set the
    // parent <li> to data-ibt-state="ancestor".
    //
    // This replaces the old :has() CSS hack with server-side markup.
    // ---------------------------------------------------------------------
    if ( preg_match_all( '/<li[^>]*\bhas-child\b[^>]*>.*?<\/li>/is', $content, $li_matches ) ) {
        foreach ( $li_matches[0] as $li_block ) {
            // Extract the submenu <ul> for this parent
            if ( preg_match('/<ul[^>]*class="[^"]*wp-block-navigation__submenu-container[^"]*"[^>]*>([\s\S]*?)<\/ul>/i', $li_block, $ul_match) ) {
                $submenu_html = $ul_match[1];
            } else {
                continue; // no submenu found
            }

            // Only continue if submenu has an active/ancestor link
            if ( ! preg_match('/data-ibt-state="(?:active|ancestor)"/i', $submenu_html) ) {
                continue;
            }


            $new_li_block = $li_block;

            if ( preg_match( '/\sdata-ibt-state="/i', $li_block ) ) {
                // If the <li> already has data-ibt-state, normalise it to "ancestor".
                $new_li_block = preg_replace(
                    '/data-ibt-state="[^"]*"/i',
                    'data-ibt-state="ancestor"',
                    $li_block,
                    1
                );
            } else {
                // Inject data-ibt-state="ancestor" into the opening <li ...> tag.
                $new_li_block = preg_replace(
                    '/<li\b([\s\S]*?)>/i',
                    '<li$1 data-ibt-state="ancestor">',
                    $li_block,
                    1
                );
            }

            if ( $new_li_block !== $li_block ) {
                $content = str_replace( $li_block, $new_li_block, $content );
            }
        }
    }

    // Optional: uncomment for troubleshooting.
    // error_log( '[IBT NAV] state=' . $state . ' path=' . $current_path );

    return $content;
}

add_filter( 'render_block', 'ibt_nav_states_render', 10, 2 );
