<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Ryokourent_Core
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up temporary options or transients if necessary.
// We preserve CPT motor and penyewaan posts by default to avoid accidental business data loss.
delete_transient('ryokourent_dashboard_stats');
