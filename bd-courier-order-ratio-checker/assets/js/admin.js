jQuery(document).ready(function($) {
    // Refresh button for both Order Edit and Orders List pages
    $(document).on('click', '.bdcrc-refresh-button', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent event bubbling
        const button = $(this);
        const orderId = button.data('order-id');
        const context = button.data('context'); // "edit" or "list"
        console.log(`Refresh button clicked for order: ${orderId} with context: ${context}`);
        
        // Update button label based on context with Dashicon and spin animation
        if (context === 'edit') {
            button.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span> রিফ্রেশ হচ্ছে...');
        } else {
            button.prop('disabled', true).html('<span class="dashicons dashicons-update dashicons-spin"></span>');
        }
        
        const action = context === 'edit' ? 'refresh_courier_data_edit' : 'refresh_courier_data_list';
        
        $.ajax({
            url: bdcourierSearchAjax.ajaxurl,
            type: 'POST',
            data: {
                action: action,
                order_id: orderId,
                nonce: bdcourierSearchAjax.refresh_nonce
            },
            success: function(response) {
                if (response.success) {
                    if (context === 'edit') {
                        $('#courier-data-table').html(response.data.table);
                        button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> রিফ্রেশ কুরিয়ার ডেটা');
                    } else {
                        $('#order-ratio-' + orderId).html(response.data.table);
                        button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span>');
                    }
                } else {
                    alert('Error: ' + response.data);
                    restoreButton(context, button);
                }
            },
            error: function(xhr, status, error) {
                alert('Ajax error: ' + error);
                restoreButton(context, button);
            }
        });
        return false;
    });

    // Helper function to restore the button label after Ajax completes
    function restoreButton(context, button) {
        if (context === 'edit') {
            button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> রিফ্রেশ কুরিয়ার ডেটা');
        } else {
            button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span>');
        }
    }

    // Helper: Format phone number.
    function formatPhoneNumber(phone) {
        // Remove non-numeric characters.
        phone = phone.replace(/[^0-9]/g, '');
        // Check prefix with indexOf for broader browser support
        if (phone.indexOf('880') === 0) {
            phone = phone.slice(3);
        } else if (phone.indexOf('0') === 0) {
            phone = phone.slice(1);
        }
        if (phone.length === 10) {
            phone = '0' + phone;
        }
        return phone;
    }
});
