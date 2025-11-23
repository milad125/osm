<?php
/**
 * AI Predictor Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_AI_Predictor {

    /**
     * Predict delivery time
     */
    public function predict_delivery_time($method, $distance, $city) {
        // Base delivery times (hours)
        $base_times = array(
            'osm1_tapin' => 48,
            'osm1_alopeyk' => 24,
            'osm1_snappbox' => 24,
            'osm1_tipax' => 72,
            'osm1_flash' => 4,
        );

        $base_time = $base_times[$method] ?? 24;

        // Adjust based on distance
        $distance_factor = $distance / 100; // Add 1 hour per 100km
        $adjusted_time = $base_time + $distance_factor;

        // Adjust based on city (major cities are faster)
        $city_factors = array(
            'تهران' => 0.8,
            'اصفهان' => 0.9,
            'مشهد' => 0.9,
            'شیراز' => 0.9,
            'تبریز' => 0.95,
        );

        $city_factor = $city_factors[$city] ?? 1.0;
        $final_time = $adjusted_time * $city_factor;

        // Round to nearest hour
        $final_time = round($final_time);

        // Convert to human readable
        if ($final_time < 24) {
            return sprintf(__('%d ساعت', 'osm1'), $final_time);
        } else {
            $days = floor($final_time / 24);
            $hours = $final_time % 24;
            if ($hours > 0) {
                return sprintf(__('%d روز و %d ساعت', 'osm1'), $days, $hours);
            } else {
                return sprintf(__('%d روز', 'osm1'), $days);
            }
        }
    }

    /**
     * Predict optimal shipping method
     */
    public function predict_optimal_method($lat, $lng, $weight, $urgency) {
        $methods = array('osm1_tapin', 'osm1_alopeyk', 'osm1_snappbox', 'osm1_tipax');
        
        if ($urgency === 'high') {
            $methods[] = 'osm1_flash';
        }

        $scores = array();
        
        foreach ($methods as $method) {
            $score = 0;
            
            // Cost factor (lower is better)
            $cost = $this->estimate_cost($method, $lat, $lng, $weight);
            $score += (1000000 - $cost) / 10000;
            
            // Speed factor
            $time = $this->estimate_time($method, $lat, $lng);
            $score += (168 - $time) * 10; // Max 1 week
            
            // Reliability factor (based on method)
            $reliability = $this->get_reliability($method);
            $score += $reliability * 50;
            
            $scores[$method] = $score;
        }

        arsort($scores);
        return array_keys($scores)[0];
    }

    /**
     * Estimate cost
     */
    private function estimate_cost($method, $lat, $lng, $weight) {
        // Simplified estimation
        $base_costs = array(
            'osm1_tapin' => 50000,
            'osm1_alopeyk' => 60000,
            'osm1_snappbox' => 55000,
            'osm1_tipax' => 45000,
            'osm1_flash' => 80000,
        );

        return $base_costs[$method] ?? 50000;
    }

    /**
     * Estimate time
     */
    private function estimate_time($method, $lat, $lng) {
        $base_times = array(
            'osm1_tapin' => 48,
            'osm1_alopeyk' => 24,
            'osm1_snappbox' => 24,
            'osm1_tipax' => 72,
            'osm1_flash' => 4,
        );

        return $base_times[$method] ?? 24;
    }

    /**
     * Get reliability score
     */
    private function get_reliability($method) {
        $scores = array(
            'osm1_tapin' => 0.9,
            'osm1_alopeyk' => 0.85,
            'osm1_snappbox' => 0.8,
            'osm1_tipax' => 0.95,
            'osm1_flash' => 0.75,
        );

        return $scores[$method] ?? 0.8;
    }
}

