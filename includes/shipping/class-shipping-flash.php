<?php
/**
 * Flash Delivery Shipping Method
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once OSM1_PLUGIN_DIR . 'includes/shipping/class-shipping-base.php';

class OSM1_Shipping_Flash extends OSM1_Shipping_Base {

    /**
     * Constructor
     */
    public function __construct($instance_id = 0) {
        $this->id = 'osm1_flash';
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
                'label' => __('فعال کردن ارسال فلش', 'osm1'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('عنوان', 'osm1'),
                'type' => 'text',
                'description' => __('عنوان روش ارسال', 'osm1'),
                'default' => __('ارسال فلش', 'osm1'),
            ),
            'cost_multiplier' => array(
                'title' => __('ضریب هزینه', 'osm1'),
                'type' => 'number',
                'description' => __('ضریب هزینه برای ارسال فلش (مثلا 1.5 به معنای 50% بیشتر)', 'osm1'),
                'default' => 1.5,
                'step' => 0.1,
                'min' => 1,
            ),
        );
    }

    /**
     * Get shipping method description
     */
    protected function get_shipping_method_description() {
        return __('ارسال فلش - تحویل در همان روز', 'osm1');
    }

    /**
     * Get default title
     */
    protected function get_default_title() {
        return __('ارسال فلش', 'osm1');
    }

    /**
     * Calculate shipping cost
     */
    protected function calculate_shipping_cost($package) {
        $destination_lat = WC()->session->get('osm1_destination_lat');
        $destination_lng = WC()->session->get('osm1_destination_lng');

        if (!$destination_lat || !$destination_lng) {
            return false;
        }

        // Get base cost from another method (e.g., Alopeyk)
        $base_method = new OSM1_Shipping_Alopeyk();
        $base_cost = $base_method->calculate_shipping_cost($package);

        if ($base_cost === false) {
            return false;
        }

        // Apply multiplier
        $multiplier = floatval($this->get_option('cost_multiplier', 1.5));
        $flash_cost = $base_cost * $multiplier;

        return $flash_cost;
    }
}

