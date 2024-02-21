<?php

function stm_customs_enqueue_scripts() {
	$v = time();
	wp_enqueue_style( 'stm-style-customs', STM_ELEMENTOR_CUSTOMS_URL . '/assets/css/style.css', array(), time() );
}

function stm_customs_enqueue_on_plugins_loaded() {
	add_action( 'wp_enqueue_scripts', 'stm_customs_enqueue_scripts', 999 );

}

add_action( 'init', 'stm_customs_enqueue_on_plugins_loaded', 9999 );