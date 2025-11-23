<?php
/**
 * Tapin API Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-base.php';

class OSM1_API_Tapin extends OSM1_API_Base {

    /**
     * Initialize
     */
    protected function init() {
        $this->api_endpoint = 'https://api.tapin.ir/api/v2/';
        $this->api_key = get_option('osm1_tapin_api_key', '');
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
            'origin' => array(
                'lat' => $origin['latitude'],
                'lng' => $origin['longitude'],
            ),
            'destination' => array(
                'lat' => $destination['lat'],
                'lng' => $destination['lng'],
            ),
        );

        $response = $this->request('public/price', 'POST', $data);
        
        if ($response && isset($response['data']['price'])) {
            return floatval($response['data']['price']);
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

        $response = $this->request('shipment/create', 'POST', $order_data);
        
        if ($response && isset($response['data']['tracking_number'])) {
            return $response['data'];
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

        $response = $this->request('shipment/track/' . $tracking_number);
        
        if ($response && isset($response['data'])) {
            return $response['data'];
        }

        return false;
    }
}

