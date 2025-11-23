<?php
/**
 * Plugin Activator
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Activator {

    /**
     * Activate plugin
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Shipping centers table
        $table_centers = $wpdb->prefix . 'osm1_shipping_centers';
        $sql_centers = "CREATE TABLE IF NOT EXISTS $table_centers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            city varchar(100) NOT NULL,
            province varchar(100) NOT NULL,
            phone varchar(20),
            email varchar(100),
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Shipping orders table
        $table_orders = $wpdb->prefix . 'osm1_shipping_orders';
        $sql_orders = "CREATE TABLE IF NOT EXISTS $table_orders (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            shipping_method varchar(50) NOT NULL,
            tracking_number varchar(100),
            status varchar(50) DEFAULT 'pending',
            origin_center_id bigint(20),
            destination_lat decimal(10,8),
            destination_lng decimal(11,8),
            destination_address text,
            shipping_cost decimal(10,2),
            packaging_cost decimal(10,2),
            total_cost decimal(10,2),
            estimated_delivery_date datetime,
            actual_delivery_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY tracking_number (tracking_number)
        ) $charset_collate;";

        // User loyalty points table
        $table_loyalty = $wpdb->prefix . 'osm1_loyalty_points';
        $sql_loyalty = "CREATE TABLE IF NOT EXISTS $table_loyalty (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            points int(11) DEFAULT 0,
            total_earned int(11) DEFAULT 0,
            total_spent int(11) DEFAULT 0,
            level varchar(50) DEFAULT 'bronze',
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        // Loyalty transactions table
        $table_transactions = $wpdb->prefix . 'osm1_loyalty_transactions';
        $sql_transactions = "CREATE TABLE IF NOT EXISTS $table_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20),
            points int(11) NOT NULL,
            type varchar(20) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_centers);
        dbDelta($sql_orders);
        dbDelta($sql_loyalty);
        dbDelta($sql_transactions);
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'osm1_google_maps_api_key' => '',
            'osm1_map_center_lat' => 35.6892,
            'osm1_map_center_lng' => 51.3890,
            'osm1_packaging_cost' => 0,
            'osm1_packaging_cost_type' => 'fixed',
            'osm1_enable_tapin' => 'no',
            'osm1_enable_alopeyk' => 'no',
            'osm1_enable_snappbox' => 'no',
            'osm1_enable_tipax' => 'no',
            'osm1_enable_flash_delivery' => 'no',
            'osm1_enable_loyalty' => 'yes',
            'osm1_loyalty_points_per_order' => 10,
            'osm1_enable_notifications' => 'yes',
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Add OSM1 shipping methods to all existing shipping zones if enabled
     * This is public so it can be called from an admin action without re-activation
     */
    public static function add_shipping_methods_to_zones() {
        if (!class_exists('WC_Shipping_Zones')) {
            return;
        }

        $available_methods = array();
        if (get_option('osm1_enable_postex') === 'yes') {
            $available_methods[] = 'osm1_postex_regular';
            $available_methods[] = 'osm1_postex_express';
        }
        if (get_option('osm1_enable_tapin') === 'yes') {
            $available_methods[] = 'osm1_tapin';
        }
        if (get_option('osm1_enable_alopeyk') === 'yes') {
            $available_methods[] = 'osm1_alopeyk';
        }
        if (get_option('osm1_enable_snappbox') === 'yes') {
            $available_methods[] = 'osm1_snappbox';
        }
        if (get_option('osm1_enable_tipax') === 'yes') {
            $available_methods[] = 'osm1_tipax';
        }
        if (get_option('osm1_enable_flash_delivery') === 'yes') {
            $available_methods[] = 'osm1_flash';
        }

        if (empty($available_methods)) {
            return;
        }

        $zones = WC_Shipping_Zones::get_zones();
        foreach ($zones as $zone) {
            $zone_id = isset($zone['zone_id']) ? intval($zone['zone_id']) : 0;
            $zone_obj = new WC_Shipping_Zone($zone_id);
            $methods = $zone_obj->get_shipping_methods();
            $existing_method_ids = array();
            foreach ($methods as $m) {
                if (isset($m->id)) {
                    $existing_method_ids[] = $m->id;
                }
            }
            foreach ($available_methods as $method_id) {
                if (!in_array($method_id, $existing_method_ids, true)) {
                    try {
                        $zone_obj->add_shipping_method($method_id);
                    } catch (Exception $e) {
                        // ignore failures
                    }
                }
            }
        }
        // Default zone
        $default_zone = new WC_Shipping_Zone(0);
        $methods = $default_zone->get_shipping_methods();
        $existing_method_ids = array();
        foreach ($methods as $m) {
            if (isset($m->id)) {
                $existing_method_ids[] = $m->id;
            }
        }
        foreach ($available_methods as $method_id) {
            if (!in_array($method_id, $existing_method_ids, true)) {
                try {
                    $default_zone->add_shipping_method($method_id);
                } catch (Exception $e) {
                    // ignore
                }
            }
        }
    }
}

