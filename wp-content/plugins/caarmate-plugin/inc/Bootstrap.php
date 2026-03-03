<?php

declare(strict_types=1);

/**
 * Bootstrap — Wires all plugin subsystems into WordPress.
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

class Bootstrap
{
    /**
     * Register all WordPress hooks.
     *
     * @return void
     */
    public function init(): void
    {
        $postTypes = new PostTypes();
        add_action('init', [$postTypes, 'register']);

        $roles = new Roles();
        add_action('after_switch_theme', [$roles, 'register']);

        // Ensure roles exist on every admin load (idempotent).
        if (is_admin()) {
            $roles->register();
        }

        $meta = new Meta();
        $meta->register();

        $shortcodes = new Shortcodes();
        $shortcodes->register();

        $bookingEngine = new BookingEngine();
        $bookingEngine->init();

        $filter = new Filter();
        $filter->register();

        $accessControl = new AccessControl();
        $accessControl->register();

        $auth = new Auth();
        $auth->register();
    }
}
