/**
 * Frontend JavaScript
 *
 * @package OSM1
 */

(function($) {
    'use strict';

    let map;
    let marker;
    let geocoder;
    let selectedLat = null;
    let selectedLng = null;

    $(document).ready(function() {
        // Check if we're on checkout page
        if (!$('body').hasClass('woocommerce-checkout')) {
            return;
        }

        // Inject map for Block-based checkout
        injectMapForBlocks();

        // Initialize map if it already exists (classic checkout)
        if ($('#osm1-checkout-map').length) {
            initializeExistingMap();
        }
    });

    /**
     * Inject map for Block-based checkout
     */
    function injectMapForBlocks() {
        // Check if map already exists
        if ($('#osm1-checkout-map').length) {
            return;
        }

        // Check if API key exists
        if (!osm1Data.apiKey || osm1Data.apiKey === '') {
            return;
        }

        // Try to find shipping block or shipping options
        const shippingSelectors = [
            '.wc-block-components-shipping-rates-control',
            '.wc-block-checkout__shipping-option',
            '.wc-block-components-shipping-address',
            '[data-block-name="woocommerce/checkout-shipping-block"]',
            '.wp-block-woocommerce-checkout-shipping-block',
            '.wc-block-checkout__shipping',
            'form.woocommerce-checkout .woocommerce-shipping-fields',
        ];

        let $targetElement = null;
        let attempts = 0;
        const maxAttempts = 20;

        const findAndInject = function() {
            attempts++;

            // Try to find target element
            for (let i = 0; i < shippingSelectors.length; i++) {
                const $element = $(shippingSelectors[i]);
                if ($element.length && !$element.find('#osm1-checkout-map').length) {
                    $targetElement = $element;
                    break;
                }
            }

            // If not found, try to find checkout form
            if (!$targetElement) {
                $targetElement = $('form.woocommerce-checkout, .wc-block-checkout__form, .wp-block-woocommerce-checkout');
            }

            if ($targetElement && $targetElement.length && !$targetElement.find('#osm1-checkout-map').length) {
                // Get template
                const template = $('#osm1-map-template').html();
                if (template) {
                    // Inject before shipping options or after billing
                    if ($targetElement.find('.wc-block-components-shipping-rates-control').length) {
                        $targetElement.find('.wc-block-components-shipping-rates-control').before(template);
                    } else if ($targetElement.find('.wc-block-checkout__shipping').length) {
                        $targetElement.find('.wc-block-checkout__shipping').after(template);
                    } else {
                        // Try to find a good place to inject
                        const $shippingSection = $targetElement.find('[class*="shipping"], [data-block-name*="shipping"]').first();
                        if ($shippingSection.length) {
                            $shippingSection.after(template);
                        } else {
                            // Last resort: append to form
                            $targetElement.append(template);
                        }
                    }

                    // Wait a bit then initialize map
                    setTimeout(function() {
                        if ($('#osm1-checkout-map').length) {
                            initializeExistingMap();
                        }
                    }, 500);
                }
            } else if (attempts < maxAttempts) {
                // Try again after a delay
                setTimeout(findAndInject, 500);
            }
        };

        // Start looking for target element
        findAndInject();

        // Also use MutationObserver for dynamic content
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function(mutations) {
                if (!$('#osm1-checkout-map').length) {
                    findAndInject();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Stop observing after 15 seconds
            setTimeout(function() {
                observer.disconnect();
            }, 15000);
        }

        // Fallback: Try to inject after a delay (for slow loading pages)
        setTimeout(function() {
            if (!$('#osm1-checkout-map').length && osm1Data.apiKey) {
                // Try one more time
                findAndInject();
            }
        }, 2000);
    }

    /**
     * Initialize existing map (for classic checkout)
     */
    function initializeExistingMap() {
        // Check if API key exists
        if (!osm1Data.apiKey || osm1Data.apiKey === '') {
            $('#osm1-checkout-map').html('<div style="padding: 20px; text-align: center; color: #d63638;"><strong>⚠️</strong> ' + osm1Data.strings.mapError + '</div>');
            return;
        }

        // Wait for Google Maps to load
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            initMap();
            initShippingMethodChange();
        } else {
            // If Google Maps not loaded yet, wait for it
            let checkCount = 0;
            const checkInterval = setInterval(function() {
                checkCount++;
                if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                    clearInterval(checkInterval);
                    initMap();
                    initShippingMethodChange();
                } else if (checkCount > 50) { // Timeout after 5 seconds
                    clearInterval(checkInterval);
                    $('#osm1-checkout-map').html('<div style="padding: 20px; text-align: center; color: #d63638;"><strong>⚠️</strong> ' + osm1Data.strings.mapError + '</div>');
                }
            }, 100);
        }
    }

    function initMap() {
        // Double check Google Maps is loaded
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.error('Google Maps API not loaded');
            $('#osm1-checkout-map').html('<div style="padding: 20px; text-align: center; color: #d63638;"><strong>⚠️</strong> ' + osm1Data.strings.mapError + '</div>');
            return;
        }

        const mapCenter = osm1Data.mapCenter;
        const mapOptions = {
            center: { lat: mapCenter.lat, lng: mapCenter.lng },
            zoom: 12,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            disableDefaultUI: false,
            zoomControl: true,
            mapTypeControl: false,
            scaleControl: true,
            streetViewControl: false,
            rotateControl: false,
            fullscreenControl: true
        };

        try {
            map = new google.maps.Map(document.getElementById('osm1-checkout-map'), mapOptions);
        } catch (error) {
            console.error('Error initializing map:', error);
            $('#osm1-checkout-map').html('<div style="padding: 20px; text-align: center; color: #d63638;"><strong>⚠️</strong> ' + osm1Data.strings.mapError + '</div>');
            return;
        }
        geocoder = new google.maps.Geocoder();

        // Add click listener
        map.addListener('click', function(event) {
            placeMarker(event.latLng);
            updateLocationInfo(event.latLng);
            calculateShippingCost(event.latLng);
        });

        // Try to get user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    map.setCenter(userLocation);
                    placeMarker(userLocation);
                    updateLocationInfo(userLocation);
                },
                function(error) {
                    console.log('Geolocation error:', error);
                }
            );
        }

        // Add search box
        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'جستجوی آدرس...';
        input.className = 'osm1-map-search';
        
        const searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_RIGHT].push(input);

        searchBox.addListener('places_changed', function() {
            const places = searchBox.getPlaces();
            if (places.length === 0) return;

            const place = places[0];
            if (!place.geometry) return;

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
                map.setZoom(17);
            }

            placeMarker(place.geometry.location);
            updateLocationInfo(place.geometry.location);
            calculateShippingCost(place.geometry.location);
        });
    }

    function placeMarker(location) {
        if (marker) {
            marker.setPosition(location);
        } else {
            marker = new google.maps.Marker({
                position: location,
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP
            });

            marker.addListener('dragend', function() {
                updateLocationInfo(marker.getPosition());
                calculateShippingCost(marker.getPosition());
            });
        }

        selectedLat = location.lat();
        selectedLng = location.lng();
        
        $('#osm1-latitude').val(selectedLat);
        $('#osm1-longitude').val(selectedLng);
    }

    function updateLocationInfo(location) {
        geocoder.geocode({ location: location }, function(results, status) {
            if (results[0]) {
                const address = results[0].formatted_address;
                $('#osm1-address').val(address);
                $('#osm1-selected-address').text(address);
                $('#osm1-map-info').show();

                // Extract city
                for (let i = 0; i < results[0].address_components.length; i++) {
                    const component = results[0].address_components[i];
                    if (component.types.includes('locality')) {
                        $('#osm1-city').val(component.long_name);
                        $('#osm1-selected-city').text(component.long_name);
                        break;
                    }
                }
            }
        });
    }

    function calculateShippingCost(location) {
        // Support both classic and block checkout
        let selectedMethod = $('input[name="shipping_method[0]"]:checked').val();
        
        // For block checkout
        if (!selectedMethod) {
            selectedMethod = $('input[name^="shipping_method"]:checked').val();
        }
        
        // For block checkout with different structure
        if (!selectedMethod) {
            selectedMethod = $('.wc-block-components-radio-control__input:checked').closest('label').find('input[type="radio"]').val();
        }
        
        if (!selectedMethod || !selectedMethod.includes('osm1_')) {
            return;
        }

        const lat = location.lat();
        const lng = location.lng();

        // Show loading
        const $shippingRow = $('tr.shipping').first();
        $shippingRow.find('.shipping-cost').html('<span class="osm1-calculating">' + osm1Data.strings.calculating + '</span>');

        $.ajax({
            url: osm1Data.ajaxUrl,
            type: 'POST',
            data: {
                action: 'osm1_calculate_shipping',
                nonce: osm1Data.nonce,
                lat: lat,
                lng: lng,
                method: selectedMethod
            },
            success: function(response) {
                if (response.success) {
                    // Update shipping cost display
                    $shippingRow.find('.shipping-cost').html(response.data.formatted_cost);
                    
                    // Trigger WooCommerce update
                    $('body').trigger('update_checkout');
                } else {
                    $shippingRow.find('.shipping-cost').html('<span class="osm1-error">' + (response.data.message || osm1Data.strings.error) + '</span>');
                }
            },
            error: function() {
                $shippingRow.find('.shipping-cost').html('<span class="osm1-error">' + osm1Data.strings.error + '</span>');
            }
        });
    }

    function initShippingMethodChange() {
        // Support both classic and block checkout
        $(document.body).on('change', 'input[name^="shipping_method"]', function() {
            if (selectedLat && selectedLng) {
                calculateShippingCost({ lat: selectedLat, lng: selectedLng });
            }
        });

        // For block checkout radio buttons
        $(document.body).on('change', '.wc-block-components-radio-control__input', function() {
            if (selectedLat && selectedLng) {
                calculateShippingCost({ lat: selectedLat, lng: selectedLng });
            }
        });

        // For block checkout with event delegation
        $(document.body).on('change', '[name*="shipping"]', function() {
            if (selectedLat && selectedLng) {
                setTimeout(function() {
                    calculateShippingCost({ lat: selectedLat, lng: selectedLng });
                }, 100);
            }
        });
    }

    // Update shipping when cart updates (both classic and block)
    $(document.body).on('updated_checkout updated_checkout_block', function() {
        if (selectedLat && selectedLng) {
            let selectedMethod = $('input[name="shipping_method[0]"]:checked').val();
            if (!selectedMethod) {
                selectedMethod = $('input[name^="shipping_method"]:checked').val();
            }
            if (!selectedMethod) {
                selectedMethod = $('.wc-block-components-radio-control__input:checked').closest('label').find('input[type="radio"]').val();
            }
            if (selectedMethod && selectedMethod.includes('osm1_')) {
                calculateShippingCost({ lat: selectedLat, lng: selectedLng });
            }
        }
    });

})(jQuery);


