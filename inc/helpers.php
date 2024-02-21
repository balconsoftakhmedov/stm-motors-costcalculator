<?php

use cBuilder\Helpers\CCBFieldsHelper;

function stm_customs_enqueue_scripts() {
	$v = time();
	wp_enqueue_style( 'stm-style-customs', STM_ELEMENTOR_CUSTOMS_URL . '/assets/css/style.css', array(), time() );
}

function stm_customs_enqueue_on_plugins_loaded() {
	add_action( 'wp_enqueue_scripts', 'stm_customs_enqueue_scripts', 999 );
	if ( ! is_admin() || ! empty( $_GET['page'] ) && 'cost_calculator_builder' === $_GET['action'] ) {  // phpcs:ignore WordPress.Security.NonceVerification
			wp_enqueue_script( 'calc-builder-main-js', STM_ELEMENTOR_CUSTOMS_URL . '/assets/bundle.js', array( 'cbb-sticky-sidebar-js' ), time(), true );
	}
}

add_action( 'init', 'stm_customs_enqueue_on_plugins_loaded', 9999 );


if ( ! is_admin() || ! empty( $_GET['page'] ) && 'cost_calculator_builder' === $_GET['action'] ) {  // phpcs:ignore WordPress.Security.NonceVerification
		wp_enqueue_script( 'calc-builder-main-js', STM_ELEMENTOR_CUSTOMS_URL . '/assets/js/bundle.js', array( 'cbb-sticky-sidebar-js' ), time(), true );
}

function custom_plugin_stylesheet_directory( $stylesheet_dir, $stylesheet, $theme_root ) {
	$stylesheet_dir = STM_ELEMENTOR_CUSTOMS_PATH;
	return $stylesheet_dir;
}

add_filter( 'stylesheet_directory', 'custom_plugin_stylesheet_directory', 1, 3 );