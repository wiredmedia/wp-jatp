<?php
/**
 * @package Internals
 *
 * Code used when the plugin is removed (not just deactivated but actively deleted through the WordPress Admin).
 */

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();

global $wpdb;
$options = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE option_name LIKE '%wired-twitter%'");
foreach ( $options as $option) {
  delete_option( $option->option_name );
}