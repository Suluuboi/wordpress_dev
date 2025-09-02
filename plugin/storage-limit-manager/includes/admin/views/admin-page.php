<?php

/**
 * Admin page template for Storage Limit Manager
 *
 * @package StorageLimitManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1><?php _e('Storage Limit Manager', 'storage-limit-manager'); ?></h1>

    <div class="slm-admin-container">
        <!-- Usage Statistics -->
        <div class="slm-usage-stats">
            <h2><?php _e('Current Usage Statistics', 'storage-limit-manager'); ?></h2>
            <div class="slm-stats-grid">
                <div class="slm-stat-item">
                    <h3><?php _e('Total Used', 'storage-limit-manager'); ?></h3>
                    <p class="slm-stat-value"><?php echo $this->format_bytes($current_usage); ?></p>
                </div>
                <div class="slm-stat-item">
                    <h3><?php _e('Storage Limit', 'storage-limit-manager'); ?></h3>
                    <p class="slm-stat-value"><?php echo $this->format_bytes($max_storage_bytes); ?></p>
                </div>
                <div class="slm-stat-item">
                    <h3><?php _e('Remaining', 'storage-limit-manager'); ?></h3>
                    <p class="slm-stat-value"><?php echo $this->format_bytes($max_storage_bytes - $current_usage); ?></p>
                </div>
                <div class="slm-stat-item">
                    <h3><?php _e('Usage Percentage', 'storage-limit-manager'); ?></h3>
                    <p class="slm-stat-value"><?php echo number_format($percentage, 1); ?>%</p>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="slm-progress-section">
                <h3><?php _e('Storage Usage', 'storage-limit-manager'); ?></h3>
                <div class="slm-progress-container slm-admin-progress">
                    <?php
                    $bar_class = 'slm-progress-normal';
                    if ($percentage >= 90) {
                        $bar_class = 'slm-progress-critical';
                    } elseif ($percentage >= 75) {
                        $bar_class = 'slm-progress-warning';
                    }
                    ?>
                    <div class="slm-progress-bar <?php echo esc_attr($bar_class); ?>" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                </div>
                <p class="slm-progress-text">
                    <?php echo sprintf(
                        __('%s of %s used (%s%%)', 'storage-limit-manager'),
                        $this->format_bytes($current_usage),
                        $this->format_bytes($max_storage_bytes),
                        number_format($percentage, 1)
                    ); ?>
                </p>
            </div>

            <button type="button" id="slm-recalculate" class="button button-secondary">
                <?php _e('Recalculate Usage', 'storage-limit-manager'); ?>
            </button>

            <button type="button" id="slm-repair-attachments" class="button button-secondary" style="margin-left: 10px;">
                <?php _e('Repair Damaged Files', 'storage-limit-manager'); ?>
            </button>

            <div style="margin-top: 10px;">
                <span id="slm-recalculate-status"></span>
                <span id="slm-repair-status"></span>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="slm-settings-form">
            <h2><?php _e('Settings', 'storage-limit-manager'); ?></h2>
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