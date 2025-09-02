/**
 * Elementor Integration for Storage Limit Manager
 * Provides client-side storage limit checking for Elementor uploads
 */

(function($) {
    'use strict';

    // Storage limit data from PHP
    var storageData = window.slm_elementor_data || {};
    var restrictions = storageData.restrictions || {};
    var messages = storageData.messages || {};

    /**
     * Initialize Elementor integration
     */
    function initElementorIntegration() {
        // Wait for Elementor to be ready
        if (typeof elementor !== 'undefined') {
            setupElementorHooks();
        } else {
            // Wait for Elementor to load
            $(document).on('elementor:init', function() {
                setupElementorHooks();
            });
        }
    }

    /**
     * Setup Elementor hooks and event listeners
     */
    function setupElementorHooks() {
        // Hook into Elementor's media upload
        if (elementor && elementor.channels && elementor.channels.editor) {
            elementor.channels.editor.on('media:upload:start', checkStorageBeforeUpload);
        }

        // Hook into file input changes in Elementor panels
        $(document).on('change', '.elementor-control-media input[type="file"]', function(e) {
            checkFileSize(e.target.files);
        });

        // Hook into drag and drop uploads
        $(document).on('dragover drop', '.elementor-control-media', function(e) {
            if (e.type === 'drop' && e.originalEvent.dataTransfer.files.length > 0) {
                checkFileSize(e.originalEvent.dataTransfer.files);
            }
        });

        // Display storage info in Elementor panel
        displayStorageInfo();
    }

    /**
     * Check storage before upload starts
     */
    function checkStorageBeforeUpload(data) {
        if (!restrictions.can_upload) {
            // Block the upload
            if (elementor && elementor.channels && elementor.channels.editor) {
                elementor.channels.editor.trigger('media:upload:error', {
                    message: messages.storage_exceeded || 'Storage limit exceeded'
                });
            }
            return false;
        }
    }

    /**
     * Check file size before upload
     */
    function checkFileSize(files) {
        if (!files || files.length === 0) {
            return true;
        }

        var totalSize = 0;
        for (var i = 0; i < files.length; i++) {
            totalSize += files[i].size;
        }

        // Check if upload would exceed limit
        if (restrictions.remaining_bytes && totalSize > restrictions.remaining_bytes) {
            showStorageError();
            return false;
        }

        return true;
    }

    /**
     * Show storage error message
     */
    function showStorageError() {
        // Show Elementor notification if available
        if (elementor && elementor.notifications) {
            elementor.notifications.showToast({
                message: messages.storage_exceeded || 'Storage limit exceeded',
                type: 'error'
            });
        } else {
            // Fallback to alert
            alert(messages.storage_exceeded || 'Storage limit exceeded');
        }
    }

    /**
     * Display storage information in Elementor panel
     */
    function displayStorageInfo() {
        if (!restrictions.blocking_enabled) {
            return;
        }

        // Create storage info element
        var storageInfo = $('<div class="slm-elementor-storage-info">' +
            '<h4>Storage Usage</h4>' +
            '<div class="slm-progress-bar">' +
                '<div class="slm-progress-fill" style="width: ' + restrictions.percentage_used + '%"></div>' +
            '</div>' +
            '<p>' + formatBytes(restrictions.current_usage_bytes) + ' / ' + formatBytes(restrictions.max_storage_bytes) + '</p>' +
            '<p>Remaining: ' + formatBytes(restrictions.remaining_bytes) + '</p>' +
        '</div>');

        // Add CSS styles
        if (!$('#slm-elementor-styles').length) {
            $('<style id="slm-elementor-styles">' +
                '.slm-elementor-storage-info { padding: 15px; margin: 10px 0; background: #f9f9f9; border-radius: 3px; }' +
                '.slm-elementor-storage-info h4 { margin: 0 0 10px 0; font-size: 14px; }' +
                '.slm-progress-bar { height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden; margin: 10px 0; }' +
                '.slm-progress-fill { height: 100%; background: #007cba; transition: width 0.3s ease; }' +
                '.slm-elementor-storage-info p { margin: 5px 0; font-size: 12px; color: #666; }' +
            '</style>').appendTo('head');
        }

        // Insert into Elementor panel
        var targetPanel = $('#elementor-panel-content-wrapper .elementor-panel-navigation');
        if (targetPanel.length) {
            targetPanel.after(storageInfo);
        }
    }

    /**
     * Format bytes to human readable format
     */
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    /**
     * Override Elementor's upload function to add storage checks
     */
    function overrideElementorUpload() {
        // Wait for Elementor modules to be available
        if (typeof elementorModules === 'undefined') {
            setTimeout(overrideElementorUpload, 100);
            return;
        }

        // Override the media upload control
        if (elementorModules.controls && elementorModules.controls.Media) {
            var originalOnUpload = elementorModules.controls.Media.prototype.onUpload;
            
            elementorModules.controls.Media.prototype.onUpload = function(e) {
                // Check storage before proceeding with upload
                if (!checkFileSize(e.target.files)) {
                    e.preventDefault();
                    return false;
                }
                
                // Call original upload function
                return originalOnload.call(this, e);
            };
        }
    }

    /**
     * Monitor AJAX requests for upload actions
     */
    function monitorAjaxUploads() {
        // Override jQuery AJAX to intercept Elementor uploads
        var originalAjax = $.ajax;
        
        $.ajax = function(options) {
            // Check if this is an Elementor upload request
            if (options.url && options.url.indexOf('admin-ajax.php') !== -1 && 
                options.data && typeof options.data === 'string' && 
                options.data.indexOf('elementor_ajax') !== -1) {
                
                // Check if this is an upload action
                if (options.data.indexOf('upload') !== -1 || options.data.indexOf('media') !== -1) {
                    // Check storage limits
                    if (!restrictions.can_upload) {
                        // Block the request
                        if (options.error) {
                            options.error({}, 'error', messages.upload_blocked || 'Upload blocked');
                        }
                        return;
                    }
                }
            }
            
            // Call original AJAX function
            return originalAjax.call(this, options);
        };
    }

    /**
     * Update storage data from server
     */
    function updateStorageData() {
        $.ajax({
            url: ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'slm_get_usage_stats',
                nonce: storageData.nonce || ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    restrictions = response.data;
                    // Update display if needed
                    $('.slm-elementor-storage-info').remove();
                    displayStorageInfo();
                }
            }
        });
    }

    /**
     * Initialize everything when document is ready
     */
    $(document).ready(function() {
        initElementorIntegration();
        overrideElementorUpload();
        monitorAjaxUploads();
        
        // Update storage data periodically
        setInterval(updateStorageData, 30000); // Every 30 seconds
    });

    // Also initialize when Elementor preview loads
    $(window).on('elementor/frontend/init', function() {
        initElementorIntegration();
    });

})(jQuery);
