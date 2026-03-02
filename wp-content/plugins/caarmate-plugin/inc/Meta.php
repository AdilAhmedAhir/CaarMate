<?php

declare(strict_types=1);

/**
 * Meta — Registers custom post meta, admin meta boxes, and save routines.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class Meta
{
    /**
     * Nonce action / field names.
     */
    private const RIDE_NONCE_ACTION = 'cm_ride_meta_save';
    private const RIDE_NONCE_FIELD = '_cm_ride_nonce';

    /**
     * Wire all hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('init', [$this, 'registerSchema']);
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_action('save_post_cm_ride', [$this, 'saveRideMeta'], 10, 2);
    }

    // -------------------------------------------------------------------------
    //  Schema Registration (REST-enabled)
    // -------------------------------------------------------------------------

    /**
     * Register post meta for cm_ride and cm_booking.
     *
     * @return void
     */
    public function registerSchema(): void
    {
        $this->registerRideMeta();
        $this->registerBookingMeta();
    }

    /**
     * Register meta fields for the Ride CPT.
     *
     * @return void
     */
    private function registerRideMeta(): void
    {
        $fields = [
            '_cm_departure' => ['type' => 'string', 'sanitize' => 'sanitize_text_field'],
            '_cm_destination' => ['type' => 'string', 'sanitize' => 'sanitize_text_field'],
            '_cm_datetime' => ['type' => 'string', 'sanitize' => 'sanitize_text_field'],
            '_cm_total_seats' => ['type' => 'integer', 'sanitize' => 'absint'],
            '_cm_available_seats' => ['type' => 'integer', 'sanitize' => 'absint'],
            '_cm_price' => ['type' => 'number', 'sanitize' => 'floatval'],
        ];

        foreach ($fields as $key => $config) {
            register_post_meta('cm_ride', $key, [
                'type' => $config['type'],
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => $config['sanitize'],
                'auth_callback' => static function () {
                    return current_user_can('edit_posts');
                },
            ]);
        }
    }

    /**
     * Register meta fields for the Booking CPT.
     *
     * @return void
     */
    private function registerBookingMeta(): void
    {
        $fields = [
            '_cm_ride_id' => ['type' => 'integer', 'sanitize' => 'absint'],
            '_cm_passenger_id' => ['type' => 'integer', 'sanitize' => 'absint'],
            '_cm_status' => ['type' => 'string', 'sanitize' => 'sanitize_text_field'],
            '_cm_seats_booked' => ['type' => 'integer', 'sanitize' => 'absint'],
        ];

        foreach ($fields as $key => $config) {
            register_post_meta('cm_booking', $key, [
                'type' => $config['type'],
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => $config['sanitize'],
                'auth_callback' => static function () {
                    return current_user_can('edit_posts');
                },
            ]);
        }
    }

    // -------------------------------------------------------------------------
    //  Admin Meta Boxes
    // -------------------------------------------------------------------------

    /**
     * Register admin meta boxes for Rides and Bookings.
     *
     * @return void
     */
    public function registerMetaBoxes(): void
    {
        add_meta_box(
            'cm_ride_trip_details',
            __('Trip Details', 'caarmate'),
            [$this, 'renderRideMetaBox'],
            'cm_ride',
            'normal',
            'high'
        );

        add_meta_box(
            'cm_booking_ledger',
            __('Booking Ledger', 'caarmate'),
            [$this, 'renderBookingMetaBox'],
            'cm_booking',
            'normal',
            'high'
        );
    }

    /**
     * Render the Trip Details meta box for Rides.
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function renderRideMetaBox(\WP_Post $post): void
    {
        $departure = get_post_meta($post->ID, '_cm_departure', true);
        $destination = get_post_meta($post->ID, '_cm_destination', true);
        $datetime = get_post_meta($post->ID, '_cm_datetime', true);
        $totalSeats = get_post_meta($post->ID, '_cm_total_seats', true);
        $availableSeats = get_post_meta($post->ID, '_cm_available_seats', true);
        $price = get_post_meta($post->ID, '_cm_price', true);

        wp_nonce_field(self::RIDE_NONCE_ACTION, self::RIDE_NONCE_FIELD);
        ?>
        <style>
            .cm-meta-table {
                width: 100%;
                border-collapse: collapse;
            }

            .cm-meta-table th {
                text-align: left;
                padding: 10px 10px 10px 0;
                width: 160px;
                vertical-align: top;
            }

            .cm-meta-table td {
                padding: 8px 0;
            }

            .cm-meta-table input {
                width: 100%;
                max-width: 400px;
                padding: 6px 8px;
            }
        </style>
        <table class="cm-meta-table">
            <tr>
                <th><label for="cm_departure">
                        <?php esc_html_e('Departure', 'caarmate'); ?>
                    </label></th>
                <td><input type="text" id="cm_departure" name="_cm_departure"
                        value="<?php echo esc_attr((string) $departure); ?>" placeholder="e.g. Dhaka"></td>
            </tr>
            <tr>
                <th><label for="cm_destination">
                        <?php esc_html_e('Destination', 'caarmate'); ?>
                    </label></th>
                <td><input type="text" id="cm_destination" name="_cm_destination"
                        value="<?php echo esc_attr((string) $destination); ?>" placeholder="e.g. Chittagong"></td>
            </tr>
            <tr>
                <th><label for="cm_datetime">
                        <?php esc_html_e('Date & Time', 'caarmate'); ?>
                    </label></th>
                <td><input type="datetime-local" id="cm_datetime" name="_cm_datetime"
                        value="<?php echo esc_attr((string) $datetime); ?>"></td>
            </tr>
            <tr>
                <th><label for="cm_total_seats">
                        <?php esc_html_e('Total Seats', 'caarmate'); ?>
                    </label></th>
                <td><input type="number" id="cm_total_seats" name="_cm_total_seats"
                        value="<?php echo esc_attr((string) $totalSeats); ?>" min="1" max="8" step="1"></td>
            </tr>
            <tr>
                <th><label for="cm_available_seats">
                        <?php esc_html_e('Available Seats', 'caarmate'); ?>
                    </label></th>
                <td><input type="number" id="cm_available_seats" name="_cm_available_seats"
                        value="<?php echo esc_attr((string) $availableSeats); ?>" min="0" max="8" step="1"></td>
            </tr>
            <tr>
                <th><label for="cm_price">
                        <?php esc_html_e('Price per Seat', 'caarmate'); ?>
                    </label></th>
                <td><input type="number" id="cm_price" name="_cm_price" value="<?php echo esc_attr((string) $price); ?>" min="0"
                        step="0.01" placeholder="0.00"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the Booking Ledger meta box (READ-ONLY).
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function renderBookingMetaBox(\WP_Post $post): void
    {
        $rideId = get_post_meta($post->ID, '_cm_ride_id', true);
        $passengerId = get_post_meta($post->ID, '_cm_passenger_id', true);
        $status = get_post_meta($post->ID, '_cm_status', true);
        $seatsBooked = get_post_meta($post->ID, '_cm_seats_booked', true);

        // Resolve display names.
        $rideName = $rideId ? get_the_title((int) $rideId) : '—';
        $passengerUser = $passengerId ? get_userdata((int) $passengerId) : null;
        $passengerName = $passengerUser ? $passengerUser->display_name : '—';

        $statusLabels = [
            'pending' => '🟡 Pending',
            'confirmed' => '🟢 Confirmed',
            'cancelled' => '🔴 Cancelled',
        ];
        $statusDisplay = $statusLabels[$status] ?? ucfirst((string) $status);
        ?>
        <style>
            .cm-ledger-table {
                width: 100%;
                border-collapse: collapse;
            }

            .cm-ledger-table th {
                text-align: left;
                padding: 10px 10px 10px 0;
                width: 160px;
                color: #1d2327;
            }

            .cm-ledger-table td {
                padding: 10px 0;
                font-weight: 500;
            }

            .cm-ledger-notice {
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
                padding: 10px 14px;
                margin-top: 12px;
                font-size: 13px;
            }
        </style>
        <table class="cm-ledger-table">
            <tr>
                <th>
                    <?php esc_html_e('Ride', 'caarmate'); ?>
                </th>
                <td>
                    <?php echo esc_html($rideName); ?>
                    <?php if ($rideId): ?>
                        <a href="<?php echo esc_url(get_edit_post_link((int) $rideId) ?? ''); ?>"
                            style="margin-left: 6px; font-size: 12px;">(
                            <?php esc_html_e('Edit Ride', 'caarmate'); ?>)
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Passenger', 'caarmate'); ?>
                </th>
                <td>
                    <?php echo esc_html($passengerName); ?>
                    <?php if ($passengerId): ?>
                        <span style="color: #888; font-size: 12px;">(ID:
                            <?php echo esc_html((string) $passengerId); ?>)
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Status', 'caarmate'); ?>
                </th>
                <td>
                    <?php echo esc_html($statusDisplay); ?>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Seats Booked', 'caarmate'); ?>
                </th>
                <td>
                    <?php echo esc_html((string) ($seatsBooked ?: '—')); ?>
                </td>
            </tr>
        </table>
        <div class="cm-ledger-notice">
            <?php esc_html_e('This ledger is read-only. Booking data is managed programmatically to protect financial integrity.', 'caarmate'); ?>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    //  Save Logic (Ride only — Bookings are programmatic)
    // -------------------------------------------------------------------------

    /**
     * Save ride meta on post save.
     *
     * @param int      $postId Post ID.
     * @param \WP_Post $post   Post object.
     * @return void
     */
    public function saveRideMeta(int $postId, \WP_Post $post): void
    {
        // Bail on autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verify nonce.
        $nonce = $_POST[self::RIDE_NONCE_FIELD] ?? '';
        if (!wp_verify_nonce((string) $nonce, self::RIDE_NONCE_ACTION)) {
            return;
        }

        // Check permissions.
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        // Sanitize and save.
        $departure = sanitize_text_field(wp_unslash($_POST['_cm_departure'] ?? ''));
        $destination = sanitize_text_field(wp_unslash($_POST['_cm_destination'] ?? ''));
        $datetime = sanitize_text_field(wp_unslash($_POST['_cm_datetime'] ?? ''));
        $totalSeats = absint($_POST['_cm_total_seats'] ?? 0);
        $price = (float) ($_POST['_cm_price'] ?? 0);

        // Available seats: default to total seats on first creation.
        $existingAvailable = get_post_meta($postId, '_cm_available_seats', true);
        if (isset($_POST['_cm_available_seats'])) {
            $availableSeats = absint($_POST['_cm_available_seats']);
        } elseif ($existingAvailable === '' || $existingAvailable === false) {
            $availableSeats = $totalSeats;
        } else {
            $availableSeats = absint($existingAvailable);
        }

        // Clamp available seats to total seats.
        if ($availableSeats > $totalSeats && $totalSeats > 0) {
            $availableSeats = $totalSeats;
        }

        // Ensure price is non-negative.
        $price = max(0.0, $price);

        update_post_meta($postId, '_cm_departure', $departure);
        update_post_meta($postId, '_cm_destination', $destination);
        update_post_meta($postId, '_cm_datetime', $datetime);
        update_post_meta($postId, '_cm_total_seats', $totalSeats);
        update_post_meta($postId, '_cm_available_seats', $availableSeats);
        update_post_meta($postId, '_cm_price', $price);
    }
}
