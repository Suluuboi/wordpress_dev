<?php

/**
 * AJAX Handler for Storage Limit Manager
 *
 * @package StorageLimitManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SLM_Ajax Class
 */
class SLM_Ajax
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_ajax_slm_recalculate_usage', array($this, 'recalculate_usage'));
        add_action('wp_ajax_slm_get_usage_stats', array($this, 'get_usage_stats'));
        add_action('wp_ajax_slm_check_upload_size', array($this, 'check_upload_size'));
        add_action('wp_ajax_slm_update_setting', array($this, 'update_setting'));
        add_action('wp_ajax_slm_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_slm_import_settings', array($this, 'import_settings'));
        add_action('wp_ajax_slm_repair_attachments', array($this, 'repair_attachments'));
    }

    /**
     * AJAX handler for recalculating usage
     */
    public function recalculate_usage()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $calculator = StorageLimitManager::instance()->storage_calculator;

        try {
            // Use force recalculation for manual button clicks
            $total_size = $calculator->force_recalculate_usage();
            $stats = $calculator->get_usage_statistics();

            wp_send_json_success(array(
                'total_size' => $total_size,
                'formatted_size' => $calculator->format_bytes($total_size),
                'stats' => $stats,
                'message' => __('Usage recalculated successfully.', 'storage-limit-manager')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error recalculating usage: ', 'storage-limit-manager') . $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler for getting current usage statistics
     */
    public function get_usage_stats()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $calculator = StorageLimitManager::instance()->storage_calculator;
        $stats = $calculator->get_usage_statistics();
        $file_stats = $calculator->get_file_count_statistics();

        // Check if auto-recalculation happened recently
        $auto_recalculated = get_transient('slm_auto_recalculated');
        if ($auto_recalculated) {
            delete_transient('slm_auto_recalculated');
            $stats['auto_recalculated'] = true;
        } else {
            $stats['auto_recalculated'] = false;
        }

        wp_send_json_success(array(
            'usage_stats' => $stats,
            'file_stats' => $file_stats,
            'storage_status' => $calculator->get_storage_status(),
            'auto_recalculated' => $stats['auto_recalculated'],
            'total_bytes' => $stats['total_bytes'],
            'formatted' => $stats['formatted']
        ));
    }

    /**
     * AJAX handler for checking if a file size can be uploaded
     */
    public function check_upload_size()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $file_size = isset($_POST['file_size']) ? absint($_POST['file_size']) : 0;

        if ($file_size <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid file size.', 'storage-limit-manager')
            ));
        }

        $upload_handler = StorageLimitManager::instance()->upload_handler;
        $result = $upload_handler->can_upload_file_size($file_size);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX handler for updating a single setting
     */
    public function update_setting()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $setting_key = sanitize_text_field($_POST['setting_key'] ?? '');
        $setting_value = $_POST['setting_value'] ?? '';

        if (empty($setting_key)) {
            wp_send_json_error(array(
                'message' => __('Setting key is required.', 'storage-limit-manager')
            ));
        }

        $settings = StorageLimitManager::instance()->settings;

        // Sanitize the value based on the setting type
        switch ($setting_key) {
            case 'max_storage_mb':
                $setting_value = absint($setting_value);
                break;
            case 'show_progress_bar':
            case 'block_uploads':
                $setting_value = (bool) $setting_value;
                break;
            default:
                $setting_value = sanitize_text_field($setting_value);
        }

        $result = $settings->update_setting($setting_key, $setting_value);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Setting updated successfully.', 'storage-limit-manager'),
                'setting_key' => $setting_key,
                'setting_value' => $setting_value
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update setting.', 'storage-limit-manager')
            ));
        }
    }

    /**
     * AJAX handler for exporting settings
     */
    public function export_settings()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $settings = StorageLimitManager::instance()->settings;
        $export_data = $settings->export_settings();

        wp_send_json_success(array(
            'export_data' => $export_data,
            'filename' => 'slm-settings-' . date('Y-m-d-H-i-s') . '.json',
            'message' => __('Settings exported successfully.', 'storage-limit-manager')
        ));
    }

    /**
     * AJAX handler for importing settings
     */
    public function import_settings()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $import_data = $_POST['import_data'] ?? '';

        if (empty($import_data)) {
            wp_send_json_error(array(
                'message' => __('Import data is required.', 'storage-limit-manager')
            ));
        }

        // Decode JSON data
        $decoded_data = json_decode(stripslashes($import_data), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array(
                'message' => __('Invalid JSON data.', 'storage-limit-manager')
            ));
        }

        $settings = StorageLimitManager::instance()->settings;
        $result = $settings->import_settings($decoded_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('Settings imported successfully.', 'storage-limit-manager'),
                'settings' => $settings->get_settings()
            ));
        }
    }

    /**
     * Get upload progress for large file uploads
     */
    public function get_upload_progress()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $upload_id = sanitize_text_field($_POST['upload_id'] ?? '');

        if (empty($upload_id)) {
            wp_send_json_error(array(
                'message' => __('Upload ID is required.', 'storage-limit-manager')
            ));
        }

        // Get upload progress from session or transient
        $progress = get_transient('slm_upload_progress_' . $upload_id);

        if ($progress === false) {
            wp_send_json_error(array(
                'message' => __('Upload progress not found.', 'storage-limit-manager')
            ));
        }

        wp_send_json_success($progress);
    }

    /**
     * Clear usage cache
     */
    public function clear_usage_cache()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $calculator = StorageLimitManager::instance()->storage_calculator;
        $calculator->clear_usage_cache();

        wp_send_json_success(array(
            'message' => __('Usage cache cleared successfully.', 'storage-limit-manager')
        ));
    }

    /**
     * AJAX handler for repairing damaged attachment references
     */
    public function repair_attachments()
    {
        // Verify nonce
        if (!check_ajax_referer('slm_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'storage-limit-manager')
            ));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions.', 'storage-limit-manager')
            ));
        }

        $repaired = 0;
        $errors = 0;

        // Get all attachments
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'inherit'
        ));

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);

            // Check if file path exists
            if (!$file_path || !file_exists($file_path)) {
                // Try to regenerate attachment metadata
                $metadata = wp_generate_attachment_metadata($attachment->ID, $file_path);

                if ($metadata) {
                    wp_update_attachment_metadata($attachment->ID, $metadata);
                    $repaired++;
                } else {
                    $errors++;
                }
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('Repair completed. %d attachments repaired, %d errors encountered.', 'storage-limit-manager'),
                $repaired,
                $errors
            ),
            'repaired' => $repaired,
            'errors' => $errors
        ));
    }
}
