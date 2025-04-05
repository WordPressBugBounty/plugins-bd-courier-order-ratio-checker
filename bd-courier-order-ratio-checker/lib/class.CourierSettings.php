<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CourierSettings
 * Manages the plugin settings page using Vue.js with a modern, ModuleGarden‑inspired design.
 */
class CourierSettings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_bd_courier_settings_page' ) );
        add_action( 'wp_ajax_save_courier_settings', array( $this, 'save_courier_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Add the BD Courier Settings menu page.
     */
    public function add_bd_courier_settings_page() {
        add_menu_page(
            'BD Courier Settings',
            'BD Courier Settings',
            'manage_options',
            'bd-courier-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-admin-generic',
            56
        );
    }

    /**
     * Enqueue Vue.js (locally) and custom admin JS.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( $hook !== 'toplevel_page_bd-courier-settings' ) {
            return;
        }

        wp_enqueue_script(
            'vue-js',
            plugins_url( 'assets/js/vue.js', dirname( __FILE__ ) ),
            [],
            '2.7.14',
            true
        );

        wp_enqueue_script(
            'bd-courier-admin',
            plugins_url( 'assets/js/admin-settings.js', dirname( __FILE__ ) ),
            [ 'vue-js', 'jquery' ],
            '1.0',
            true
        );

        wp_localize_script( 'bd-courier-admin', 'bdCourierSettings', [
            'apiToken' => get_option( 'bd_courier_api_token', '' ),
            'usePaid'  => (bool) get_option( 'bd_courier_api_paid', 0 ),
            'nonce'    => wp_create_nonce( 'save_courier_settings_nonce' ),
            'ajaxurl'  => admin_url( 'admin-ajax.php' ),
        ]);
    }

    /**
     * Render the Vue-powered settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="mg-settings-page">
            <div class="mg-logo">
                <img src="<?php echo esc_url( plugins_url( 'assets/images/logo.png', dirname( __FILE__ ) ) ); ?>" alt="Logo">
            </div>
            <div class="mg-card">
                <div id="vue-settings">
                    <div class="mg-card-header">
                        <h1>BD Courier Settings</h1>
                        <span v-if="saving" class="mg-spinner"></span>
                        <span v-else-if="successMessage" class="mg-success">{{ successMessage }}</span>
                    </div>
                    <div class="mg-card-body">
                        <div class="mg-form-group">
                            <label for="mg-api-token">API Token</label>
                            <input type="text" id="mg-api-token" v-model="apiToken" @focus="onFocus" @blur="onBlur">
                        </div>
                        <div class="mg-form-group">
                            <label>Are you using a Paid Package?</label>
                            <div class="mg-switch-container">
                                <span class="mg-switch-description">
                                    Enable this if you are using a paid package from bdcourier.com. For Free Package, keep disabled.
                                </span>
                                <label class="switch">
                                    <input type="checkbox" v-model="usePaid">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="mg-form-group mg-button-group">
                            <button @click="saveSettings" @mouseover="hoverButton" @mouseout="unhoverButton">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mg-extra-cards">
                <div class="mg-extra-card">
                    <div class="mg-extra-card-header">
                        <h2>Fraud Blocker & Incomplete Order Tracking</h2>
                    </div>
                    <div class="mg-extra-card-body">
                        <a href="https://diana.cx/bdplugins" target="_blank"><img style="max-width:100%" src="<?php echo esc_url( plugins_url( 'assets/images/fbio.png', dirname( __FILE__ ) ) ); ?>"></a>
                    </div>
                </div>
                <div class="mg-extra-card">
                    <div class="mg-extra-card-header">
                        <h2>Tutorial</h2>
                    </div>
                    <div class="mg-extra-card-body">
                        <div class="mg-video-container">
                            <iframe width="100%" height="200" src="https://www.youtube.com/embed/GaNfLcZFBNc" 
                                frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>.notice { display: none; }</style>
        <?php
    }

    /**
     * Handle AJAX request to save settings.
     */
    public function save_courier_settings() {
        if (
            ! isset( $_POST['_wpnonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'save_courier_settings_nonce' )
        ) {
            wp_send_json_error( 'Invalid nonce.' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized.' );
        }

        $apiToken = isset( $_POST['apiToken'] ) ? sanitize_text_field( wp_unslash( $_POST['apiToken'] ) ) : '';
        $usePaid  = isset( $_POST['usePaid'] ) ? intval( wp_unslash( $_POST['usePaid'] ) ) : 0;

        update_option( 'bd_courier_api_token', $apiToken );
        update_option( 'bd_courier_api_paid', $usePaid );

        wp_send_json_success();
    }
}
