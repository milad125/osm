<?php
/**
 * Postex API Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once OSM1_PLUGIN_DIR . 'includes/api/class-api-base.php';

class OSM1_API_Postex extends OSM1_API_Base {

    protected function init() {
        $this->api_endpoint = 'https://api.postex.ir/api/v1/';
        $this->api_key = get_option('osm1_postex_api_key', '');
        $this->api_token = get_option('osm1_postex_api_token', '');
    }

    protected function get_headers() {
        $headers = array(
            'Content-Type' => 'application/json',
        );
        if (!empty($this->api_key)) {
            $headers['X-API-KEY'] = $this->api_key;
        }
        if (!empty($this->api_token)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_token;
        }
        return $headers;
    }

    public function calculate_cost($origin, $destination) {
        if (empty($this->api_key) && empty($this->api_token)) {
            return false;
        }

        $data = array(
            'origin' => array(
                'lat' => $origin['latitude'] ?? '',
                'lng' => $origin['longitude'] ?? '',
                'postal_code' => $origin['postal_code'] ?? '',
            ),
            'destination' => array(
                'lat' => $destination['lat'] ?? '',
                'lng' => $destination['lng'] ?? '',
                'city' => $destination['city'] ?? '',
                'postal_code' => $destination['postal_code'] ?? '',
            ),
            'weight' => $destination['weight'] ?? 1,
        );

        $response = $this->request('shipping/calculate', 'POST', $data);
        if ($response) {
            if (isset($response['regular']) || isset($response['express'])) {
                return $response;
            }
            if (isset($response['price'])) {
                return floatval($response['price']);
            }
        }

        return false;
    }

    /**
     * Create a shipment via Postex API
     */
    public function create_shipment($order_data) {
        if (empty($this->api_key) && empty($this->api_token)) {
            return false;
        }

        $response = $this->request('shipments', 'POST', $order_data);
        if ($response) {
            return $response;
        }
        return false;
    }

    /**
     * Track a shipment via Postex API
     */
    public function track_shipment($tracking_number) {
        if (empty($this->api_key) && empty($this->api_token)) {
            return false;
        }

        $response = $this->request('shipments/' . urlencode($tracking_number) . '/track');
        if ($response) {
            return $response;
        }
        return false;
    }
}
