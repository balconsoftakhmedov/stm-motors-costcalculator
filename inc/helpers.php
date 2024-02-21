<?php

add_filter( 'stm_ew_locate_template', function ( $located, $templates ) {

	$plugin_path = MOTORS_ELEMENTOR_CUSTOMS_PATH;
	$locat       = false;

	foreach ( (array) $templates as $template ) {
		if ( substr( $template, - 4 ) !== '.php' ) {
			$template .= '.php';
		}
		$locat = $plugin_path . '/templates/' . $template;

		if ( file_exists( $locat ) ) {

			$located = $locat;

			break;
		}
	}

	return $located;
}, 10, 2 );
function stm_customs_enqueue_scripts() {
	$v = time();
	wp_enqueue_style( 'stm-style-customs', MOTORS_ELEMENTOR_CUSTOMS_URL . '/assets/css/style.css', array(), time() );
}

function stm_customs_enqueue_on_plugins_loaded() {
	add_action( 'wp_enqueue_scripts', 'stm_customs_enqueue_scripts', 999 );

}

function stm_listings_attributes_child( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'where'  => array(),
			'key_by' => '',
		)
	);

	$result = array();
	$data   = array_filter( (array) get_option( 'stm_commercial_options' ) );

	foreach ( $data as $key => $_data ) {
		$passed = true;
		foreach ( $args['where'] as $_field => $_val ) {
			if ( array_key_exists( $_field, $_data ) && boolval( $_data[ $_field ] ) !== boolval( $_val ) ) {
				$passed = false;
				break;
			}
		}

		if ( $passed ) {
			if ( $args['key_by'] ) {
				$result[ $_data[ $args['key_by'] ] ] = $_data;
			} else {
				$result[] = $_data;
			}
		}
	}

	return apply_filters( 'stm_commercial_listings_attributes', $result, $args );
}
function stm_passenger_attributes( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'where'  => array(),
			'key_by' => '',
		)
	);

	$result = array();
	$data   = array_filter( (array) get_option( 'stm_passenger_options' ) );

	foreach ( $data as $key => $_data ) {
		$passed = true;
		foreach ( $args['where'] as $_field => $_val ) {
			if ( array_key_exists( $_field, $_data ) && boolval( $_data[ $_field ] ) !== boolval( $_val ) ) {
				$passed = false;
				break;
			}
		}

		if ( $passed ) {
			if ( $args['key_by'] ) {
				$result[ $_data[ $args['key_by'] ] ] = $_data;
			} else {
				$result[] = $_data;
			}
		}
	}

	return apply_filters( 'stm_passenger_listings_attributes', $result, $args );
}
if ( ! function_exists( 'stm_get_listing_archive_link_child' ) ) {
	/**
	 * Get inventory URL.
	 */
	function stm_get_listing_archive_link_child( $filters = array() ) {
		$listing_link = stm_listings_user_defined_filter_page();

		if ( ! $listing_link ) {

			$options = get_option( 'stm_post_types_options' );

			$default_type = array(
				'listings' => array(
					'title'        => __( 'Listings', 'stm_vehicles_listing' ),
					'plural_title' => __( 'Listings', 'stm_vehicles_listing' ),
					'rewrite'      => 'listings',
				),
			);

			$stm_vehicle_options = wp_parse_args( $options, $default_type );

			$listing_link = site_url() . '/commercial-for-sale-page/';
		} else {
			$listing_link = get_permalink( $listing_link );
		}
$listing_link = site_url() . '/commercial-inventory/';
		$qs = array();
		foreach ( $filters as $key => $val ) {
			$info = stm_get_all_by_slug( preg_replace( '/^(min_|max_)/', '', $key ) );
			$val  = ( is_array( $val ) ) ? implode( ',', $val ) : $val;
			$qs[] = $key . ( ! empty( $info['listing_rows_numbers'] ) ? '[]=' : '=' ) . $val;
		}

		if ( count( $qs ) ) {
			$listing_link .= ( strpos( $listing_link, '?' ) ? '&' : '?' ) . join( '&', $qs );
		}

		return $listing_link;
	}
}

if ( ! function_exists( 'stm_get_passenger_archive_link_child' ) ) {
	/**
	 * Get inventory URL.
	 */
	function stm_get_passenger_archive_link_child( $filters = array() ) {
		$listing_link = stm_listings_user_defined_filter_page();

		if ( ! $listing_link ) {

			$options = get_option( 'stm_post_types_options' );

			$default_type = array(
				'listings' => array(
					'title'        => __( 'Listings', 'stm_vehicles_listing' ),
					'plural_title' => __( 'Listings', 'stm_vehicles_listing' ),
					'rewrite'      => 'listings',
				),
			);

			$stm_vehicle_options = wp_parse_args( $options, $default_type );

			$listing_link = site_url() . '/commercial-inventory/';
		} else {
			$listing_link = get_permalink( $listing_link );
		}
$listing_link = site_url() . '/passenger-inventory/';
		$qs = array();
		foreach ( $filters as $key => $val ) {
			$info = stm_get_all_by_slug( preg_replace( '/^(min_|max_)/', '', $key ) );
			$val  = ( is_array( $val ) ) ? implode( ',', $val ) : $val;
			$qs[] = $key . ( ! empty( $info['listing_rows_numbers'] ) ? '[]=' : '=' ) . $val;
		}

		if ( count( $qs ) ) {
			$listing_link .= ( strpos( $listing_link, '?' ) ? '&' : '?' ) . join( '&', $qs );
		}

		return $listing_link;
	}
}

