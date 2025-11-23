<?php
/**
 * Shipping Methods Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Shipping_Methods {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_methods'));
    }

    /**
     * Add shipping methods
     */
    public function add_shipping_methods($methods) {
        if (get_option('osm1_enable_tapin') === 'yes') {
            $methods['osm1_tapin'] = 'OSM1_Shipping_Tapin';
        }
        if (get_option('osm1_enable_alopeyk') === 'yes') {
            $methods['osm1_alopeyk'] = 'OSM1_Shipping_Alopeyk';
        }
        if (get_option('osm1_enable_snappbox') === 'yes') {
            $methods['osm1_snappbox'] = 'OSM1_Shipping_SnappBox';
        }
        if (get_option('osm1_enable_tipax') === 'yes') {
            $methods['osm1_tipax'] = 'OSM1_Shipping_Tipax';
        }
        if (get_option('osm1_enable_flash_delivery') === 'yes') {
            $methods['osm1_flash'] = 'OSM1_Shipping_Flash';
        }

        return $methods;
    }
}

