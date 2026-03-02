<?php

declare(strict_types=1);

/**
 * Roles — Registers custom WordPress roles for the CaarMate platform.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class Roles
{
    /**
     * Register custom roles (idempotent — safe to call multiple times).
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerDriver();
        $this->registerPassenger();
    }

    /**
     * Register the Driver role.
     *
     * Baseline: author-level capabilities plus custom ride management.
     *
     * @return void
     */
    private function registerDriver(): void
    {
        if (wp_roles()->is_role('cm_driver')) {
            return;
        }

        add_role('cm_driver', __('Driver', 'caarmate'), [
            // Core WordPress.
            'read' => true,
            'upload_files' => true,
            'edit_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_published_posts' => true,

            // CaarMate — Rides.
            'edit_cm_rides' => true,
            'publish_cm_rides' => true,
            'delete_cm_rides' => true,
        ]);
    }

    /**
     * Register the Passenger role.
     *
     * Baseline: subscriber-level capabilities plus booking creation.
     *
     * @return void
     */
    private function registerPassenger(): void
    {
        if (wp_roles()->is_role('cm_passenger')) {
            return;
        }

        add_role('cm_passenger', __('Passenger', 'caarmate'), [
            // Core WordPress.
            'read' => true,

            // CaarMate — Bookings.
            'create_cm_bookings' => true,
            'read_cm_bookings' => true,
        ]);
    }
}
