<?php
/**
 * Plugin Name: STM Costcalculator Custom
 * Plugin URI:  https://stylemixthemes.com/
 * Description: STM WordPress Csutom Plugin
 * Version:     1.0.0
 * Author:      rusty
 * Author URI:  https://stylemixthemes.com/
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stm-motors-costcalculator
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

define( 'STM_ELEMENTOR_CUSTOMS_PATH', dirname( __FILE__ ) );
define( 'STM_ELEMENTOR_CUSTOMS_URL', plugins_url( '', __FILE__ ) );
include_once 'inc/loader.php';

