=== Storage Limit Manager ===
Contributors: yourname
Tags: storage, upload, limit, quota, media, files, disk space
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enforce upload limits and display usage statistics for WordPress media files with visual progress bars and comprehensive admin controls.

== Description ==

Storage Limit Manager is a comprehensive WordPress plugin that helps you control and monitor your site's media storage usage. Perfect for hosting providers, membership sites, or any WordPress installation where storage management is crucial.

= Key Features =

* **Configurable Storage Limits**: Set maximum total upload size limits for your entire WordPress site
* **Real-time Usage Tracking**: Automatically tracks and calculates the total size of all uploaded media files
* **Upload Blocking**: Prevents new uploads that would exceed your set storage limit
* **Visual Progress Bars**: Beautiful progress bars showing current vs. available storage
* **Comprehensive Admin Interface**: Dedicated settings page with detailed usage statistics
* **User-friendly Error Messages**: Clear feedback when uploads are rejected with upgrade instructions
* **Automatic Recalculation**: Smart usage tracking with manual recalculation option
* **Responsive Design**: Works perfectly on desktop and mobile devices

= Perfect For =

* Hosting providers offering tiered storage plans
* Membership sites with different storage allowances
* Development environments with limited storage
* Any site requiring storage management and monitoring

= Admin Features =

* **Settings Page**: Configure maximum storage limits in MB or GB
* **Usage Statistics**: View current usage, remaining space, and percentage consumed
* **Visual Dashboard**: Color-coded progress bars (green, yellow, red based on usage)
* **Real-time Updates**: AJAX-powered recalculation without page refresh
* **Smart Notifications**: Progress bars visible on media library and upload pages

= Technical Highlights =

* Hooks into WordPress upload processes for seamless integration
* Efficient storage calculation and caching
* Proper error handling and user feedback
* Follows WordPress coding standards and best practices
* Lightweight and optimized for performance

== Installation ==

1. Upload the `storage-limit-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Storage Limits to configure your storage limit
4. The plugin will automatically start tracking usage and enforcing limits

== Frequently Asked Questions ==

= How does the plugin calculate storage usage? =

The plugin scans all media attachments in your WordPress database and calculates the total file size. It updates this information automatically when files are uploaded or deleted.

= What happens when the storage limit is reached? =

When enabled, the plugin will block new uploads and display a clear error message to users, including instructions about upgrading their plan or freeing up space.

= Can I change the storage limit after activation? =

Yes, you can modify the storage limit at any time through the Settings > Storage Limits page. Changes take effect immediately.

= Does the plugin affect existing files? =

No, the plugin only affects new uploads. Existing files remain untouched and continue to count toward your storage usage.

= Can I disable upload blocking but keep monitoring? =

Yes, you can uncheck the "Block Uploads When Limit Exceeded" option to monitor usage without blocking uploads.

= How accurate is the usage calculation? =

The plugin calculates actual file sizes on disk, providing accurate usage statistics. You can manually recalculate at any time using the "Recalculate Usage" button.

== Screenshots ==

1. Admin settings page with usage statistics and configuration options
2. Visual progress bar showing storage usage
3. Upload error message when limit is exceeded
4. Progress bar in WordPress admin area

== Changelog ==

= 1.0.0 =
* Initial release
* Configurable storage limits
* Real-time usage tracking
* Visual progress bars
* Upload blocking functionality
* Comprehensive admin interface
* AJAX-powered recalculation
* Responsive design
* Error handling and user feedback

== Upgrade Notice ==

= 1.0.0 =
Initial release of Storage Limit Manager. Install to start managing your WordPress storage limits today!

== Support ==

For support, feature requests, or bug reports, please visit our support forum or contact us directly.

== Privacy Policy ==

This plugin does not collect, store, or transmit any personal data. It only tracks file sizes and storage usage locally on your WordPress installation.

== Technical Requirements ==

* WordPress 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher
* Sufficient server permissions to read file sizes

== Developer Information ==

This plugin follows WordPress coding standards and includes:
* Proper sanitization and validation
* Secure AJAX handling with nonces
* Efficient database queries
* Clean, documented code
* Extensible architecture for future enhancements
