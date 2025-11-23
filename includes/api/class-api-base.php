<?php
/**
 * Base API Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class OSM1_API_Base {

    /**
     * API endpoint
     */
    protected $api_endpoint;

    /**
     * API key
     */
    protected $api_key;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize (to be implemented by child classes)
     */
    abstract protected function init();

    /**
     * Make API request
     */
    protected function request($endpoint, $method = 'GET', $data = array()) {
        $url = $this->api_endpoint . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'headers' => $this->get_headers(),
        );

        if ($method === 'POST' || $method === 'PUT') {
            $args['body'] = json_encode($data);
        } else {
            $url = add_query_arg($data, $url);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);

        if ($status >= 200 && $status < 300) {
            return json_decode($body, true);
        }

        return false;
    }

    /**
     * Get headers (to be implemented by child classes)
     */
    abstract protected function get_headers();

    /**
     * Calculate cost (to be implemented by child classes)
     */
    abstract public function calculate_cost($origin, $destination);

    /**
     * Create shipment (to be implemented by child classes)
     */
    abstract public function create_shipment($order_data);

    /**
     * Track shipment (to be implemented by child classes)
     */
    abstract public function track_shipment($tracking_number);
}

