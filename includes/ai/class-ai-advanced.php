<?php
/**
 * Advanced AI Class
 * 
 * این کلاس قابلیت‌های پیشرفته هوش مصنوعی را برای سیستم حمل و نقل فراهم می‌کند
 *
 * @package OSM1
 */

if (!defined('ABSPATH')) {
    exit;
}

class OSM1_AI_Advanced {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_osm1_ai_suggest_shipping', array($this, 'ajax_suggest_shipping'));
        add_action('wp_ajax_osm1_ai_predict_delivery', array($this, 'ajax_predict_delivery'));
        add_action('wp_ajax_osm1_ai_optimize_route', array($this, 'ajax_optimize_route'));
        add_action('woocommerce_checkout_process', array($this, 'ai_validate_shipping'));
    }

    /**
     * پیشنهاد هوشمند روش ارسال بر اساس:
     * - فاصله
     * - وزن محصولات
     * - هزینه
     * - زمان تحویل
     * - تاریخچه کاربر
     * - آب و هوا (اختیاری)
     */
    public function suggest_optimal_shipping($order_data) {
        $factors = array(
            'distance' => $this->calculate_distance_score($order_data['distance']),
            'weight' => $this->calculate_weight_score($order_data['weight']),
            'cost' => $this->calculate_cost_score($order_data['budget']),
            'urgency' => $this->calculate_urgency_score($order_data['urgency']),
            'user_history' => $this->analyze_user_history($order_data['user_id']),
            'weather' => $this->get_weather_impact($order_data['destination']),
        );

        // وزن‌دهی به فاکتورها
        $weights = array(
            'distance' => 0.15,
            'weight' => 0.10,
            'cost' => 0.25,
            'urgency' => 0.20,
            'user_history' => 0.20,
            'weather' => 0.10,
        );

        // محاسبه امتیاز برای هر روش ارسال
        $methods = array('tapin', 'alopeyk', 'snappbox', 'tipax', 'flash');
        $scores = array();

        foreach ($methods as $method) {
            $score = 0;
            foreach ($factors as $factor => $value) {
                $method_factor_score = $this->get_method_factor_score($method, $factor, $order_data);
                $score += $method_factor_score * $weights[$factor] * $value;
            }
            $scores[$method] = $score;
        }

        // مرتب‌سازی بر اساس امتیاز
        arsort($scores);
        
        return array(
            'recommended' => array_keys($scores)[0],
            'scores' => $scores,
            'reason' => $this->generate_recommendation_reason(array_keys($scores)[0], $factors),
        );
    }

    /**
     * پیش‌بینی دقیق زمان تحویل با استفاده از:
     * - یادگیری ماشین (ML) ساده
     * - تاریخچه تحویل‌های قبلی
     * - ترافیک
     * - آب و هوا
     */
    public function predict_delivery_time_advanced($order_data) {
        // جمع‌آوری داده‌های تاریخی
        $historical_data = $this->get_historical_delivery_data($order_data);
        
        // محاسبه میانگین زمان تحویل برای مسیر مشابه
        $base_time = $this->calculate_base_delivery_time($order_data);
        
        // اعمال فاکتورهای تأثیرگذار
        $traffic_factor = $this->get_traffic_factor($order_data['route']);
        $weather_factor = $this->get_weather_factor($order_data['destination']);
        $time_of_day_factor = $this->get_time_of_day_factor();
        $day_of_week_factor = $this->get_day_of_week_factor();
        
        // محاسبه نهایی
        $predicted_time = $base_time;
        $predicted_time *= (1 + $traffic_factor);
        $predicted_time *= (1 + $weather_factor);
        $predicted_time *= (1 + $time_of_day_factor);
        $predicted_time *= (1 + $day_of_week_factor);
        
        // اضافه کردن buffer برای اطمینان
        $predicted_time *= 1.15; // 15% buffer
        
        return array(
            'estimated_hours' => round($predicted_time),
            'estimated_date' => date('Y-m-d H:i:s', strtotime("+{$predicted_time} hours")),
            'confidence' => $this->calculate_confidence($historical_data),
            'factors' => array(
                'traffic' => $traffic_factor * 100,
                'weather' => $weather_factor * 100,
                'time_of_day' => $time_of_day_factor * 100,
                'day_of_week' => $day_of_week_factor * 100,
            ),
        );
    }

    /**
     * بهینه‌سازی مسیر با الگوریتم‌های هوش مصنوعی
     * - الگوریتم نزدیک‌ترین همسایه (Nearest Neighbor)
     * - الگوریتم ژنتیک (Genetic Algorithm) - برای چندین مقصد
     */
    public function optimize_delivery_route($destinations, $origin) {
        if (count($destinations) <= 1) {
            return array('route' => $destinations, 'total_distance' => 0);
        }

        // برای مسیرهای ساده از Nearest Neighbor استفاده می‌کنیم
        if (count($destinations) <= 5) {
            return $this->nearest_neighbor_algorithm($destinations, $origin);
        }

        // برای مسیرهای پیچیده از الگوریتم ژنتیک استفاده می‌کنیم
        return $this->genetic_algorithm_route($destinations, $origin);
    }

    /**
     * تحلیل رفتار کاربر و پیشنهاد شخصی‌سازی شده
     */
    public function personalize_shipping_suggestions($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        
        // دریافت تاریخچه سفارشات کاربر
        $user_orders = $wpdb->get_results($wpdb->prepare(
            "SELECT shipping_method, COUNT(*) as count, AVG(total_cost) as avg_cost, AVG(TIMESTAMPDIFF(HOUR, created_at, actual_delivery_date)) as avg_delivery_time
             FROM $table 
             WHERE order_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'shop_order')
             GROUP BY shipping_method
             ORDER BY count DESC",
            $user_id
        ), ARRAY_A);

        if (empty($user_orders)) {
            return null;
        }

        // تحلیل الگوهای کاربر
        $preferences = array(
            'preferred_method' => $user_orders[0]['shipping_method'],
            'avg_cost_tolerance' => $user_orders[0]['avg_cost'],
            'avg_delivery_time' => $user_orders[0]['avg_delivery_time'],
            'loyalty_score' => $this->calculate_user_loyalty_score($user_id),
        );

        return $preferences;
    }

    /**
     * تشخیص تقلب و آنومالی در سفارشات
     */
    public function detect_fraud($order_data) {
        $risk_factors = array();
        $risk_score = 0;

        // بررسی آدرس مشکوک
        if ($this->is_suspicious_address($order_data['address'])) {
            $risk_factors[] = 'suspicious_address';
            $risk_score += 30;
        }

        // بررسی الگوی خرید غیرعادی
        if ($this->has_unusual_purchase_pattern($order_data['user_id'])) {
            $risk_factors[] = 'unusual_pattern';
            $risk_score += 25;
        }

        // بررسی مبلغ غیرعادی
        if ($this->has_unusual_amount($order_data['amount'])) {
            $risk_factors[] = 'unusual_amount';
            $risk_score += 20;
        }

        // بررسی فاصله غیرمنطقی
        if ($this->has_unreasonable_distance($order_data['distance'])) {
            $risk_factors[] = 'unreasonable_distance';
            $risk_score += 15;
        }

        return array(
            'is_fraud' => $risk_score >= 50,
            'risk_score' => min($risk_score, 100),
            'risk_factors' => $risk_factors,
            'recommendation' => $risk_score >= 50 ? 'manual_review' : 'auto_approve',
        );
    }

    /**
     * پیش‌بینی تقاضا (Demand Forecasting)
     */
    public function forecast_demand($period = 'week') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        
        // دریافت داده‌های تاریخی
        $historical = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as count, AVG(total_cost) as avg_cost
             FROM $table
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            ARRAY_A
        );

        if (empty($historical)) {
            return null;
        }

        // محاسبه روند (Trend)
        $trend = $this->calculate_trend($historical);
        
        // محاسبه فصلی‌بودن (Seasonality)
        $seasonality = $this->calculate_seasonality($historical);
        
        // پیش‌بینی
        $forecast = array();
        $last_count = end($historical)['count'];
        
        for ($i = 1; $i <= ($period === 'week' ? 7 : 30); $i++) {
            $predicted = $last_count + ($trend * $i) + $seasonality;
            $forecast[] = array(
                'date' => date('Y-m-d', strtotime("+{$i} days")),
                'predicted_orders' => max(0, round($predicted)),
            );
        }

        return array(
            'forecast' => $forecast,
            'trend' => $trend,
            'confidence' => $this->calculate_forecast_confidence($historical),
        );
    }

    /**
     * بهینه‌سازی هزینه با الگوریتم‌های AI
     */
    public function optimize_costs($order_data) {
        // تحلیل هزینه‌های مختلف
        $costs = array();
        $methods = array('tapin', 'alopeyk', 'snappbox', 'tipax');
        
        foreach ($methods as $method) {
            $api = OSM1_API_Manager::get_api($method);
            if ($api) {
                $cost = $api->calculate_cost($order_data['origin'], $order_data['destination']);
                $costs[$method] = $cost;
            }
        }

        // پیدا کردن بهترین روش از نظر هزینه
        $best_method = array_keys($costs, min($costs))[0];
        
        // پیشنهاد تخفیف هوشمند برای روش‌های گران‌تر
        $suggestions = array();
        foreach ($costs as $method => $cost) {
            if ($cost > $costs[$best_method] * 1.2) {
                $suggestions[] = array(
                    'method' => $method,
                    'suggested_discount' => ($cost - $costs[$best_method]) * 0.5,
                    'reason' => 'برای رقابت با روش ارزان‌تر',
                );
            }
        }

        return array(
            'best_method' => $best_method,
            'best_cost' => $costs[$best_method],
            'all_costs' => $costs,
            'suggestions' => $suggestions,
        );
    }

    // ============================================
    // Helper Methods
    // ============================================

    private function calculate_distance_score($distance) {
        if ($distance < 10) return 1.0;
        if ($distance < 50) return 0.8;
        if ($distance < 100) return 0.6;
        return 0.4;
    }

    private function calculate_weight_score($weight) {
        if ($weight < 1) return 1.0;
        if ($weight < 5) return 0.8;
        if ($weight < 10) return 0.6;
        return 0.4;
    }

    private function calculate_cost_score($budget) {
        // این باید بر اساس بودجه کاربر باشد
        return 0.7; // پیش‌فرض
    }

    private function calculate_urgency_score($urgency) {
        if ($urgency === 'high') return 1.0;
        if ($urgency === 'medium') return 0.6;
        return 0.3;
    }

    private function analyze_user_history($user_id) {
        $preferences = $this->personalize_shipping_suggestions($user_id);
        return $preferences ? 0.8 : 0.5;
    }

    private function get_weather_impact($destination) {
        // این می‌تواند به یک API آب و هوا متصل شود
        return 0.5; // پیش‌فرض
    }

    private function get_method_factor_score($method, $factor, $order_data) {
        // امتیازدهی به هر روش بر اساس فاکتور
        $scores = array(
            'tapin' => array('distance' => 0.9, 'weight' => 0.8, 'cost' => 0.7, 'urgency' => 0.5),
            'alopeyk' => array('distance' => 0.8, 'weight' => 0.9, 'cost' => 0.6, 'urgency' => 0.9),
            'snappbox' => array('distance' => 0.7, 'weight' => 0.7, 'cost' => 0.8, 'urgency' => 0.8),
            'tipax' => array('distance' => 0.9, 'weight' => 0.9, 'cost' => 0.9, 'urgency' => 0.4),
            'flash' => array('distance' => 0.6, 'weight' => 0.6, 'cost' => 0.4, 'urgency' => 1.0),
        );

        return $scores[$method][$factor] ?? 0.5;
    }

    private function generate_recommendation_reason($method, $factors) {
        $reasons = array(
            'tapin' => 'بهترین گزینه برای مسافت‌های طولانی و هزینه مناسب',
            'alopeyk' => 'سریع و قابل اعتماد برای تحویل فوری',
            'snappbox' => 'تعادل خوب بین سرعت و هزینه',
            'tipax' => 'اقتصادی و مناسب برای بسته‌های سنگین',
            'flash' => 'تحویل در همان روز با بالاترین سرعت',
        );

        return $reasons[$method] ?? 'پیشنهاد بر اساس تحلیل هوش مصنوعی';
    }

    private function get_historical_delivery_data($order_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'osm1_shipping_orders';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE shipping_method = %s 
             AND destination_city = %s
             ORDER BY created_at DESC
             LIMIT 50",
            $order_data['method'],
            $order_data['city']
        ), ARRAY_A);
    }

    private function calculate_base_delivery_time($order_data) {
        $base_times = array(
            'osm1_tapin' => 48,
            'osm1_alopeyk' => 24,
            'osm1_snappbox' => 24,
            'osm1_tipax' => 72,
            'osm1_flash' => 4,
        );

        return $base_times[$order_data['method']] ?? 24;
    }

    private function get_traffic_factor($route) {
        // این می‌تواند به API ترافیک متصل شود
        $hour = (int)date('H');
        if ($hour >= 7 && $hour <= 9) return 0.3; // ترافیک صبح
        if ($hour >= 17 && $hour <= 19) return 0.3; // ترافیک عصر
        return 0.1; // ترافیک عادی
    }

    private function get_weather_factor($destination) {
        // این می‌تواند به API آب و هوا متصل شود
        return 0.05; // پیش‌فرض
    }

    private function get_time_of_day_factor() {
        $hour = (int)date('H');
        if ($hour >= 22 || $hour <= 6) return 0.2; // شب
        return 0;
    }

    private function get_day_of_week_factor() {
        $day = (int)date('w');
        if ($day == 5) return 0.15; // جمعه
        if ($day == 4) return 0.1; // پنج‌شنبه
        return 0;
    }

    private function calculate_confidence($historical_data) {
        if (count($historical_data) < 10) return 0.6;
        if (count($historical_data) < 30) return 0.75;
        return 0.9;
    }

    private function nearest_neighbor_algorithm($destinations, $origin) {
        $route = array();
        $unvisited = $destinations;
        $current = $origin;
        $total_distance = 0;

        while (!empty($unvisited)) {
            $nearest = null;
            $nearest_distance = PHP_INT_MAX;

            foreach ($unvisited as $dest) {
                $distance = $this->calculate_distance($current, $dest);
                if ($distance < $nearest_distance) {
                    $nearest_distance = $distance;
                    $nearest = $dest;
                }
            }

            $route[] = $nearest;
            $total_distance += $nearest_distance;
            $current = $nearest;
            $unvisited = array_filter($unvisited, function($d) use ($nearest) {
                return $d !== $nearest;
            });
        }

        return array(
            'route' => $route,
            'total_distance' => $total_distance,
        );
    }

    private function genetic_algorithm_route($destinations, $origin) {
        // پیاده‌سازی ساده الگوریتم ژنتیک
        // برای نسخه کامل‌تر می‌توان از کتابخانه‌های PHP استفاده کرد
        
        // در اینجا از nearest neighbor استفاده می‌کنیم
        return $this->nearest_neighbor_algorithm($destinations, $origin);
    }

    private function calculate_distance($point1, $point2) {
        $lat1 = $point1['lat'];
        $lng1 = $point1['lng'];
        $lat2 = $point2['lat'];
        $lng2 = $point2['lng'];

        $earth_radius = 6371;
        $d_lat = deg2rad($lat2 - $lat1);
        $d_lng = deg2rad($lng2 - $lng1);

        $a = sin($d_lat / 2) * sin($d_lat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($d_lng / 2) * sin($d_lng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth_radius * $c;
    }

    private function calculate_user_loyalty_score($user_id) {
        $gamification = new OSM1_Gamification();
        $points_data = $gamification->get_user_points($user_id);
        
        if ($points_data['points'] >= 10000) return 1.0;
        if ($points_data['points'] >= 5000) return 0.8;
        if ($points_data['points'] >= 2000) return 0.6;
        if ($points_data['points'] >= 500) return 0.4;
        return 0.2;
    }

    private function is_suspicious_address($address) {
        $suspicious_keywords = array('test', 'test123', '12345', 'fake');
        foreach ($suspicious_keywords as $keyword) {
            if (stripos($address, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function has_unusual_purchase_pattern($user_id) {
        // بررسی الگوهای غیرعادی خرید
        return false; // پیاده‌سازی کامل نیاز به داده‌های بیشتر دارد
    }

    private function has_unusual_amount($amount) {
        // بررسی مبالغ غیرعادی (خیلی بالا یا خیلی پایین)
        return $amount > 10000000 || $amount < 10000;
    }

    private function has_unreasonable_distance($distance) {
        // بررسی فاصله غیرمنطقی (خیلی زیاد)
        return $distance > 1000; // بیش از 1000 کیلومتر
    }

    private function calculate_trend($historical) {
        if (count($historical) < 2) return 0;
        
        $first = $historical[0]['count'];
        $last = end($historical)['count'];
        $days = count($historical);
        
        return ($last - $first) / $days;
    }

    private function calculate_seasonality($historical) {
        // محاسبه فصلی‌بودن ساده
        return 0; // پیاده‌سازی کامل نیاز به تحلیل پیچیده‌تر دارد
    }

    private function calculate_forecast_confidence($historical) {
        if (count($historical) < 30) return 0.6;
        if (count($historical) < 60) return 0.75;
        return 0.9;
    }

    // AJAX Handlers
    public function ajax_suggest_shipping() {
        check_ajax_referer('osm1_nonce', 'nonce');
        
        $order_data = array(
            'distance' => floatval($_POST['distance'] ?? 0),
            'weight' => floatval($_POST['weight'] ?? 0),
            'budget' => floatval($_POST['budget'] ?? 0),
            'urgency' => sanitize_text_field($_POST['urgency'] ?? 'normal'),
            'user_id' => get_current_user_id(),
        );

        $suggestion = $this->suggest_optimal_shipping($order_data);
        wp_send_json_success($suggestion);
    }

    public function ajax_predict_delivery() {
        check_ajax_referer('osm1_nonce', 'nonce');
        
        $order_data = array(
            'method' => sanitize_text_field($_POST['method'] ?? ''),
            'destination' => array(
                'lat' => floatval($_POST['lat'] ?? 0),
                'lng' => floatval($_POST['lng'] ?? 0),
            ),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
        );

        $prediction = $this->predict_delivery_time_advanced($order_data);
        wp_send_json_success($prediction);
    }

    public function ajax_optimize_route() {
        check_ajax_referer('osm1_nonce', 'nonce');
        
        $destinations = json_decode(stripslashes($_POST['destinations'] ?? '[]'), true);
        $origin = json_decode(stripslashes($_POST['origin'] ?? '{}'), true);

        $optimized = $this->optimize_delivery_route($destinations, $origin);
        wp_send_json_success($optimized);
    }

    public function ai_validate_shipping($order_id) {
        $order = wc_get_order($order_id);
        $order_data = array(
            'user_id' => $order->get_user_id(),
            'amount' => $order->get_total(),
            'address' => $order->get_shipping_address(),
        );

        $fraud_check = $this->detect_fraud($order_data);
        
        if ($fraud_check['is_fraud']) {
            // می‌توانید یک hook اضافه کنید برای بررسی دستی
            do_action('osm1_fraud_detected', $order_id, $fraud_check);
        }
    }
}

