<?php

declare(strict_types=1);

/**
 * PostTypes — Registers custom post types for the CaarMate platform.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class PostTypes
{
    /**
     * Register all custom post types.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerRide();
        $this->registerBooking();
    }

    /**
     * Register the Ride CPT.
     *
     * Public, REST-enabled, supports Block Editor.
     *
     * @return void
     */
    private function registerRide(): void
    {
        $labels = [
            'name' => __('Rides', 'caarmate'),
            'singular_name' => __('Ride', 'caarmate'),
            'add_new' => __('Add New Ride', 'caarmate'),
            'add_new_item' => __('Add New Ride', 'caarmate'),
            'edit_item' => __('Edit Ride', 'caarmate'),
            'new_item' => __('New Ride', 'caarmate'),
            'view_item' => __('View Ride', 'caarmate'),
            'search_items' => __('Search Rides', 'caarmate'),
            'not_found' => __('No rides found', 'caarmate'),
            'not_found_in_trash' => __('No rides found in Trash', 'caarmate'),
            'all_items' => __('All Rides', 'caarmate'),
            'menu_name' => __('Rides', 'caarmate'),
        ];

        register_post_type('cm_ride', [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'rest_base' => 'rides',
            'menu_icon' => 'dashicons-car',
            'menu_position' => 5,
            'has_archive' => true,
            'rewrite' => ['slug' => 'rides'],
            'supports' => ['title', 'author', 'custom-fields'],
            'capability_type' => 'post',
        ]);
    }

    /**
     * Register the Booking CPT.
     *
     * Private ledger — not publicly queryable, but REST-enabled for headless access.
     *
     * @return void
     */
    private function registerBooking(): void
    {
        $labels = [
            'name' => __('Bookings', 'caarmate'),
            'singular_name' => __('Booking', 'caarmate'),
            'add_new' => __('Add New Booking', 'caarmate'),
            'add_new_item' => __('Add New Booking', 'caarmate'),
            'edit_item' => __('Edit Booking', 'caarmate'),
            'new_item' => __('New Booking', 'caarmate'),
            'view_item' => __('View Booking', 'caarmate'),
            'search_items' => __('Search Bookings', 'caarmate'),
            'not_found' => __('No bookings found', 'caarmate'),
            'not_found_in_trash' => __('No bookings found in Trash', 'caarmate'),
            'all_items' => __('All Bookings', 'caarmate'),
            'menu_name' => __('Bookings', 'caarmate'),
        ];

        register_post_type('cm_booking', [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'rest_base' => 'bookings',
            'menu_icon' => 'dashicons-clipboard',
            'menu_position' => 6,
            'has_archive' => false,
            'supports' => ['title', 'author', 'custom-fields'],
            'capability_type' => 'post',
        ]);
    }
}
