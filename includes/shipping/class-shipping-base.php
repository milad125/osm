<?php
/**
 * Base Shipping Method Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class OSM1_Shipping_Base extends WC_Shipping_Method {

    /**
     * Constructor
     */
    public function __construct($instance_id = 0) {
        $this->instance_id = absint($instance_id);
        $this->method_description = $this->get_shipping_method_description();
        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );
        
        $this->init();
    }

    /**
     * Initialize
     */
    public function init() {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', $this->get_default_title());
        $this->tax_status = $this->get_option('tax_status', 'taxable');
        $this->cost = $this->get_option('cost', 0);
        $this->enabled = $this->get_option('enabled', 'yes');

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Calculate shipping
     */
    public function calculate_shipping($package = array()) {
        $cost = $this->calculate_shipping_cost($package);
        
        if ($cost === false) {
            return;
        }

        // Add packaging cost
        $packaging_cost = $this->get_packaging_cost();
        $total_cost = $cost + $packaging_cost;

        $rate = array(
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $total_cost,
            'package' => $package,
        );

        $this->add_rate($rate);
    }

    /**
     * Calculate shipping cost (to be implemented by child classes)
     */
    abstract protected function calculate_shipping_cost($package);

    /**
     * Get shipping method description (can be overridden by child classes)
     */
    protected function get_shipping_method_description() {
        return __('روش ارسال پیشرفته', 'osm1');
    }

    /**
     * Get default title (to be implemented by child classes)
     */
    abstract protected function get_default_title();

    /**
     * Get packaging cost
     */
    protected function get_packaging_cost() {
        $cost = floatval(get_option('osm1_packaging_cost', 0));
        $type = get_option('osm1_packaging_cost_type', 'fixed');
        
        if ($type === 'percentage') {
            $cart_total = WC()->cart->get_subtotal();
            $cost = ($cart_total * $cost) / 100;
        }
        
        return $cost;
    }

    /**
     * Get distance between two points
     */
    protected function get_distance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371; // km

        $d_lat = deg2rad($lat2 - $lat1);
        $d_lng = deg2rad($lng2 - $lng1);

        $a = sin($d_lat / 2) * sin($d_lat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($d_lng / 2) * sin($d_lng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earth_radius * $c;

        return $distance;
    }

    /**
     * Get nearest shipping center
     */
    protected function get_nearest_center($lat, $lng) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'osm1_shipping_centers';
        $centers = $wpdb->get_results(
            "SELECT * FROM $table WHERE is_active = 1",
            ARRAY_A
        );

        $nearest = null;
        $min_distance = PHP_INT_MAX;

        foreach ($centers as $center) {
            $distance = $this->get_distance($lat, $lng, $center['latitude'], $center['longitude']);
            if ($distance < $min_distance) {
                $min_distance = $distance;
                $nearest = $center;
            }
        }

        return $nearest;
    }
}

