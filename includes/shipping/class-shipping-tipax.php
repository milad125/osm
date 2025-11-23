<?php
/**
 * Tipax Shipping Method
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once OSM1_PLUGIN_DIR . 'includes/shipping/class-shipping-base.php';

class OSM1_Shipping_Tipax extends OSM1_Shipping_Base {

    /**
     * Constructor
     */
    public function __construct($instance_id = 0) {
        $this->id = 'osm1_tipax';
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
                'label' => __('فعال کردن تیپاکس', 'osm1'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('عنوان', 'osm1'),
                'type' => 'text',
                'description' => __('عنوان روش ارسال', 'osm1'),
                'default' => __('تیپاکس', 'osm1'),
            ),
            'cost' => array(
                'title' => __('هزینه ثابت', 'osm1'),
                'type' => 'number',
                'description' => __('هزینه ثابت ارسال (ریال)', 'osm1'),
                'default' => 0,
            ),
        );
    }

    /**
     * Get method description
     */
    protected function get_method_description() {
        return __('ارسال از طریق تیپاکس', 'osm1');
    }

    /**
     * Get default title
     */
    protected function get_default_title() {
        return __('تیپاکس', 'osm1');
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

        $center = $this->get_nearest_center($destination_lat, $destination_lng);
        if (!$center) {
            return false;
        }

        $api = new OSM1_API_Tipax();
        $cost = $api->calculate_cost($center, array(
            'lat' => $destination_lat,
            'lng' => $destination_lng,
            'weight' => WC()->cart->get_cart_contents_weight(),
        ));

        if ($cost === false) {
            $distance = $this->get_distance(
                $center['latitude'],
                $center['longitude'],
                $destination_lat,
                $destination_lng
            );
            $cost = floatval($this->cost) + ($distance * 1200);
        }

        return $cost;
    }
}

