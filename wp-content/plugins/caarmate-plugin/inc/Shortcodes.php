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
        add_shortcode('cm_dashboard', [$this, 'renderDashboard']);
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

    // -------------------------------------------------------------------------
    //  [cm_dashboard]
    // -------------------------------------------------------------------------

    /**
     * Render the role-based user dashboard.
     *
     * - Guest → login prompt.
     * - Driver (cm_driver / administrator) → table of own rides.
     * - Passenger (cm_passenger / default) → ticket cards of bookings.
     *
     * @param array|string $atts Shortcode attributes (unused).
     * @return string Escaped HTML.
     */
    public function renderDashboard($atts = []): string
    {
        // --- Gate: require login ---
        if (!is_user_logged_in()) {
            $loginUrl = esc_url(wp_login_url(home_url('/dashboard/')));

            return '<div class="cm-no-results">'
                . '<h3>' . esc_html__('Please log in', 'caarmate') . '</h3>'
                . '<p>' . esc_html__('You need an account to view your dashboard.', 'caarmate') . '</p>'
                . '<a href="' . $loginUrl . '" class="cm-sidebar-btn" style="display:inline-block;margin-top:20px;padding:14px 32px">'
                . esc_html__('Log In', 'caarmate')
                . '</a>'
                . '</div>';
        }

        $user = wp_get_current_user();
        $displayName = esc_html($user->display_name);
        $isDriver = in_array('cm_driver', (array) $user->roles, true)
            || in_array('administrator', (array) $user->roles, true);

        // --- Header ---
        $roleBadge = $isDriver
            ? esc_html__('Driver', 'caarmate')
            : esc_html__('Passenger', 'caarmate');

        $html = '<div class="cm-dashboard-header">'
            . '<p class="cm-dash-role">' . $roleBadge . '</p>'
            . '<h2 class="cm-dash-welcome">'
            . sprintf(esc_html__('Welcome back, %s', 'caarmate'), $displayName)
            . '</h2>'
            . '</div>';

        // --- Role-specific content ---
        if ($isDriver) {
            $html .= $this->renderDriverView($user);
        } else {
            $html .= $this->renderPassengerView($user);
        }

        return $html;
    }

    /**
     * Render the Driver's ride management table.
     *
     * @param \WP_User $user Current user.
     * @return string Escaped HTML table.
     */
    private function renderDriverView(\WP_User $user): string
    {
        $rides = get_posts([
            'post_type' => 'cm_ride',
            'author' => $user->ID,
            'numberposts' => -1,
            'post_status' => 'publish',
        ]);

        if (empty($rides)) {
            $newRideUrl = esc_url(admin_url('post-new.php?post_type=cm_ride'));

            return '<div class="cm-no-results">'
                . '<h3>' . esc_html__('No rides yet', 'caarmate') . '</h3>'
                . '<p>' . esc_html__('You haven\'t published any rides. Create your first one!', 'caarmate') . '</p>'
                . '<a href="' . $newRideUrl . '" class="cm-sidebar-btn" style="display:inline-block;margin-top:20px;padding:14px 32px">'
                . esc_html__('Create Ride', 'caarmate')
                . '</a>'
                . '</div>';
        }

        $html = '<div class="cm-dash-table-wrapper">'
            . '<table class="cm-dash-table">'
            . '<thead><tr>'
            . '<th>' . esc_html__('Route', 'caarmate') . '</th>'
            . '<th>' . esc_html__('Date', 'caarmate') . '</th>'
            . '<th>' . esc_html__('Price', 'caarmate') . '</th>'
            . '<th>' . esc_html__('Seats', 'caarmate') . '</th>'
            . '<th>' . esc_html__('Actions', 'caarmate') . '</th>'
            . '</tr></thead><tbody>';

        foreach ($rides as $ride) {
            $dep = esc_html(get_post_meta($ride->ID, '_cm_departure', true));
            $dest = esc_html(get_post_meta($ride->ID, '_cm_destination', true));
            $dt = get_post_meta($ride->ID, '_cm_datetime', true);
            $price = '$' . number_format((float) get_post_meta($ride->ID, '_cm_price', true), 2);
            $avail = absint(get_post_meta($ride->ID, '_cm_available_seats', true));
            $total = absint(get_post_meta($ride->ID, '_cm_total_seats', true));
            $editUrl = esc_url(get_edit_post_link($ride->ID) ?: '');
            $viewUrl = esc_url(get_permalink($ride->ID));

            $dateFormatted = '';
            $ts = strtotime((string) $dt);
            if ($ts !== false) {
                $dateFormatted = esc_html(date_i18n('M j, Y', $ts));
            }

            $html .= '<tr>'
                . '<td><strong>' . $dep . '</strong> <span class="cm-route-arrow">➝</span> <strong>' . $dest . '</strong></td>'
                . '<td>' . $dateFormatted . '</td>'
                . '<td class="cm-price-tag" style="font-size:1rem">' . esc_html($price) . '</td>'
                . '<td>' . $avail . ' / ' . $total . '</td>'
                . '<td>'
                . '<a href="' . $viewUrl . '" class="cm-text-link" style="margin-right:16px">'
                . esc_html__('View', 'caarmate') . '</a>'
                . '<a href="' . $editUrl . '" class="cm-text-link">'
                . esc_html__('Edit', 'caarmate') . '</a>'
                . '</td>'
                . '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Render the Passenger's booking cards.
     *
     * @param \WP_User $user Current user.
     * @return string Escaped HTML ticket cards.
     */
    private function renderPassengerView(\WP_User $user): string
    {
        $bookings = get_posts([
            'post_type' => 'cm_booking',
            'numberposts' => -1,
            'post_status' => 'any',
            'meta_key' => '_cm_passenger_id',
            'meta_value' => $user->ID,
        ]);

        if (empty($bookings)) {
            $searchUrl = esc_url(home_url('/rides/'));

            return '<div class="cm-no-results">'
                . '<h3>' . esc_html__('No bookings yet', 'caarmate') . '</h3>'
                . '<p>' . esc_html__('You haven\'t booked any rides. Find your first journey!', 'caarmate') . '</p>'
                . '<a href="' . $searchUrl . '" class="cm-sidebar-btn" style="display:inline-block;margin-top:20px;padding:14px 32px">'
                . esc_html__('Search Rides', 'caarmate')
                . '</a>'
                . '</div>';
        }

        $html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px">';

        foreach ($bookings as $booking) {
            $rideId = absint(get_post_meta($booking->ID, '_cm_ride_id', true));
            if (!$rideId) {
                continue;
            }

            $dep = esc_html(get_post_meta($rideId, '_cm_departure', true));
            $dest = esc_html(get_post_meta($rideId, '_cm_destination', true));
            $dt = get_post_meta($rideId, '_cm_datetime', true);
            $price = '$' . number_format((float) get_post_meta($rideId, '_cm_price', true), 2);
            $viewUrl = esc_url(get_permalink($rideId));

            $dateFormatted = '—';
            $ts = strtotime((string) $dt);
            if ($ts !== false) {
                $dateFormatted = esc_html(date_i18n('F j, Y \a\t g:i A', $ts));
            }

            $html .= '<div class="cm-ticket-card">'
                . '<div class="cm-ticket-body">'
                . '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">'
                . '<div class="cm-time-badge">' . $dateFormatted . '</div>'
                . '<span class="cm-status-badge cm-status-success">'
                . esc_html__('Confirmed', 'caarmate') . '</span>'
                . '</div>'
                . '<div class="cm-route-row">'
                . '<span class="cm-city-text">' . $dep . '</span>'
                . '<span class="cm-route-arrow">➝</span>'
                . '<span class="cm-city-text">' . $dest . '</span>'
                . '</div>'
                . '</div>'
                . '<div class="cm-ticket-divider"></div>'
                . '<div class="cm-ticket-footer">'
                . '<div class="cm-price-tag">' . esc_html($price) . '</div>'
                . '<a href="' . $viewUrl . '" class="cm-view-btn">'
                . esc_html__('View Ride', 'caarmate') . '</a>'
                . '</div>'
                . '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
