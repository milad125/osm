<?php
/**
 * Frontend Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Frontend {

    /**
     * Constructor
     */
    public function __construct() {
        // Multiple hooks for classic checkout (non-block themes)
        add_action('woocommerce_review_order_before_shipping', array($this, 'add_map_to_checkout'));
        add_action('woocommerce_before_order_notes', array($this, 'add_map_to_checkout'));
        add_action('woocommerce_checkout_billing', array($this, 'add_map_to_checkout_alternative'), 5);
        
        // For Block-based checkout, we'll inject via JavaScript
        add_action('wp_footer', array($this, 'add_map_template_for_blocks'));
        
        // WooCommerce Blocks support - multiple hooks for compatibility
        add_filter('woocommerce_store_api_checkout_update_order_from_request', array($this, 'save_shipping_location_blocks'), 10, 2);
        add_filter('woocommerce_blocks_loaded', array($this, 'register_block_support'));
        
        add_action('woocommerce_checkout_process', array($this, 'validate_shipping_location'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_shipping_location'));
        add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'add_packaging_cost_to_label'), 10, 2);
        add_action('wp_ajax_osm1_calculate_shipping', array($this, 'ajax_calculate_shipping'));
        add_action('wp_ajax_nopriv_osm1_calculate_shipping', array($this, 'ajax_calculate_shipping'));
        add_action('wp_ajax_osm1_get_city_from_coords', array($this, 'ajax_get_city_from_coords'));
        add_action('wp_ajax_nopriv_osm1_get_city_from_coords', array($this, 'ajax_get_city_from_coords'));
    }

    /**
     * Add map template for Block-based checkout (injected via JS)
     */
    public function add_map_template_for_blocks() {
        if (!is_checkout()) {
            return;
        }

        $api_key = get_option('osm1_google_maps_api_key', '');
        if (empty($api_key)) {
            return;
        }

        $saved_lat = WC()->session ? WC()->session->get('osm1_destination_lat') : '';
        $saved_lng = WC()->session ? WC()->session->get('osm1_destination_lng') : '';
        ?>
        <script type="text/template" id="osm1-map-template">
            <div class="osm1-checkout-map-container" style="margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd;">
                <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 18px; color: #333;"><?php _e('انتخاب موقعیت تحویل', 'osm1'); ?></h3>
                <p class="osm1-map-description" style="margin-bottom: 15px; color: #666; font-size: 14px;">
                    <?php _e('لطفا موقعیت دقیق تحویل سفارش را روی نقشه انتخاب کنید', 'osm1'); ?>
                </p>
                <div id="osm1-checkout-map" style="width: 100%; height: 400px; margin: 15px 0; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; position: relative;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #666;">
                        <div class="osm1-loading" style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #6366f1; border-radius: 50%; animation: osm1-spin 1s linear infinite; margin-bottom: 10px;"></div>
                        <p><?php _e('در حال بارگذاری نقشه...', 'osm1'); ?></p>
                    </div>
                </div>
                <input type="hidden" id="osm1-latitude" name="osm1_latitude" value="<?php echo esc_attr($saved_lat); ?>">
                <input type="hidden" id="osm1-longitude" name="osm1_longitude" value="<?php echo esc_attr($saved_lng); ?>">
                <input type="hidden" id="osm1-city" name="osm1_city" value="">
                <input type="hidden" id="osm1-address" name="osm1_address" value="">
                <div id="osm1-map-info" class="osm1-map-info" style="display: none; margin-top: 15px; padding: 15px; background: #fff; border-radius: 4px; border: 1px solid #ddd;">
                    <p style="margin: 8px 0; font-size: 14px;"><strong style="color: #333; margin-left: 10px;"><?php _e('آدرس انتخاب شده:', 'osm1'); ?></strong> <span id="osm1-selected-address"></span></p>
                    <p style="margin: 8px 0; font-size: 14px;"><strong style="color: #333; margin-left: 10px;"><?php _e('شهر:', 'osm1'); ?></strong> <span id="osm1-selected-city"></span></p>
                </div>
            </div>
        </script>
        <style>
            @keyframes osm1-spin {
                0% { transform: translate(-50%, -50%) rotate(0deg); }
                100% { transform: translate(-50%, -50%) rotate(360deg); }
            }
        </style>
        <?php
    }

    /**
     * Register block support
     */
    public function register_block_support() {
        // This hook is called when WooCommerce Blocks are loaded
        // We can use it to add custom data if needed
    }

    /**
     * Save shipping location for Block-based checkout
     */
    public function save_shipping_location_blocks($order, $request) {
        if (isset($request['extensions']['osm1'])) {
            $data = $request['extensions']['osm1'];
            if (isset($data['latitude'])) {
                update_post_meta($order->get_id(), '_osm1_latitude', sanitize_text_field($data['latitude']));
            }
            if (isset($data['longitude'])) {
                update_post_meta($order->get_id(), '_osm1_longitude', sanitize_text_field($data['longitude']));
            }
            if (isset($data['city'])) {
                update_post_meta($order->get_id(), '_osm1_city', sanitize_text_field($data['city']));
            }
            if (isset($data['address'])) {
                update_post_meta($order->get_id(), '_osm1_address', sanitize_text_field($data['address']));
            }
        }
        return $order;
    }

    /**
     * Add map to checkout
     */
    public function add_map_to_checkout() {
        // Only show once
        static $map_shown = false;
        if ($map_shown) {
            return;
        }
        $map_shown = true;

        // Check if API key exists
        $api_key = get_option('osm1_google_maps_api_key', '');
        if (empty($api_key)) {
            return; // Don't show map if no API key
        }

        // Get saved coordinates from session if available
        $saved_lat = WC()->session ? WC()->session->get('osm1_destination_lat') : '';
        $saved_lng = WC()->session ? WC()->session->get('osm1_destination_lng') : '';
        ?>
        <tr class="osm1-map-row">
            <td colspan="2">
                <div class="osm1-checkout-map-container">
                    <h3><?php _e('انتخاب موقعیت تحویل', 'osm1'); ?></h3>
                    <p class="osm1-map-description">
                        <?php _e('لطفا موقعیت دقیق تحویل سفارش را روی نقشه انتخاب کنید', 'osm1'); ?>
                    </p>
                    <div id="osm1-checkout-map" style="width: 100%; height: 400px; margin: 15px 0; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; position: relative;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #666;">
                            <div class="osm1-loading" style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #6366f1; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px;"></div>
                            <p><?php _e('در حال بارگذاری نقشه...', 'osm1'); ?></p>
                        </div>
                    </div>
                    <input type="hidden" id="osm1-latitude" name="osm1_latitude" value="<?php echo esc_attr($saved_lat); ?>">
                    <input type="hidden" id="osm1-longitude" name="osm1_longitude" value="<?php echo esc_attr($saved_lng); ?>">
                    <input type="hidden" id="osm1-city" name="osm1_city" value="">
                    <input type="hidden" id="osm1-address" name="osm1_address" value="">
                    <div id="osm1-map-info" class="osm1-map-info" style="display: none;">
                        <p><strong><?php _e('آدرس انتخاب شده:', 'osm1'); ?></strong> <span id="osm1-selected-address"></span></p>
                        <p><strong><?php _e('شهر:', 'osm1'); ?></strong> <span id="osm1-selected-city"></span></p>
                        <div id="osm1-shipping-costs-preview" style="margin-top: 15px; padding: 15px; background: #f0f9ff; border-radius: 8px; border-right: 4px solid #3b82f6;">
                            <h4 style="margin-top: 0; color: #1e40af; font-size: 16px;">
                                <span style="margin-left: 8px;">💰</span>
                                <?php _e('هزینه ارسال:', 'osm1'); ?>
                            </h4>
                            <div id="osm1-costs-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                                <!-- Costs will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <style>
            @keyframes spin {
                0% { transform: translate(-50%, -50%) rotate(0deg); }
                100% { transform: translate(-50%, -50%) rotate(360deg); }
            }
        </style>
        <?php
    }

    /**
     * Add map to checkout (alternative hook)
     */
    public function add_map_to_checkout_alternative() {
        // Only show once
        static $map_shown = false;
        if ($map_shown) {
            return;
        }
        
        // Check if map was already shown by other hook
        if (did_action('woocommerce_review_order_before_shipping')) {
            return;
        }
        
        $map_shown = true;
        $this->add_map_to_checkout();
    }

    /**
     * Validate shipping location
     */
    public function validate_shipping_location() {
        if (empty($_POST['osm1_latitude']) || empty($_POST['osm1_longitude'])) {
            wc_add_notice(__('لطفا موقعیت تحویل را روی نقشه انتخاب کنید.', 'osm1'), 'error');
        }
    }

    /**
     * Save shipping location
     */
    public function save_shipping_location($order_id) {
        if (!empty($_POST['osm1_latitude'])) {
            update_post_meta($order_id, '_osm1_latitude', sanitize_text_field($_POST['osm1_latitude']));
        }
        if (!empty($_POST['osm1_longitude'])) {
            update_post_meta($order_id, '_osm1_longitude', sanitize_text_field($_POST['osm1_longitude']));
        }
        if (!empty($_POST['osm1_city'])) {
            update_post_meta($order_id, '_osm1_city', sanitize_text_field($_POST['osm1_city']));
        }
        if (!empty($_POST['osm1_address'])) {
            update_post_meta($order_id, '_osm1_address', sanitize_text_field($_POST['osm1_address']));
        }
    }

    /**
     * Add packaging cost to shipping label
     */
    public function add_packaging_cost_to_label($label, $method) {
        if (strpos($method->id, 'osm1_') === 0) {
            $packaging_cost = $this->get_packaging_cost();
            if ($packaging_cost > 0) {
                $label .= ' <small>(' . __('هزینه بسته‌بندی:', 'osm1') . ' ' . wc_price($packaging_cost) . ')</small>';
            }
        }
        return $label;
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
     * AJAX: Calculate shipping
     */
    public function ajax_calculate_shipping() {
        check_ajax_referer('osm1_nonce', 'nonce');

        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $shipping_method = sanitize_text_field($_POST['method'] ?? '');

        if (!$lat || !$lng || !$shipping_method) {
            wp_send_json_error(array('message' => __('اطلاعات ناقص است.', 'osm1')));
        }

        // Save to session
        if (WC()->session) {
            WC()->session->set('osm1_destination_lat', $lat);
            WC()->session->set('osm1_destination_lng', $lng);
        }

        $calculator = new OSM1_Shipping_Calculator();
        $result = $calculator->calculate($lat, $lng, $shipping_method);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Get city from coordinates
     */
    public function ajax_get_city_from_coords() {
        check_ajax_referer('osm1_nonce', 'nonce');

        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);

        if (!$lat || !$lng) {
            wp_send_json_error(array('message' => __('مختصات نامعتبر است.', 'osm1')));
        }

        $geocoder = new OSM1_Geocoder();
        $city = $geocoder->get_city_from_coords($lat, $lng);

        if ($city) {
            wp_send_json_success(array('city' => $city));
        } else {
            wp_send_json_error(array('message' => __('نتوانستیم شهر را پیدا کنیم.', 'osm1')));
        }
    }
}

