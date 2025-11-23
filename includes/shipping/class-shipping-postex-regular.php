<?php
/**
 * Postex Regular Shipping Method
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once OSM1_PLUGIN_DIR . 'includes/shipping/class-shipping-base.php';

class OSM1_Shipping_Postex_Regular extends OSM1_Shipping_Base {

    public function __construct($instance_id = 0) {
        $this->id = 'osm1_postex_regular';
        parent::__construct($instance_id);
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('فعال', 'osm1'),
                'type' => 'checkbox',
                'label' => __('فعال کردن پست معمولی', 'osm1'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('عنوان', 'osm1'),
                'type' => 'text',
                'default' => __('پست معمولی', 'osm1'),
            ),
        );
    }

    protected function get_shipping_method_description() {
        return __('ارسال از طریق پست معمولی (پستکس)', 'osm1');
    }

    protected function get_default_title() {
        return __('پست معمولی', 'osm1');
    }

    protected function calculate_shipping_cost($package) {
        $coords = $this->get_destination_coords($package);
        if (!$coords) {
            // Fallback: return a base cost so the method is visible
            $weight = WC()->cart->get_cart_contents_weight();
            if ($weight <= 0) $weight = 1;
            return 30000 + ($weight * 5000); // base + weight factor
        }

        $center = $this->get_nearest_center($coords['lat'], $coords['lng']);
        if (!$center) {
            return 30000;
        }

        $api = new OSM1_API_Postex();
        $costs = $api->calculate_cost($center, array('lat' => $coords['lat'], 'lng' => $coords['lng'], 'weight' => WC()->cart->get_cart_contents_weight()));

        if ($costs && is_array($costs) && isset($costs['regular']) && $costs['regular'] !== false) {
            return $costs['regular'];
        }

        // Fallback calculation
        $distance = $this->get_distance($center['latitude'], $center['longitude'], $coords['lat'], $coords['lng']);
        $base_cost = 30000;
        $distance_cost = $distance * 500;
        $weight_cost = (WC()->cart->get_cart_contents_weight() ?: 1) * 5000;
        return $base_cost + $distance_cost + $weight_cost;
    }
}
