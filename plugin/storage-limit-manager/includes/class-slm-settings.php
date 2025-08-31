<?php

/**
 * Settings Manager for Storage Limit Manager
 *
 * @package StorageLimitManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SLM_Settings Class
 */
class SLM_Settings
{
    /**
     * Settings option name
     *
     * @var string
     */
    private $option_name = 'slm_settings';

    /**
     * Default settings
     *
     * @var array
     */
    private $default_settings = array(
        'max_storage_mb' => 1000, // 1GB default
        'show_progress_bar' => true,
        'block_uploads' => true
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        // Settings are initialized in the main class
    }

    /**
     * Get option name
     *
     * @return string
     */
    public function get_option_name()
    {
        return $this->option_name;
    }

    /**
     * Get all settings
     *
     * @return array Settings array
     */
    public function get_settings()
    {
        $settings = get_option($this->option_name, array());
        return wp_parse_args($settings, $this->default_settings);
    }

    /**
     * Get a specific setting
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed Setting value
     */
    public function get_setting($key, $default = null)
    {
        $settings = $this->get_settings();
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        if ($default !== null) {
            return $default;
        }
        
        return isset($this->default_settings[$key]) ? $this->default_settings[$key] : null;
    }

    /**
     * Update a specific setting
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool True on success
     */
    public function update_setting($key, $value)
    {
        $settings = $this->get_settings();
        $settings[$key] = $value;
        
        $sanitized_settings = $this->sanitize_settings($settings);
        $result = update_option($this->option_name, $sanitized_settings);
        
        if ($result) {
            do_action('slm_setting_updated', $key, $value, $sanitized_settings);
        }
        
        return $result;
    }

    /**
     * Update multiple settings
     *
     * @param array $new_settings Array of settings to update
     * @return bool True on success
     */
    public function update_settings($new_settings)
    {
        $current_settings = $this->get_settings();
        $updated_settings = wp_parse_args($new_settings, $current_settings);
        
        $sanitized_settings = $this->sanitize_settings($updated_settings);
        $result = update_option($this->option_name, $sanitized_settings);
        
        if ($result) {
            do_action('slm_settings_updated', $sanitized_settings, $current_settings);
        }
        
        return $result;
    }

    /**
     * Set default settings (used during activation)
     */
    public function set_default_settings()
    {
        if (!get_option($this->option_name)) {
            add_option($this->option_name, $this->default_settings);
            do_action('slm_default_settings_set', $this->default_settings);
        }
    }

    /**
     * Reset settings to defaults
     *
     * @return bool True on success
     */
    public function reset_to_defaults()
    {
        $result = update_option($this->option_name, $this->default_settings);
        
        if ($result) {
            do_action('slm_settings_reset', $this->default_settings);
        }
        
        return $result;
    }

    /**
     * Sanitize settings
     *
     * @param array $input Raw input settings
     * @return array Sanitized settings
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // Sanitize max storage MB
        if (isset($input['max_storage_mb'])) {
            $sanitized['max_storage_mb'] = absint($input['max_storage_mb']);
            
            // Ensure minimum value
            if ($sanitized['max_storage_mb'] < 1) {
                $sanitized['max_storage_mb'] = 1;
                add_settings_error(
                    $this->option_name,
                    'max_storage_mb_min',
                    __('Maximum storage must be at least 1 MB.', 'storage-limit-manager'),
                    'error'
                );
            }
            
            // Warn about very large values
            if ($sanitized['max_storage_mb'] > 1000000) { // 1TB
                add_settings_error(
                    $this->option_name,
                    'max_storage_mb_large',
                    __('Warning: Very large storage limit detected. Please ensure this is intentional.', 'storage-limit-manager'),
                    'warning'
                );
            }
        } else {
            $sanitized['max_storage_mb'] = $this->default_settings['max_storage_mb'];
        }

        // Sanitize show progress bar
        $sanitized['show_progress_bar'] = isset($input['show_progress_bar']) ? true : false;

        // Sanitize block uploads
        $sanitized['block_uploads'] = isset($input['block_uploads']) ? true : false;

        // Allow other plugins to modify sanitized settings
        $sanitized = apply_filters('slm_sanitize_settings', $sanitized, $input);

        return $sanitized;
    }

    /**
     * Get storage limit in bytes
     *
     * @return int Storage limit in bytes
     */
    public function get_storage_limit_bytes()
    {
        $max_storage_mb = $this->get_setting('max_storage_mb');
        return $max_storage_mb * 1024 * 1024;
    }

    /**
     * Check if progress bar should be shown
     *
     * @return bool True if progress bar should be shown
     */
    public function show_progress_bar()
    {
        return (bool) $this->get_setting('show_progress_bar');
    }

    /**
     * Check if uploads should be blocked when limit is exceeded
     *
     * @return bool True if uploads should be blocked
     */
    public function block_uploads_on_limit()
    {
        return (bool) $this->get_setting('block_uploads');
    }

    /**
     * Get settings for JavaScript
     *
     * @return array Settings formatted for JavaScript
     */
    public function get_js_settings()
    {
        $settings = $this->get_settings();
        
        return array(
            'maxStorageMB' => $settings['max_storage_mb'],
            'maxStorageBytes' => $this->get_storage_limit_bytes(),
            'showProgressBar' => $settings['show_progress_bar'],
            'blockUploads' => $settings['block_uploads'],
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('slm_nonce')
        );
    }

    /**
     * Export settings
     *
     * @return array Settings for export
     */
    public function export_settings()
    {
        $settings = $this->get_settings();
        
        return array(
            'version' => SLM_VERSION,
            'exported_at' => current_time('mysql'),
            'settings' => $settings
        );
    }

    /**
     * Import settings
     *
     * @param array $import_data Import data
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function import_settings($import_data)
    {
        // Validate import data structure
        if (!is_array($import_data) || !isset($import_data['settings'])) {
            return new WP_Error('invalid_import', __('Invalid import data format.', 'storage-limit-manager'));
        }

        // Check version compatibility (optional warning)
        if (isset($import_data['version']) && version_compare($import_data['version'], SLM_VERSION, '>')) {
            add_settings_error(
                $this->option_name,
                'version_mismatch',
                __('Warning: Import data is from a newer version of the plugin.', 'storage-limit-manager'),
                'warning'
            );
        }

        // Sanitize and update settings
        $sanitized_settings = $this->sanitize_settings($import_data['settings']);
        $result = update_option($this->option_name, $sanitized_settings);

        if ($result) {
            do_action('slm_settings_imported', $sanitized_settings, $import_data);
            return true;
        }

        return new WP_Error('import_failed', __('Failed to import settings.', 'storage-limit-manager'));
    }

    /**
     * Delete all settings
     *
     * @return bool True on success
     */
    public function delete_settings()
    {
        $result = delete_option($this->option_name);
        
        if ($result) {
            do_action('slm_settings_deleted');
        }
        
        return $result;
    }

    /**
     * Get default settings
     *
     * @return array Default settings
     */
    public function get_default_settings()
    {
        return $this->default_settings;
    }

    /**
     * Check if settings exist
     *
     * @return bool True if settings exist
     */
    public function settings_exist()
    {
        return get_option($this->option_name) !== false;
    }
}
