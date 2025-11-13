/**
 * GitSync Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Manual sync button handler
        $('#gitsync-manual-sync').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $status = $('#gitsync-sync-status');
            
            // Disable button and show loading state
            $button.prop('disabled', true);
            $status
                .removeClass('success error')
                .addClass('loading')
                .html('<span class="gitsync-spinner"></span> ' + gitsyncAdmin.strings.syncing)
                .show();
            
            // Send AJAX request
            $.ajax({
                url: gitsyncAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gitsync_manual_sync',
                    nonce: gitsyncAdmin.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    
                    if (response.success) {
                        $status
                            .removeClass('loading error')
                            .addClass('success')
                            .html(formatSuccessMessage(response.data));
                    } else {
                        $status
                            .removeClass('loading success')
                            .addClass('error')
                            .html(formatErrorMessage(response.data));
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false);
                    $status
                        .removeClass('loading success')
                        .addClass('error')
                        .html(gitsyncAdmin.strings.sync_error + ' ' + error);
                }
            });
        });
        
        /**
         * Format success message
         */
        function formatSuccessMessage(data) {
            var message = '<strong>' + gitsyncAdmin.strings.sync_complete + '</strong>';
            
            if (data.data) {
                message += '<br><br>';
                message += 'Processed: ' + data.data.processed + '<br>';
                message += 'Created: ' + data.data.created + '<br>';
                message += 'Updated: ' + data.data.updated + '<br>';
                message += 'Skipped: ' + data.data.skipped + '<br>';
                message += 'Errors: ' + data.data.errors;
            }
            
            return message;
        }
        
        /**
         * Format error message
         */
        function formatErrorMessage(data) {
            var message = '<strong>' + gitsyncAdmin.strings.sync_error + '</strong>';
            
            if (data.message) {
                message += '<br>' + data.message;
            }
            
            if (data.data && Array.isArray(data.data)) {
                message += '<br><br><em>Details:</em><br>';
                message += data.data.join('<br>');
            }
            
            return message;
        }
    });

})(jQuery);
