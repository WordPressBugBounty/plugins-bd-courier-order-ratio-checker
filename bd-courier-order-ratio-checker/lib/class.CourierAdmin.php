<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CourierAdmin
 * Handles admin display and AJAX endpoints for order details and orders list.
 */
class CourierAdmin {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_search_script' ] );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'display_order_ratio_in_admin' ] );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'display_order_ratio_column_content' ], 10, 2 );
        add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_order_ratio_column' ] );
        add_action( 'wp_ajax_refresh_courier_data_edit', [ $this, 'refresh_courier_data_edit' ] );
        add_action( 'wp_ajax_refresh_courier_data_list', [ $this, 'refresh_courier_data_list' ] );
        add_action( 'wp_ajax_fetch_order_ratios', [ $this, 'fetch_order_ratios' ] );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_search_script() {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script(
            'bdcourier-search-ajax',
            plugin_dir_url( __FILE__ ) . '../assets/js/admin.js',
            [ 'jquery' ],
            time(),
            true
        );
        wp_localize_script(
            'bdcourier-search-ajax',
            'bdcourierSearchAjax',
            [
                'ajaxurl'       => admin_url( 'admin-ajax.php' ),
                'search_nonce'  => wp_create_nonce( 'search_courier_data_nonce' ),
                'refresh_nonce' => wp_create_nonce( 'refresh_courier_data_nonce' ),
            ]
        );
        wp_enqueue_style(
            'bdcourier-admin-css',
            plugins_url( '../assets/css/admin.css', __FILE__ ),
            [],
            time()
        );
        // Enqueue dashicons for refresh button icons.
        wp_enqueue_style( 'dashicons' );
    }

    /**
     * Display full courier data table on the Order Edit page.
     *
     * @param WC_Order $order
     */
    public function display_order_ratio_in_admin( $order ) {
        $customer_phone = $order->get_billing_phone();
        if ( ! $customer_phone ) {
            echo '<p style="color: red;">' . esc_html__( 'No phone number found for this order.', 'bd-courier-order-ratio-checker' ) . '</p>';
            return;
        }
        $courier_data = get_post_meta( $order->get_id(), '_courier_data', true );
        echo '<button type="button" id="bdcrc-refresh-button" class="bdcrc-refresh-button bangla" data-order-id="' . esc_attr( $order->get_id() ) . '" data-context="edit">'
            . wp_kses( '<span class="dashicons dashicons-update"></span>', array(
                  'span' => array(
                      'class' => array(),
                  ),
              ) )
            . ' ' . ( $courier_data ? esc_html__( 'রিফ্রেশ কুরিয়ার ডেটা', 'bd-courier-order-ratio-checker' ) : esc_html__( 'Check Data', 'bd-courier-order-ratio-checker' ) )
            . '</button>';
        echo '<div id="courier-data-table">';
        if ( $courier_data ) {
            $this->display_courier_data( $courier_data );
        }
        echo '</div>';
    }

    /**
     * Add a new column for order ratio on Orders list.
     *
     * @param array $columns
     * @return array
     */
    public function add_order_ratio_column( $columns ) {
        $columns['order_ratio'] = __( 'Order Success Ratio', 'bd-courier-order-ratio-checker' );
        return $columns;
    }

    /**
     * Display courier summary in the Orders list column.
     *
     * @param string $column
     * @param int    $post_id
     */
    public function display_order_ratio_column_content( $column, $post_id ) {
        if ( 'order_ratio' === $column ) {
            $order = wc_get_order( $post_id );
            if ( ! $order ) {
                echo esc_html__( 'No order found', 'bd-courier-order-ratio-checker' );
                return;
            }
            $order_id     = $order->get_id();
            $courier_data = get_post_meta( $order_id, '_courier_data', true );
            echo '<div id="order-ratio-' . esc_attr( $order_id ) . '">';
            if ( $courier_data && isset( $courier_data['summary'] ) ) {
                $total_parcel   = isset( $courier_data['summary']['total_parcel'] ) ? (int) $courier_data['summary']['total_parcel'] : 0;
                $success_parcel = isset( $courier_data['summary']['success_parcel'] ) ? (int) $courier_data['summary']['success_parcel'] : 0;
                $cancel_parcel  = $total_parcel - $success_parcel;
                $success_ratio  = $total_parcel > 0 ? ( $success_parcel / $total_parcel ) * 100 : 0;
                $cancel_ratio   = 100 - $success_ratio;

                // Display summary details.
                echo '<div class="bd-courier-summary">';
                echo '<strong>' . esc_html__( 'All: ', 'bd-courier-order-ratio-checker' ) . '</strong>' . esc_html( $total_parcel ) . ' ';
                echo '<strong class="success-text">' . esc_html__( 'Success: ', 'bd-courier-order-ratio-checker' ) . '</strong>' . esc_html( $success_parcel ) . ' ';
                echo '<strong class="cancel-text">' . esc_html__( 'Cancel: ', 'bd-courier-order-ratio-checker' ) . '</strong>' . esc_html( $cancel_parcel );
                echo '</div>';

                // Inline container for progress bar and refresh button.
                echo '<div class="inline-container">';
                    // Using <span> for the progress bar with inner spans.
                    echo '<span class="bd-courier-progress-bar">';
                        echo '<span class="success-bar" style="width:' . esc_attr( $success_ratio ) . '%;"></span>';
                        if ( $cancel_ratio > 0 ) {
                            echo '<span class="cancel-bar" style="width:' . esc_attr( $cancel_ratio ) . '%;"></span>';
                        }
                        echo '<span class="progress-text">' . esc_html( number_format( $success_ratio, 1 ) ) . '</span>';
                    echo '</span>';
                    echo '<button type="button" class="bdcrc-refresh-button bangla" data-order-id="' . esc_attr( $order_id ) . '" data-context="list">'
                        . wp_kses( '<span class="dashicons dashicons-update"></span>', array(
                              'span' => array(
                                  'class' => array(),
                              ),
                          ) )
                        . '</button>';
                echo '</div>';
            } else {
                echo '<button type="button" class="bdcrc-refresh-button bangla" data-order-id="' . esc_attr( $order_id ) . '" data-context="list">'
                    . esc_html__( 'কুরিয়ার রেশিও চেক', 'bd-courier-order-ratio-checker' )
                    . '</button>';
            }
            echo '</div>';
        }
    }

    /**
     * Placeholder for additional ratio fetching logic.
     */
    public function fetch_order_ratios() {
        // Additional logic can be added here.
    }

    /**
     * AJAX endpoint: refresh courier data on Order Edit page.
     */
    public function refresh_courier_data_edit() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'refresh_courier_data_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Invalid nonce.', 'bd-courier-order-ratio-checker' ) );
            return;
        }
        if ( isset( $_POST['order_id'] ) && ! empty( $_POST['order_id'] ) ) {
            $order_id = intval( wp_unslash( $_POST['order_id'] ) );
        } else {
            wp_send_json_error( esc_html__( 'Invalid order ID.', 'bd-courier-order-ratio-checker' ) );
            return;
        }
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( esc_html__( 'Order not found.', 'bd-courier-order-ratio-checker' ) );
            return;
        }
        $phone = $order->get_billing_phone();
        if ( ! $phone ) {
            wp_send_json_error( esc_html__( 'No phone number found.', 'bd-courier-order-ratio-checker' ) );
            return;
        }
        $courier_data = CourierAPI::fetch_order_ratio_from_api( $phone );
        if ( $courier_data ) {
            update_post_meta( $order_id, '_courier_data', $courier_data );
            ob_start();
            $this->display_courier_data( $courier_data );
            $html = ob_get_clean();
            wp_send_json_success( [ 'table' => $html ] );
        } else {
            wp_send_json_error( esc_html__( 'Failed to fetch courier data.', 'bd-courier-order-ratio-checker' ) );
        }
    }

    /**
     * AJAX endpoint: refresh summary on Orders List page.
     */
    public function refresh_courier_data_list() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'refresh_courier_data_nonce' ) ) {
            wp_send_json_error( esc_html__( 'Invalid nonce.', 'bd-courier-order-ratio-checker' ) );
            return;
        }
        if ( isset( $_POST['order_id'] ) && ! empty( $_POST['order_id'] ) ) {
            $order_id = intval( wp_unslash( $_POST['order_id'] ) );
        } else {
            wp_send_json_error( esc_html__( 'Invalid order ID.', 'bd-courier-order-ratio-checker' ) );
            return;
        }
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( esc_html__( 'Order not found.', 'bd-courier-order-ratio-checker' ) );
            return;
        }
        $phone = $order->get_billing_phone();
        if ( ! $phone ) {
            wp_send_json_error( esc_html__( 'No phone number found.', 'bd-courier-order-ratio-checker' ) );
            return;
        }
        $courier_data = CourierAPI::fetch_order_ratio_from_api( $phone );
        if ( $courier_data && isset( $courier_data['summary'] ) ) {
            update_post_meta( $order_id, '_courier_data', $courier_data );
            $total_parcel   = isset( $courier_data['summary']['total_parcel'] ) ? (int) $courier_data['summary']['total_parcel'] : 0;
            $success_parcel = isset( $courier_data['summary']['success_parcel'] ) ? (int) $courier_data['summary']['success_parcel'] : 0;
            $cancel_parcel  = $total_parcel - $success_parcel;
            $success_ratio  = $total_parcel > 0 ? ( $success_parcel / $total_parcel ) * 100 : 0;
            $cancel_ratio   = 100 - $success_ratio;
            ob_start();
            echo '<div id="order-ratio-' . esc_attr( $order_id ) . '">';
                echo '<div class="bd-courier-summary">';
                echo '<strong>' . esc_html__( 'All: ', 'bd-courier-order-ratio-checker' ) . '</strong>' . esc_html( $total_parcel ) . ' ';
                echo '<strong class="success-text">' . esc_html__( 'Success: ', 'bd-courier-order-ratio-checker' ) . '</strong>' . esc_html( $success_parcel ) . ' ';
                echo '<strong class="cancel-text">' . esc_html__( 'Cancel: ', 'bd-courier-order-ratio-checker' ) . '</strong>' . esc_html( $cancel_parcel );
                echo '</div>';
                echo '<div class="inline-container">';
                    echo '<span class="bd-courier-progress-bar">';
                        echo '<span class="success-bar" style="width:' . esc_attr( $success_ratio ) . '%;"></span>';
                        if ( $cancel_ratio > 0 ) {
                            echo '<span class="cancel-bar" style="width:' . esc_attr( $cancel_ratio ) . '%;"></span>';
                        }
                        echo '<span class="progress-text">' . esc_html( number_format( $success_ratio, 1 ) ) . ' %</span>';
                    echo '</span>';
                    echo '<button type="button" class="bdcrc-refresh-button bangla" data-order-id="' . esc_attr( $order_id ) . '" data-context="list">'
                        . wp_kses( '<span class="dashicons dashicons-update"></span>', array(
                              'span' => array(
                                  'class' => array(),
                              ),
                          ) )
                        . '</button>';
                echo '</div>';
            echo '</div>';
            $html = ob_get_clean();
            wp_send_json_success( [ 'table' => $html ] );
        } else {
            wp_send_json_error( esc_html__( 'Failed to fetch courier data.', 'bd-courier-order-ratio-checker' ) );
        }
    }

    /**
     * Helper function to display a full courier data table.
     *
     * @param array $courier_data
     */
    private function display_courier_data( $courier_data ) {
        $total   = 0;
        $success = 0;
        $return  = 0;

        echo '<table class="bd-courier-table bangla">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'কুরিয়ার', 'bd-courier-order-ratio-checker' ) . '</th>';
        echo '<th>' . esc_html__( 'মোট', 'bd-courier-order-ratio-checker' ) . '</th>';
        echo '<th>' . esc_html__( 'সফল', 'bd-courier-order-ratio-checker' ) . '</th>';
        echo '<th>' . esc_html__( 'রিটার্ন', 'bd-courier-order-ratio-checker' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        foreach ( $courier_data as $courier => $data ) {
            if ( 'summary' !== $courier && is_array( $data ) ) {
                $t = isset( $data['total_parcel'] ) ? (int) $data['total_parcel'] : 0;
                $s = isset( $data['success_parcel'] ) ? (int) $data['success_parcel'] : 0;
                $r = $t - $s;
                $total   += $t;
                $success += $s;
                $return  += $r;
                $logo_path = plugin_dir_url( __FILE__ ) . '../assets/images/' . strtolower( $courier ) . '-logo.png';
                echo '<tr>';
                echo '<td><img src="' . esc_url( $logo_path ) . '" alt="' . esc_attr( ucfirst( $courier ) ) . ' ' . esc_html__( 'লোগো', 'bd-courier-order-ratio-checker' ) . '" class="bdcrc-courier-logo bangla"></td>';
                echo '<td class="bangla">' . esc_html( $t ) . '</td>';
                echo '<td class="bangla">' . esc_html( $s ) . '</td>';
                echo '<td class="bangla">' . esc_html( $r ) . '</td>';
                echo '</tr>';
            }
        }
        // Summary row.
        echo '<tr class="summary-row bangla">';
        echo '<td><strong>' . esc_html__( 'মোট', 'bd-courier-order-ratio-checker' ) . '</strong></td>';
        echo '<td class="bangla"><strong>' . esc_html( $total ) . '</strong></td>';
        echo '<td class="bangla"><strong>' . esc_html( $success ) . '</strong></td>';
        echo '<td class="bangla"><strong>' . esc_html( $return ) . '</strong></td>';
        echo '</tr>';
        echo '</tbody></table>';

        // Percentage bar below the table.
        if ( $total > 0 ) {
            $success_ratio = ( $success / $total ) * 100;
            $cancel_ratio  = 100 - $success_ratio;

            echo '<div class="bd-courier-progress-bar bangla">';
            echo '<span class="success-bar" style="width:' . esc_attr( $success_ratio ) . '%;"></span>';
            echo '<span class="cancel-bar" style="width:' . esc_attr( $cancel_ratio ) . '%;"></span>';
            echo '<span class="progress-text">' . esc_html( number_format( $success_ratio, 1 ) ) . ' %</span>';
            echo '</div>';
        }
    }
}
