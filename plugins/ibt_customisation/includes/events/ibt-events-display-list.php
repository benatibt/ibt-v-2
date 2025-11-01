<?php
/* -------------------------------------------------------------------------
   IBT EVENTS DISPLAY – LIST
   -------------------------------------------------------------------------
   Purpose:
   - Render lists of events for use in shortcode.
   - Handles featured-event substitution and list layout markup.

   Notes:
   - Uses ibt_events_get_field() for data access and formatting.
   - Returns complete HTML strings; never echoes.
   - No block or query registration — purely display logic.
------------------------------------------------------------------------- */

if ( ! defined( 'ABSPATH' ) ) exit;
