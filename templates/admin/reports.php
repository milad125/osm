<?php
/**
 * Admin Reports Template
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'osm1_shipping_orders';

// Get statistics
$total_orders = $wpdb->get_var("SELECT COUNT(*) FROM $table");
$total_revenue = $wpdb->get_var("SELECT SUM(total_cost) FROM $table");
$avg_cost = $wpdb->get_var("SELECT AVG(total_cost) FROM $table");

// Orders by status
$orders_by_status = $wpdb->get_results(
    "SELECT status, COUNT(*) as count FROM $table GROUP BY status",
    ARRAY_A
);

// Orders by method
$orders_by_method = $wpdb->get_results(
    "SELECT shipping_method, COUNT(*) as count, SUM(total_cost) as revenue 
     FROM $table GROUP BY shipping_method",
    ARRAY_A
);

// Monthly statistics
$monthly_stats = $wpdb->get_results(
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as orders,
        SUM(total_cost) as revenue
     FROM $table 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY month
     ORDER BY month DESC",
    ARRAY_A
);
?>

<div class="wrap osm1-admin-wrap">
    <h1>
        <span style="display: inline-block; margin-left: 12px; font-size: 24px;">📊</span>
        <?php _e('گزارشات و آمار', 'osm1'); ?>
    </h1>

    <div class="osm1-reports-overview">
        <div class="osm1-dashboard-widgets">
            <div class="osm1-widget">
                <h3><?php _e('کل سفارشات', 'osm1'); ?></h3>
                <div class="osm1-stat">
                    <span class="stat-value"><?php echo number_format($total_orders); ?></span>
                </div>
            </div>
            <div class="osm1-widget">
                <h3><?php _e('کل درآمد', 'osm1'); ?></h3>
                <div class="osm1-stat">
                    <span class="stat-value"><?php echo wc_price($total_revenue); ?></span>
                </div>
            </div>
            <div class="osm1-widget">
                <h3><?php _e('میانگین هزینه', 'osm1'); ?></h3>
                <div class="osm1-stat">
                    <span class="stat-value"><?php echo wc_price($avg_cost); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="osm1-reports-sections">
        <div class="osm1-reports-chart">
            <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                <span style="margin-left: 8px;">📈</span>
                <?php _e('سفارشات بر اساس وضعیت', 'osm1'); ?>
            </h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('وضعیت', 'osm1'); ?></th>
                        <th><?php _e('تعداد', 'osm1'); ?></th>
                        <th><?php _e('درصد', 'osm1'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders_by_status as $stat): ?>
                        <tr>
                            <td><?php echo esc_html($stat['status']); ?></td>
                            <td><?php echo number_format($stat['count']); ?></td>
                            <td><?php echo $total_orders > 0 ? number_format(($stat['count'] / $total_orders) * 100, 2) : 0; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="osm1-reports-chart">
            <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                <span style="margin-left: 8px;">🚚</span>
                <?php _e('سفارشات بر اساس روش ارسال', 'osm1'); ?>
            </h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('روش ارسال', 'osm1'); ?></th>
                        <th><?php _e('تعداد', 'osm1'); ?></th>
                        <th><?php _e('درآمد', 'osm1'); ?></th>
                        <th><?php _e('میانگین', 'osm1'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders_by_method as $method): ?>
                        <tr>
                            <td><?php echo esc_html($method['shipping_method']); ?></td>
                            <td><?php echo number_format($method['count']); ?></td>
                            <td><?php echo wc_price($method['revenue']); ?></td>
                            <td><?php echo wc_price($method['revenue'] / $method['count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="osm1-reports-chart">
            <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                <span style="margin-left: 8px;">📅</span>
                <?php _e('آمار ماهانه (12 ماه اخیر)', 'osm1'); ?>
            </h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ماه', 'osm1'); ?></th>
                        <th><?php _e('تعداد سفارش', 'osm1'); ?></th>
                        <th><?php _e('درآمد', 'osm1'); ?></th>
                        <th><?php _e('میانگین', 'osm1'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_stats as $month): ?>
                        <tr>
                            <td><?php echo esc_html($month['month']); ?></td>
                            <td><?php echo number_format($month['orders']); ?></td>
                            <td><?php echo wc_price($month['revenue']); ?></td>
                            <td><?php echo wc_price($month['revenue'] / $month['orders']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

