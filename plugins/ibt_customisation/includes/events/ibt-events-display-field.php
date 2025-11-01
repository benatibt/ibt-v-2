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

