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
        $actionUrl = esc_url(admin_url('admin-post.php'));

        return '<div class="cm-cta-container">'
            . '<form method="post" action="' . $actionUrl . '" class="cm-booking-form">'
            . $nonceField
            . '<input type="hidden" name="action" value="cm_book_ride">'
            . '<input type="hidden" name="ride_id" value="' . esc_attr((string) $rideId) . '">'
            . '<button type="submit" class="cm-btn cm-btn-primary">'
            . esc_html__('Book Seat', 'caarmate')
            . '</button>'
            . '</form>'
            . '</div>';
    }
}
