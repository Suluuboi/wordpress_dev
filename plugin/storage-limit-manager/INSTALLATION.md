# Storage Limit Manager - Installation & Usage Guide

## Quick Installation

### Method 1: Direct Upload
1. Download or copy the `storage-limit-manager` folder
2. Upload it to your WordPress `/wp-content/plugins/` directory
3. Go to WordPress Admin → Plugins
4. Find "Storage Limit Manager" and click "Activate"

### Method 2: WordPress Admin Upload
1. Zip the `storage-limit-manager` folder
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
4. Click "Activate Plugin"

## Initial Configuration

### 1. Access Settings
- Navigate to **Settings → Storage Limits** in your WordPress admin
- You'll see the main configuration page with usage statistics

### 2. Configure Storage Limit
- Set your desired **Maximum Storage (MB)**
  - 1000 MB = 1 GB
  - 5000 MB = 5 GB
  - 10000 MB = 10 GB
- Enable/disable **Show Progress Bar** (recommended: enabled)
- Enable/disable **Block Uploads When Limit Exceeded** (recommended: enabled)

### 3. Initial Usage Calculation
- The plugin automatically calculates current usage on activation
- Click **"Recalculate Usage"** if you want to refresh the data
- This scans all media files and provides accurate storage statistics

## Features Overview

### Admin Dashboard
- **Current Usage Statistics**: Real-time storage consumption data
- **Visual Progress Bar**: Color-coded storage usage indicator
  - Green: Normal usage (0-74%)
  - Yellow: Warning level (75-89%)
  - Red: Critical level (90-100%)
- **Detailed Statistics**: Used space, total limit, remaining space, percentage

### Upload Management
- **Automatic Blocking**: Prevents uploads that exceed the limit
- **Clear Error Messages**: Informative feedback when uploads are rejected
- **Real-time Validation**: Checks file size before processing

### Progress Bar Locations
The storage progress bar appears on:
- Media Library page
- Upload New Media page
- Post/Page edit screens
- Plugin settings page

## Usage Examples

### Example 1: Hosting Provider
```
Storage Limit: 5000 MB (5 GB)
Use Case: Offer different hosting tiers
- Basic Plan: 1 GB
- Pro Plan: 5 GB
- Enterprise: 20 GB
```

### Example 2: Membership Site
```
Storage Limit: 2000 MB (2 GB)
Use Case: Member file uploads
- Free Members: 500 MB
- Premium Members: 2 GB
- VIP Members: 10 GB
```

### Example 3: Development Environment
```
Storage Limit: 1000 MB (1 GB)
Use Case: Prevent runaway storage usage during development
```

## Troubleshooting

### Common Issues

#### 1. Usage Not Updating
**Problem**: Storage usage doesn't reflect recent uploads/deletions
**Solution**: 
- Go to Settings → Storage Limits
- Click "Recalculate Usage" button
- Wait for the success message

#### 2. Progress Bar Not Showing
**Problem**: Progress bar doesn't appear in admin
**Solution**:
- Check Settings → Storage Limits
- Ensure "Show Progress Bar" is enabled
- Clear browser cache
- Check if you're on a supported admin page

#### 3. Uploads Still Working Despite Limit
**Problem**: Files upload even when limit is exceeded
**Solution**:
- Verify "Block Uploads When Limit Exceeded" is enabled
- Check if the storage limit is set correctly
- Recalculate usage to ensure accurate data

#### 4. Incorrect File Size Calculations
**Problem**: Usage statistics seem wrong
**Solution**:
- Click "Recalculate Usage" to rescan all files
- Check file permissions on upload directory
- Verify WordPress can read file sizes

### Performance Considerations

#### Large Media Libraries
- Initial calculation may take time with thousands of files
- Recalculation is performed efficiently but may take 30-60 seconds
- Usage updates happen automatically on upload/delete

#### Server Resources
- Plugin uses minimal server resources
- File size calculations are cached
- AJAX requests are optimized

## Advanced Configuration

### Customizing Error Messages
The plugin provides default error messages, but you can customize them by modifying the plugin code in the `check_upload_limit()` function.

### Adjusting Progress Bar Colors
Modify the CSS in `assets/admin-style.css` to change progress bar colors:
- `.slm-progress-normal`: Green (normal usage)
- `.slm-progress-warning`: Yellow (warning level)
- `.slm-progress-critical`: Red (critical level)

### Database Storage
The plugin stores settings in WordPress options:
- `slm_settings`: Plugin configuration
- `slm_usage_data`: Current usage statistics

## Security Features

- **Nonce Verification**: All AJAX requests are secured
- **Capability Checks**: Only administrators can modify settings
- **Input Sanitization**: All user inputs are properly sanitized
- **SQL Injection Prevention**: Uses WordPress database functions

## Compatibility

### WordPress Versions
- Minimum: WordPress 5.0
- Tested up to: WordPress 6.4
- Recommended: Latest stable version

### PHP Versions
- Minimum: PHP 7.4
- Recommended: PHP 8.0 or higher

### Server Requirements
- MySQL 5.6 or higher
- File system read permissions
- Standard WordPress hosting environment

## Support & Maintenance

### Regular Maintenance
- Monitor storage usage regularly
- Adjust limits as needed
- Keep WordPress and plugins updated

### Backup Considerations
- Plugin settings are included in WordPress database backups
- No additional backup requirements

### Uninstallation
- Deactivate plugin through WordPress admin
- Plugin data remains in database for reactivation
- To completely remove: delete plugin files and database options

## Best Practices

1. **Set Realistic Limits**: Consider your hosting plan and user needs
2. **Monitor Regularly**: Check usage statistics weekly
3. **Communicate Limits**: Inform users about storage restrictions
4. **Plan for Growth**: Adjust limits as your site grows
5. **Test Thoroughly**: Verify upload blocking works as expected

## Getting Help

If you encounter issues:
1. Check this installation guide
2. Review WordPress error logs
3. Test with default WordPress theme
4. Disable other plugins temporarily
5. Contact support with specific error messages
