<?php
/**
 * Plugin Name: AI Markdown Mirror
 * Plugin URI: https://troymaya.com/
 * Description: Creates AI-readable Markdown mirrors, /llms.txt, /llms-full.txt, and hidden discovery links in the HTML head.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Troy Ochowicz
 * Author URI: https://troymaya.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-markdown-mirror
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AMM_VERSION', '0.1.0');
define('AMM_PLUGIN_FILE', __FILE__);
define('AMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AMM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once AMM_PLUGIN_DIR . 'includes/class-settings.php';
require_once AMM_PLUGIN_DIR . 'includes/class-markdown.php';
require_once AMM_PLUGIN_DIR . 'includes/class-routes.php';
require_once AMM_PLUGIN_DIR . 'includes/class-metaboxes.php';
require_once AMM_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook(__FILE__, array('AMM_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('AMM_Plugin', 'deactivate'));

add_action('plugins_loaded', array('AMM_Plugin', 'instance'));
