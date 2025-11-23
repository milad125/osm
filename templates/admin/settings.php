<?php
/**
 * Admin Settings Template
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

if (isset($_GET['updated'])) {
    echo '<div class="notice notice-success"><p>' . __('تنظیمات ذخیره شد.', 'osm1') . '</p></div>';
}
?>

<div class="wrap osm1-admin-wrap">
    <h1>
        <span style="display: inline-block; margin-left: 12px; font-size: 24px;">⚙️</span>
        <?php _e('تنظیمات سیستم حمل و نقل', 'osm1'); ?>
    </h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible" style="margin: 20px 30px 0;">
            <p><strong>✅</strong> <?php _e('تنظیمات با موفقیت ذخیره شد.', 'osm1'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php settings_fields('osm1_settings'); ?>

        <div class="osm1-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active">
                    <span style="margin-left: 6px;">⚙️</span>
                    <?php _e('عمومی', 'osm1'); ?>
                </a>
                <a href="#apis" class="nav-tab">
                    <span style="margin-left: 6px;">🔌</span>
                    <?php _e('API ها', 'osm1'); ?>
                </a>
                <a href="#packaging" class="nav-tab">
                    <span style="margin-left: 6px;">📦</span>
                    <?php _e('بسته‌بندی', 'osm1'); ?>
                </a>
                <a href="#loyalty" class="nav-tab">
                    <span style="margin-left: 6px;">🎁</span>
                    <?php _e('وفاداری', 'osm1'); ?>
                </a>
                <a href="#notifications" class="nav-tab">
                    <span style="margin-left: 6px;">🔔</span>
                    <?php _e('اعلان‌ها', 'osm1'); ?>
                </a>
            </nav>

            <div id="general" class="tab-content active">
                <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                    <span style="margin-left: 8px;">⚙️</span>
                    <?php _e('تنظیمات عمومی', 'osm1'); ?>
                </h2>
                <table class="form-table">
                    <tr>
                        <th><label for="osm1_google_maps_api_key"><?php _e('کلید API نقشه گوگل', 'osm1'); ?></label></th>
                        <td>
                            <input type="text" id="osm1_google_maps_api_key" name="osm1_google_maps_api_key" 
                                   value="<?php echo esc_attr(get_option('osm1_google_maps_api_key', '')); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('کلید API نقشه گوگل برای نمایش نقشه', 'osm1'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('مرکز نقشه (عرض جغرافیایی)', 'osm1'); ?></label></th>
                        <td>
                            <input type="number" step="0.000001" name="osm1_map_center_lat" 
                                   value="<?php echo esc_attr(get_option('osm1_map_center_lat', 35.6892)); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e('مرکز نقشه (طول جغرافیایی)', 'osm1'); ?></label></th>
                        <td>
                            <input type="number" step="0.000001" name="osm1_map_center_lng" 
                                   value="<?php echo esc_attr(get_option('osm1_map_center_lng', 51.3890)); ?>" />
                        </td>
                    </tr>
                </table>
            </div>

            <div id="apis" class="tab-content">
                <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                    <span style="margin-left: 8px;">🔌</span>
                    <?php _e('تنظیمات API', 'osm1'); ?>
                </h2>
                
                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-right: 4px solid #3b82f6;">
                    <h3 style="margin-top: 0; color: #1f2937;">
                        <span style="margin-left: 8px;">📮</span>
                        <?php _e('پست تاپین', 'osm1'); ?>
                    </h3>
                <table class="form-table">
                    <tr>
                        <th><label><input type="checkbox" name="osm1_enable_tapin" value="yes" 
                                         <?php checked(get_option('osm1_enable_tapin'), 'yes'); ?> />
                            <?php _e('فعال کردن تاپین', 'osm1'); ?></label></th>
                    </tr>
                    <tr>
                        <th><label><?php _e('کلید API', 'osm1'); ?></label></th>
                        <td><input type="text" name="osm1_tapin_api_key" 
                                   value="<?php echo esc_attr(get_option('osm1_tapin_api_key', '')); ?>" 
                                   class="regular-text" /></td>
                    </tr>
                </table>
                </div>

                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-right: 4px solid #10b981;">
                    <h3 style="margin-top: 0; color: #1f2937;">
                        <span style="margin-left: 8px;">🚚</span>
                        <?php _e('الوپیک', 'osm1'); ?>
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th><label><input type="checkbox" name="osm1_enable_alopeyk" value="yes" 
                                             <?php checked(get_option('osm1_enable_alopeyk'), 'yes'); ?> />
                                <?php _e('فعال کردن الوپیک', 'osm1'); ?></label></th>
                        </tr>
                        <tr>
                            <th><label><?php _e('کلید API', 'osm1'); ?></label></th>
                            <td><input type="text" name="osm1_alopeyk_api_key" 
                                       value="<?php echo esc_attr(get_option('osm1_alopeyk_api_key', '')); ?>" 
                                       class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-right: 4px solid #f59e0b;">
                    <h3 style="margin-top: 0; color: #1f2937;">
                        <span style="margin-left: 8px;">📦</span>
                        <?php _e('اسنپ باکس', 'osm1'); ?>
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th><label><input type="checkbox" name="osm1_enable_snappbox" value="yes" 
                                             <?php checked(get_option('osm1_enable_snappbox'), 'yes'); ?> />
                                <?php _e('فعال کردن اسنپ باکس', 'osm1'); ?></label></th>
                        </tr>
                        <tr>
                            <th><label><?php _e('کلید API', 'osm1'); ?></label></th>
                            <td><input type="text" name="osm1_snappbox_api_key" 
                                       value="<?php echo esc_attr(get_option('osm1_snappbox_api_key', '')); ?>" 
                                       class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-right: 4px solid #8b5cf6;">
                    <h3 style="margin-top: 0; color: #1f2937;">
                        <span style="margin-left: 8px;">📮</span>
                        <?php _e('تیپاکس', 'osm1'); ?>
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th><label><input type="checkbox" name="osm1_enable_tipax" value="yes" 
                                             <?php checked(get_option('osm1_enable_tipax'), 'yes'); ?> />
                                <?php _e('فعال کردن تیپاکس', 'osm1'); ?></label></th>
                        </tr>
                        <tr>
                            <th><label><?php _e('کلید API', 'osm1'); ?></label></th>
                            <td><input type="text" name="osm1_tipax_api_key" 
                                       value="<?php echo esc_attr(get_option('osm1_tipax_api_key', '')); ?>" 
                                       class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-right: 4px solid #6b7280;">
                    <h3 style="margin-top: 0; color: #1f2937;">
                        <span style="margin-left: 8px;">📮</span>
                        <?php _e('پستکس (Postex)', 'osm1'); ?>
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th><label><input type="checkbox" name="osm1_enable_postex" value="yes" 
                                             <?php checked(get_option('osm1_enable_postex'), 'yes'); ?> />
                                <?php _e('فعال کردن پستکس', 'osm1'); ?></label></th>
                        </tr>
                        <tr>
                            <th><label><?php _e('کلید API', 'osm1'); ?></label></th>
                            <td><input type="text" name="osm1_postex_api_key" 
                                       value="<?php echo esc_attr(get_option('osm1_postex_api_key', '')); ?>" 
                                       class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label><?php _e('توکن API', 'osm1'); ?></label></th>
                            <td><input type="text" name="osm1_postex_api_token" 
                                       value="<?php echo esc_attr(get_option('osm1_postex_api_token', '')); ?>" 
                                       class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-right: 4px solid #ef4444;">
                    <h3 style="margin-top: 0; color: #1f2937;">
                        <span style="margin-left: 8px;">⚡</span>
                        <?php _e('ارسال فلش', 'osm1'); ?>
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th><label><input type="checkbox" name="osm1_enable_flash_delivery" value="yes" 
                                             <?php checked(get_option('osm1_enable_flash_delivery'), 'yes'); ?> />
                                <?php _e('فعال کردن ارسال فلش', 'osm1'); ?></label></th>
                        </tr>
                    </table>
                </div>
            </div>

            <div id="packaging" class="tab-content">
                <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                    <span style="margin-left: 8px;">📦</span>
                    <?php _e('هزینه بسته‌بندی', 'osm1'); ?>
                </h2>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('هزینه بسته‌بندی', 'osm1'); ?></label></th>
                        <td>
                            <input type="number" step="1000" name="osm1_packaging_cost" 
                                   value="<?php echo esc_attr(get_option('osm1_packaging_cost', 0)); ?>" />
                            <select name="osm1_packaging_cost_type" style="margin-right: 10px;">
                                <option value="fixed" <?php selected(get_option('osm1_packaging_cost_type'), 'fixed'); ?>>
                                    <?php _e('ثابت (ریال)', 'osm1'); ?>
                                </option>
                                <option value="percentage" <?php selected(get_option('osm1_packaging_cost_type'), 'percentage'); ?>>
                                    <?php _e('درصدی', 'osm1'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="loyalty" class="tab-content">
                <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                    <span style="margin-left: 8px;">🎁</span>
                    <?php _e('برنامه وفاداری', 'osm1'); ?>
                </h2>
                <table class="form-table">
                    <tr>
                        <th><label><input type="checkbox" name="osm1_enable_loyalty" value="yes" 
                                         <?php checked(get_option('osm1_enable_loyalty'), 'yes'); ?> />
                            <?php _e('فعال کردن برنامه وفاداری', 'osm1'); ?></label></th>
                    </tr>
                    <tr>
                        <th><label><?php _e('امتیاز به ازای هر سفارش', 'osm1'); ?></label></th>
                        <td><input type="number" name="osm1_loyalty_points_per_order" 
                                   value="<?php echo esc_attr(get_option('osm1_loyalty_points_per_order', 10)); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label><?php _e('امتیاز به ازای هر ریال', 'osm1'); ?></label></th>
                        <td><input type="number" step="0.01" name="osm1_loyalty_points_per_rial" 
                                   value="<?php echo esc_attr(get_option('osm1_loyalty_points_per_rial', 0.01)); ?>" /></td>
                    </tr>
                </table>
            </div>

            <div id="notifications" class="tab-content">
                <h2 style="margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6;">
                    <span style="margin-left: 8px;">🔔</span>
                    <?php _e('اعلان‌ها', 'osm1'); ?>
                </h2>
                <table class="form-table">
                    <tr>
                        <th><label><input type="checkbox" name="osm1_enable_notifications" value="yes" 
                                         <?php checked(get_option('osm1_enable_notifications'), 'yes'); ?> />
                            <?php _e('فعال کردن اعلان‌ها', 'osm1'); ?></label></th>
                    </tr>
                    <tr>
                        <th><label><input type="checkbox" name="osm1_notification_email" value="yes" 
                                         <?php checked(get_option('osm1_notification_email'), 'yes'); ?> />
                            <?php _e('ارسال ایمیل', 'osm1'); ?></label></th>
                    </tr>
                    <tr>
                        <th><label><input type="checkbox" name="osm1_notification_sms" value="yes" 
                                         <?php checked(get_option('osm1_notification_sms'), 'yes'); ?> />
                            <?php _e('ارسال SMS', 'osm1'); ?></label></th>
                    </tr>
                </table>
            </div>
        </div>

        <div style="margin: 30px; text-align: left;">
            <?php submit_button(__('ذخیره تنظیمات', 'osm1'), 'primary large', 'submit', false, array('style' => 'padding: 12px 30px; font-size: 16px;')); ?>
        </div>
        <div style="margin: 30px; text-align: left;">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('osm1_repair_zones_nonce'); ?>
                <input type="hidden" name="action" value="osm1_repair_zones" />
                <button type="submit" class="button secondary" style="padding: 10px 20px;">🔧 <?php _e('رفع مشکل متدهای ارسال در زون‌ها (Repair zones)', 'osm1'); ?></button>
            </form>
        </div>
    </form>
</div>

