/**
 * Storage Limit Manager Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Recalculate usage button handler
    $('#slm-recalculate').on('click', function() {
        var $button = $(this);
        var $status = $('#slm-recalculate-status');
        
        // Disable button and show loading
        $button.prop('disabled', true);
        $status.removeClass('success error').html('<span class="slm-loading"></span> Recalculating...');
        
        // AJAX request
        $.ajax({
            url: slm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'slm_recalculate_usage',
                nonce: slm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').html('✓ Usage recalculated successfully');
                    
                    // Update the page with new values
                    updateUsageDisplay(response.data.total_size, response.data.formatted_size);
                    
                    // Auto-hide success message after 3 seconds
                    setTimeout(function() {
                        $status.fadeOut(function() {
                            $(this).html('').show();
                        });
                    }, 3000);
                } else {
                    $status.addClass('error').html('✗ Error recalculating usage');
                }
            },
            error: function() {
                $status.addClass('error').html('✗ Network error occurred');
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false);
            }
        });
    });
    
    // Function to update usage display
    function updateUsageDisplay(totalBytes, formattedSize) {
        // Get current settings to calculate percentage
        var maxStorageMB = parseInt($('input[name*="max_storage_mb"]').val()) || 1000;
        var maxStorageBytes = maxStorageMB * 1024 * 1024;
        var percentage = Math.min(100, (totalBytes / maxStorageBytes) * 100);
        
        // Update progress bars
        $('.slm-progress-bar').each(function() {
            var $bar = $(this);
            
            // Update width
            $bar.css('width', percentage + '%');
            
            // Update color class
            $bar.removeClass('slm-progress-normal slm-progress-warning slm-progress-critical');
            if (percentage >= 90) {
                $bar.addClass('slm-progress-critical');
            } else if (percentage >= 75) {
                $bar.addClass('slm-progress-warning');
            } else {
                $bar.addClass('slm-progress-normal');
            }
        });
        
        // Update text displays
        $('.slm-usage-text, .slm-progress-text').each(function() {
            var $text = $(this);
            var remainingBytes = maxStorageBytes - totalBytes;
            var formattedMax = formatBytes(maxStorageBytes);
            var formattedRemaining = formatBytes(remainingBytes);
            
            if ($text.hasClass('slm-usage-text')) {
                $text.html('Used: ' + formattedSize + ' / ' + formattedMax + ' (' + percentage.toFixed(1) + '%) | Remaining: ' + formattedRemaining);
            } else if ($text.hasClass('slm-progress-text')) {
                $text.html(formattedSize + ' of ' + formattedMax + ' used (' + percentage.toFixed(1) + '%)');
            }
        });
        
        // Update stat values if they exist
        updateStatValues(totalBytes, maxStorageBytes, formattedSize);
    }
    
    // Function to update individual stat values
    function updateStatValues(totalBytes, maxStorageBytes, formattedSize) {
        var $statItems = $('.slm-stat-item');
        
        $statItems.each(function() {
            var $item = $(this);
            var $value = $item.find('.slm-stat-value');
            var heading = $item.find('h3').text().toLowerCase();
            
            if (heading.includes('total used')) {
                $value.text(formattedSize);
            } else if (heading.includes('storage limit')) {
                $value.text(formatBytes(maxStorageBytes));
            } else if (heading.includes('remaining')) {
                $value.text(formatBytes(maxStorageBytes - totalBytes));
            } else if (heading.includes('percentage')) {
                var percentage = Math.min(100, (totalBytes / maxStorageBytes) * 100);
                $value.text(percentage.toFixed(1) + '%');
            }
        });
    }
    
    // Helper function to format bytes
    function formatBytes(bytes, precision) {
        precision = precision || 2;
        var units = ['B', 'KB', 'MB', 'GB', 'TB'];
        var i = 0;
        
        while (bytes >= 1024 && i < units.length - 1) {
            bytes /= 1024;
            i++;
        }
        
        return bytes.toFixed(precision) + ' ' + units[i];
    }
    
    // Real-time validation for storage limit input
    $('input[name*="max_storage_mb"]').on('input', function() {
        var $input = $(this);
        var value = parseInt($input.val());
        
        if (value < 1) {
            $input.css('border-color', '#dc3232');
            showInputError($input, 'Storage limit must be at least 1 MB');
        } else if (value > 1000000) {
            $input.css('border-color', '#ff9800');
            showInputWarning($input, 'Very large storage limit detected');
        } else {
            $input.css('border-color', '');
            hideInputMessage($input);
        }
    });
    
    // Function to show input error
    function showInputError($input, message) {
        var $container = $input.closest('td');
        var $existing = $container.find('.slm-input-message');
        
        if ($existing.length) {
            $existing.removeClass('warning').addClass('error').text(message);
        } else {
            $container.append('<p class="slm-input-message error">' + message + '</p>');
        }
    }
    
    // Function to show input warning
    function showInputWarning($input, message) {
        var $container = $input.closest('td');
        var $existing = $container.find('.slm-input-message');
        
        if ($existing.length) {
            $existing.removeClass('error').addClass('warning').text(message);
        } else {
            $container.append('<p class="slm-input-message warning">' + message + '</p>');
        }
    }
    
    // Function to hide input message
    function hideInputMessage($input) {
        var $container = $input.closest('td');
        $container.find('.slm-input-message').remove();
    }
    
    // Add tooltips to progress bars
    $('.slm-progress-container').each(function() {
        var $container = $(this);
        var $bar = $container.find('.slm-progress-bar');
        
        $container.on('mouseenter', function() {
            var width = $bar.css('width');
            var percentage = parseFloat(width) / parseFloat($container.width()) * 100;
            
            $container.attr('title', 'Storage Usage: ' + percentage.toFixed(1) + '%');
        });
    });
    
    // Smooth animations for progress bar updates
    $('.slm-progress-bar').each(function() {
        var $bar = $(this);
        var targetWidth = $bar.css('width');
        
        // Start from 0 and animate to target
        $bar.css('width', '0%');
        setTimeout(function() {
            $bar.css({
                'width': targetWidth,
                'transition': 'width 1s ease-in-out'
            });
        }, 100);
    });
    
    // Auto-refresh usage data every 5 minutes on admin pages
    if (window.location.href.indexOf('storage-limit-manager') !== -1) {
        setInterval(function() {
            $('#slm-recalculate').trigger('click');
        }, 300000); // 5 minutes
    }
    
    // Keyboard accessibility for recalculate button
    $('#slm-recalculate').on('keydown', function(e) {
        if (e.which === 13 || e.which === 32) { // Enter or Space
            e.preventDefault();
            $(this).trigger('click');
        }
    });
    
    // Form validation before submission
    $('form').on('submit', function(e) {
        var $form = $(this);
        var $storageInput = $form.find('input[name*="max_storage_mb"]');
        
        if ($storageInput.length) {
            var value = parseInt($storageInput.val());
            
            if (value < 1) {
                e.preventDefault();
                alert('Please enter a valid storage limit (minimum 1 MB).');
                $storageInput.focus();
                return false;
            }
        }
    });
});

// Add CSS for input messages
jQuery(document).ready(function($) {
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .slm-input-message {
                margin: 5px 0 0 0;
                font-size: 12px;
                font-style: italic;
            }
            .slm-input-message.error {
                color: #dc3232;
            }
            .slm-input-message.warning {
                color: #ff9800;
            }
        `)
        .appendTo('head');
});
