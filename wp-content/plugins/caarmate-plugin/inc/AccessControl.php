<?php

declare(strict_types=1);

/**
 * AccessControl — Manages dashboard visibility and admin restrictions per role.
 *
 * - Passengers are fully blocked from wp-admin (redirected to /dashboard/).
 * - Drivers see a stripped-down wp-admin (only Rides, Bookings, Profile).
 * - Admins retain full access.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class AccessControl
{
    /**
     * Wire all access-control hooks.
     *
     * @return void
     */
    public function register(): void
    {
        // Redirect passengers away from wp-admin.
        add_action('admin_init', [$this, 'redirectPassengersFromAdmin']);

        // Hide admin bar for passengers on the frontend.
        add_action('after_setup_theme', [$this, 'hideAdminBarForPassengers']);

        // Strip wp-admin menu items for drivers.
        add_action('admin_menu', [$this, 'restrictDriverMenus'], 999);

        // Redirect after login to /dashboard/ for non-admins.
        add_filter('login_redirect', [$this, 'loginRedirect'], 10, 3);
    }

    /**
     * Redirect passengers away from wp-admin to /dashboard/.
     *
     * Allows AJAX requests through so frontend features still work.
     *
     * @return void
     */
    public function redirectPassengersFromAdmin(): void
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        $user = wp_get_current_user();

        if (in_array('cm_passenger', (array) $user->roles, true)) {
            wp_safe_redirect(home_url('/dashboard/'));
            exit;
        }
    }

    /**
     * Hide the admin bar on the frontend for passengers.
     *
     * @return void
     */
    public function hideAdminBarForPassengers(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();

        if (in_array('cm_passenger', (array) $user->roles, true)) {
            show_admin_bar(false);
        }
    }

    /**
     * Restrict wp-admin sidebar menus for drivers.
     *
     * Keeps: Dashboard, Rides, Bookings, Media, Profile.
     * Removes: Posts, Pages, Comments, Appearance, Plugins, Tools, Settings, Users.
     *
     * @return void
     */
    public function restrictDriverMenus(): void
    {
        $user = wp_get_current_user();

        // Only apply to drivers, not admins.
        if (
            !in_array('cm_driver', (array) $user->roles, true)
            || in_array('administrator', (array) $user->roles, true)
        ) {
            return;
        }

        $removeSlugs = [
            'edit.php',                  // Posts
            'edit.php?post_type=page',   // Pages
            'edit-comments.php',         // Comments
            'themes.php',               // Appearance
            'plugins.php',              // Plugins
            'tools.php',                // Tools
            'options-general.php',      // Settings
            'users.php',                // Users
        ];

        foreach ($removeSlugs as $slug) {
            remove_menu_page($slug);
        }
    }

    /**
     * Redirect non-admin users to /dashboard/ after login.
     *
     * @param string   $redirectTo   Default redirect URL.
     * @param string   $requestedRedirect Requested redirect URL.
     * @param \WP_User|\WP_Error $user The user object or error.
     * @return string Modified redirect URL.
     */
    public function loginRedirect($redirectTo, $requestedRedirect, $user): string
    {
        if (is_wp_error($user)) {
            return (string) $redirectTo;
        }

        // If user explicitly requested a specific page (e.g. booking redirect), honour it.
        if (
            !empty($requestedRedirect)
            && $requestedRedirect !== admin_url()
            && strpos($requestedRedirect, 'wp-admin') === false
        ) {
            return (string) $requestedRedirect;
        }

        // Admin stays in wp-admin.
        if (in_array('administrator', (array) $user->roles, true)) {
            return (string) $redirectTo;
        }

        // Everyone else goes to /dashboard/.
        return home_url('/dashboard/');
    }
}
