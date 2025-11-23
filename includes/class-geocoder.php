<?php
/**
 * Geocoder Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Geocoder {

    /**
     * Get city from coordinates
     */
    public function get_city_from_coords($lat, $lng) {
        $api_key = get_option('osm1_google_maps_api_key', '');
        
        if (empty($api_key)) {
            return $this->get_city_fallback($lat, $lng);
        }

        $url = add_query_arg(array(
            'latlng' => $lat . ',' . $lng,
            'key' => $api_key,
            'language' => 'fa',
        ), 'https://maps.googleapis.com/maps/api/geocode/json');

        $response = wp_remote_get($url, array('timeout' => 10));

        if (is_wp_error($response)) {
            return $this->get_city_fallback($lat, $lng);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['results'][0]['address_components'])) {
            foreach ($data['results'][0]['address_components'] as $component) {
                if (in_array('locality', $component['types'])) {
                    return $component['long_name'];
                }
            }
        }

        return $this->get_city_fallback($lat, $lng);
    }

    /**
     * Get city fallback (simple approximation)
     */
    private function get_city_fallback($lat, $lng) {
        // Simple approximation for Iran cities
        // This is a basic fallback - in production, use a proper geocoding service
        
        if ($lat >= 35.6 && $lat <= 35.8 && $lng >= 51.2 && $lng <= 51.6) {
            return 'تهران';
        }
        
        if ($lat >= 36.1 && $lat <= 36.4 && $lng >= 50.0 && $lng <= 50.5) {
            return 'کرج';
        }
        
        if ($lat >= 32.6 && $lat <= 32.8 && $lng >= 51.6 && $lng <= 51.8) {
            return 'اصفهان';
        }
        
        return 'تهران'; // Default
    }

    /**
     * Get address from coordinates
     */
    public function get_address_from_coords($lat, $lng) {
        $api_key = get_option('osm1_google_maps_api_key', '');
        
        if (empty($api_key)) {
            return '';
        }

        $url = add_query_arg(array(
            'latlng' => $lat . ',' . $lng,
            'key' => $api_key,
            'language' => 'fa',
        ), 'https://maps.googleapis.com/maps/api/geocode/json');

        $response = wp_remote_get($url, array('timeout' => 10));

        if (is_wp_error($response)) {
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['results'][0]['formatted_address'])) {
            return $data['results'][0]['formatted_address'];
        }

        return '';
    }
}

