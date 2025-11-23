<?php
/**
 * Main Plugin Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Main {

    /**
     * Instance
     *
     * @var OSM1_Main
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return OSM1_Main
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once OSM1_PLUGIN_DIR . 'includes/class-activator.php';
        require_once OSM1_PLUGIN_DIR . 'includes/class-deactivator.php';
        
        // Load other core classes
        require_once OSM1_PLUGIN_DIR . 'includes/class-shipping-calculator.php';
        require_once OSM1_PLUGIN_DIR . 'includes/class-geocoder.php';
        require_once OSM1_PLUGIN_DIR . 'includes/class-ai-predictor.php';
        
        // Admin
        if (is_admin()) {
            require_once OSM1_PLUGIN_DIR . 'includes/admin/class-admin.php';
            new OSM1_Admin();
        }

        // Frontend
        require_once OSM1_PLUGIN_DIR . 'includes/frontend/class-frontend.php';
        if (!is_admin() || wp_doing_ajax()) {
            new OSM1_Frontend();
        }

        // Shipping methods
        require_once OSM1_PLUGIN_DIR . 'includes/shipping/class-shipping-methods.php';
        require_once OSM1_PLUGIN_DIR . 'includes/shipping/class-shipping-postex-regular.php';
        require_once OSM1_PLUGIN_DIR . 'includes/shipping/class-shipping-postex-express.php';
        new OSM1_Shipping_Methods();

        // API Manager
        require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-manager.php';
        require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-base.php';
        require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-tapin.php';
        require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-alopeyk.php';
        require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-snappbox.php';
        require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-tipax.php';
        require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-postex.php';
        new OSM1_API_Manager();

        // Tracking
        require_once OSM1_PLUGIN_DIR . 'includes/tracking/class-tracking.php';
        new OSM1_Tracking();

        // Notifications
        require_once OSM1_PLUGIN_DIR . 'includes/notifications/class-notifications.php';
        new OSM1_Notifications();

        // Gamification
        require_once OSM1_PLUGIN_DIR . 'includes/gamification/class-gamification.php';
        new OSM1_Gamification();

        // Advanced AI
        require_once OSM1_PLUGIN_DIR . 'includes/ai/class-ai-advanced.php';
        new OSM1_AI_Advanced();
    }

    /**
     * Load textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('osm1', false, dirname(OSM1_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (is_checkout() || is_cart()) {
            // Enqueue frontend script - make sure it loads after Google Maps if API key exists
            $dependencies = array('jquery', 'woocommerce');
            $api_key = get_option('osm1_google_maps_api_key', '');
            if (!empty($api_key)) {
                $dependencies[] = 'google-maps';
            }

            wp_enqueue_script(
                'osm1-frontend',
                OSM1_PLUGIN_URL . 'assets/js/frontend.js',
                $dependencies,
                OSM1_VERSION,
                true
            );

            wp_enqueue_style(
                'osm1-frontend',
                OSM1_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                OSM1_VERSION
            );

            // Vazir Font
            wp_enqueue_style(
                'vazir-font',
                'https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css',
                array(),
                null
            );

            // Google Maps API - باید قبل از frontend.js لود شود
            $api_key = get_option('osm1_google_maps_api_key', '');
            if (!empty($api_key)) {
                wp_enqueue_script(
                    'google-maps',
                    'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=places,geometry&language=fa',
                    array(),
                    null,
                    false // Load in header to ensure it loads before our script
                );
            }

            // Localize script
            wp_localize_script('osm1-frontend', 'osm1Data', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('osm1_nonce'),
                'apiKey' => $api_key,
                'mapCenter' => array(
                    'lat' => floatval(get_option('osm1_map_center_lat', 35.6892)),
                    'lng' => floatval(get_option('osm1_map_center_lng', 51.3890))
                ),
                'strings' => array(
                    'selectLocation' => __('انتخاب موقعیت روی نقشه', 'osm1'),
                    'calculating' => __('در حال محاسبه...', 'osm1'),
                    'error' => __('خطا در محاسبه هزینه', 'osm1'),
                    'mapError' => __('خطا در بارگذاری نقشه. لطفا API key را بررسی کنید.', 'osm1'),
                )
            ));
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'osm1') === false) {
            return;
        }

        wp_enqueue_script(
            'osm1-admin',
            OSM1_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-tabs', 'jquery-ui-accordion'),
            OSM1_VERSION,
            true
        );

        wp_enqueue_style(
            'osm1-admin',
            OSM1_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            OSM1_VERSION
        );

        // Vazir Font
        wp_enqueue_style(
            'vazir-font',
            'https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css',
            array(),
            null
        );

        wp_localize_script('osm1-admin', 'osm1Admin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('osm1_admin_nonce'),
        ));
    }
}

