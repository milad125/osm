<?php
/**
 * Admin Loyalty Template
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$points_table = $wpdb->prefix . 'osm1_loyalty_points';
$transactions_table = $wpdb->prefix . 'osm1_loyalty_transactions';

// Get top users
$top_users = $wpdb->get_results(
    "SELECT * FROM $points_table ORDER BY points DESC LIMIT 20",
    ARRAY_A
);

// Get recent transactions
$recent_transactions = $wpdb->get_results(
    "SELECT t.*, u.display_name, u.user_email 
     FROM $transactions_table t 
     LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID 
     ORDER BY t.created_at DESC LIMIT 50",
    ARRAY_A
);

$gamification = new OSM1_Gamification();
?>

<div class="wrap osm1-admin-wrap">
    <h1>
        <span style="display: inline-block; margin-left: 12px; font-size: 24px;">🎁</span>
        <?php _e('برنامه وفاداری', 'osm1'); ?>
    </h1>

    <div class="osm1-loyalty-stats">
        <div class="osm1-loyalty-stat">
            <div class="osm1-loyalty-stat-value">
                <?php echo number_format($wpdb->get_var("SELECT COUNT(*) FROM $points_table")); ?>
            </div>
            <div class="osm1-loyalty-stat-label"><?php _e('کاربران فعال', 'osm1'); ?></div>
        </div>
        <div class="osm1-loyalty-stat">
            <div class="osm1-loyalty-stat-value">
                <?php echo number_format($wpdb->get_var("SELECT SUM(total_earned) FROM $points_table")); ?>
            </div>
            <div class="osm1-loyalty-stat-label"><?php _e('کل امتیاز توزیع شده', 'osm1'); ?></div>
        </div>
        <div class="osm1-loyalty-stat">
            <div class="osm1-loyalty-stat-value">
                <?php echo number_format($wpdb->get_var("SELECT SUM(total_spent) FROM $points_table")); ?>
            </div>
            <div class="osm1-loyalty-stat-label"><?php _e('کل امتیاز استفاده شده', 'osm1'); ?></div>
        </div>
        <div class="osm1-loyalty-stat">
            <div class="osm1-loyalty-stat-value">
                <?php echo number_format($wpdb->get_var("SELECT SUM(points) FROM $points_table")); ?>
            </div>
            <div class="osm1-loyalty-stat-label"><?php _e('امتیاز باقی‌مانده', 'osm1'); ?></div>
        </div>
    </div>

    <div class="osm1-loyalty-sections">
        <div class="osm1-top-users" style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); padding: 30px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                <span style="margin-left: 8px;">🏆</span>
                <?php _e('کاربران برتر', 'osm1'); ?>
            </h2>
            <?php if (empty($top_users)): ?>
                <p><?php _e('کاربری یافت نشد.', 'osm1'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('رتبه', 'osm1'); ?></th>
                            <th><?php _e('کاربر', 'osm1'); ?></th>
                            <th><?php _e('امتیاز', 'osm1'); ?></th>
                            <th><?php _e('سطح', 'osm1'); ?></th>
                            <th><?php _e('کل کسب شده', 'osm1'); ?></th>
                            <th><?php _e('کل استفاده شده', 'osm1'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_users as $index => $user): ?>
                            <?php
                            $wp_user = get_userdata($user['user_id']);
                            $level_label = $gamification->get_level_label($user['level']);
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <?php if ($wp_user): ?>
                                        <strong><?php echo esc_html($wp_user->display_name); ?></strong><br>
                                        <small><?php echo esc_html($wp_user->user_email); ?></small>
                                    <?php else: ?>
                                        <?php _e('کاربر حذف شده', 'osm1'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo number_format($user['points']); ?></strong></td>
                                <td>
                                    <span class="osm1-level-badge osm1-level-<?php echo esc_attr($user['level']); ?>">
                                        <?php echo esc_html($level_label); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($user['total_earned']); ?></td>
                                <td><?php echo number_format($user['total_spent']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="osm1-recent-transactions" style="background: #fff; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); padding: 30px;">
            <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                <span style="margin-left: 8px;">💳</span>
                <?php _e('تراکنش‌های اخیر', 'osm1'); ?>
            </h2>
            <?php if (empty($recent_transactions)): ?>
                <p><?php _e('تراکنشی یافت نشد.', 'osm1'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('کاربر', 'osm1'); ?></th>
                            <th><?php _e('سفارش', 'osm1'); ?></th>
                            <th><?php _e('امتیاز', 'osm1'); ?></th>
                            <th><?php _e('نوع', 'osm1'); ?></th>
                            <th><?php _e('توضیحات', 'osm1'); ?></th>
                            <th><?php _e('تاریخ', 'osm1'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <?php if ($transaction['display_name']): ?>
                                        <strong><?php echo esc_html($transaction['display_name']); ?></strong><br>
                                        <small><?php echo esc_html($transaction['user_email']); ?></small>
                                    <?php else: ?>
                                        <?php _e('کاربر حذف شده', 'osm1'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transaction['order_id']): ?>
                                        <a href="<?php echo admin_url('post.php?post=' . $transaction['order_id'] . '&action=edit'); ?>">
                                            #<?php echo $transaction['order_id']; ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transaction['type'] === 'earned'): ?>
                                        <span style="color: green;">+<?php echo number_format($transaction['points']); ?></span>
                                    <?php else: ?>
                                        <span style="color: red;">-<?php echo number_format($transaction['points']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($transaction['type'] === 'earned'): ?>
                                        <span class="osm1-status-badge osm1-status-delivered"><?php _e('کسب شده', 'osm1'); ?></span>
                                    <?php else: ?>
                                        <span class="osm1-status-badge osm1-status-pending"><?php _e('استفاده شده', 'osm1'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($transaction['description']); ?></td>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($transaction['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

