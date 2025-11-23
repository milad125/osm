<?php
/**
 * API Manager Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_API_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        // API classes will be loaded on demand
    }

    /**
     * Get API instance
     */
    public static function get_api($service) {
        $class_name = 'OSM1_API_' . ucfirst($service);
        if (class_exists($class_name)) {
            return new $class_name();
        }
        return false;
    }
}

