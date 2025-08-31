<?php
/**
 * Storage Limit Manager Uninstall Script
 * 
 * This file is executed when the plugin is uninstalled (deleted) from WordPress.
 * It cleans up all plugin data from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('slm_settings');
delete_option('slm_usage_data');

// For multisite installations, delete options from all sites
if (is_multisite()) {
    global $wpdb;
    
    // Get all blog IDs
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        
        // Delete options for this site
        delete_option('slm_settings');
        delete_option('slm_usage_data');
        
        restore_current_blog();
    }
}

// Clear any cached data
wp_cache_flush();
?>
