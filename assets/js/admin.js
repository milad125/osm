/**
 * Admin JavaScript
 *
 * @package OSM1
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initTabs();
        initAccordions();
        initAjaxActions();
    });

    function initTabs() {
        $('.osm1-settings-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this).attr('href');
            
            // Update tabs with smooth transition
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Update content with fade effect
            $('.tab-content').removeClass('active').fadeOut(200, function() {
                $(target).fadeIn(300).addClass('active');
            });
        });
    }

    function initAccordions() {
        $('.osm1-accordion').accordion({
            collapsible: true,
            heightStyle: 'content'
        });
    }

    function initAjaxActions() {
        // Delete center
        $(document).on('click', '.osm1-delete-center', function(e) {
            e.preventDefault();
            
            if (!confirm('آیا مطمئن هستید؟')) {
                return;
            }
            
            const centerId = $(this).data('center-id');
            const $row = $(this).closest('.osm1-center-item');
            
            $.ajax({
                url: osm1Admin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'osm1_delete_center',
                    nonce: osm1Admin.nonce,
                    center_id: centerId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('خطا در حذف مرکز');
                    }
                },
                error: function() {
                    alert('خطا در ارتباط با سرور');
                }
            });
        });

        // Update order status
        $(document).on('change', '.osm1-order-status', function() {
            const $select = $(this);
            const orderId = $select.data('order-id');
            const newStatus = $select.val();
            
            $.ajax({
                url: osm1Admin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'osm1_update_order_status',
                    nonce: osm1Admin.nonce,
                    order_id: orderId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $select.css('background-color', '#10b981');
                        setTimeout(function() {
                            $select.css('background-color', '');
                            location.reload();
                        }, 500);
                    } else {
                        alert('خطا در به‌روزرسانی وضعیت');
                    }
                },
                error: function() {
                    alert('خطا در ارتباط با سرور');
                }
            });
        });
    }

    // Form validation with visual feedback
    $('form').on('submit', function() {
        const $form = $(this);
        let isValid = true;
        
        $form.find('input[required]').each(function() {
            const $input = $(this);
            if (!$input.val()) {
                isValid = false;
                $input.css({
                    'border-color': '#ef4444',
                    'box-shadow': '0 0 0 3px rgba(239, 68, 68, 0.1)'
                });
                
                // Remove error styling after 3 seconds
                setTimeout(function() {
                    $input.css({
                        'border-color': '',
                        'box-shadow': ''
                    });
                }, 3000);
            } else {
                $input.css({
                    'border-color': '#10b981',
                    'box-shadow': '0 0 0 3px rgba(16, 185, 129, 0.1)'
                });
                
                setTimeout(function() {
                    $input.css({
                        'border-color': '',
                        'box-shadow': ''
                    });
                }, 1000);
            }
        });
        
        if (!isValid) {
            alert('⚠️ لطفا تمام فیلدهای الزامی را پر کنید.');
            return false;
        }
    });
    
    // Add smooth scroll to top on page load
    $(window).on('load', function() {
        $('html, body').animate({ scrollTop: 0 }, 300);
    });

})(jQuery);

