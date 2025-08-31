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
 * Main Storage Limit Manager Class
 */
class StorageLimitManager
{

    private $option_name = 'slm_settings';
    private $usage_option = 'slm_usage_data';

    public function __construct()
    {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('admin_notices', array($this, 'display_storage_bar'));
        }

        // Upload hooks
        add_filter('wp_handle_upload_prefilter', array($this, 'check_upload_limit'));
        add_action('add_attachment', array($this, 'update_usage_on_upload'));
        add_action('delete_attachment', array($this, 'update_usage_on_delete'));

        // AJAX hooks
        add_action('wp_ajax_slm_recalculate_usage', array($this, 'ajax_recalculate_usage'));
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Set default options
        $default_settings = array(
            'max_storage_mb' => 1000, // 1GB default
            'show_progress_bar' => true,
            'block_uploads' => true
        );

        if (!get_option($this->option_name)) {
            add_option($this->option_name, $default_settings);
        }

        // Calculate initial usage
        $this->recalculate_total_usage();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Clean up if needed
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_options_page(
            'Storage Limit Manager',
            'Storage Limits',
            'manage_options',
            'storage-limit-manager',
            array($this, 'admin_page')
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init()
    {
        register_setting('slm_settings_group', $this->option_name, array($this, 'sanitize_settings'));

        add_settings_section(
            'slm_main_section',
            'Storage Limit Settings',
            array($this, 'section_callback'),
            'storage-limit-manager'
        );

        add_settings_field(
            'max_storage_mb',
            'Maximum Storage (MB)',
            array($this, 'max_storage_callback'),
            'storage-limit-manager',
            'slm_main_section'
        );

        add_settings_field(
            'show_progress_bar',
            'Show Progress Bar',
            array($this, 'show_progress_bar_callback'),
            'storage-limit-manager',
            'slm_main_section'
        );

        add_settings_field(
            'block_uploads',
            'Block Uploads When Limit Exceeded',
            array($this, 'block_uploads_callback'),
            'storage-limit-manager',
            'slm_main_section'
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only load on relevant admin pages
        $relevant_pages = array('upload.php', 'media-new.php', 'post.php', 'post-new.php', 'settings_page_storage-limit-manager');

        if (in_array($hook, $relevant_pages) || strpos($hook, 'storage-limit-manager') !== false) {
            wp_enqueue_style('slm-admin-style', SLM_PLUGIN_URL . 'assets/admin-style.css', array(), SLM_VERSION);
            wp_enqueue_script('slm-admin-script', SLM_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), SLM_VERSION, true);

            wp_localize_script('slm-admin-script', 'slm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('slm_nonce')
            ));
        }
    }

    /**
     * Check upload limit before processing
     */
    public function check_upload_limit($file)
    {
        $settings = get_option($this->option_name);

        if (!$settings['block_uploads']) {
            return $file;
        }

        $max_storage_bytes = $settings['max_storage_mb'] * 1024 * 1024;
        $current_usage = $this->get_current_usage();
        $file_size = $file['size'];

        if (($current_usage + $file_size) > $max_storage_bytes) {
            $file['error'] = sprintf(
                'Upload failed: This file would exceed your storage limit of %s MB. Current usage: %s MB. Please upgrade your plan or delete some files to free up space.',
                number_format($settings['max_storage_mb']),
                number_format($current_usage / (1024 * 1024), 2)
            );
        }

        return $file;
    }

    /**
     * Update usage when file is uploaded
     */
    public function update_usage_on_upload($attachment_id)
    {
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $file_size = filesize($file_path);
            $current_usage = $this->get_current_usage();
            $this->update_usage_data($current_usage + $file_size);
        }
    }

    /**
     * Update usage when file is deleted
     */
    public function update_usage_on_delete($attachment_id)
    {
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $file_size = filesize($file_path);
            $current_usage = $this->get_current_usage();
            $this->update_usage_data(max(0, $current_usage - $file_size));
        }
    }

    /**
     * Get current storage usage
     */
    public function get_current_usage()
    {
        $usage_data = get_option($this->usage_option);
        return isset($usage_data['total_bytes']) ? $usage_data['total_bytes'] : 0;
    }

    /**
     * Update usage data
     */
    private function update_usage_data($total_bytes)
    {
        $usage_data = array(
            'total_bytes' => $total_bytes,
            'last_updated' => current_time('timestamp')
        );
        update_option($this->usage_option, $usage_data);
    }

    /**
     * Recalculate total usage by scanning all attachments
     */
    public function recalculate_total_usage()
    {
        $total_size = 0;

        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'inherit'
        ));

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            if ($file_path && file_exists($file_path)) {
                $total_size += filesize($file_path);
            }
        }

        $this->update_usage_data($total_size);
        return $total_size;
    }

    /**
     * AJAX handler for recalculating usage
     */
    public function ajax_recalculate_usage()
    {
        check_ajax_referer('slm_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $total_size = $this->recalculate_total_usage();

        wp_send_json_success(array(
            'total_size' => $total_size,
            'formatted_size' => $this->format_bytes($total_size)
        ));
    }

    /**
     * Display storage progress bar in admin
     */
    public function display_storage_bar()
    {
        $settings = get_option($this->option_name);

        if (!$settings['show_progress_bar']) {
            return;
        }

        $screen = get_current_screen();
        $show_on_screens = array('upload', 'media', 'post', 'page', 'settings_page_storage-limit-manager');

        if (!in_array($screen->base, $show_on_screens)) {
            return;
        }

        $current_usage = $this->get_current_usage();
        $max_storage_bytes = $settings['max_storage_mb'] * 1024 * 1024;
        $percentage = ($current_usage / $max_storage_bytes) * 100;
        $percentage = min(100, $percentage);

        $bar_class = 'slm-progress-normal';
        if ($percentage >= 90) {
            $bar_class = 'slm-progress-critical';
        } elseif ($percentage >= 75) {
            $bar_class = 'slm-progress-warning';
        }

        echo '<div class="notice notice-info slm-storage-notice">';
        echo '<div class="slm-storage-info">';
        echo '<h4>Storage Usage</h4>';
        echo '<div class="slm-progress-container">';
        echo '<div class="slm-progress-bar ' . $bar_class . '" style="width: ' . $percentage . '%"></div>';
        echo '</div>';
        echo '<p class="slm-usage-text">';
        echo sprintf(
            'Used: %s / %s (%s%%) | Remaining: %s',
            $this->format_bytes($current_usage),
            $this->format_bytes($max_storage_bytes),
            number_format($percentage, 1),
            $this->format_bytes($max_storage_bytes - $current_usage)
        );
        echo '</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Format bytes to human readable format
     */
    private function format_bytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        $sanitized['max_storage_mb'] = absint($input['max_storage_mb']);
        $sanitized['show_progress_bar'] = isset($input['show_progress_bar']) ? true : false;
        $sanitized['block_uploads'] = isset($input['block_uploads']) ? true : false;

        return $sanitized;
    }

    // Settings callbacks
    public function section_callback()
    {
        echo '<p>Configure storage limits and display options for your WordPress site.</p>';
    }

    public function max_storage_callback()
    {
        $settings = get_option($this->option_name);
        echo '<input type="number" name="' . $this->option_name . '[max_storage_mb]" value="' . $settings['max_storage_mb'] . '" min="1" />';
        echo '<p class="description">Maximum storage allowed in megabytes (MB). 1000 MB = 1 GB</p>';
    }

    public function show_progress_bar_callback()
    {
        $settings = get_option($this->option_name);
        echo '<input type="checkbox" name="' . $this->option_name . '[show_progress_bar]" value="1" ' . checked(1, $settings['show_progress_bar'], false) . ' />';
        echo '<label>Display storage progress bar in admin area</label>';
    }

    public function block_uploads_callback()
    {
        $settings = get_option($this->option_name);
        echo '<input type="checkbox" name="' . $this->option_name . '[block_uploads]" value="1" ' . checked(1, $settings['block_uploads'], false) . ' />';
        echo '<label>Block uploads when storage limit is exceeded</label>';
    }

    /**
     * Admin page content
     */
    public function admin_page()
    {
        $settings = get_option($this->option_name);
        $current_usage = $this->get_current_usage();
        $max_storage_bytes = $settings['max_storage_mb'] * 1024 * 1024;
        $percentage = ($current_usage / $max_storage_bytes) * 100;
        $percentage = min(100, $percentage);

?>
        <div class="wrap">
            <h1>Storage Limit Manager</h1>

            <div class="slm-admin-container">
                <!-- Usage Statistics -->
                <div class="slm-usage-stats">
                    <h2>Current Usage Statistics</h2>
                    <div class="slm-stats-grid">
                        <div class="slm-stat-item">
                            <h3>Total Used</h3>
                            <p class="slm-stat-value"><?php echo $this->format_bytes($current_usage); ?></p>
                        </div>
                        <div class="slm-stat-item">
                            <h3>Storage Limit</h3>
                            <p class="slm-stat-value"><?php echo $this->format_bytes($max_storage_bytes); ?></p>
                        </div>
                        <div class="slm-stat-item">
                            <h3>Remaining</h3>
                            <p class="slm-stat-value"><?php echo $this->format_bytes($max_storage_bytes - $current_usage); ?></p>
                        </div>
                        <div class="slm-stat-item">
                            <h3>Usage Percentage</h3>
                            <p class="slm-stat-value"><?php echo number_format($percentage, 1); ?>%</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="slm-progress-section">
                        <h3>Storage Usage</h3>
                        <div class="slm-progress-container slm-admin-progress">
                            <?php
                            $bar_class = 'slm-progress-normal';
                            if ($percentage >= 90) {
                                $bar_class = 'slm-progress-critical';
                            } elseif ($percentage >= 75) {
                                $bar_class = 'slm-progress-warning';
                            }
                            ?>
                            <div class="slm-progress-bar <?php echo $bar_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <p class="slm-progress-text">
                            <?php echo sprintf(
                                '%s of %s used (%s%%)',
                                $this->format_bytes($current_usage),
                                $this->format_bytes($max_storage_bytes),
                                number_format($percentage, 1)
                            ); ?>
                        </p>
                    </div>

                    <button type="button" id="slm-recalculate" class="button button-secondary">
                        Recalculate Usage
                    </button>
                    <span id="slm-recalculate-status"></span>
                </div>

                <!-- Settings Form -->
                <div class="slm-settings-form">
                    <h2>Settings</h2>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('slm_settings_group');
                        do_settings_sections('storage-limit-manager');
                        submit_button();
                        ?>
                    </form>
                </div>
            </div>
        </div>
<?php
    }
}

// Initialize the plugin
new StorageLimitManager();
