<?php
/**
 * Notifications Class
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_Notifications {

    /**
     * Constructor
     */
    public function __construct() {
        // Notifications are sent on demand
    }

    /**
     * Send tracking created notification
     */
    public function send_tracking_created($order_id, $tracking_number) {
        if (get_option('osm1_enable_notifications') !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();

        // Email notification
        if (get_option('osm1_notification_email') === 'yes') {
            $this->send_email($customer_email, array(
                'subject' => __('شماره رهگیری سفارش شما', 'osm1'),
                'message' => sprintf(
                    __('سفارش شما با شماره %s ثبت شد. شماره رهگیری: %s', 'osm1'),
                    $order->get_order_number(),
                    $tracking_number
                ),
            ));
        }

        // SMS notification
        if (get_option('osm1_notification_sms') === 'yes' && !empty($customer_phone)) {
            $this->send_sms($customer_phone, sprintf(
                __('سفارش شما ثبت شد. شماره رهگیری: %s', 'osm1'),
                $tracking_number
            ));
        }

        // Admin notification
        $this->notify_admin(array(
            'type' => 'tracking_created',
            'order_id' => $order_id,
            'tracking_number' => $tracking_number,
        ));
    }

    /**
     * Send status update notification
     */
    public function send_status_update($order_id, $new_status) {
        if (get_option('osm1_enable_notifications') !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();

        $status_labels = array(
            'picked_up' => __('سفارش شما تحویل گرفته شد', 'osm1'),
            'in_transit' => __('سفارش شما در حال ارسال است', 'osm1'),
            'out_for_delivery' => __('سفارش شما در مسیر تحویل است', 'osm1'),
            'delivered' => __('سفارش شما تحویل داده شد', 'osm1'),
            'failed' => __('ارسال سفارش شما ناموفق بود', 'osm1'),
        );

        $message = $status_labels[$new_status] ?? __('وضعیت سفارش شما تغییر کرد', 'osm1');

        // Email
        if (get_option('osm1_notification_email') === 'yes') {
            $this->send_email($customer_email, array(
                'subject' => __('به‌روزرسانی وضعیت سفارش', 'osm1'),
                'message' => $message,
            ));
        }

        // SMS
        if (get_option('osm1_notification_sms') === 'yes' && !empty($customer_phone)) {
            $this->send_sms($customer_phone, $message);
        }
    }

    /**
     * Send email
     */
    private function send_email($to, $data) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail(
            $to,
            $data['subject'],
            $this->format_email_template($data['message']),
            $headers
        );
    }

    /**
     * Format email template
     */
    private function format_email_template($message) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html dir="rtl" lang="fa">
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Tahoma, Arial, sans-serif; direction: rtl; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('سیستم حمل و نقل', 'osm1'); ?></h1>
                </div>
                <div class="content">
                    <?php echo wp_kses_post($message); ?>
                </div>
                <div class="footer">
                    <?php _e('این ایمیل به صورت خودکار ارسال شده است.', 'osm1'); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Send SMS
     */
    private function send_sms($phone, $message) {
        // Integrate with SMS gateway (e.g., Kavenegar, Melipayamak)
        // This is a placeholder - implement based on your SMS provider
        
        $sms_api_key = get_option('osm1_sms_api_key', '');
        if (empty($sms_api_key)) {
            return;
        }

        // Example: Kavenegar API
        $url = 'https://api.kavenegar.com/v1/' . $sms_api_key . '/sms/send.json';
        
        $args = array(
            'method' => 'POST',
            'body' => array(
                'receptor' => $phone,
                'sender' => get_option('osm1_sms_sender', '10001001'),
                'message' => $message,
            ),
        );

        wp_remote_post($url, $args);
    }

    /**
     * Notify admin
     */
    private function notify_admin($data) {
        $admin_email = get_option('admin_email');
        
        $message = sprintf(
            __('یک رویداد جدید در سیستم حمل و نقل: %s - سفارش: %s', 'osm1'),
            $data['type'],
            $data['order_id']
        );

        wp_mail(
            $admin_email,
            __('اعلان سیستم حمل و نقل', 'osm1'),
            $message
        );
    }
}

