/**
 * My Custom Plugin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Add click event to plugin messages
        $('.my-custom-plugin-message').on('click', function() {
            $(this).fadeOut(300).fadeIn(300);
        });

        // Console log for debugging
        console.log('My Custom Plugin JavaScript loaded successfully!');
        
        // Example AJAX call (uncomment if needed)
        /*
        $('.my-custom-plugin-button').on('click', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'my_custom_plugin_action',
                    nonce: ajax_object.nonce,
                    // Add your data here
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', error);
                }
            });
        });
        */
    });

})(jQuery);
