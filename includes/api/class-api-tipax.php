<?php
/**
 * Tipax API Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-base.php';

class OSM1_API_Tipax extends OSM1_API_Base {

    /**
     * Initialize
     */
    protected function init() {
        $this->api_endpoint = 'https://api.tipax.ir/api/v1/';
        $this->api_key = get_option('osm1_tipax_api_key', '');
    }

    /**
     * Get headers
     */
    protected function get_headers() {
        return array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key,
        );
    }

    /**
     * Calculate cost
     */
    public function calculate_cost($origin, $destination) {
        if (empty($this->api_key)) {
            return false;
        }

        $data = array(
            'origin_city' => $origin['city'],
            'destination_city' => $destination['city'] ?? '',
            'weight' => $destination['weight'] ?? 1,
        );

        $response = $this->request('price/calculate', 'POST', $data);
        
        if ($response && isset($response['price'])) {
            return floatval($response['price']);
        }

        return false;
    }

    /**
     * Create shipment
     */
    public function create_shipment($order_data) {
        if (empty($this->api_key)) {
            return false;
        }

        $response = $this->request('shipments', 'POST', $order_data);
        
        if ($response && isset($response['tracking_code'])) {
            return $response;
        }

        return false;
    }

    /**
     * Track shipment
     */
    public function track_shipment($tracking_number) {
        if (empty($this->api_key)) {
            return false;
        }

        $response = $this->request('shipments/' . $tracking_number . '/track');
        
        if ($response && isset($response['status'])) {
            return $response;
        }

        return false;
    }
}

