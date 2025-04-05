<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CourierSearch
 * Handles the Courier Search admin page and its AJAX endpoint.
 */
class CourierSearch {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_bd_courier_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_search_courier_data', array( $this, 'search_courier_data' ) );
        add_action( 'wp_ajax_nopriv_search_courier_data', array( $this, 'search_courier_data' ) );
    }

    public function enqueue_scripts( $hook ) {
    if ( $hook !== 'toplevel_page_bd-courier-search' ) {
        return;
    }

    wp_enqueue_script(
        'sweetalert2',
        plugins_url( 'assets/js/sweetalert2.all.min.js
', dirname( __FILE__ ) ),
        array(),
        '11.0.0',
        true
    );
}


    public function add_bd_courier_menu() {
        add_menu_page(
            'Courier Search',
            'Courier Search',
            'manage_options',
            'bd-courier-search',
            array( $this, 'render_search_page' ),
            'dashicons-search',
            25
        );
    }

    public function render_search_page() {
        ?>
        <style>
            /* Same CSS styles as before */
        </style>
        <div class="mg-settings-page">
            <div class="mg-logo">
                <img src="<?php echo esc_url( plugins_url('assets/images/logo.png', dirname(__FILE__) ) ); ?>" alt="Logo">
            </div>
            <div class="mg-card">
                <div class="mg-card-header">
                    <h1>Courier Search</h1>
                </div>
                <div class="mg-card-body">
                    <div class="mg-form-group">
                        <form id="bdcourier-search-form" class="mg-search-form" method="post">
                            <?php wp_nonce_field( 'search_courier_data_nonce', 'nonce' ); ?>
                            <input type="text" id="phone" name="phone" placeholder="Enter Phone Number" required>
                            <button type="submit">Search</button>
                        </form>
                    </div>
                    <div class="mg-form-group mg-result-wrapper">
                        <div id="courier-search-result"></div>
                    </div>
                </div>
                <div class="mg-card-footer">
                    Search Powered By <a href="https://bdcourier.com" target="_blank">bdcourier.com</a>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($){
                $('#bdcourier-search-form').on('submit', function(e){
                    e.preventDefault();
                    var phone = $('#phone').val();
                    var nonce = $('#nonce').val();
                    $('#courier-search-result').html('').hide();
                    Swal.fire({
                        title: 'Please wait...',
                        text: 'We are checking Data From Courier Server',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'search_courier_data',
                            phone: phone,
                            nonce: nonce
                        },
                        success: function(response){
                            Swal.close();
                            if(response.success){
                                $('#courier-search-result').html(response.data.table).show();
                            } else {
                                Swal.fire('Error', response.data, 'error');
                            }
                        },
                        error: function(){
                            Swal.close();
                            Swal.fire('Error', 'AJAX error occurred', 'error');
                        }
                    });
                });
            });
        </script>
        <?php
    }

   public function search_courier_data() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'search_courier_data_nonce' ) ) {
        wp_send_json_error( __( 'Invalid nonce.', 'bd-courier-order-ratio-checker' ) );
    }

    if ( empty( $_POST['phone'] ) ) {
        wp_send_json_error( __( 'Phone number is required.', 'bd-courier-order-ratio-checker' ) );
    }

    $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ) );
    $courier_data = CourierAPI::fetch_order_ratio_from_api( $phone );

    if ( $courier_data ) {
        $total_sum   = 0;
        $success_sum = 0;

        ob_start();
        ?>
        <style>
            .progress-bar-container {
                background: #f1f1f1;
                border-radius: 5px;
                overflow: hidden;
                margin-top: 10px;
                height: 20px;
            }
            .progress-bar-success {
                background-color: #34C759;
                height: 100%;
                float: left;
                text-align: center;
                color: white;
                line-height: 20px;
                font-size: 12px;
            }
            .progress-bar-fail {
                background-color: #E73534;
                height: 100%;
                float: left;
                text-align: center;
                color: white;
                line-height: 20px;
                font-size: 12px;
            }
        </style>
        <table class="bd-courier-table bangla">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'কুরিয়ার', 'bd-courier-order-ratio-checker' ); ?></th>
                    <th><?php esc_html_e( 'মোট', 'bd-courier-order-ratio-checker' ); ?></th>
                    <th><?php esc_html_e( 'সফল', 'bd-courier-order-ratio-checker' ); ?></th>
                    <th><?php esc_html_e( 'রিটার্ন', 'bd-courier-order-ratio-checker' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ( $courier_data as $courier => $data ) {
                    if ( 'summary' !== $courier && is_array( $data ) ) {
                        $total_parcel   = isset( $data['total_parcel'] ) ? (int) $data['total_parcel'] : 0;
                        $success_parcel = isset( $data['success_parcel'] ) ? (int) $data['success_parcel'] : 0;
                        $return_parcel  = $total_parcel - $success_parcel;

                        $total_sum   += $total_parcel;
                        $success_sum += $success_parcel;

                        $logo_path = plugin_dir_url( __FILE__ ) . '../assets/images/' . strtolower( $courier ) . '-logo.png';
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo esc_url( $logo_path ); ?>" alt="<?php echo esc_attr( ucfirst( $courier ) . ' লোগো' ); ?>" class="bdcrc-courier-logo bangla">
                            </td>
                            <td class="bangla"><?php echo esc_html( $total_parcel ); ?></td>
                            <td class="bangla"><?php echo esc_html( $success_parcel ); ?></td>
                            <td class="bangla"><?php echo esc_html( $return_parcel ); ?></td>
                        </tr>
                        <?php
                    }
                }

                $return_sum      = $total_sum - $success_sum;
                $success_percent = $total_sum > 0 ? round( ( $success_sum / $total_sum ) * 100 ) : 0;
                $fail_percent    = 100 - $success_percent;
                ?>
                <tr style="font-weight: bold; background: #ecf0f1;">
                    <td><?php esc_html_e( 'সারাংশ', 'bd-courier-order-ratio-checker' ); ?></td>
                    <td class="bangla"><?php echo esc_html( $total_sum ); ?></td>
                    <td class="bangla"><?php echo esc_html( $success_sum ); ?></td>
                    <td class="bangla"><?php echo esc_html( $return_sum ); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="progress-bar-container">
            <div class="progress-bar-success" style="width: <?php echo esc_attr( $success_percent ); ?>%;">
                <?php echo esc_html( $success_percent ); ?>% সফল
            </div>
            <div class="progress-bar-fail" style="width: <?php echo esc_attr( $fail_percent ); ?>%;">
                <?php echo esc_html( $fail_percent ); ?>% রিটার্ন
            </div>
        </div>
        <?php
        $table_html = ob_get_clean();
        wp_send_json_success( [ 'table' => $table_html ] );
    } else {
        wp_send_json_error( __( 'Failed to fetch data from API.', 'bd-courier-order-ratio-checker' ) );
    }
}

}
