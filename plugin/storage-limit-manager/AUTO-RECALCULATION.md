# Automatic Recalculation Feature

## Overview

The Storage Limit Manager plugin now automatically recalculates storage usage whenever files are uploaded to WordPress, regardless of which plugin or method is used for the upload. This ensures that storage limits are always enforced with accurate, up-to-date usage data.

## How It Works

### Automatic Triggers

The plugin automatically triggers recalculation when:

1. **Standard WordPress uploads** via Media Library
2. **Elementor uploads** through the page builder
3. **WooCommerce product images**
4. **Contact Form 7 file uploads**
5. **Gravity Forms file uploads**
6. **Any plugin using WordPress core upload functions**
7. **Custom AJAX upload implementations**

### Multi-Layer Detection

The automatic recalculation uses multiple detection methods:

- **Core WordPress Hooks**: `wp_handle_upload`, `wp_handle_sideload`
- **AJAX Action Monitoring**: All upload-related AJAX requests
- **Plugin-Specific Hooks**: Direct integration with popular plugins
- **File System Tracking**: Monitors successful file writes

### Smart Scheduling

To prevent performance issues:

- Recalculation is **scheduled** rather than run immediately
- Multiple uploads in quick succession trigger only **one recalculation**
- Recalculation runs at the **end of the request** (`shutdown` hook)
- **Transient caching** prevents duplicate calculations

## Manual vs Automatic Recalculation

### Manual Recalculation Button
- **Immediate execution**: Runs right when clicked
- **Force recalculation**: Bypasses scheduling and caching
- **Admin feedback**: Shows progress and results
- **Full scan**: Always performs complete file system scan

### Automatic Recalculation
- **Scheduled execution**: Runs at end of request
- **Smart caching**: Prevents unnecessary duplicate runs
- **Background operation**: Doesn't interrupt user workflow
- **Triggered by uploads**: Only runs when files are actually uploaded

## Technical Implementation

### Key Components

1. **Storage Calculator** (`class-slm-storage-calculator.php`)
   - `schedule_recalculation()` - Schedules automatic recalculation
   - `maybe_run_scheduled_recalculation()` - Executes scheduled recalculation
   - `force_recalculate_usage()` - Forces immediate recalculation (manual button)

2. **Upload Handler** (`class-slm-upload-handler.php`)
   - Enhanced with automatic recalculation triggers
   - Monitors all upload scenarios

3. **Plugin Integrations** (`class-slm-plugin-integrations.php`)
   - Specific hooks for third-party plugins
   - Elementor, WooCommerce, Contact Form 7, etc.

4. **AJAX Handler** (`class-slm-ajax.php`)
   - Provides auto-recalculation status to frontend
   - Handles manual recalculation requests

### Hooks and Filters

```php
// Core WordPress uploads
add_action('wp_handle_upload', array($this, 'trigger_recalculation_on_upload'), 10, 2);
add_action('wp_handle_sideload', array($this, 'trigger_recalculation_on_upload'), 10, 2);

// AJAX uploads
add_action('wp_ajax_upload-attachment', array($this, 'schedule_recalculation'), 999);
add_action('wp_ajax_elementor_ajax', array($this, 'schedule_recalculation_for_elementor'), 999);

// Execution
add_action('shutdown', array($this, 'maybe_run_scheduled_recalculation'));
```

## User Experience

### Admin Notifications
- **Automatic notifications** when recalculation occurs
- **Visual feedback** in admin interface
- **Real-time updates** of storage usage displays

### Performance Optimization
- **Non-blocking**: Doesn't slow down uploads
- **Efficient scheduling**: Prevents resource waste
- **Smart caching**: Avoids unnecessary calculations

## Configuration

### Transient Settings
- `slm_recalculation_scheduled`: Flags pending recalculation (60 seconds)
- `slm_auto_recalculated`: Indicates recent auto-recalculation (5 minutes)

### Customization Hooks
```php
// Modify recalculation batch size
add_filter('slm_recalculate_batch_size', function($size) {
    return 200; // Default: 100
});

// Hook into recalculation events
add_action('slm_usage_recalculated', function($total_size, $processed_files, $missing_files) {
    // Custom logic after recalculation
});
```

## Benefits

1. **Always Accurate**: Storage usage is always up-to-date
2. **Universal Coverage**: Works with any upload method
3. **Performance Optimized**: Smart scheduling prevents slowdowns
4. **User Friendly**: Transparent operation with helpful notifications
5. **Extensible**: Easy to add support for new plugins

## Troubleshooting

### If Automatic Recalculation Isn't Working

1. **Check Plugin Conflicts**: Ensure no other plugins are interfering
2. **Verify Permissions**: Confirm proper file system access
3. **Review Error Logs**: Check WordPress debug logs for issues
4. **Test Manual Button**: Verify manual recalculation works
5. **Clear Transients**: Delete `slm_recalculation_scheduled` transient

### Performance Considerations

- Large media libraries may take longer to recalculate
- Batch processing prevents memory issues
- Scheduled execution prevents upload delays
- Transient caching reduces server load

## Future Enhancements

- **Real-time WebSocket updates** for instant feedback
- **Incremental calculation** for large libraries
- **Background processing** with WP Cron
- **Advanced caching strategies** for better performance
