<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://steppay.kr
 * @since             1.0.0
 * @package           Stepcover_Recovery
 *
 * @wordpress-plugin
 * Plugin Name:       스텝커버 복구 플러그인
 * Plugin URI:        https://stepcover.kr/recovery
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            StepPay
 * Author URI:        https://steppay.kr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       stepcover-recovery
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'STEPCOVER_RECOVERY_VERSION', '1.0.0' );

define('STEPCOVER_API_BASE_URL', 'https://api.steppay.kr/api/v1/cover/');
//define('STEPCOVER_API_BASE_URL', 'https://api.develop.steppay.kr/api/v1/cover/');
//define('STEPCOVER_API_BASE_URL', 'http://localhost:8080/api/v1/cover/');
define('STEPCOVER_RECOVERY_PAGE_URL', 'https://cover.steppay.kr/#/');
//define('STEPCOVER_RECOVERY_PAGE_URL', 'https://cover.develop.steppay.kr/#/');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-stepcover-recovery-activator.php
 */
function activate_stepcover_recovery() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-stepcover-recovery-activator.php';
	Stepcover_Recovery_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-stepcover-recovery-deactivator.php
 */
function deactivate_stepcover_recovery() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-stepcover-recovery-deactivator.php';
	Stepcover_Recovery_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_stepcover_recovery' );
register_deactivation_hook( __FILE__, 'deactivate_stepcover_recovery' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-stepcover-recovery.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_stepcover_recovery() {
    require __DIR__ . '/vendor/autoload.php';

	$plugin = new Stepcover_Recovery();
	$plugin->run();

}
run_stepcover_recovery();

