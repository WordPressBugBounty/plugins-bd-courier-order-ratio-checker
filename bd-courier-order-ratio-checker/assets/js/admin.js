jQuery(document).ready(function($) {
    $('#bdcourier-search-form').on('submit', function(e) {
        e.preventDefault();
        
        var phone = $('#phone').val();
        var button = $(this).find('button');

        // Format the phone number
        phone = formatPhoneNumber(phone);

        // Validate that the phone number is 11 digits and numeric
        if (!/^\d{11}$/.test(phone)) {
            alert('Please enter a valid 11-digit phone number.');
            return; // Stop the form from submitting
        }

        button.prop('disabled', true).text('Searching...');

        $.ajax({
            url: bdcourierSearchAjax.ajaxurl, // Using localized ajaxurl
            type: 'POST',
            data: {
                action: 'search_courier_data',
                phone: phone,
                nonce: bdcourierSearchAjax.search_nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#courier-search-result')
                        .html(response.data.table)
                        .css('display', 'block');
                } else {
                    alert('Error: ' + response.data);
                }
                button.prop('disabled', false).text('Search');
            },
            error: function(xhr, status, error) {
                alert('Ajax error: ' + error);
                button.prop('disabled', false).text('Search');
            }
        });
    });

    $('.bdcrc-refresh-button').on('click', function() {
    var button = $(this);
    var orderId = button.data('order-id'); // Fetch the order ID from the button

    button.prop('disabled', true).html(' <i class="fas fa-sync fa-spin"></i> রিফ্রেশ হচ্ছে...'); // Change button text to Bangla

    $.ajax({
        url: bdcourierSearchAjax.ajaxurl, // Using localized ajaxurl
        type: 'POST',
        data: {
            action: 'refresh_courier_data',
            order_id: orderId,
            nonce: bdcourierSearchAjax.refresh_nonce // Correctly passing nonce as 'nonce'
        },
        success: function(response) {
            if (response.success) {
                $('#courier-data-table').html(response.data.table); // Update table with new data

                // Re-add the data-order-id after refresh
                button.data('order-id', orderId); // Retain the order ID
                button.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> রিফ্রেশ কুরিয়ার ডেটা');
            } else {
                alert('Error: ' + response.data);
                button.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> রিফ্রেশ কুরিয়ার ডেটা');
            }
        },
        error: function(xhr, status, error) {
            alert('Ajax error: ' + error);
            button.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> রিফ্রেশ কুরিয়ার ডেটা');
        }
    });
});


    // Function to format phone number
    function formatPhoneNumber(phone) {
        // Remove all non-digit characters except '+' 
        phone = phone.replace(/[^0-9]/g, '');

        // Handle specific cases for formatting
        if (phone.startsWith('880')) {
            phone = phone.slice(3); // Remove '880'
        } else if (phone.startsWith('+880')) {
            phone = phone.slice(4); // Remove '+880'
        } else if (phone.startsWith('0')) {
            phone = phone.slice(1); // Remove leading '0' if exists
        }

        // Ensure the phone number starts with '0'
        if (!phone.startsWith('0') && phone.length === 10) {
            phone = '0' + phone; // Add leading '0' if it's a 10-digit number
        }

        return phone; // Return the formatted phone number
    }
});

