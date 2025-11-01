<?php
/* -------------------------------------------------------------------------
   IBT EVENTS DISPLAY – FIELD
   -------------------------------------------------------------------------
   Purpose:
   - Render individual event fields for use in shortcodes and block templates.
   - Handles minimal HTML wrapping (<p>, buttons, etc.) around values
     returned from ibt_events_get_field() in helpers.

   Notes:
   - No queries or layout logic — only markup for a single field.
   - Returns strings; never echoes.
------------------------------------------------------------------------- */

if ( ! defined( 'ABSPATH' ) ) exit;


// -------------------------------------------------------------------------
// Render one event field with appropriate markup.
// Called by [ibt_event_field] shortcode via ibt-events-shortcodes.php.
// -------------------------------------------------------------------------
function ibt_events_render_field( $post_id, $key ) {
    if ( empty( $post_id ) || empty( $key ) ) {
        return '';
    }

    $value = ibt_events_get_field( $post_id, $key );
    if ( $value === '' ) {
        return '';
    }

    // Base class for all fields
    $class = 'ibt-event-field ibt-event-field--' . sanitize_html_class( str_replace( '_', '-', $key ) );

    switch ( $key ) {
        
        // Map Button → theme-styled button, URL from DB must be escaped
        // Do not add line breaks in code, sprintf will add them and WP will convert to <br>
        case 'ibt_event_map_button':
            return sprintf('<div class="wp-block-button is-style-primary-solid %1$s"><a class="wp-block-button__link wp-element-button" href="%2$s" target="_blank" rel="noopener">View on Google Maps</a></div>',
                esc_attr( $class ),
                esc_url( $value )
            );

        
        // Online flag → standalone paragraph (so it collapses when empty)
        case 'ibt_event_online':
            $trimmed = trim( $value );
            if ( $trimmed === '' ) {
                return '';
            }

            return sprintf(
                '<p class="%1$s ibt-event-field--online">%2$s</p>',
                esc_attr( $class ),
                esc_html( $trimmed )
            );


        // Default → inline span (no wrapping, template controls structure)
        default:
            $trimmed = trim( $value );

            // Detect if helper returned HTML (e.g. <p> or <br> tags for venue address)
            if ( $trimmed !== strip_tags( $trimmed ) ) {
                // Allow only safe tags (paragraphs, line breaks, emphasis, links)
                $content = wp_kses( $trimmed, array(
                    'p'   => array(),
                    'br'  => array(),
                    'em'  => array(),
                    'strong' => array(),
                    'a'   => array(
                        'href' => true,
                        'target' => true,
                        'rel' => true,
                        'class' => true,
                    ),
                ) );
            } else {
                // Plain text fallback
                $content = esc_html( $trimmed );
            }

            return sprintf(
                '<span class="%1$s">%2$s</span>',
                esc_attr( $class ),
                $content
            );


    }
}