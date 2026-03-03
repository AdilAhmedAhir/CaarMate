<?php

declare(strict_types=1);

/**
 * Shortcodes — Front-end data-binding shortcodes for CaarMate CPTs.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class Shortcodes
{
    /**
     * Allowed keys for the [cm_ride_meta] shortcode.
     *
     * Maps user-facing key → actual post meta key.
     */
    private const ALLOWED_KEYS = [
        'departure' => '_cm_departure',
        'destination' => '_cm_destination',
        'datetime' => '_cm_datetime',
        'total_seats' => '_cm_total_seats',
        'available_seats' => '_cm_available_seats',
        'price' => '_cm_price',
    ];

    /**
     * Register all shortcodes.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('cm_ride_meta', [$this, 'renderRideMeta']);
        add_shortcode('cm_book_cta', [$this, 'renderBookCta']);
        add_shortcode('cm_ride_search_widget', [$this, 'renderSearchWidget']);
        add_shortcode('cm_ride_filter_sidebar', [$this, 'renderFilterSidebar']);
    }

    // -------------------------------------------------------------------------
    //  [cm_ride_search_widget]
    // -------------------------------------------------------------------------

    /**
     * Render the ride search form widget.
     *
     * Outputs a semantic GET form targeting the /rides/ archive.
     *
     * @param array|string $atts Shortcode attributes (unused, reserved).
     * @return string Escaped HTML form.
     */
    public function renderSearchWidget($atts = []): string
    {
        $actionUrl = esc_url(home_url('/rides/'));

        return '<form method="get" action="' . $actionUrl . '" class="cm-search-widget">'
            . '<input type="text" name="departure" class="cm-minimal-input"'
            . ' placeholder="' . esc_attr__('Leaving from...', 'caarmate') . '" required>'
            . '<input type="text" name="destination" class="cm-minimal-input"'
            . ' placeholder="' . esc_attr__('Going to...', 'caarmate') . '" required>'
            . '<button type="submit" class="cm-btn-stark">'
            . esc_html__('Search Rides', 'caarmate')
            . '</button>'
            . '<p class="cm-driver-link">'
            . '<a href="' . esc_url(admin_url('post-new.php?post_type=cm_ride')) . '">'
            . esc_html__('Or offer a seat instead', 'caarmate') . ' &rarr;</a>'
            . '</p>'
            . '</form>';
    }

    // -------------------------------------------------------------------------
    //  [cm_ride_meta key="..."]
    // -------------------------------------------------------------------------

    /**
     * Render a single cm_ride meta value.
     *
     * Usage: [cm_ride_meta key="departure"]
     *
     * @param array|string $atts Shortcode attributes.
     * @return string Escaped HTML output, or empty string on failure.
     */
    public function renderRideMeta($atts): string
    {
        $atts = shortcode_atts(['key' => ''], $atts, 'cm_ride_meta');

        $key = sanitize_text_field($atts['key']);

        // Bail if key is not in the allow-list.
        if ($key === '' || !isset(self::ALLOWED_KEYS[$key])) {
            return '';
        }

        // Bail if we are not inside a cm_ride post.
        $postId = get_the_ID();
        if (!$postId || get_post_type($postId) !== 'cm_ride') {
            return '';
        }

        $metaKey = self::ALLOWED_KEYS[$key];
        $value = get_post_meta($postId, $metaKey, true);

        if ($value === '' || $value === false) {
            return '<span class="cm-meta-empty">—</span>';
        }

        return $this->formatMetaValue($key, $value);
    }

    /**
     * Apply display formatting per meta key.
     *
     * @param string $key   The user-facing key name.
     * @param mixed  $value The raw meta value.
     * @return string Escaped, formatted HTML.
     */
    private function formatMetaValue(string $key, $value): string
    {
        switch ($key) {
            case 'price':
                $formatted = '$' . number_format((float) $value, 2);
                return esc_html($formatted);

            case 'datetime':
                $timestamp = strtotime((string) $value);
                if ($timestamp === false) {
                    return esc_html((string) $value);
                }
                return esc_html(date_i18n('F j, Y \a\t g:i A', $timestamp));

            case 'total_seats':
            case 'available_seats':
                return esc_html((string) absint($value));

            default:
                return esc_html((string) $value);
        }
    }

    // -------------------------------------------------------------------------
    //  [cm_book_cta]
    // -------------------------------------------------------------------------

    /**
     * Render the booking call-to-action.
     *
     * - Not logged in → link to login page.
     * - Logged in as the ride author (driver) → ownership notice.
     * - Logged in as anyone else → booking form.
     *
     * @param array|string $atts Shortcode attributes (unused, reserved for future).
     * @return string Escaped HTML output.
     */
    public function renderBookCta($atts = []): string
    {
        // Bail if we are not inside a cm_ride post.
        $postId = get_the_ID();
        if (!$postId || get_post_type($postId) !== 'cm_ride') {
            return '';
        }

        // --- Guest: prompt login ---
        if (!is_user_logged_in()) {
            $loginUrl = esc_url(wp_login_url(get_permalink($postId)));

            return '<div class="cm-cta-container">'
                . '<a href="' . $loginUrl . '" class="cm-btn cm-btn-primary">'
                . esc_html__('Log in to Book', 'caarmate')
                . '</a>'
                . '<p class="cm-cta-hint">'
                . esc_html__('You need an account to reserve a seat.', 'caarmate')
                . '</p>'
                . '</div>';
        }

        // --- Driver: cannot book own ride ---
        $post = get_post($postId);
        if ($post && (int) $post->post_author === get_current_user_id()) {
            return '<div class="cm-cta-container">'
                . '<p class="cm-cta-notice">'
                . esc_html__('This is your ride. You cannot book your own trip.', 'caarmate')
                . '</p>'
                . '</div>';
        }

        // --- Passenger: booking form ---
        $nonceField = wp_nonce_field('cm_book_ride', '_cm_book_nonce', true, false);
        $rideId = absint($postId);
        $actionUrl = esc_url(get_permalink($postId));

        return '<div class="cm-cta-container">'
            . '<form method="post" action="' . $actionUrl . '" class="cm-booking-form">'
            . $nonceField
            . '<input type="hidden" name="cm_ride_id" value="' . esc_attr((string) $rideId) . '">'
            . '<button type="submit" class="cm-btn cm-btn-primary">'
            . esc_html__('Book Seat', 'caarmate')
            . '</button>'
            . '</form>'
            . '</div>';
    }

    // -------------------------------------------------------------------------
    //  [cm_ride_filter_sidebar]
    // -------------------------------------------------------------------------

    /**
     * Render the sidebar filter form for the ride archive page.
     *
     * Pre-fills inputs with current GET parameters so users
     * can see and refine their search.
     *
     * @param array|string $atts Shortcode attributes (unused).
     * @return string Escaped HTML form.
     */
    public function renderFilterSidebar($atts = []): string
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET search form, no state change.
        $departure = isset($_GET['departure'])
            ? sanitize_text_field(wp_unslash($_GET['departure'])) : '';
        $destination = isset($_GET['destination'])
            ? sanitize_text_field(wp_unslash($_GET['destination'])) : '';
        $date = isset($_GET['date'])
            ? sanitize_text_field(wp_unslash($_GET['date'])) : '';
        // phpcs:enable

        $actionUrl = esc_url(home_url('/rides/'));

        return '<form method="get" action="' . $actionUrl . '" class="cm-sidebar-form">'
            . '<div class="cm-sidebar-field">'
            . '<label class="cm-sidebar-label" for="cm-departure">'
            . esc_html__('From', 'caarmate') . '</label>'
            . '<input type="text" id="cm-departure" name="departure"'
            . ' class="cm-sidebar-input"'
            . ' placeholder="' . esc_attr__('City or town...', 'caarmate') . '"'
            . ' value="' . esc_attr($departure) . '">'
            . '</div>'
            . '<div class="cm-sidebar-field">'
            . '<label class="cm-sidebar-label" for="cm-destination">'
            . esc_html__('To', 'caarmate') . '</label>'
            . '<input type="text" id="cm-destination" name="destination"'
            . ' class="cm-sidebar-input"'
            . ' placeholder="' . esc_attr__('Where to?', 'caarmate') . '"'
            . ' value="' . esc_attr($destination) . '">'
            . '</div>'
            . '<div class="cm-sidebar-field">'
            . '<label class="cm-sidebar-label" for="cm-date">'
            . esc_html__('Date', 'caarmate') . '</label>'
            . '<input type="date" id="cm-date" name="date"'
            . ' class="cm-sidebar-input"'
            . ' value="' . esc_attr($date) . '">'
            . '</div>'
            . '<button type="submit" class="cm-sidebar-btn">'
            . esc_html__('Update Search', 'caarmate')
            . '</button>'
            . '</form>';
    }
}
