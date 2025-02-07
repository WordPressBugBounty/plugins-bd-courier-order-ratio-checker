<?php

if (!defined('ABSPATH')) {
    exit;
}

class OrderRatioChecker {

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_search_script']);
        add_action('admin_menu', [$this, 'add_bd_courier_menu']);
        add_action('admin_menu', [$this, 'add_bd_courier_settings_page']);
        add_action('admin_init', [$this, 'register_bd_courier_settings']);
        add_shortcode('bdcourier_search', [$this, 'display_search_form']);
        add_action('wp_ajax_search_courier_data', [$this, 'search_courier_data']);
        add_action('wp_ajax_nopriv_search_courier_data', [$this, 'search_courier_data']);
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'display_order_ratio_in_admin']);
        add_action('wp_ajax_refresh_courier_data', [$this, 'refresh_courier_data']);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'display_order_ratio_column_content'], 10, 2);
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_order_ratio_column']);
        add_action('wp_ajax_fetch_order_ratios', [$this, 'fetch_order_ratios']);
    }
    public function display_order_ratio_in_admin($order) {
    $customer_phone = $order->get_billing_phone();

    if (!$customer_phone) {
        echo '<p style="color: red;">No phone number found for this order.</p>';
        return;
    }

    $courier_data = get_post_meta($order->get_id(), '_courier_data', true);

    if (!$courier_data) {
        $courier_data = $this->fetch_order_ratio_from_api($customer_phone);
        if ($courier_data) {
            update_post_meta($order->get_id(), '_courier_data', $courier_data);
        }
    }

    if ($courier_data) {
		echo '<button id="bdcrc-refresh-button" class="bdcrc-refresh-button bangla" data-order-id="' . esc_attr($order->get_id()) . '">
            <i class="fas fa-sync-alt"></i> রিফ্রেশ কুরিয়ার ডেটা
          </button>';
        echo '<div id="courier-data-table">';
        $this->display_courier_data($courier_data);
        echo '</div>';
       
    } else {
        echo '<p style="color: red;">Failed to fetch courier data.</p>';
    }
}

    
    public function add_order_ratio_column($columns) {
        $columns['order_ratio'] = __('Order Success Ratio', 'bd-courier-order-ratio-checker');
        return $columns;
    }

    public function display_order_ratio_column_content($column, $post_id) {
    if ('order_ratio' === $column) {
        $order = wc_get_order($post_id);

        if (!$order) {
            echo esc_html__('No order found', 'bd-courier-order-ratio-checker');
            return;
        }

        $order_id = $order->get_id();
        $courier_data = get_post_meta($order_id, '_courier_data', true);

        if (!$courier_data || !isset($courier_data['summary'])) {
            $customer_phone = $order->get_billing_phone();
            if ($customer_phone) {
                $courier_data = $this->fetch_order_ratio_from_api($customer_phone);
                if ($courier_data) {
                    update_post_meta($order_id, '_courier_data', $courier_data);
                }
            }
        }

        if ($courier_data && isset($courier_data['summary'])) {
            $total_parcel = isset($courier_data['summary']['total_parcel']) ? (int) $courier_data['summary']['total_parcel'] : 0;
            $success_parcel = isset($courier_data['summary']['success_parcel']) ? (int) $courier_data['summary']['success_parcel'] : 0;
            $cancel_parcel = $total_parcel - $success_parcel;

            $success_ratio = $total_parcel > 0 ? ($success_parcel / $total_parcel) * 100 : 0;
            $cancel_ratio = 100 - $success_ratio;

            // Escaping HTML output
            echo '<div class="bd-courier-progress-bar">';
            echo '<div class="success-bar" style="width: ' . esc_attr($success_ratio) . '%"></div>';
            if ($cancel_ratio > 0) {
                echo '<div class="cancel-bar" style="width: ' . esc_attr($cancel_ratio) . '%"></div>';
            }
            echo '</div>';

            echo '<div class="bd-courier-summary">';
            echo '<strong>' . esc_html__('All: ', 'bd-courier-order-ratio-checker') . '</strong>' . esc_html($total_parcel) . '&nbsp;&nbsp;';
            echo '<strong class="success-text">' . esc_html__('Success: ', 'bd-courier-order-ratio-checker') . '</strong>' . esc_html($success_parcel) . '&nbsp;&nbsp;';
            echo '<strong class="cancel-text">' . esc_html__('Cancel: ', 'bd-courier-order-ratio-checker') . '</strong>' . esc_html($cancel_parcel);
            echo '</div>';
        } else {
             echo '<button class="bdcrc-fetch-data-btn" data-order-id="' . esc_attr($order_id) . '">' . esc_html__('Check Data', 'bd-courier-order-ratio-checker') . '</button>';
        }
    }
}



    

    public function add_bd_courier_menu() {
        add_menu_page(
            'Courier Search',
            'Courier Search',
            'manage_options',
            'bd-courier-search',
            [$this, 'render_search_page'],
            'dashicons-search',
            25
        );
    }

    public function add_bd_courier_settings_page() {
        add_menu_page(
            'BD Courier Settings',
            'BD Courier Settings',
            'manage_options',
            'bd-courier-settings',
            [$this, 'render_settings_page'],
            'dashicons-admin-generic',
            56
        );
    }

    public function render_settings_page() {
        ?>
        <div class="bdcrc-container">
    <div class="bdcrc-box">
        <div class="bdcrc-horizontal-tabs">
            <div class="bdcrc-logo">
                <img src="<?php echo esc_url(plugins_url('assets/images/logo.png', __DIR__)); ?>" alt="<?php echo esc_attr__('Logo', 'bd-courier-order-ratio-checker'); ?>" class="bdcrc-logo-img">

            </div>
   
        </div>

        <div class="bdcrc-tab-body">
            <!-- Settings Tab Content -->
            <div id="" class="bdcrc-content-section">
                <form method="post" action="options.php" class="bdcrc-settings-form">
    <?php 
    // This generates hidden fields necessary for WordPress settings
    settings_fields('bd_courier_settings_group'); 
    ?>
    
    
    <h2><?php esc_html_e('API Settings', 'bd-courier-order-ratio-checker'); ?></h2>

    <div class="bdcrc-form-group">
    <label for="bd_courier_api_token" class="bdcrc-label"><?php esc_html_e('API Token', 'bd-courier-order-ratio-checker'); ?></label>
    <input type="text" name="bd_courier_api_token" id="bd_courier_api_token" value="<?php echo esc_attr(get_option('bd_courier_api_token')); ?>" class="bdcrc-form-control">
</div>

    <div class="bdcrc-form-group">
        <input type="submit" name="submit" id="submit" class="bdcrc-btn bdcrc-btn-primary" value="<?php esc_attr_e('Save Changes', 'bd-courier-order-ratio-checker'); ?>">
    </div>
</form>

<div class="bdcrc-support-card mb-4 bangla" style="margin-top:15px">
                    <div class="bdcrc-support-card-body">
                        <h5 class="bdcrc-support-card-title"><b>আমাদের অফিসিয়াল সাপোর্ট গ্রুপে যোগ দিন</b></h5>
                        <p>আমাদের সাথে সংযুক্ত হোন এবং সরাসরি আমাদের টিম থেকে সাপোর্ট নিন। নিচের বাটনে ক্লিক করে আমাদের অফিসিয়াল হোয়াটসঅ্যাপ গ্রুপে যোগ দিন এবং দ্রুত সহায়তা এবং আপডেট পান!</p>
                        <a href="https://chat.whatsapp.com/E47tFfzImWO09OTFe1GE43" class="bdcrc-btn bdcrc-btn-whatsapp" target="_blank">
                            <i class="fab fa-whatsapp"></i> হোয়াটসঅ্যাপ গ্রুপে যোগ দিন
                        </a>
                    </div>
                      </div>
                     <div class="bdcrc-support-card mb-4 bangla">
                    <div class="bdcrc-support-card-body">
                        <h5 class="bdcrc-support-card-title"><b>আমাদের অফিসিয়াল ফেসবুক গ্রুপে যোগ দিন</b></h5>
                        <p>আমাদের সাথে সংযুক্ত হোন এবং সরাসরি আমাদের টিম থেকে সাপোর্ট নিন। নিচের বাটনে ক্লিক করে আমাদের অফিসিয়াল ফেসবুক গ্রুপে যোগ দিন এবং দ্রুত সহায়তা এবং আপডেট পান!</p>
                        <a href="https://www.facebook.com/groups/bdcourier" class="bdcrc-btn bdcrc-btn-facebook" target="_blank">
                            <i class="fab fa-facebook"></i> ফেসবুক গ্রুপে যোগ দিন
                        </a>
                    </div>
                </div>
                  <div class="bdcrc-support-card mb-4 bangla">
                    <div class="bdcrc-support-card-body">
                        <h5 class="bdcrc-support-card-title"><b>আমাদের অফিসিয়াল ফেসবুক পেজ ফলো করুন</b></h5>
                        <p>সর্বশেষ আপডেট এবং তথ্য পেতে আমাদের ফেসবুক পেজটি ফলো করুন।</p>
                        <a href="https://www.facebook.com/BDcourierORC/" class="bdcrc-btn bdcrc-btn-facebook" target="_blank">
                            <i class="fab fa-facebook"></i> ফেসবুক পেজে যান
                        </a>
                    </div>
                </div>
                
              

            </div>
            
          
        </div>
    </div>
</div>

        <?php
    }

    public function register_bd_courier_settings() {
        register_setting('bd_courier_settings_group', 'bd_courier_api_token');

        add_settings_section('bd_courier_settings_section', 'API Settings', null, 'bd-courier-settings');

        add_settings_field('bd_courier_api_token', 'API Token', [$this, 'render_api_token_field'], 'bd-courier-settings', 'bd_courier_settings_section');
    }

    public function render_api_token_field() {
        $api_token = get_option('bd_courier_api_token');
        echo '<input type="text" name="bd_courier_api_token" value="' . esc_attr($api_token) . '" class="regular-text" />';
    }

    public function render_search_page() {
        ?>
         <div class="bdcrc-container">
    <div class="bdcrc-box">
        <div class="bdcrc-horizontal-tabs">
            <div class="bdcrc-logo">
                <img src="<?php echo esc_url(plugins_url('assets/images/logo.png', __DIR__)); ?>" alt="<?php echo esc_attr__('Logo', 'bd-courier-order-ratio-checker'); ?>" class="bdcrc-logo-img">

            </div>
         
        </div>

        <div class="bdcrc-tab-body">

            
            <!-- Checkout Tab Content -->
               <div id="courier-search-container">
            <form id="bdcourier-search-form" method="post">
                <?php wp_nonce_field('bdcourier_search_nonce_action', 'bdcourier_search_nonce_field'); ?>
                <input type="text" id="phone" name="phone" placeholder="Enter Phone Number" required>
                <button type="submit">Search</button>
            </form>
            <div id="courier-search-result"></div>
        </div>

       
        </div>
    </div>
</div>
        <?php
    }

   public function enqueue_search_script() {
    // Enqueue jQuery
    wp_enqueue_script('jquery');

    // Enqueue the custom admin JS script with timestamp version to prevent caching
    wp_enqueue_script(
        'bdcourier-search-ajax',
        plugin_dir_url(__DIR__) . 'assets/js/admin.js',
        ['jquery'],
        time(),  // Use current time as version
        true
    );

    // Localize script to pass ajaxurl and nonce
    wp_localize_script(
        'bdcourier-search-ajax',
        'bdcourierSearchAjax',
        [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'search_nonce'   => wp_create_nonce('search_courier_data_nonce'),
            'refresh_nonce'   => wp_create_nonce('refresh_courier_data_nonce'),
        ]
    );

    // Enqueue admin CSS with timestamp version to prevent caching
    wp_enqueue_style(
        'bdcourier-admin-css',
        plugins_url('assets/css/admin.css', __DIR__),  // CSS path
        [],
        time()  // Use current time as version
    );
}



    public function search_courier_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'search_courier_data_nonce')) {
            wp_send_json_error(__('Invalid nonce.', 'bd-courier-order-ratio-checker'));
            return;
        }

        if (isset($_POST['phone']) && !empty($_POST['phone'])) {
            $phone = sanitize_text_field(wp_unslash($_POST['phone']));
        } else {
            wp_send_json_error(__('Phone number is required.', 'bd-courier-order-ratio-checker'));
        }

        $courier_data = $this->fetch_order_ratio_from_api($phone);

        if ($courier_data) {
            ob_start();
            $this->display_courier_data($courier_data);
            $table_html = ob_get_clean();
            wp_send_json_success(['table' => $table_html]);
        } else {
            wp_send_json_error(__('Failed to fetch data from API.', 'bd-courier-order-ratio-checker'));
        }
    }

    private function fetch_order_ratio_from_api($phone) {
        $api_token = get_option('bd_courier_api_token');
        $url = 'https://bdcourier.com/api/courier-check?phone=' . urlencode($phone);
        $headers = [
            'Authorization' => 'Bearer ' . esc_attr($api_token),
            'Content-Type'  => 'application/json',
        ];

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'timeout' => 100,
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return isset($data['courierData']) ? $data['courierData'] : null;
    }

private function display_courier_data($courier_data) {

    echo '<table class="bd-courier-table bangla">';
    echo '<thead><tr><th>কুরিয়ার</th><th>মোট</th><th>সফল</th><th>রিটার্ন</th></tr></thead><tbody>';

    foreach ($courier_data as $courier => $data) {
        if ($courier !== 'summary' && is_array($data)) {
            $total_parcel = isset($data['total_parcel']) ? $data['total_parcel'] : 'এন/এ';
            $success_parcel = isset($data['success_parcel']) ? $data['success_parcel'] : 'এন/এ';
            $return_parcel = isset($data['total_parcel']) && isset($data['success_parcel']) ? $total_parcel - $success_parcel : 'এন/এ';

            $logo_path = plugin_dir_url(__DIR__) . 'assets/images/' . strtolower($courier) . '-logo.png';

            echo '<tr>';
            echo '<td><img src="' . esc_url($logo_path) . '" alt="' . esc_html(ucfirst($courier)) . ' লোগো" class="bdcrc-courier-logo bangla"></td>';
            echo '<td class="bangla">' . esc_html($total_parcel) . '</td>';
            echo '<td class="bangla">' . esc_html($success_parcel) . '</td>';
            echo '<td class="bangla">' . esc_html($return_parcel) . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';

    // Summary row
    if (isset($courier_data['summary']) && is_array($courier_data['summary'])) {
        $total_parcel = isset($courier_data['summary']['total_parcel']) ? $courier_data['summary']['total_parcel'] : 'এন/এ';
        $success_parcel = isset($courier_data['summary']['success_parcel']) ? $courier_data['summary']['success_parcel'] : 'এন/এ';
        $success_ratio = isset($courier_data['summary']['success_ratio']) ? $courier_data['summary']['success_ratio'] : 0;
        $return_ratio = $total_parcel > 0 ? 100 - $success_ratio : 0;

        // Progress bar for Success/Return ratio
        echo '<div class="bdcrc-progress-bar-wrapper bangla">';
        
        // Only show the success bar if the success ratio is greater than zero
        if ($success_ratio > 0) {
            echo '<div class="bdcrc-progress-bar bdcrc-success-bar bangla" style="width:' . esc_attr($success_ratio) . '%;"></div>';
        }

        // Only show the return bar if the return ratio is greater than zero
        if ($return_ratio > 0) {
            echo '<div class="bdcrc-progress-bar bdcrc-cancel-bar bangla" style="width:' . esc_attr($return_ratio) . '%;"></div>';
        }

        // Only show the text if both success and return ratios are greater than zero
        if ($success_ratio > 0 || $return_ratio > 0) {
            echo '<span class="bdcrc-progress-bar-text bangla">' . esc_html($success_ratio) . '% সফল / ' . esc_html($return_ratio) . '% রিটার্ন</span>';
        }

        echo '</div>'; // End progress bar wrapper

        // Container for summary pills and refresh button
        echo '<div class="bdcrc-summary-container bangla">';

        // Summary pills display
        echo '<div class="bdcrc-summary-pills bangla">';
        echo '<span class="bdcrc-pill bdcrc-total-pill bangla">মোট : ' . esc_html($total_parcel) . '</span>';
        echo '<span class="bdcrc-pill bdcrc-success-pill bangla">সফল : ' . esc_html($success_parcel) . '</span>';
        echo '<span class="bdcrc-pill bdcrc-cancel-pill bangla">রিটার্ন : ' . esc_html($total_parcel - $success_parcel) . '</span>';
        echo '</div>'; // End summary pills



        echo '</div>'; // End summary container
    }
}


   public function refresh_courier_data() {
    // Verify the nonce to ensure request authenticity
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'refresh_courier_data_nonce')) {
        wp_send_json_error(__('Invalid nonce.', 'bd-courier-order-ratio-checker'));
        return;
    }

    // Validate and sanitize the order ID
    if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
        $order_id = intval(wp_unslash($_POST['order_id']));
    } else {
        wp_send_json_error(__('Invalid order ID.', 'bd-courier-order-ratio-checker'));
        return;
    }

    // Get the order object
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(__('Order not found.', 'bd-courier-order-ratio-checker'));
        return;
    }

    // Retrieve the phone number associated with the order
    $phone = $order->get_billing_phone();
    if (!$phone) {
        wp_send_json_error(__('No phone number found.', 'bd-courier-order-ratio-checker'));
        return;
    }

    // Fetch courier data using the API
    $courier_data = $this->fetch_order_ratio_from_api($phone);
    if ($courier_data) {
        // Save the courier data in the order's post meta
        update_post_meta($order_id, '_courier_data', $courier_data);

        // Buffer the output of the courier data display
        ob_start();
        $this->display_courier_data($courier_data);
        $table_html = ob_get_clean();

        // Send the updated table back in the AJAX response
        wp_send_json_success(['table' => $table_html]);
    } else {
        wp_send_json_error(__('Failed to fetch courier data.', 'bd-courier-order-ratio-checker'));
    }
}

}
