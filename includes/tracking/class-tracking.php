<?php
/**
 * Tracking Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Tracking {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('woocommerce_order_status_changed', array($this, 'create_tracking_on_order'), 10, 3);
        add_action('wp_ajax_osm1_track_order', array($this, 'ajax_track_order'));
        add_action('wp_ajax_nopriv_osm1_track_order', array($this, 'ajax_track_order'));
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_tracking_info'));
        add_action('wp', array($this, 'update_tracking_statuses'));
    }

    /**
     * Create tracking on order status change
     */
    public function create_tracking_on_order($order_id, $old_status, $new_status) {
        if ($new_status !== 'processing' && $new_status !== 'completed') {
            return;
        }

        $order = wc_get_order($order_id);
        $shipping_methods = $order->get_shipping_methods();

        foreach ($shipping_methods as $shipping_method) {
            if (strpos($shipping_method->get_method_id(), 'osm1_') === 0) {
                $this->create_tracking_record($order_id, $shipping_method);
                break;
            }
        }
    }

    /**
     * Create tracking record
     */
    private function create_tracking_record($order_id, $shipping_method) {
        global $wpdb;

        $table = $wpdb->prefix . 'osm1_shipping_orders';
        
        // Check if already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d",
            $order_id
        ));

        if ($existing) {
            return;
        }

        $order = wc_get_order($order_id);
        $lat = get_post_meta($order_id, '_osm1_latitude', true);
        $lng = get_post_meta($order_id, '_osm1_longitude', true);
        $city = get_post_meta($order_id, '_osm1_city', true);
        $address = get_post_meta($order_id, '_osm1_address', true);

        $shipping_cost = $shipping_method->get_total();
        $packaging_cost = $this->get_packaging_cost($order_id);

        $method_id = $shipping_method->get_method_id();
        $service = str_replace('osm1_', '', $method_id);

        // Create shipment via API
        $api_manager = new OSM1_API_Manager();
        $api = $api_manager::get_api($service);

        $shipment_data = array(
            'order_id' => $order_id,
            'origin' => $this->get_origin_center($lat, $lng),
            'destination' => array(
                'lat' => $lat,
                'lng' => $lng,
                'address' => $address,
                'city' => $city,
            ),
            'weight' => $order->get_total_weight(),
            'customer' => array(
                'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone(),
            ),
        );

        $api_result = false;
        if ($api) {
            $api_result = $api->create_shipment($shipment_data);
        }

        $tracking_number = $api_result['tracking_number'] ?? $api_result['id'] ?? $this->generate_tracking_number($order_id);

        $wpdb->insert(
            $table,
            array(
                'order_id' => $order_id,
                'shipping_method' => $method_id,
                'tracking_number' => $tracking_number,
                'status' => 'pending',
                'destination_lat' => $lat,
                'destination_lng' => $lng,
                'destination_address' => $address,
                'shipping_cost' => $shipping_cost - $packaging_cost,
                'packaging_cost' => $packaging_cost,
                'total_cost' => $shipping_cost,
            ),
            array('%d', '%s', '%s', '%s', '%f', '%f', '%s', '%f', '%f', '%f')
        );

        // Send notification
        $notifications = new OSM1_Notifications();
        $notifications->send_tracking_created($order_id, $tracking_number);
    }

    /**
     * Get origin center
     */
    private function get_origin_center($lat, $lng) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'osm1_shipping_centers';
        $center = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE latitude = %f AND longitude = %f AND is_active = 1 LIMIT 1",
            $lat,
            $lng
        ), ARRAY_A);

        if (!$center) {
            // Get nearest
            $centers = $wpdb->get_results(
                "SELECT * FROM $table WHERE is_active = 1",
                ARRAY_A
            );

            $nearest = null;
            $min_distance = PHP_INT_MAX;

            foreach ($centers as $c) {
                $distance = $this->get_distance($lat, $lng, $c['latitude'], $c['longitude']);
                if ($distance < $min_distance) {
                    $min_distance = $distance;
                    $nearest = $c;
                }
            }

            return $nearest;
        }

        return $center;
    }

    /**
     * Get distance
     */
    private function get_distance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371;

        $d_lat = deg2rad($lat2 - $lat1);
        $d_lng = deg2rad($lng2 - $lng1);

        $a = sin($d_lat / 2) * sin($d_lat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($d_lng / 2) * sin($d_lng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth_radius * $c;
    }

    /**
     * Get packaging cost
     */
    private function get_packaging_cost($order_id) {
        $order = wc_get_order($order_id);
        $cost = floatval(get_option('osm1_packaging_cost', 0));
        $type = get_option('osm1_packaging_cost_type', 'fixed');
        
        if ($type === 'percentage') {
            $cost = ($order->get_subtotal() * $cost) / 100;
        }
        
        return $cost;
    }

    /**
     * Generate tracking number
     */
    private function generate_tracking_number($order_id) {
        return 'OSM' . str_pad($order_id, 8, '0', STR_PAD_LEFT) . rand(100, 999);
    }

    /**
     * AJAX: Track order
     */
    public function ajax_track_order() {
        check_ajax_referer('osm1_nonce', 'nonce');

        $tracking_number = sanitize_text_field($_POST['tracking_number'] ?? '');
        
        if (empty($tracking_number)) {
            wp_send_json_error(array('message' => __('شماره رهگیری الزامی است.', 'osm1')));
        }

        $tracking_data = $this->get_tracking_data($tracking_number);

        if ($tracking_data) {
            wp_send_json_success($tracking_data);
        } else {
            wp_send_json_error(array('message' => __('اطلاعات رهگیری یافت نشد.', 'osm1')));
        }
    }

    /**
     * Get tracking data
     */
    public function get_tracking_data($tracking_number) {
        global $wpdb;

        $table = $wpdb->prefix . 'osm1_shipping_orders';
        $record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE tracking_number = %s",
            $tracking_number
        ), ARRAY_A);

        if (!$record) {
            return false;
        }

        // Get real-time tracking from API
        $api_manager = new OSM1_API_Manager();
        $service = str_replace('osm1_', '', $record['shipping_method']);
        $api = $api_manager::get_api($service);

        $api_tracking = false;
        if ($api) {
            $api_tracking = $api->track_shipment($tracking_number);
        }

        return array(
            'tracking_number' => $record['tracking_number'],
            'status' => $api_tracking['status'] ?? $record['status'],
            'order_id' => $record['order_id'],
            'shipping_method' => $record['shipping_method'],
            'destination' => $record['destination_address'],
            'estimated_delivery' => $record['estimated_delivery_date'],
            'actual_delivery' => $record['actual_delivery_date'],
            'events' => $api_tracking['events'] ?? array(),
            'current_location' => $api_tracking['current_location'] ?? null,
        );
    }

    /**
     * Display tracking info
     */
    public function display_tracking_info($order) {
        global $wpdb;

        $table = $wpdb->prefix . 'osm1_shipping_orders';
        $tracking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d",
            $order->get_id()
        ), ARRAY_A);

        if (!$tracking) {
            return;
        }

        ?>
        <section class="osm1-tracking-info">
            <h2><?php _e('اطلاعات رهگیری', 'osm1'); ?></h2>
            <p><strong><?php _e('شماره رهگیری:', 'osm1'); ?></strong> <?php echo esc_html($tracking['tracking_number']); ?></p>
            <p><strong><?php _e('وضعیت:', 'osm1'); ?></strong> <?php echo esc_html($this->get_status_label($tracking['status'])); ?></p>
            <div id="osm1-tracking-map" style="width: 100%; height: 300px; margin-top: 15px;"></div>
        </section>
        <?php
    }

    /**
     * Get status label
     */
    private function get_status_label($status) {
        $labels = array(
            'pending' => __('در انتظار', 'osm1'),
            'picked_up' => __('تحویل گرفته شده', 'osm1'),
            'in_transit' => __('در حال ارسال', 'osm1'),
            'out_for_delivery' => __('در مسیر تحویل', 'osm1'),
            'delivered' => __('تحویل داده شده', 'osm1'),
            'failed' => __('ناموفق', 'osm1'),
        );

        return $labels[$status] ?? $status;
    }

    /**
     * Update tracking statuses (cron-like)
     */
    public function update_tracking_statuses() {
        // This should be called via cron, but for simplicity we check on page load
        if (!is_admin() && !isset($_GET['osm1_update_tracking'])) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        
        $pending_orders = $wpdb->get_results(
            "SELECT * FROM $table WHERE status NOT IN ('delivered', 'failed')",
            ARRAY_A
        );

        $api_manager = new OSM1_API_Manager();

        foreach ($pending_orders as $order) {
            $service = str_replace('osm1_', '', $order['shipping_method']);
            $api = $api_manager::get_api($service);

            if ($api) {
                $tracking_data = $api->track_shipment($order['tracking_number']);
                if ($tracking_data && isset($tracking_data['status'])) {
                    $wpdb->update(
                        $table,
                        array('status' => $tracking_data['status']),
                        array('id' => $order['id']),
                        array('%s'),
                        array('%d')
                    );

                    // Send notification if status changed
                    if ($tracking_data['status'] !== $order['status']) {
                        $notifications = new OSM1_Notifications();
                        $notifications->send_status_update($order['order_id'], $tracking_data['status']);
                    }
                }
            }
        }
    }
}

