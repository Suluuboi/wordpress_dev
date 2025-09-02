<?php

/**
 * Storage Calculator for Storage Limit Manager
 *
 * @package StorageLimitManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SLM_Storage_Calculator Class
 */
class SLM_Storage_Calculator
{
    /**
     * Usage option name
     *
     * @var string
     */
    private $usage_option = 'slm_usage_data';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('add_attachment', array($this, 'update_usage_on_upload'));
        add_action('delete_attachment', array($this, 'update_usage_on_delete'));
    }

    /**
     * Get current storage usage
     *
     * @return int Usage in bytes
     */
    public function get_current_usage()
    {
        $usage_data = get_option($this->usage_option);
        return isset($usage_data['total_bytes']) ? (int) $usage_data['total_bytes'] : 0;
    }

    /**
     * Update usage data
     *
     * @param int $total_bytes Total bytes used
     */
    private function update_usage_data($total_bytes)
    {
        $usage_data = array(
            'total_bytes' => (int) $total_bytes,
            'last_updated' => current_time('timestamp')
        );
        update_option($this->usage_option, $usage_data);

        // Trigger action for other plugins/themes to hook into
        do_action('slm_usage_updated', $total_bytes, $usage_data);
    }

    /**
     * Update usage when file is uploaded
     *
     * @param int $attachment_id Attachment ID
     */
    public function update_usage_on_upload($attachment_id)
    {
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $file_size = filesize($file_path);
            $current_usage = $this->get_current_usage();
            $this->update_usage_data($current_usage + $file_size);

            // Log the upload for debugging
            do_action('slm_file_uploaded', $attachment_id, $file_size, $current_usage + $file_size);
        }
    }

    /**
     * Update usage when file is deleted
     *
     * @param int $attachment_id Attachment ID
     */
    public function update_usage_on_delete($attachment_id)
    {
        $file_path = get_attached_file($attachment_id);
        if ($file_path && file_exists($file_path)) {
            $file_size = filesize($file_path);
            $current_usage = $this->get_current_usage();
            $new_usage = max(0, $current_usage - $file_size);
            $this->update_usage_data($new_usage);

            // Log the deletion for debugging
            do_action('slm_file_deleted', $attachment_id, $file_size, $new_usage);
        }
    }

    /**
     * Recalculate total usage by scanning all attachments
     *
     * @return int Total size in bytes
     */
    public function recalculate_total_usage()
    {
        $total_size = 0;
        $processed_files = 0;
        $missing_files = 0;

        // Get all attachments in batches to handle large media libraries
        $batch_size = apply_filters('slm_recalculate_batch_size', 100);
        $offset = 0;

        do {
            $attachments = get_posts(array(
                'post_type' => 'attachment',
                'posts_per_page' => $batch_size,
                'offset' => $offset,
                'post_status' => 'inherit',
                'fields' => 'ids'
            ));

            foreach ($attachments as $attachment_id) {
                $file_path = get_attached_file($attachment_id);
                if ($file_path && file_exists($file_path)) {
                    $file_size = filesize($file_path);
                    if ($file_size !== false) {
                        $total_size += $file_size;
                        $processed_files++;
                    }
                } else {
                    $missing_files++;
                }
            }

            $offset += $batch_size;

            // Prevent infinite loops
            if (count($attachments) < $batch_size) {
                break;
            }
        } while (count($attachments) > 0);

        $this->update_usage_data($total_size);

        // Log recalculation results
        do_action('slm_usage_recalculated', $total_size, $processed_files, $missing_files);

        return $total_size;
    }

    /**
     * Get usage statistics
     *
     * @return array Usage statistics
     */
    public function get_usage_statistics()
    {
        $usage_data = get_option($this->usage_option);
        $settings = StorageLimitManager::instance()->settings->get_settings();

        $current_usage = $this->get_current_usage();
        $max_storage_bytes = $settings['max_storage_mb'] * 1024 * 1024;
        $percentage = $max_storage_bytes > 0 ? ($current_usage / $max_storage_bytes) * 100 : 0;
        $percentage = min(100, $percentage);

        return array(
            'total_bytes' => $current_usage,
            'max_bytes' => $max_storage_bytes,
            'remaining_bytes' => max(0, $max_storage_bytes - $current_usage),
            'percentage_used' => $percentage,
            'last_updated' => isset($usage_data['last_updated']) ? $usage_data['last_updated'] : 0,
            'formatted' => array(
                'total' => $this->format_bytes($current_usage),
                'max' => $this->format_bytes($max_storage_bytes),
                'remaining' => $this->format_bytes(max(0, $max_storage_bytes - $current_usage)),
                'percentage' => number_format($percentage, 1) . '%'
            )
        );
    }

    /**
     * Check if storage limit is exceeded
     *
     * @param int $additional_bytes Additional bytes to check
     * @return bool True if limit would be exceeded
     */
    public function would_exceed_limit($additional_bytes = 0)
    {
        $settings = StorageLimitManager::instance()->settings->get_settings();
        $max_storage_bytes = $settings['max_storage_mb'] * 1024 * 1024;
        $current_usage = $this->get_current_usage();

        return ($current_usage + $additional_bytes) > $max_storage_bytes;
    }

    /**
     * Get storage status
     *
     * @return string Status: 'normal', 'warning', 'critical'
     */
    public function get_storage_status()
    {
        $stats = $this->get_usage_statistics();
        $percentage = $stats['percentage_used'];

        if ($percentage >= 90) {
            return 'critical';
        } elseif ($percentage >= 75) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted string
     */
    public function format_bytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get file count statistics
     *
     * @return array File count by type
     */
    public function get_file_count_statistics()
    {
        global $wpdb;

        $stats = array();

        // Get total attachment count
        $total_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_status = 'inherit'
        ");

        $stats['total_files'] = (int) $total_count;

        // Get count by mime type
        $mime_counts = $wpdb->get_results("
            SELECT post_mime_type, COUNT(*) as count 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_status = 'inherit' 
            GROUP BY post_mime_type 
            ORDER BY count DESC
        ");

        $stats['by_type'] = array();
        foreach ($mime_counts as $mime_count) {
            $type = explode('/', $mime_count->post_mime_type)[0];
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type] += (int) $mime_count->count;
        }

        return $stats;
    }

    /**
     * Clear usage cache
     */
    public function clear_usage_cache()
    {
        delete_option($this->usage_option);
        do_action('slm_usage_cache_cleared');
    }
}
