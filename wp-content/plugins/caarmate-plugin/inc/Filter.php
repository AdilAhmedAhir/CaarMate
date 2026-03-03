<?php

declare(strict_types=1);

/**
 * Filter — Intercepts the main WP_Query on the Ride archive
 * and applies meta_query filters from URL parameters.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class Filter
{
    /**
     * Hook into pre_get_posts.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('pre_get_posts', [$this, 'filterMainQuery']);
    }

    /**
     * Filter the main archive query for cm_ride based on GET parameters.
     *
     * Supported parameters:
     *   - departure   (string, LIKE match against _cm_departure)
     *   - destination (string, LIKE match against _cm_destination)
     *   - date        (string, >= comparison against _cm_datetime)
     *
     * @param \WP_Query $query The current query object.
     * @return void
     */
    public function filterMainQuery(\WP_Query $query): void
    {
        // Gate 1: Only run on the front-end main query.
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // Gate 2: Only run on the cm_ride archive or taxonomy.
        if (!is_post_type_archive('cm_ride') && !$query->is_tax('cm_ride_category')) {
            return;
        }

        $meta_query = ['relation' => 'AND'];
        $has_filter = false;

        // --- Departure filter ---
        if (!empty($_GET['departure'])) {
            $departure = sanitize_text_field(wp_unslash($_GET['departure']));
            $meta_query[] = [
                'key' => '_cm_departure',
                'value' => $departure,
                'compare' => 'LIKE',
            ];
            $has_filter = true;
        }

        // --- Destination filter ---
        if (!empty($_GET['destination'])) {
            $destination = sanitize_text_field(wp_unslash($_GET['destination']));
            $meta_query[] = [
                'key' => '_cm_destination',
                'value' => $destination,
                'compare' => 'LIKE',
            ];
            $has_filter = true;
        }

        // --- Date filter (show rides on or after this date) ---
        if (!empty($_GET['date'])) {
            $date = sanitize_text_field(wp_unslash($_GET['date']));
            $meta_query[] = [
                'key' => '_cm_datetime',
                'value' => $date,
                'compare' => '>=',
                'type' => 'CHAR',
            ];
            $has_filter = true;
        }

        if ($has_filter) {
            $query->set('meta_query', $meta_query);
        }
    }
}
