<?php
/**
 * Shipping Calculator Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Shipping_Calculator {

    /**
     * Calculate shipping cost
     */
    public function calculate($lat, $lng, $method) {
        // Save to session for WooCommerce
        WC()->session->set('osm1_destination_lat', $lat);
        WC()->session->set('osm1_destination_lng', $lng);

        // Get city
        $geocoder = new OSM1_Geocoder();
        $city = $geocoder->get_city_from_coords($lat, $lng);

        // Get nearest center
        $center = $this->get_nearest_center($lat, $lng);
        if (!$center) {
            return array(
                'success' => false,
                'message' => __('مرکز ارسال یافت نشد.', 'osm1'),
            );
        }

        // Calculate distance
        $distance = $this->get_distance(
            $center['latitude'],
            $center['longitude'],
            $lat,
            $lng
        );

        // Get cost from API
        $api_cost = $this->get_api_cost($method, $center, array(
            'lat' => $lat,
            'lng' => $lng,
            'city' => $city,
        ));

        // Get packaging cost
        $packaging_cost = $this->get_packaging_cost();

        // Total cost
        $total_cost = $api_cost !== false ? $api_cost : $this->calculate_fallback_cost($method, $distance);
        $total_cost += $packaging_cost;

        // AI prediction for delivery time
        $ai_predictor = new OSM1_AI_Predictor();
        $estimated_delivery = $ai_predictor->predict_delivery_time($method, $distance, $city);

        return array(
            'success' => true,
            'cost' => $total_cost,
            'formatted_cost' => wc_price($total_cost),
            'distance' => round($distance, 2),
            'city' => $city,
            'estimated_delivery' => $estimated_delivery,
            'center' => $center['name'],
        );
    }

    /**
     * Get API cost
     */
    private function get_api_cost($method, $center, $destination) {
        $api_manager = new OSM1_API_Manager();
        
        $method_map = array(
            'osm1_tapin' => 'tapin',
            'osm1_alopeyk' => 'alopeyk',
            'osm1_snappbox' => 'snappbox',
            'osm1_tipax' => 'tipax',
            'osm1_postex_regular' => 'postex',
            'osm1_postex_express' => 'postex',
        );

        $service = $method_map[$method] ?? null;
        if (!$service) {
            return false;
        }

        $api = $api_manager::get_api($service);
        if (!$api) {
            return false;
        }

        return $api->calculate_cost($center, $destination);
    }

    /**
     * Calculate fallback cost
     */
    private function calculate_fallback_cost($method, $distance) {
        $base_costs = array(
            'osm1_tapin' => 50000,
            'osm1_alopeyk' => 60000,
            'osm1_snappbox' => 55000,
            'osm1_tipax' => 45000,
            'osm1_flash' => 80000,
        );

        $per_km_costs = array(
            'osm1_tapin' => 1000,
            'osm1_alopeyk' => 2000,
            'osm1_snappbox' => 1500,
            'osm1_tipax' => 1200,
            'osm1_flash' => 3000,
        );

        $base = $base_costs[$method] ?? 50000;
        $per_km = $per_km_costs[$method] ?? 1000;

        return $base + ($distance * $per_km);
    }

    /**
     * Get packaging cost
     */
    private function get_packaging_cost() {
        $cost = floatval(get_option('osm1_packaging_cost', 0));
        $type = get_option('osm1_packaging_cost_type', 'fixed');
        
        if ($type === 'percentage') {
            $cart_total = WC()->cart->get_subtotal();
            $cost = ($cart_total * $cost) / 100;
        }
        
        return $cost;
    }

    /**
     * Get distance
     */
    private function get_distance($lat1, $lng1, $lat2, $lng2) {
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
     * Get nearest center
     */
    private function get_nearest_center($lat, $lng) {
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

