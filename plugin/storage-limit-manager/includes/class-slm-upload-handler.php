<?php

/**
 * Upload Handler for Storage Limit Manager
 *
 * @package StorageLimitManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SLM_Upload_Handler Class
 */
class SLM_Upload_Handler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Core WordPress upload filters
        // Core WordPress upload filters
        add_filter('wp_handle_upload_prefilter', array($this, 'check_upload_limit'));
        add_filter('wp_handle_sideload_prefilter', array($this, 'check_upload_limit'));

        // Standard WordPress AJAX upload actions

        // Standard WordPress AJAX upload actions
        add_action('wp_ajax_upload-attachment', array($this, 'check_ajax_upload'), 1);
        add_action('wp_ajax_nopriv_upload-attachment', array($this, 'check_ajax_upload'), 1);

        // Elementor specific upload actions
        add_action('wp_ajax_elementor_ajax', array($this, 'check_elementor_uploads'), 1);
        add_action('wp_ajax_nopriv_elementor_ajax', array($this, 'check_elementor_uploads'), 1);

        // Additional third-party plugin upload actions
        add_action('wp_ajax_upload_image', array($this, 'check_ajax_upload'), 1);
        add_action('wp_ajax_nopriv_upload_image', array($this, 'check_ajax_upload'), 1);
        add_action('wp_ajax_upload_file', array($this, 'check_ajax_upload'), 1);
        add_action('wp_ajax_nopriv_upload_file', array($this, 'check_ajax_upload'), 1);

        // REMOVED: Problematic early hooks that can interfere with file processing
        // The storage calculator now handles recalculation safely via add_attachment hook

        // Hook into media library operations
        add_action('wp_ajax_query-attachments', array($this, 'inject_storage_info'), 1);

        // Add upload restrictions display
        add_action('post-upload-ui', array($this, 'display_upload_restrictions'));
        add_action('pre-upload-ui', array($this, 'display_upload_restrictions'));
    }

    /**
     * Check upload limit before processing
     *
     * @param array $file File array
     * @return array Modified file array
     */
    public function check_upload_limit($file)
    {
        // Skip check if there's already an error
        if (isset($file['error']) && !empty($file['error'])) {
            return $file;
        }

        $settings = StorageLimitManager::instance()->settings->get_settings();

        // Skip check if blocking is disabled
        if (!$settings['block_uploads']) {
            return $file;
        }

        $calculator = StorageLimitManager::instance()->storage_calculator;
        $file_size = isset($file['size']) ? (int) $file['size'] : 0;

        // Check if upload would exceed limit
        if ($calculator->would_exceed_limit($file_size)) {
            $stats = $calculator->get_usage_statistics();


            $file['error'] = sprintf(
                __('Upload failed: This file would exceed your storage limit of %s. Current usage: %s. Please upgrade your plan or delete some files to free up space.', 'storage-limit-manager'),
                $stats['formatted']['max'],
                $stats['formatted']['total']
            );

            // Log the blocked upload
            do_action('slm_upload_blocked', $file, $file_size, $stats);
        }

        return $file;
    }

    /**
     * Check AJAX upload requests
     */
    public function check_ajax_upload()
    {
        // Only proceed if we have upload data
        if (empty($_FILES)) {
            return;
        }

        $settings = StorageLimitManager::instance()->settings->get_settings();

        // Skip check if blocking is disabled
        if (!$settings['block_uploads']) {
            return;
        }

        $calculator = StorageLimitManager::instance()->storage_calculator;


        // Check each uploaded file
        foreach ($_FILES as $file_key => $file_data) {
            if (isset($file_data['size']) && is_array($file_data['size'])) {
                // Multiple files
                foreach ($file_data['size'] as $index => $file_size) {
                    if ($calculator->would_exceed_limit($file_size)) {
                        $this->send_upload_error($calculator);
                        return;
                    }
                }
            } elseif (isset($file_data['size'])) {
                // Single file
                if ($calculator->would_exceed_limit($file_data['size'])) {
                    $this->send_upload_error($calculator);
                    return;
                }
            }
        }
    }

    /**
     * Send upload error response for AJAX requests
     *
     * @param SLM_Storage_Calculator $calculator
     */
    private function send_upload_error($calculator)
    {
        $stats = $calculator->get_usage_statistics();


        $error_message = sprintf(
            __('Upload failed: This file would exceed your storage limit of %s. Current usage: %s. Please upgrade your plan or delete some files to free up space.', 'storage-limit-manager'),
            $stats['formatted']['max'],
            $stats['formatted']['total']
        );

        wp_die($error_message, __('Storage Limit Exceeded', 'storage-limit-manager'), array(
            'response' => 413, // Payload Too Large
            'back_link' => true
        ));
    }

    /**
     * Get upload restrictions info
     *
     * @return array Upload restrictions
     */
    public function get_upload_restrictions()
    {
        $settings = StorageLimitManager::instance()->settings->get_settings();
        $calculator = StorageLimitManager::instance()->storage_calculator;
        $stats = $calculator->get_usage_statistics();

        return array(
            'blocking_enabled' => $settings['block_uploads'],
            'max_storage_bytes' => $stats['max_bytes'],
            'current_usage_bytes' => $stats['total_bytes'],
            'remaining_bytes' => $stats['remaining_bytes'],
            'percentage_used' => $stats['percentage_used'],
            'status' => $calculator->get_storage_status(),
            'can_upload' => $stats['remaining_bytes'] > 0 || !$settings['block_uploads']
        );
    }

    /**
     * Check if a specific file size can be uploaded
     *
     * @param int $file_size File size in bytes
     * @return array Result with success status and message
     */
    public function can_upload_file_size($file_size)
    {
        $settings = StorageLimitManager::instance()->settings->get_settings();
        $calculator = StorageLimitManager::instance()->storage_calculator;

        // If blocking is disabled, always allow
        if (!$settings['block_uploads']) {
            return array(
                'success' => true,
                'message' => __('Upload allowed (blocking disabled)', 'storage-limit-manager')
            );
        }

        // Check if file would exceed limit
        if ($calculator->would_exceed_limit($file_size)) {
            $stats = $calculator->get_usage_statistics();


            return array(
                'success' => false,
                'message' => sprintf(
                    __('File size (%s) would exceed storage limit. Current usage: %s / %s', 'storage-limit-manager'),
                    $calculator->format_bytes($file_size),
                    $stats['formatted']['total'],
                    $stats['formatted']['max']
                )
            );
        }

        return array(
            'success' => true,
            'message' => __('Upload allowed', 'storage-limit-manager')
        );
    }

    /**
     * Get maximum uploadable file size based on remaining storage
     *
     * @return int Maximum file size in bytes
     */
    public function get_max_uploadable_size()
    {
        $settings = StorageLimitManager::instance()->settings->get_settings();
        $calculator = StorageLimitManager::instance()->storage_calculator;

        // If blocking is disabled, return PHP/WordPress limits
        if (!$settings['block_uploads']) {
            return min(
                wp_max_upload_size(),
                $this->get_php_max_upload_size()
            );
        }

        $stats = $calculator->get_usage_statistics();
        $remaining_bytes = $stats['remaining_bytes'];

        // Return the smaller of remaining storage or system limits
        return min(
            $remaining_bytes,
            wp_max_upload_size(),
            $this->get_php_max_upload_size()
        );
    }

    /**
     * Get PHP maximum upload size
     *
     * @return int Size in bytes
     */
    private function get_php_max_upload_size()
    {
        $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
        $post_max = $this->parse_size(ini_get('post_max_size'));
        $memory_limit = $this->parse_size(ini_get('memory_limit'));

        return min($upload_max, $post_max, $memory_limit);
    }

    /**
     * Parse size string to bytes
     *
     * @param string $size Size string (e.g., "2M", "1G")
     * @return int Size in bytes
     */
    private function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    /**
     * Add upload restrictions to media upload form
     */
    public function display_upload_restrictions()
    {
        $restrictions = $this->get_upload_restrictions();
        $calculator = StorageLimitManager::instance()->storage_calculator;

        if (!$restrictions['blocking_enabled']) {
            return;
        }

        $max_uploadable = $this->get_max_uploadable_size();

        echo '<div class="slm-upload-restrictions">';
        echo '<h4>' . __('Storage Restrictions', 'storage-limit-manager') . '</h4>';
        echo '<p>' . sprintf(
            __('Storage used: %s / %s (%s%%)', 'storage-limit-manager'),
            $calculator->format_bytes($restrictions['current_usage_bytes']),
            $calculator->format_bytes($restrictions['max_storage_bytes']),
            number_format($restrictions['percentage_used'], 1)
        ) . '</p>';


        if ($restrictions['remaining_bytes'] > 0) {
            echo '<p>' . sprintf(
                __('Maximum file size: %s', 'storage-limit-manager'),
                $calculator->format_bytes($max_uploadable)
            ) . '</p>';
        } else {
            echo '<p class="slm-error">' . __('Storage limit reached. Please delete some files before uploading.', 'storage-limit-manager') . '</p>';
        }
        echo '</div>';
    }

    /**
     * Check Elementor specific uploads
     */
    public function check_elementor_uploads()
    {
        // Check if this is an Elementor upload request
        $action = isset($_POST['actions']) ? $_POST['actions'] : '';

        if (empty($action)) {
            return;
        }

        // Parse Elementor actions (they send JSON)
        $actions = json_decode(stripslashes($action), true);

        if (!is_array($actions)) {
            return;
        }

        // Look for upload-related actions
        foreach ($actions as $action_data) {
            if (
                isset($action_data['action']) &&
                (strpos($action_data['action'], 'upload') !== false ||
                    strpos($action_data['action'], 'media') !== false)
            ) {

                // Check if we have file uploads
                if (!empty($_FILES)) {
                    $this->check_ajax_upload();
                    return;
                }
            }
        }
    }

    // REMOVED: Problematic early upload checking methods
    // These were interfering with WordPress file processing
    // Storage limits are still enforced via the prefilter hooks above

    /**
     * Inject storage information into media library queries
     */
    public function inject_storage_info()
    {
        $restrictions = $this->get_upload_restrictions();

        // Add storage info to the response
        add_filter('wp_prepare_attachment_for_js', function ($response, $attachment, $meta) use ($restrictions) {
            $response['slm_storage_info'] = array(
                'can_upload' => $restrictions['can_upload'],
                'remaining_bytes' => $restrictions['remaining_bytes'],
                'percentage_used' => $restrictions['percentage_used'],
                'status' => $restrictions['status']
            );
            return $response;
        }, 10, 3);
    }
}
