<?php

/**
 * Admin functionality for Storage Limit Manager
 *
 * @package StorageLimitManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SLM_Admin Class
 */
class SLM_Admin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'display_storage_bar'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_options_page(
            __('Storage Limit Manager', 'storage-limit-manager'),
            __('Storage Limits', 'storage-limit-manager'),
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
        $settings = StorageLimitManager::instance()->settings;
        
        register_setting('slm_settings_group', $settings->get_option_name(), array($settings, 'sanitize_settings'));

        add_settings_section(
            'slm_main_section',
            __('Storage Limit Settings', 'storage-limit-manager'),
            array($this, 'section_callback'),
            'storage-limit-manager'
        );

        add_settings_field(
            'max_storage_mb',
            __('Maximum Storage (MB)', 'storage-limit-manager'),
            array($this, 'max_storage_callback'),
            'storage-limit-manager',
            'slm_main_section'
        );

        add_settings_field(
            'show_progress_bar',
            __('Show Progress Bar', 'storage-limit-manager'),
            array($this, 'show_progress_bar_callback'),
            'storage-limit-manager',
            'slm_main_section'
        );

        add_settings_field(
            'block_uploads',
            __('Block Uploads When Limit Exceeded', 'storage-limit-manager'),
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
     * Display storage progress bar in admin
     */
    public function display_storage_bar()
    {
        $settings = StorageLimitManager::instance()->settings;
        $calculator = StorageLimitManager::instance()->storage_calculator;
        
        $settings_data = $settings->get_settings();

        if (!$settings_data['show_progress_bar']) {
            return;
        }

        $screen = get_current_screen();
        $show_on_screens = array('upload', 'media', 'post', 'page', 'settings_page_storage-limit-manager');

        if (!in_array($screen->base, $show_on_screens)) {
            return;
        }

        $current_usage = $calculator->get_current_usage();
        $max_storage_bytes = $settings_data['max_storage_mb'] * 1024 * 1024;
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
        echo '<h4>' . __('Storage Usage', 'storage-limit-manager') . '</h4>';
        echo '<div class="slm-progress-container">';
        echo '<div class="slm-progress-bar ' . esc_attr($bar_class) . '" style="width: ' . esc_attr($percentage) . '%"></div>';
        echo '</div>';
        echo '<p class="slm-usage-text">';
        echo sprintf(
            __('Used: %s / %s (%s%%) | Remaining: %s', 'storage-limit-manager'),
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

    // Settings callbacks
    public function section_callback()
    {
        echo '<p>' . __('Configure storage limits and display options for your WordPress site.', 'storage-limit-manager') . '</p>';
    }

    public function max_storage_callback()
    {
        $settings = StorageLimitManager::instance()->settings;
        $settings_data = $settings->get_settings();
        $option_name = $settings->get_option_name();
        
        echo '<input type="number" name="' . esc_attr($option_name) . '[max_storage_mb]" value="' . esc_attr($settings_data['max_storage_mb']) . '" min="1" />';
        echo '<p class="description">' . __('Maximum storage allowed in megabytes (MB). 1000 MB = 1 GB', 'storage-limit-manager') . '</p>';
    }

    public function show_progress_bar_callback()
    {
        $settings = StorageLimitManager::instance()->settings;
        $settings_data = $settings->get_settings();
        $option_name = $settings->get_option_name();
        
        echo '<input type="checkbox" name="' . esc_attr($option_name) . '[show_progress_bar]" value="1" ' . checked(1, $settings_data['show_progress_bar'], false) . ' />';
        echo '<label>' . __('Display storage progress bar in admin area', 'storage-limit-manager') . '</label>';
    }

    public function block_uploads_callback()
    {
        $settings = StorageLimitManager::instance()->settings;
        $settings_data = $settings->get_settings();
        $option_name = $settings->get_option_name();
        
        echo '<input type="checkbox" name="' . esc_attr($option_name) . '[block_uploads]" value="1" ' . checked(1, $settings_data['block_uploads'], false) . ' />';
        echo '<label>' . __('Block uploads when storage limit is exceeded', 'storage-limit-manager') . '</label>';
    }

    /**
     * Admin page content
     */
    public function admin_page()
    {
        $settings = StorageLimitManager::instance()->settings;
        $calculator = StorageLimitManager::instance()->storage_calculator;
        
        $settings_data = $settings->get_settings();
        $current_usage = $calculator->get_current_usage();
        $max_storage_bytes = $settings_data['max_storage_mb'] * 1024 * 1024;
        $percentage = ($current_usage / $max_storage_bytes) * 100;
        $percentage = min(100, $percentage);

        include SLM_PLUGIN_PATH . 'includes/admin/views/admin-page.php';
    }
}
