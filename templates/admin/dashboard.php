<?php
/**
 * Admin Dashboard Template
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap osm1-admin-wrap">
    <h1>
        <span style="display: inline-block; margin-left: 12px; font-size: 24px;">🚚</span>
        <?php _e('داشبورد سیستم حمل و نقل', 'osm1'); ?>
    </h1>

    <div class="osm1-dashboard-widgets">
        <div class="osm1-widget">
            <h3>
                <span style="margin-left: 8px;">📦</span>
                <?php _e('آمار امروز', 'osm1'); ?>
            </h3>
            <div class="osm1-stat">
                <span class="stat-value"><?php echo number_format($this->get_today_orders_count()); ?></span>
                <span class="stat-label"><?php _e('سفارش جدید', 'osm1'); ?></span>
            </div>
        </div>

        <div class="osm1-widget">
            <h3>
                <span style="margin-left: 8px;">🚛</span>
                <?php _e('در حال ارسال', 'osm1'); ?>
            </h3>
            <div class="osm1-stat">
                <span class="stat-value"><?php echo number_format($this->get_in_transit_count()); ?></span>
                <span class="stat-label"><?php _e('سفارش در مسیر', 'osm1'); ?></span>
            </div>
        </div>

        <div class="osm1-widget">
            <h3>
                <span style="margin-left: 8px;">✅</span>
                <?php _e('تحویل داده شده', 'osm1'); ?>
            </h3>
            <div class="osm1-stat">
                <span class="stat-value"><?php echo number_format($this->get_delivered_count()); ?></span>
                <span class="stat-label"><?php _e('سفارش تحویل شده', 'osm1'); ?></span>
            </div>
        </div>

        <div class="osm1-widget">
            <h3>
                <span style="margin-left: 8px;">💰</span>
                <?php _e('درآمد امروز', 'osm1'); ?>
            </h3>
            <div class="osm1-stat">
                <span class="stat-value" style="font-size: 32px;"><?php echo wc_price($this->get_today_revenue()); ?></span>
                <span class="stat-label"><?php _e('ریال', 'osm1'); ?></span>
            </div>
        </div>
    </div>

    <div class="osm1-recent-orders">
        <h2>
            <span style="margin-left: 10px; font-size: 20px;">📋</span>
            <?php _e('سفارشات اخیر', 'osm1'); ?>
        </h2>
        <?php $this->display_recent_orders(); ?>
    </div>
</div>

