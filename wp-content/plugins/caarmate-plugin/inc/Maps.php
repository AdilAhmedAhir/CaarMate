<?php

declare(strict_types=1);

/**
 * Maps — Google Maps integration for CaarMate.
 *
 * Enqueues the Google Maps JavaScript API with Places library,
 * initializes autocomplete on departure/destination fields,
 * and provides a [cm_route_map] shortcode.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class Maps
{
    /** @var string Google Maps API key. */
    private const API_KEY = 'AIzaSyAtkGiDUVVNVLPCWCng_ZukrutgXW4S0ws';

    /**
     * Wire hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_shortcode('cm_route_map', [$this, 'renderRouteMap']);
    }

    /**
     * Enqueue Google Maps JS + our init script on relevant pages.
     *
     * @return void
     */
    public function enqueueScripts(): void
    {
        // Load on: homepage (hero search), rides archive (filter sidebar),
        // single ride (route map), and ride creation in admin.
        $shouldLoad = is_front_page()
            || is_post_type_archive('cm_ride')
            || is_singular('cm_ride')
            || is_page(['dashboard', 'login', 'register']);

        if (!$shouldLoad) {
            return;
        }

        // Google Maps JS with Places library.
        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=' . self::API_KEY . '&libraries=places&callback=Function.prototype',
            [],
            null,
            true
        );

        // Our custom init script (inline).
        wp_add_inline_script('google-maps-api', $this->getInlineScript(), 'after');
    }

    /**
     * Inline JS: autocomplete on departure/destination fields + route map init.
     *
     * @return string JavaScript code.
     */
    private function getInlineScript(): string
    {
        return <<<'JS'
(function() {
    'use strict';

    // Wait for Google Maps to load.
    function waitForGoogle(fn) {
        if (window.google && google.maps && google.maps.places) {
            fn();
        } else {
            setTimeout(function() { waitForGoogle(fn); }, 200);
        }
    }

    waitForGoogle(function() {
        // --- Autocomplete on all departure/destination inputs ---
        var depInputs = document.querySelectorAll('input[name="departure"], input[id*="cm-departure"], #cm-ac-departure');
        var destInputs = document.querySelectorAll('input[name="destination"], input[id*="cm-destination"], #cm-ac-destination');

        var acOptions = {
            types: ['(regions)'],
            componentRestrictions: { country: 'bd' },
            fields: ['formatted_address', 'geometry', 'name']
        };

        depInputs.forEach(function(input) {
            new google.maps.places.Autocomplete(input, acOptions);
            // Prevent form submit on Enter while dropdown is open.
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && document.querySelector('.pac-container:not([style*="display: none"])')) {
                    e.preventDefault();
                }
            });
        });

        destInputs.forEach(function(input) {
            new google.maps.places.Autocomplete(input, acOptions);
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && document.querySelector('.pac-container:not([style*="display: none"])')) {
                    e.preventDefault();
                }
            });
        });

        // --- Route Map (on single ride page) ---
        var mapEl = document.getElementById('cm-route-map');
        if (mapEl) {
            var dep = mapEl.dataset.departure;
            var dest = mapEl.dataset.destination;

            if (dep && dest) {
                var map = new google.maps.Map(mapEl, {
                    zoom: 8,
                    center: { lat: 23.8103, lng: 90.4125 }, // Dhaka center default
                    disableDefaultUI: true,
                    zoomControl: true,
                    styles: [
                        { featureType: 'water', stylers: [{ color: '#e0f2f1' }] },
                        { featureType: 'landscape', stylers: [{ color: '#f5f5f5' }] },
                        { featureType: 'road.highway', stylers: [{ color: '#dadada' }] },
                        { featureType: 'poi', stylers: [{ visibility: 'off' }] }
                    ]
                });

                var directionsService = new google.maps.DirectionsService();
                var directionsRenderer = new google.maps.DirectionsRenderer({
                    map: map,
                    suppressMarkers: false,
                    polylineOptions: {
                        strokeColor: '#2c7a7b',
                        strokeWeight: 4,
                        strokeOpacity: 0.85
                    }
                });

                directionsService.route({
                    origin: dep + ', Bangladesh',
                    destination: dest + ', Bangladesh',
                    travelMode: 'DRIVING'
                }, function(result, status) {
                    if (status === 'OK') {
                        directionsRenderer.setDirections(result);

                        // Show distance & duration.
                        var leg = result.routes[0].legs[0];
                        var infoEl = document.getElementById('cm-route-info');
                        if (infoEl && leg) {
                            infoEl.innerHTML =
                                '<span class="cm-route-stat">🛣️ ' + leg.distance.text + '</span>' +
                                '<span class="cm-route-stat">⏱️ ' + leg.duration.text + '</span>';
                        }
                    }
                });
            }
        }
    });
})();
JS;
    }

    /**
     * [cm_route_map] — Render a Google Maps route between departure and destination.
     *
     * @param array|string $atts Shortcode attributes.
     * @return string HTML container with data attributes.
     */
    public function renderRouteMap($atts = []): string
    {
        $postId = get_the_ID();
        if (!$postId || get_post_type($postId) !== 'cm_ride') {
            return '';
        }

        $departure = esc_attr(get_post_meta($postId, '_cm_departure', true));
        $destination = esc_attr(get_post_meta($postId, '_cm_destination', true));

        if (empty($departure) || empty($destination)) {
            return '';
        }

        return '<div class="cm-route-map-wrapper">'
            . '<h3 class="cm-section-title">' . esc_html__('Route Map', 'caarmate') . '</h3>'
            . '<div id="cm-route-map" class="cm-route-map"'
            . ' data-departure="' . $departure . '"'
            . ' data-destination="' . $destination . '"'
            . '></div>'
            . '<div id="cm-route-info" class="cm-route-info"></div>'
            . '</div>';
    }
}
