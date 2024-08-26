<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://steppay.kr
 * @since      1.0.0
 *
 * @package    Stepcover_Recovery
 * @subpackage Stepcover_Recovery/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Stepcover_Recovery
 * @subpackage Stepcover_Recovery/includes
 * @author     StepPay <dev@steppay.kr>
 */
class Stepcover_Recovery_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'stepcover-recovery',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
