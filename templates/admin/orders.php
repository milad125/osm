<?php
/**
 * Admin Orders Template
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'osm1_shipping_orders';

$orders = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50", ARRAY_A);
?>

<div class="wrap osm1-admin-wrap">
    <h1>
        <span style="display: inline-block; margin-left: 12px; font-size: 24px;">📦</span>
        <?php _e('سفارشات ارسال', 'osm1'); ?>
    </h1>

    <div class="osm1-orders-table">
        <?php if (empty($orders)): ?>
            <p><?php _e('سفارشی یافت نشد.', 'osm1'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('شماره سفارش', 'osm1'); ?></th>
                        <th><?php _e('روش ارسال', 'osm1'); ?></th>
                        <th><?php _e('شماره رهگیری', 'osm1'); ?></th>
                        <th><?php _e('وضعیت', 'osm1'); ?></th>
                        <th><?php _e('مقصد', 'osm1'); ?></th>
                        <th><?php _e('هزینه کل', 'osm1'); ?></th>
                        <th><?php _e('تاریخ', 'osm1'); ?></th>
                        <th><?php _e('عملیات', 'osm1'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $wc_order = wc_get_order($order['order_id']);
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $order['order_id'] . '&action=edit'); ?>">
                                    #<?php echo $order['order_id']; ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($order['shipping_method']); ?></td>
                            <td><code><?php echo esc_html($order['tracking_number']); ?></code></td>
                            <td>
                                <select class="osm1-order-status" data-order-id="<?php echo $order['id']; ?>">
                                    <option value="pending" <?php selected($order['status'], 'pending'); ?>><?php _e('در انتظار', 'osm1'); ?></option>
                                    <option value="picked_up" <?php selected($order['status'], 'picked_up'); ?>><?php _e('تحویل گرفته شده', 'osm1'); ?></option>
                                    <option value="in_transit" <?php selected($order['status'], 'in_transit'); ?>><?php _e('در حال ارسال', 'osm1'); ?></option>
                                    <option value="out_for_delivery" <?php selected($order['status'], 'out_for_delivery'); ?>><?php _e('در مسیر تحویل', 'osm1'); ?></option>
                                    <option value="delivered" <?php selected($order['status'], 'delivered'); ?>><?php _e('تحویل داده شده', 'osm1'); ?></option>
                                    <option value="failed" <?php selected($order['status'], 'failed'); ?>><?php _e('ناموفق', 'osm1'); ?></option>
                                </select>
                            </td>
                            <td><?php echo esc_html($order['destination_address']); ?></td>
                            <td><?php echo wc_price($order['total_cost']); ?></td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="#" class="button button-small osm1-track-order" data-tracking="<?php echo esc_attr($order['tracking_number']); ?>">
                                    <?php _e('رهگیری', 'osm1'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

