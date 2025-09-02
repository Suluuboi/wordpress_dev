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
        $main_page = add_options_page(
            __('Storage Limit Manager', 'storage-limit-manager'),
            __('Storage Limits', 'storage-limit-manager'),
            'manage_options',
            'storage-limit-manager',
            array($this, 'admin_page')
        );

        // Add integrations submenu
        add_submenu_page(
            'options-general.php',
            __('Plugin Integrations - Storage Limit Manager', 'storage-limit-manager'),
            __('Plugin Integrations', 'storage-limit-manager'),
            'manage_options',
            'storage-limit-integrations',
            array($this, 'integrations_page')
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
        echo '<h4>' . __('Storage Usage (Hans)', 'storage-limit-manager') . '</h4>';
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

    /**
     * Plugin integrations page content
     */
    public function integrations_page()
    {
        $plugin_integrations = StorageLimitManager::instance()->plugin_integrations;
        $integrated_plugins = $plugin_integrations->get_integrated_plugins();

?>
        <div class="wrap">
            <h1><?php _e('Plugin Integrations - Storage Limit Manager', 'storage-limit-manager'); ?></h1>

            <div class="notice notice-info">
                <p><?php _e('This page shows which plugins are integrated with Storage Limit Manager to ensure upload limits are enforced across all plugins.', 'storage-limit-manager'); ?></p>
            </div>

            <div class="slm-integrations-grid">
                <div class="slm-integration-card">
                    <h3><?php _e('Active Integrations', 'storage-limit-manager'); ?></h3>

                    <?php if (!empty($integrated_plugins)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Plugin', 'storage-limit-manager'); ?></th>
                                    <th><?php _e('Version', 'storage-limit-manager'); ?></th>
                                    <th><?php _e('Status', 'storage-limit-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($integrated_plugins as $plugin_key => $plugin_data): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($plugin_data['name']); ?></strong></td>
                                        <td><?php echo esc_html($plugin_data['version']); ?></td>
                                        <td>
                                            <span class="slm-status-badge slm-status-<?php echo esc_attr($plugin_data['status']); ?>">
                                                <?php echo esc_html(ucfirst($plugin_data['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><?php _e('No integrated plugins detected. Install and activate supported plugins to see them here.', 'storage-limit-manager'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="slm-integration-card">
                    <h3><?php _e('Supported Plugins', 'storage-limit-manager'); ?></h3>
                    <ul class="slm-supported-plugins">
                        <li><strong>Elementor</strong> - <?php _e('Page builder with media uploads', 'storage-limit-manager'); ?></li>
                        <li><strong>WooCommerce</strong> - <?php _e('Product image uploads', 'storage-limit-manager'); ?></li>
                        <li><strong>Contact Form 7</strong> - <?php _e('File upload fields', 'storage-limit-manager'); ?></li>
                        <li><strong>Gravity Forms</strong> - <?php _e('File upload fields', 'storage-limit-manager'); ?></li>
                        <li><strong>WP Bakery</strong> - <?php _e('Page builder uploads', 'storage-limit-manager'); ?></li>
                        <li><strong>Beaver Builder</strong> - <?php _e('Page builder uploads', 'storage-limit-manager'); ?></li>
                        <li><strong>Divi</strong> - <?php _e('Theme builder uploads', 'storage-limit-manager'); ?></li>
                    </ul>
                </div>

                <div class="slm-integration-card">
                    <h3><?php _e('How It Works', 'storage-limit-manager'); ?></h3>
                    <p><?php _e('Storage Limit Manager automatically detects and integrates with supported plugins to ensure that all file uploads respect your storage limits, regardless of which plugin initiates the upload.', 'storage-limit-manager'); ?></p>

                    <h4><?php _e('Integration Features:', 'storage-limit-manager'); ?></h4>
                    <ul>
                        <li><?php _e('Automatic upload blocking when limits are exceeded', 'storage-limit-manager'); ?></li>
                        <li><?php _e('Real-time storage usage tracking', 'storage-limit-manager'); ?></li>
                        <li><?php _e('Consistent error messages across all plugins', 'storage-limit-manager'); ?></li>
                        <li><?php _e('Client-side validation for better user experience', 'storage-limit-manager'); ?></li>
                    </ul>
                </div>
            </div>

            <style>
                .slm-integrations-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }

                .slm-integration-card {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    padding: 20px;
                }

                .slm-integration-card h3 {
                    margin-top: 0;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }

                .slm-status-badge {
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    font-weight: bold;
                }

                .slm-status-integrated {
                    background: #d4edda;
                    color: #155724;
                }

                .slm-supported-plugins {
                    list-style: none;
                    padding: 0;
                }

                .slm-supported-plugins li {
                    padding: 8px 0;
                    border-bottom: 1px solid #eee;
                }

                .slm-supported-plugins li:last-child {
                    border-bottom: none;
                }
            </style>
        </div>
<?php
    }
}
