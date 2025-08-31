<?php

/**
 * Plugin Name: Storage Limit Manager
 * Plugin URI: https://example.com/storage-limit-manager
 * Description: Enforces upload limits and displays usage statistics for WordPress media files. Set configurable storage limits and track usage with visual progress bars.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: storage-limit-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SLM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SLM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SLM_VERSION', '1.0.0');

/**
 * Main plugin initialization
 */
function slm_init()
{
    // Include the main plugin class
    require_once SLM_PLUGIN_PATH . 'includes/class-storage-limit-manager.php';

    // Initialize the plugin
    StorageLimitManager::instance();
}

// Initialize the plugin
slm_init();
