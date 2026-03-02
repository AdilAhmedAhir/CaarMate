<?php

declare(strict_types=1);

/**
 * CaarMate Canvas – Theme Functions
 *
 * This file acts as a clean wrapper for asset enqueuing only.
 * All business logic lives in the CaarMate plugin.
 *
 * @package CaarMate\Canvas
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue theme styles.
 *
 * @return void
 */
function caarmate_canvas_enqueue_styles(): void
{
    wp_enqueue_style(
        'caarmate-canvas-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );

    wp_enqueue_style(
        'caarmate-canvas-main',
        get_template_directory_uri() . '/assets/css/main.css',
        ['caarmate-canvas-style'],
        (string) filemtime(get_template_directory() . '/assets/css/main.css')
    );
}

add_action('wp_enqueue_scripts', 'caarmate_canvas_enqueue_styles');

/**
 * Register theme support.
 *
 * @return void
 */
function caarmate_canvas_setup(): void
{
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('responsive-embeds');
}

add_action('after_setup_theme', 'caarmate_canvas_setup');
