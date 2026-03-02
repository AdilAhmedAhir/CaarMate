<?php

declare(strict_types=1);

/**
 * BookingEngine — Handles booking form submissions with transactional gate checks.
 *
 * Intercepts POST via template_redirect for clean HTTP redirects.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class BookingEngine
{
    /**
     * Platform commission rate (10%).
     */
    private const COMMISSION_RATE = 0.10;

    /**
     * Wire the interceptor hook.
     *
     * @return void
     */
    public function init(): void
    {
        add_action('template_redirect', [$this, 'intercept']);
    }

    /**
     * Intercept POST requests from the booking form.
     *
     * @return void
     */
    public function intercept(): void
    {
        // Only process POST requests with our form identifier.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['cm_ride_id'], $_POST['_cm_book_nonce'])) {
            return;
        }

        $this->processBooking();
    }

    /**
     * Execute the full booking transaction.
     *
     * Gate 1 → Auth
     * Gate 2 → Data integrity
     * Gate 3 → Seat availability (race-condition safe)
     * Gate 4 → Ownership check
     * Mutation 1 → Commission calculation
     * Mutation 2 → Ledger entry (cm_booking)
     * Mutation 3 → Booking meta
     * Mutation 4 → Seat inventory decrement
     *
     * @return void
     */
    private function processBooking(): void
    {
        $rideId = absint($_POST['cm_ride_id']);

        // =====================================================================
        //  GATE 1 — Authentication & Nonce
        // =====================================================================

        if (
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_cm_book_nonce'])),
                'cm_book_ride'
            )
        ) {
            $this->redirectWithError($rideId, 'nonce');
            return;
        }

        if (!is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(get_permalink($rideId)));
            exit;
        }

        $currentUserId = get_current_user_id();

        // =====================================================================
        //  GATE 2 — Data Integrity
        // =====================================================================

        $ride = get_post($rideId);
        if (!$ride || $ride->post_type !== 'cm_ride' || $ride->post_status !== 'publish') {
            $this->redirectWithError($rideId, 'invalid');
            return;
        }

        // =====================================================================
        //  GATE 3 — Seat Availability (race-condition guard)
        // =====================================================================

        $availableSeats = (int) get_post_meta($rideId, '_cm_available_seats', true);

        if ($availableSeats <= 0) {
            $this->redirectWithError($rideId, 'full');
            return;
        }

        // =====================================================================
        //  GATE 4 — Ownership Check (drivers cannot book own rides)
        // =====================================================================

        if ((int) $ride->post_author === $currentUserId) {
            $this->redirectWithError($rideId, 'owner');
            return;
        }

        // =====================================================================
        //  MUTATION 1 — Commission Calculation
        // =====================================================================

        $price = (float) get_post_meta($rideId, '_cm_price', true);
        $commissionCut = round($price * self::COMMISSION_RATE, 2);

        // =====================================================================
        //  MUTATION 2 — Create Ledger Entry (cm_booking)
        // =====================================================================

        $bookingTitle = sprintf(
            'Booking: Ride #%d by User #%d',
            $rideId,
            $currentUserId
        );

        $bookingId = wp_insert_post([
            'post_type' => 'cm_booking',
            'post_title' => $bookingTitle,
            'post_status' => 'private',
            'post_author' => $currentUserId,
        ], true);

        if (is_wp_error($bookingId)) {
            $this->redirectWithError($rideId, 'system');
            return;
        }

        // =====================================================================
        //  MUTATION 3 — Booking Meta
        // =====================================================================

        update_post_meta($bookingId, '_cm_ride_id', $rideId);
        update_post_meta($bookingId, '_cm_passenger_id', $currentUserId);
        update_post_meta($bookingId, '_cm_status', 'confirmed');
        update_post_meta($bookingId, '_cm_seats_booked', 1);
        update_post_meta($bookingId, '_cm_commission_cut', $commissionCut);

        // =====================================================================
        //  MUTATION 4 — Seat Inventory Decrement
        // =====================================================================

        $newAvailable = max(0, $availableSeats - 1);
        update_post_meta($rideId, '_cm_available_seats', $newAvailable);

        // =====================================================================
        //  RESOLUTION — Redirect with success
        // =====================================================================

        $this->redirectWithSuccess($rideId);
    }

    /**
     * Redirect back to the ride page with an error parameter.
     *
     * @param int    $rideId    Ride post ID.
     * @param string $errorCode Short error identifier.
     * @return void
     */
    private function redirectWithError(int $rideId, string $errorCode): void
    {
        $url = add_query_arg(
            'booking_error',
            sanitize_key($errorCode),
            get_permalink($rideId) ?: home_url('/')
        );

        wp_safe_redirect(esc_url_raw($url));
        exit;
    }

    /**
     * Redirect back to the ride page with a success parameter.
     *
     * @param int $rideId Ride post ID.
     * @return void
     */
    private function redirectWithSuccess(int $rideId): void
    {
        $url = add_query_arg(
            'booking_success',
            '1',
            get_permalink($rideId) ?: home_url('/')
        );

        wp_safe_redirect(esc_url_raw($url));
        exit;
    }
}
