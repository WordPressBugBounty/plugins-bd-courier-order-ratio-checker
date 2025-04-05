<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CourierAPI
 * Handles fetching courier data from the external API.
 */
class CourierAPI {

    /**
     * Fetch courier order ratio from API.
     *
     * @param string $phone The phone number.
     * @return array|null
     */
    public static function fetch_order_ratio_from_api( $phone ) {
        $api_token = get_option( 'bd_courier_api_token' );
        $api_paid  = get_option( 'bd_courier_api_paid' );
        if ( $api_paid == 1 ) {
            $url = 'https://bdcourier.com/api/pro/courier-check?phone=' . urlencode( $phone );
        } else {
            $url = 'https://bdcourier.com/api/courier-check?phone=' . urlencode( $phone );
        }
        $headers = [
            'Authorization' => 'Bearer ' . esc_attr( $api_token ),
            'Content-Type'  => 'application/json',
        ];
        $response = wp_remote_post( $url, [
            'headers' => $headers,
            'timeout' => 100,
        ] );
        if ( is_wp_error( $response ) ) {
            return null;
        }
        $body = wp_remote_retrieve_body( $response );

        $data = json_decode( $body, true );
        return isset( $data['courierData'] ) ? $data['courierData'] : null;
    }
}
