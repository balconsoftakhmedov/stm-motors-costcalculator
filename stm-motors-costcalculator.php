<?php
/**
 * Plugin Name: STM Motors Custom Fleetdirect
 * Plugin URI:  https://stylemixthemes.com/
 * Description: STM Motors Extends WordPress Plugin
 * Version:     3.0.0
 * Author:      StylemixThemes
 * Author URI:  https://stylemixthemes.com/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stm_motors_custom
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

define( 'MOTORS_ELEMENTOR_CUSTOMS_PATH', dirname( __FILE__ ) );
define( 'MOTORS_ELEMENTOR_CUSTOMS_URL', plugins_url( '', __FILE__ ) );
include_once 'inc/loader.php';


function register_custom_image_categories_widget() {
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Motors_E_W\Widgets\CustomImageCategories() );
	\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Motors_E_W\Widgets\PassengerCustomImageCategories() );

}

function custom_image_categories_plugin_loaded() {
	if ( class_exists( 'Motors_E_W\MotorsApp' ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'inc/Widgets/CustomImageCategories.php';
		require_once plugin_dir_path( __FILE__ ) . 'inc/Widgets/PassengerCustomImageCategories.php';
		add_action( 'elementor/widgets/widgets_registered', 'register_custom_image_categories_widget' );
	}
}

add_action( 'init', 'custom_image_categories_plugin_loaded', 9999 );


