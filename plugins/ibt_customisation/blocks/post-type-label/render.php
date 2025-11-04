<?php

/* -------------------------------------------------------------------------
   IBT POST TYPE LABEL – RENDER CALLBACK
   -------------------------------------------------------------------------
   Purpose:
   - Displays the post type in human readable form as a h3 for inclusion
     in search results so user can understand search results.
------------------------------------------------------------------------- */


$post_id = get_the_ID();
if ( ! $post_id ) {
    return '';
}

$type = get_post_type( $post_id );

$map = array(
    'ibt_event' => 'Event',
    'product'   => 'Book',
    'library'   => 'Library',
    'post'      => 'News',
    'page'      => 'Page',
);

$label = $map[ $type ] ?? 'Other';

echo '<h3 class="ibt-search-type-label">'
   . esc_html( $label )
   . '</h3>';
