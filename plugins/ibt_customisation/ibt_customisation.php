<?php
/**
 * Plugin Name: IBT Customisation
 * Description: Custom functionality for Islands Book Trust (WooCommerce extras, etc.).
 * Version: 0.1.0
 * Author: Ben Sheppard & ChatGPT
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** SETTINGS */
const IBT_BOOKS_CATEGORY_SLUG = 'books';

/** INCLUDE: custom product fields & author block */
require_once __DIR__ . '/includes/author-isbn-fields.php';