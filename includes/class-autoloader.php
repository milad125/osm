<?php
/**
 * Autoloader Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Autoloader {

    /**
     * Initialize autoloader
     */
    public static function init() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload classes
     *
     * @param string $class_name Class name
     */
    public static function autoload($class_name) {
        // Only load OSM1 classes
        if (strpos($class_name, 'OSM1_') !== 0) {
            return;
        }

        // Convert class name to file name
        $file_name = str_replace('OSM1_', '', $class_name);
        $file_name = str_replace('_', '-', $file_name);
        $file_name = 'class-' . strtolower($file_name) . '.php';

        // Possible directories
        $directories = array(
            OSM1_PLUGIN_DIR . 'includes/',
            OSM1_PLUGIN_DIR . 'includes/admin/',
            OSM1_PLUGIN_DIR . 'includes/api/',
            OSM1_PLUGIN_DIR . 'includes/frontend/',
            OSM1_PLUGIN_DIR . 'includes/shipping/',
            OSM1_PLUGIN_DIR . 'includes/tracking/',
            OSM1_PLUGIN_DIR . 'includes/notifications/',
            OSM1_PLUGIN_DIR . 'includes/gamification/',
        );

        // Try to find the file
        foreach ($directories as $directory) {
            $file_path = $directory . $file_name;
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
    }
}

