<?php
/**
 * Gamification Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Gamification {

    /**
     * Constructor
     */
    public function __construct() {
        if (get_option('osm1_enable_loyalty') !== 'yes') {
            return;
        }

        add_action('woocommerce_order_status_completed', array($this, 'award_points_on_completion'));
        add_action('woocommerce_order_status_processing', array($this, 'award_points_on_processing'));
        add_action('wp_ajax_osm1_redeem_points', array($this, 'ajax_redeem_points'));
        add_shortcode('osm1_loyalty_points', array($this, 'display_loyalty_points'));
    }

    /**
     * Award points on order completion
     */
    public function award_points_on_completion($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        // Check if points already awarded
        global $wpdb;
        $table = $wpdb->prefix . 'osm1_loyalty_transactions';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE order_id = %d AND type = 'earned'",
            $order_id
        ));

        if ($existing > 0) {
            return;
        }

        // Calculate points
        $points_per_order = intval(get_option('osm1_loyalty_points_per_order', 10));
        $points_per_rial = floatval(get_option('osm1_loyalty_points_per_rial', 0.01));
        
        $order_total = $order->get_total();
        $points = $points_per_order + ($order_total * $points_per_rial);
        $points = round($points);

        // Award points
        $this->add_points($user_id, $points, $order_id, sprintf(
            __('امتیاز برای سفارش #%s', 'osm1'),
            $order->get_order_number()
        ));
    }

    /**
     * Award points on order processing (partial)
     */
    public function award_points_on_processing($order_id) {
        // Award partial points on processing
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        // Check if already awarded
        global $wpdb;
        $table = $wpdb->prefix . 'osm1_loyalty_transactions';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE order_id = %d AND type = 'earned'",
            $order_id
        ));

        if ($existing > 0) {
            return;
        }

        // Award 50% of points
        $points_per_order = intval(get_option('osm1_loyalty_points_per_order', 10));
        $points = round($points_per_order * 0.5);

        $this->add_points($user_id, $points, $order_id, sprintf(
            __('امتیاز پیش‌پرداخت برای سفارش #%s', 'osm1'),
            $order->get_order_number()
        ));
    }

    /**
     * Add points to user
     */
    public function add_points($user_id, $points, $order_id = null, $description = '') {
        global $wpdb;

        $points_table = $wpdb->prefix . 'osm1_loyalty_points';
        $transactions_table = $wpdb->prefix . 'osm1_loyalty_transactions';

        // Get or create user points record
        $user_points = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $points_table WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        if (!$user_points) {
            $wpdb->insert(
                $points_table,
                array(
                    'user_id' => $user_id,
                    'points' => $points,
                    'total_earned' => $points,
                    'level' => $this->calculate_level($points),
                ),
                array('%d', '%d', '%d', '%s')
            );
        } else {
            $new_points = $user_points['points'] + $points;
            $new_total_earned = $user_points['total_earned'] + $points;
            $new_level = $this->calculate_level($new_points);

            $wpdb->update(
                $points_table,
                array(
                    'points' => $new_points,
                    'total_earned' => $new_total_earned,
                    'level' => $new_level,
                ),
                array('user_id' => $user_id),
                array('%d', '%d', '%s'),
                array('%d')
            );
        }

        // Add transaction
        $wpdb->insert(
            $transactions_table,
            array(
                'user_id' => $user_id,
                'order_id' => $order_id,
                'points' => $points,
                'type' => 'earned',
                'description' => $description,
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );

        // Check for level up
        if ($user_points && $user_points['level'] !== $this->calculate_level($new_points ?? $points)) {
            $this->notify_level_up($user_id, $this->calculate_level($new_points ?? $points));
        }
    }

    /**
     * Redeem points
     */
    public function redeem_points($user_id, $points, $order_id = null, $description = '') {
        global $wpdb;

        $points_table = $wpdb->prefix . 'osm1_loyalty_points';
        $transactions_table = $wpdb->prefix . 'osm1_loyalty_transactions';

        // Get user points
        $user_points = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $points_table WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        if (!$user_points || $user_points['points'] < $points) {
            return false;
        }

        $new_points = $user_points['points'] - $points;
        $new_total_spent = $user_points['total_spent'] + $points;

        $wpdb->update(
            $points_table,
            array(
                'points' => $new_points,
                'total_spent' => $new_total_spent,
            ),
            array('user_id' => $user_id),
            array('%d', '%d'),
            array('%d')
        );

        // Add transaction
        $wpdb->insert(
            $transactions_table,
            array(
                'user_id' => $user_id,
                'order_id' => $order_id,
                'points' => $points,
                'type' => 'spent',
                'description' => $description,
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );

        return true;
    }

    /**
     * Calculate user level
     */
    private function calculate_level($points) {
        if ($points >= 10000) {
            return 'platinum';
        } elseif ($points >= 5000) {
            return 'gold';
        } elseif ($points >= 2000) {
            return 'silver';
        } elseif ($points >= 500) {
            return 'bronze';
        } else {
            return 'new';
        }
    }

    /**
     * Get level label
     */
    public function get_level_label($level) {
        $labels = array(
            'new' => __('جدید', 'osm1'),
            'bronze' => __('برنز', 'osm1'),
            'silver' => __('نقره', 'osm1'),
            'gold' => __('طلا', 'osm1'),
            'platinum' => __('پلاتین', 'osm1'),
        );

        return $labels[$level] ?? $level;
    }

    /**
     * Get user points
     */
    public function get_user_points($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'osm1_loyalty_points';
        $points = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        if (!$points) {
            return array(
                'points' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
                'level' => 'new',
            );
        }

        return $points;
    }

    /**
     * AJAX: Redeem points
     */
    public function ajax_redeem_points() {
        check_ajax_referer('osm1_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('لطفا وارد شوید.', 'osm1')));
        }

        $points = intval($_POST['points'] ?? 0);
        $order_id = intval($_POST['order_id'] ?? 0);

        if ($points <= 0) {
            wp_send_json_error(array('message' => __('تعداد امتیاز نامعتبر است.', 'osm1')));
        }

        $user_id = get_current_user_id();
        $result = $this->redeem_points(
            $user_id,
            $points,
            $order_id,
            __('استفاده از امتیاز', 'osm1')
        );

        if ($result) {
            wp_send_json_success(array(
                'message' => __('امتیاز با موفقیت استفاده شد.', 'osm1'),
                'remaining_points' => $this->get_user_points($user_id)['points'],
            ));
        } else {
            wp_send_json_error(array('message' => __('امتیاز کافی ندارید.', 'osm1')));
        }
    }

    /**
     * Display loyalty points shortcode
     */
    public function display_loyalty_points($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('لطفا وارد شوید تا امتیازهای خود را ببینید.', 'osm1') . '</p>';
        }

        $user_id = get_current_user_id();
        $points_data = $this->get_user_points($user_id);

        ob_start();
        ?>
        <div class="osm1-loyalty-widget">
            <h3><?php _e('امتیازهای وفاداری', 'osm1'); ?></h3>
            <div class="loyalty-stats">
                <p><strong><?php _e('امتیاز فعلی:', 'osm1'); ?></strong> <?php echo number_format($points_data['points']); ?></p>
                <p><strong><?php _e('سطح:', 'osm1'); ?></strong> <?php echo esc_html($this->get_level_label($points_data['level'])); ?></p>
                <p><strong><?php _e('کل امتیاز کسب شده:', 'osm1'); ?></strong> <?php echo number_format($points_data['total_earned']); ?></p>
                <p><strong><?php _e('کل امتیاز استفاده شده:', 'osm1'); ?></strong> <?php echo number_format($points_data['total_spent']); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Notify level up
     */
    private function notify_level_up($user_id, $new_level) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $notifications = new OSM1_Notifications();
        
        wp_mail(
            $user->user_email,
            __('ارتقای سطح در برنامه وفاداری', 'osm1'),
            sprintf(
                __('تبریک! شما به سطح %s ارتقا یافتید.', 'osm1'),
                $this->get_level_label($new_level)
            )
        );
    }
}

