<?php
/**
 * Tapin Shipping Method
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once OSM1_PLUGIN_DIR . 'includes/shipping/class-shipping-base.php';

class OSM1_Shipping_Tapin extends OSM1_Shipping_Base {

    /**
     * Constructor
     */
    public function __construct($instance_id = 0) {
        $this->id = 'osm1_tapin';
        parent::__construct($instance_id);
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('فعال', 'osm1'),
                'type' => 'checkbox',
                'label' => __('فعال کردن پست تاپین', 'osm1'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('عنوان', 'osm1'),
                'type' => 'text',
                'description' => __('عنوان روش ارسال', 'osm1'),
                'default' => __('پست تاپین', 'osm1'),
            ),
            'cost' => array(
                'title' => __('هزینه ثابت', 'osm1'),
                'type' => 'number',
                'description' => __('هزینه ثابت ارسال (ریال)', 'osm1'),
                'default' => 0,
                'custom_attributes' => array(
                    'step' => '1000',
                    'min' => '0',
                ),
            ),
            'cost_per_km' => array(
                'title' => __('هزینه به ازای کیلومتر', 'osm1'),
                'type' => 'number',
                'description' => __('هزینه به ازای هر کیلومتر (ریال)', 'osm1'),
                'default' => 1000,
            ),
        );
    }

    /**
     * Get shipping method description
     */
    protected function get_shipping_method_description() {
        return __('ارسال از طریق پست تاپین', 'osm1');
    }

    /**
     * Get default title
     */
    protected function get_default_title() {
        return __('پست تاپین', 'osm1');
    }

    /**
     * Calculate shipping cost
     */
    protected function calculate_shipping_cost($package) {
        // Get destination from package or session
        $destination_lat = WC()->session->get('osm1_destination_lat');
        $destination_lng = WC()->session->get('osm1_destination_lng');

        if (!$destination_lat || !$destination_lng) {
            return false;
        }

        // Get nearest center
        $center = $this->get_nearest_center($destination_lat, $destination_lng);
        if (!$center) {
            return false;
        }

        // Calculate distance
        $distance = $this->get_distance(
            $center['latitude'],
            $center['longitude'],
            $destination_lat,
            $destination_lng
        );

        // Calculate cost
        $base_cost = floatval($this->cost);
        $distance_cost = $distance * floatval($this->get_option('cost_per_km', 1000));
        $total_cost = $base_cost + $distance_cost;

        // Try to get cost from API
        $api = new OSM1_API_Tapin();
        $api_cost = $api->calculate_cost($center, array(
            'lat' => $destination_lat,
            'lng' => $destination_lng,
        ));

        if ($api_cost !== false) {
            $total_cost = $api_cost;
        }

        return $total_cost;
    }
}

