<?php
/**
 * SnappBox API Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-base.php';

class OSM1_API_SnappBox extends OSM1_API_Base {

    /**
     * Initialize
     */
    protected function init() {
        $this->api_endpoint = 'https://api.snappbox.ir/api/v1/';
        $this->api_key = get_option('osm1_snappbox_api_key', '');
    }

    /**
     * Get headers
     */
    protected function get_headers() {
        return array(
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->api_key,
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
            'pickup' => array(
                'lat' => $origin['latitude'],
                'lng' => $origin['longitude'],
            ),
            'dropoff' => array(
                'lat' => $destination['lat'],
                'lng' => $destination['lng'],
            ),
        );

        $response = $this->request('estimate', 'POST', $data);
        
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

        $response = $this->request('orders', 'POST', $order_data);
        
        if ($response && isset($response['order_id'])) {
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

        $response = $this->request('orders/' . $tracking_number);
        
        if ($response && isset($response['status'])) {
            return $response;
        }

        return false;
    }
}

