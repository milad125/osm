<?php
/**
 * Admin Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_osm1_save_settings', array($this, 'save_settings'));
    add_action('admin_post_osm1_repair_zones', array($this, 'handle_repair_zones'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('سیستم حمل و نقل', 'osm1'),
            __('حمل و نقل', 'osm1'),
            'manage_options',
            'osm1',
            array($this, 'render_dashboard'),
            'dashicons-store',
            30
        );

        add_submenu_page(
            'osm1',
            __('داشبورد', 'osm1'),
            __('داشبورد', 'osm1'),
            'manage_options',
            'osm1',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'osm1',
            __('تنظیمات', 'osm1'),
            __('تنظیمات', 'osm1'),
            'manage_options',
            'osm1-settings',
            array($this, 'render_settings')
        );

        add_submenu_page(
            'osm1',
            __('مراکز ارسال', 'osm1'),
            __('مراکز ارسال', 'osm1'),
            'manage_options',
            'osm1-centers',
            array($this, 'render_centers')
        );

        add_submenu_page(
            'osm1',
            __('سفارشات ارسال', 'osm1'),
            __('سفارشات ارسال', 'osm1'),
            'manage_options',
            'osm1-orders',
            array($this, 'render_orders')
        );

        add_submenu_page(
            'osm1',
            __('برنامه وفاداری', 'osm1'),
            __('برنامه وفاداری', 'osm1'),
            'manage_options',
            'osm1-loyalty',
            array($this, 'render_loyalty')
        );

        add_submenu_page(
            'osm1',
            __('گزارشات', 'osm1'),
            __('گزارشات', 'osm1'),
            'manage_options',
            'osm1-reports',
            array($this, 'render_reports')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('osm1_settings', 'osm1_google_maps_api_key');
        register_setting('osm1_settings', 'osm1_map_center_lat');
        register_setting('osm1_settings', 'osm1_map_center_lng');
        register_setting('osm1_settings', 'osm1_packaging_cost');
        register_setting('osm1_settings', 'osm1_packaging_cost_type');

        // API settings
        register_setting('osm1_settings', 'osm1_enable_tapin');
        register_setting('osm1_settings', 'osm1_tapin_api_key');
        register_setting('osm1_settings', 'osm1_tapin_username');
        register_setting('osm1_settings', 'osm1_tapin_password');

        register_setting('osm1_settings', 'osm1_enable_alopeyk');
        register_setting('osm1_settings', 'osm1_alopeyk_api_key');
        register_setting('osm1_settings', 'osm1_alopeyk_token');

        register_setting('osm1_settings', 'osm1_enable_snappbox');
        register_setting('osm1_settings', 'osm1_snappbox_api_key');

        register_setting('osm1_settings', 'osm1_enable_tipax');
        register_setting('osm1_settings', 'osm1_tipax_api_key');
        register_setting('osm1_settings', 'osm1_tipax_username');
        register_setting('osm1_settings', 'osm1_tipax_password');

        // Flash delivery
        register_setting('osm1_settings', 'osm1_enable_flash_delivery');
        register_setting('osm1_settings', 'osm1_flash_delivery_cost_multiplier');

        // Postex
        register_setting('osm1_settings', 'osm1_enable_postex');
        register_setting('osm1_settings', 'osm1_postex_api_key');
        register_setting('osm1_settings', 'osm1_postex_api_token');

        // Loyalty
        register_setting('osm1_settings', 'osm1_enable_loyalty');
        register_setting('osm1_settings', 'osm1_loyalty_points_per_order');
        register_setting('osm1_settings', 'osm1_loyalty_points_per_rial');

        // Notifications
        register_setting('osm1_settings', 'osm1_enable_notifications');
        register_setting('osm1_settings', 'osm1_notification_email');
        register_setting('osm1_settings', 'osm1_notification_sms');
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        include OSM1_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Render settings
     */
    public function render_settings() {
        include OSM1_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Render centers
     */
    public function render_centers() {
        include OSM1_PLUGIN_DIR . 'templates/admin/centers.php';
    }

    /**
     * Render orders
     */
    public function render_orders() {
        include OSM1_PLUGIN_DIR . 'templates/admin/orders.php';
    }

    /**
     * Render loyalty
     */
    public function render_loyalty() {
        include OSM1_PLUGIN_DIR . 'templates/admin/loyalty.php';
    }

    /**
     * Render reports
     */
    public function render_reports() {
        include OSM1_PLUGIN_DIR . 'templates/admin/reports.php';
    }

    /**
     * Save settings
     */
    public function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما دسترسی لازم را ندارید.', 'osm1'));
        }

        check_admin_referer('osm1_settings_nonce');

        $settings = $_POST['osm1_settings'] ?? array();
        
        foreach ($settings as $key => $value) {
            update_option('osm1_' . $key, sanitize_text_field($value));
        }

        wp_redirect(add_query_arg(array('page' => 'osm1-settings', 'updated' => '1'), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle repair shipping zones admin action
     */
    public function handle_repair_zones() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما دسترسی لازم را ندارید.', 'osm1'));
        }
        check_admin_referer('osm1_repair_zones_nonce');

        OSM1_Activator::add_shipping_methods_to_zones();

        wp_redirect(add_query_arg(array('page' => 'osm1-settings', 'repaired' => '1'), admin_url('admin.php')));
        exit;
    }

    /**
     * Get today orders count
     */
    public function get_today_orders_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE DATE(created_at) = CURDATE()");
    }

    /**
     * Get in transit count
     */
    public function get_in_transit_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'in_transit'");
    }

    /**
     * Get delivered count
     */
    public function get_delivered_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'delivered' AND DATE(actual_delivery_date) = CURDATE()");
    }

    /**
     * Get today revenue
     */
    public function get_today_revenue() {
        global $wpdb;
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        return floatval($wpdb->get_var("SELECT SUM(total_cost) FROM $table WHERE DATE(created_at) = CURDATE()"));
    }

    /**
     * Display recent orders
     */
    public function display_recent_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        $orders = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 10", ARRAY_A);
        
        if (empty($orders)) {
            echo '<p>' . __('سفارشی یافت نشد.', 'osm1') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('شماره سفارش', 'osm1') . '</th>';
        echo '<th>' . __('روش ارسال', 'osm1') . '</th>';
        echo '<th>' . __('شماره رهگیری', 'osm1') . '</th>';
        echo '<th>' . __('وضعیت', 'osm1') . '</th>';
        echo '<th>' . __('هزینه', 'osm1') . '</th>';
        echo '<th>' . __('تاریخ', 'osm1') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($orders as $order) {
            echo '<tr>';
            echo '<td><a href="' . admin_url('post.php?post=' . $order['order_id'] . '&action=edit') . '">#' . $order['order_id'] . '</a></td>';
            echo '<td>' . esc_html($order['shipping_method']) . '</td>';
            echo '<td>' . esc_html($order['tracking_number']) . '</td>';
            echo '<td>' . esc_html($order['status']) . '</td>';
            echo '<td>' . wc_price($order['total_cost']) . '</td>';
            echo '<td>' . date_i18n(get_option('date_format'), strtotime($order['created_at'])) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }

    /**
     * Get status label
     */
    public function get_status_label($status) {
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
}

