<?php

declare(strict_types=1);

/**
 * Plugin Name:       CaarMate
 * Plugin URI:        https://caarmate.com
 * Description:       Core functionality plugin for the CaarMate platform.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            CaarMate Team
 * Author URI:        https://caarmate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       caarmate
 * Domain Path:       /languages
 *
 * @package CaarMate\Core
 */

namespace CaarMate\Core;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('CAARMATE_VERSION', '0.1.0');
define('CAARMATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAARMATE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load internal classes.
require_once CAARMATE_PLUGIN_DIR . 'inc/Bootstrap.php';
require_once CAARMATE_PLUGIN_DIR . 'inc/Roles.php';
require_once CAARMATE_PLUGIN_DIR . 'inc/PostTypes.php';
require_once CAARMATE_PLUGIN_DIR . 'inc/Meta.php';
require_once CAARMATE_PLUGIN_DIR . 'inc/Shortcodes.php';

/**
 * Bootstrap the plugin.
 *
 * @return void
 */
function bootstrap(): void
{
    $app = new Bootstrap();
    $app->init();
}

add_action('plugins_loaded', __NAMESPACE__ . '\\bootstrap');

/**
 * Run on plugin activation.
 *
 * Registers roles and flushes rewrite rules so CPT slugs work immediately.
 *
 * @return void
 */
function activate(): void
{
    $roles = new Roles();
    $roles->register();

    $postTypes = new PostTypes();
    $postTypes->register();

    flush_rewrite_rules();
}

register_activation_hook(__FILE__, __NAMESPACE__ . '\\activate');
