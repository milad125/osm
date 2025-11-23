<?php
/**
 * Admin Centers Template
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'osm1_shipping_centers';

// Handle form submission
if (isset($_POST['osm1_add_center']) && check_admin_referer('osm1_center_nonce')) {
    $wpdb->insert(
        $table,
        array(
            'name' => sanitize_text_field($_POST['name']),
            'address' => sanitize_text_field($_POST['address']),
            'latitude' => floatval($_POST['latitude']),
            'longitude' => floatval($_POST['longitude']),
            'city' => sanitize_text_field($_POST['city']),
            'province' => sanitize_text_field($_POST['province']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ),
        array('%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%d')
    );
    
    echo '<div class="notice notice-success"><p>' . __('مرکز با موفقیت افزوده شد.', 'osm1') . '</p></div>';
}

$centers = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);
?>

<div class="wrap osm1-admin-wrap">
    <h1>
        <span style="display: inline-block; margin-left: 12px; font-size: 24px;">🏢</span>
        <?php _e('مدیریت مراکز ارسال', 'osm1'); ?>
    </h1>

    <div class="osm1-centers-management">
        <div class="osm1-add-center-form">
            <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                <span style="margin-left: 8px;">➕</span>
                <?php _e('افزودن مرکز جدید', 'osm1'); ?>
            </h2>
            <form method="post" action="">
                <?php wp_nonce_field('osm1_center_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('نام مرکز', 'osm1'); ?> *</label></th>
                        <td><input type="text" name="name" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('آدرس', 'osm1'); ?> *</label></th>
                        <td><textarea name="address" rows="3" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('عرض جغرافیایی', 'osm1'); ?> *</label></th>
                        <td><input type="number" step="0.000001" name="latitude" required /></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('طول جغرافیایی', 'osm1'); ?> *</label></th>
                        <td><input type="number" step="0.000001" name="longitude" required /></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('شهر', 'osm1'); ?> *</label></th>
                        <td><input type="text" name="city" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('استان', 'osm1'); ?> *</label></th>
                        <td><input type="text" name="province" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('تلفن', 'osm1'); ?></label></th>
                        <td><input type="text" name="phone" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('ایمیل', 'osm1'); ?></label></th>
                        <td><input type="email" name="email" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('فعال', 'osm1'); ?></label></th>
                        <td><input type="checkbox" name="is_active" value="1" checked /></td>
                    </tr>
                </table>
                <?php submit_button(__('افزودن مرکز', 'osm1'), 'primary', 'osm1_add_center'); ?>
            </form>
        </div>

        <div class="osm1-centers-list">
            <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                <span style="margin-left: 8px;">📋</span>
                <?php _e('لیست مراکز', 'osm1'); ?>
            </h2>
            <?php if (empty($centers)): ?>
                <p><?php _e('مرکزی یافت نشد.', 'osm1'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('نام', 'osm1'); ?></th>
                            <th><?php _e('آدرس', 'osm1'); ?></th>
                            <th><?php _e('شهر', 'osm1'); ?></th>
                            <th><?php _e('مختصات', 'osm1'); ?></th>
                            <th><?php _e('وضعیت', 'osm1'); ?></th>
                            <th><?php _e('عملیات', 'osm1'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($centers as $center): ?>
                            <tr>
                                <td><strong><?php echo esc_html($center['name']); ?></strong></td>
                                <td><?php echo esc_html($center['address']); ?></td>
                                <td><?php echo esc_html($center['city']); ?></td>
                                <td><?php echo esc_html($center['latitude'] . ', ' . $center['longitude']); ?></td>
                                <td>
                                    <?php if ($center['is_active']): ?>
                                        <span class="osm1-status-badge osm1-status-delivered"><?php _e('فعال', 'osm1'); ?></span>
                                    <?php else: ?>
                                        <span class="osm1-status-badge osm1-status-pending"><?php _e('غیرفعال', 'osm1'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="#" class="button button-small osm1-edit-center" data-center-id="<?php echo $center['id']; ?>">
                                        <?php _e('ویرایش', 'osm1'); ?>
                                    </a>
                                    <a href="#" class="button button-small button-link-delete osm1-delete-center" data-center-id="<?php echo $center['id']; ?>">
                                        <?php _e('حذف', 'osm1'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

