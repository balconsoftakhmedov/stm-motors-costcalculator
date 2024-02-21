<?php

namespace cBuilder\Classes;

use cBuilder\Classes\Database\Orders as OrdersModel;
use cBuilder\Classes\Database\Payments as PaymentModel;

class CCBWooCheckout {

	public static $paymentMethod = 'woocommerce';

	public static function init() {
		check_ajax_referer( 'ccb_woo_checkout', 'nonce' );

		$params = array();
		$result = array(
			'status'  => false,
			'message' => __( 'Something went wrong', 'cost-calculator-builder-pro' ),
		);

		if ( is_string( $_POST['data'] ) ) {
			$params = json_decode( stripslashes( $_POST['data'] ), true );
		}

		$data = isset( $params['woo_info'] ) ? (array) $params['woo_info'] : array();

		if ( isset( $data['enable'] ) ) {
			if ( isset( $data['redirect_to'] ) && 'cart' === $data['redirect_to'] ) {
				$result['page'] = get_permalink( get_option( 'woocommerce_cart_page_id' ) );
			} elseif ( isset( $data['redirect_to'] ) && 'checkout' === $data['redirect_to'] ) {
				$result['page'] = get_permalink( get_option( 'woocommerce_checkout_page_id' ) );
			} else {
				$product = wc_get_product( $data['product_id'] );
				if ( ! empty( $product ) ) {
					$result['page']         = 'stayOnPage';
					$result['product_name'] = $product->get_name();
				}
			}

			$uid    = ! empty( self::get_calc_data_uid() ) ? self::get_calc_data_uid() : 'calc_cart_' . time();
			$params = self::add_files( $params, $uid );

			self::render( $params, $uid );
			self::add_to_cart( $params, $uid );

			$result['success'] = true;
			$result['message'] = __( 'success', 'cost-calculator-builder-pro' );
		}

		wp_send_json( $result );
	}

	/** check uploaded files based on settings ( file upload field ) */
	protected static function validateFile( $file, $fieldId, $calcId ) {
		if ( empty( $file ) ) {
			return false;
		}

		$calcFields = get_post_meta( $calcId, 'stm-fields', true );

		/** get file field settings */
		$fileFieldIndex = array_search( $fieldId, array_column( $calcFields, 'alias' ), true );

		$extension      = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$allowedFormats = array();
		foreach ( $calcFields[ $fileFieldIndex ]['fileFormats'] as $format ) {
			$allowedFormats = array_merge( $allowedFormats, explode( '/', $format ) );
		}

		/** check file extension */
		if ( ! in_array( $extension, $allowedFormats, true ) ) {
			return false;
		}

		/** check file size */
		if ( $calcFields[ $fileFieldIndex ]['max_file_size'] < round( $file['size'] / 1024 / 1024, 1 ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $params - calc fields data
	 * @param $uid
	 *
	 * @return $params with uploaded fileinfo if file fields exist
	 */
	protected static function add_files( $params, $uid ) {
		/** upload files if exist */
		if ( ! is_array( $_FILES ) ) {
			return $params;
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' ); // phpcs:ignore
		}

		$fileUrls     = array();
		$orderDetails = $params['descriptions'];

		/** upload all files, create array for fields */
		foreach ( $_FILES as $fileKey => $file ) {
			$fieldId    = preg_replace( '/_ccb_.*/', '', $fileKey );
			$fieldIndex = array_search( $fieldId, array_column( $orderDetails, 'alias' ), true );

			/** if field not found continue */
			if ( false === $fieldIndex ) {
				continue;
			}

			/** validate file by settings */
			$isValid = self::validateFile( $file, $fieldId, $params['calcId'] );
			if ( ! $isValid ) {
				continue;
			}

			if ( ! array_key_exists( $fieldId, $fileUrls ) ) {
				$fileUrls[ $fieldId ] = array();
			}

			$file_info = wp_handle_upload(
				$file,
				array(
					'test_form' => false,
				)
			);

			if ( $file_info && empty( $file_info['error'] ) ) {
				array_push( $fileUrls[ $fieldId ], $file_info );
			}
		}

		foreach ( $orderDetails as $key => $field ) {
			if ( preg_replace( '/_field_id.*/', '', $field['alias'] ) === 'file_upload' && isset( $fileUrls[ $field['alias'] ] ) ) {
				$orderDetails[ $key ]['options'] = json_encode( $fileUrls[ $field['alias'] ] ); // phpcs:ignore
			}
		}

		$params['descriptions'] = $orderDetails;
		return $params;
	}

	public static function get_calc_data_uid() {
		return isset( $_COOKIE['calc_data_uid'] ) ? $_COOKIE['calc_data_uid'] : null;
	}

	public static function get_calc_data( $uid = false ) {
		$transient = ! empty( $uid ) ? $uid : self::get_calc_data_uid();
		return get_transient( $transient ) ? get_transient( $transient ) : array();
	}

	public static function calc_delete_calc_data( $product_id = null ) {
		$uid = self::get_calc_data_uid();
		if ( $uid ) {
			if ( $product_id ) {
				$data = self::get_calc_data();
				unset( $data[ $product_id ] );
				set_transient( $uid, $data, 12 * HOUR_IN_SECONDS );
			} else {
				delete_transient( $uid );
			}
		}
	}

	public static function render( $params, $uid ) {
		if ( ! empty( $params['woo_info'] ) ) {
			$params['woo_info'] = (array) $params['woo_info'];
			$product_id         = $params['woo_info']['product_id'];

			if ( $params['woo_info']['replace_product'] ) {
				self::clearCart( $product_id );
			}

			$data                = self::get_calc_data();
			$data[ $product_id ] = self::set_total_info( $params['calcTotals'], $params );

			setcookie( 'calc_data_uid', $uid, time() + 12 * HOUR_IN_SECONDS, '/', COOKIE_DOMAIN );
			set_transient( $uid, $data, 12 * HOUR_IN_SECONDS );
		}
	}

	private static function get_woo_product_quantity_link_aliases( $calc_id ) {
		$result = array();
		if ( ! $calc_id ) {
			return $result;
		}

		$calc_settings = get_option( 'stm_ccb_form_settings_' . $calc_id );
		if ( ! $calc_settings ) {
			return $result;
		}

		$woo_products = $calc_settings['woo_products'];
		if ( false === $woo_products['enable'] ) {
			return $result;
		}

		if ( array_key_exists( 'meta_links', $woo_products )
			&& count( $woo_products['meta_links'] ) > 0 ) {
			foreach ( $woo_products['meta_links'] as $meta_link ) {
				if ( 'quantity' !== $meta_link['woo_meta'] ) {
					continue;
				}
				array_push( $result, $meta_link['calc_field'] );
			}
		}
		return $result;
	}

	public static function add_to_cart( $params, $uid ) {
		$data = self::get_calc_data( $uid );

		$woo_meta_link_total_quantity_data = array(
			'is_set' => false,
			'total'  => 0,
		);

		foreach ( $data as $calc_data ) {
			$woo_quantity_link_aliases = self::get_woo_product_quantity_link_aliases( $calc_data['calcId'] );

			$product_id = isset( $calc_data['woo_info']['product_id'] ) ? $calc_data['woo_info']['product_id'] : null;
			if ( $product_id && intval( $product_id ) === intval( $params['woo_info']['product_id'] ) ) {
				$meta = array(
					'product_id' => $product_id,
					'item_name'  => isset( $calc_data['item_name'] ) ? $calc_data['item_name'] : '',
					'order_id'   => $params['orderId'],
				);

				if ( ! empty( $calc_data['descriptions'] ) && is_array( $calc_data['descriptions'] ) ) {
					foreach ( $calc_data['descriptions'] as $calc_item ) {

						/** add data for meta link = "quantity" */
						if ( in_array( $calc_item['alias'], $woo_quantity_link_aliases, true ) ) {
							$woo_meta_link_total_quantity_data['is_set'] = true;
							$woo_meta_link_total_quantity_data['total'] += $calc_item['value'];
						}

						if ( true === $calc_item['hidden'] ) {
							continue;
						}

						if ( str_contains( $calc_item['alias'], 'file_upload_field_id_' ) || str_contains( $calc_item['alias'], 'datePicker_field_id_' ) ) {
							if ( str_contains( $calc_item['alias'], 'file_upload_field_id_' ) && ! $calc_item['allowPrice'] ) {
								$val = '';
							} else {
								$val = $calc_item['converted'] ? ' (' . $calc_item['converted'] . ') ' . $calc_item['value'] : $calc_item['value'];
							}
						} else {
							$labels = isset( $calc_item['extra'] ) ? $calc_item['extra'] : '';
							if ( ( strpos( $calc_item['alias'], 'radio_field_id_' ) !== false || strpos( $calc_item['alias'], 'dropDown_field_id_' ) !== false ) && key_exists( 'options', $calc_item ) ) {
								$labels = self::getLabels( $calc_item['options'] );
							}

							if ( strpos( $calc_item['alias'], 'multi_range_field_id_' ) !== false && key_exists( 'options', $calc_item ) && count( $calc_item['options'] ) > 0 ) {
								$labels = key_exists( 'label', $calc_item['options'][0] ) ? $calc_item['options'][0]['label'] : '';
							}

							$val = isset( $labels ) ? $labels . ' ' . $calc_item['converted'] : $calc_item['converted'];
						}

						/** append file info */
						if ( 'file_upload' === preg_replace( '/_field_id.*/', '', $calc_item['alias'] ) && ! empty( $calc_item['options'] ) ) {
							$fileLinks = '';
							if ( 'string' === gettype( $calc_item['options'] ) ) {
								$fileOptions = json_decode( $calc_item['options'] );
								if ( is_string( $fileOptions ) ) {
									$fileOptions = json_decode( $fileOptions );
								}

								foreach ( $fileOptions as $fieldFile ) {
									if ( reset( $fileOptions ) !== $fieldFile ) {
										$fileLinks .= '<br/>';
									}
									$fileLinks .= '<a target="_blank" href="' . $fieldFile->url . '">' . basename( $fieldFile->file ) . '</a>';
									if ( end( $fileOptions ) !== $fieldFile ) {
										$fileLinks .= ', ';
									}
								}

								if ( '' !== $fileLinks ) {
									$val .= ' <br/>( ' . $fileLinks . ' )';
								}
							}
						}

						$meta['calc_data'][ $calc_item['alias'] ] = array(
							'label' => $calc_item['label'],
							'value' => $val,
						);
					}

					/** set woo meta link for stock connected data */
					if ( true === $woo_meta_link_total_quantity_data['is_set'] ) {
						$meta['ccb_woo_meta_link_quantity_data'] = $woo_meta_link_total_quantity_data;
					}
				}

				/** add totals data */
				if ( ! empty( $calc_data['ccb_total_and_label'] ) && is_array( $calc_data['ccb_total_and_label'] ) ) {
					$meta['ccb_total'] = self::inner_calc_total( $calc_data['ccb_total_and_label'] );
				} elseif ( isset( $calc_data['calcTotals'] ) ) {
					$meta['ccb_total'] = self::inner_calc_total( $calc_data['calcTotals'] );
				}
				$meta['calc_data'][] = array(
							'label' => 'subtotal',
							'value' => $params['subtotalContent'],
				);

				WC()->cart->add_to_cart(
					$product_id,
					1,
					'',
					array(),
					array(
						'ccb_calculator' => $meta,
					)
				);
			}
		}
	}

	/**
	 * Add woocommerce data to ccb order/payment
	 * @param $item_id - wc order item id
	 * @param $item wc order object
	 * @param $item_order_id wc order id ( post_id )
	 */
	public static function calc_add_wc_order( $item_id, $item, $item_order_id ) {
		$legacy_values = isset( $item->legacy_values ) && is_array( $item->legacy_values ) ? $item->legacy_values : array();

		if ( empty( $legacy_values['ccb_calculator'] ) ) {
			return;
		}

		$ccb_calculator = $item->legacy_values['ccb_calculator'];

		if ( function_exists( 'wc_get_order' ) ) {
			$order         = wc_get_order( $item_order_id );
			$billing_email = $order->get_billing_email();
			$billing_name  = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$billing_phone = $order->get_billing_phone();

			$ccb_calculator['ccb_billing'] = array(
				'form'   => 'Default Contact Form',
				'fields' => array(
					array(
						'name'  => 'name',
						'value' => $billing_name,
					),
					array(
						'name'  => 'email',
						'value' => $billing_email,
					),
					array(
						'name'  => 'phone',
						'value' => $billing_phone,
					),
				),
			);
		}

		/** add to cost calculator orders ( payment type ) just if send form is enabled  */
		self::addToCCBOrder( $ccb_calculator, $item_order_id );
	}

	private static function addToCCBOrder( $ccb_calculator, $wcOrderId ) {
		if ( ! array_key_exists( 'order_id', $ccb_calculator ) || empty( $ccb_calculator['order_id'] ) || ! $ccb_calculator['order_id'] ) {
			return;
		}

		/** set payment method to order */
		$order = OrdersModel::get( 'id', $ccb_calculator['order_id'] );

		/** if order id exist, but order not found return error */
		if ( null === $order ) {
			return;
		}

		$payment     = PaymentModel::get( 'order_id', $ccb_calculator['order_id'] );
		$paymentData = array(
			'type'        => self::$paymentMethod,
			'transaction' => $wcOrderId,
		);

		/** if no payment , create */
		if ( null !== $payment ) {
			$paymentData['total'] = $ccb_calculator['ccb_total'];
			PaymentModel::create_new_payment( $paymentData, $ccb_calculator['order_id'] );
		}

		$paymentData['updated_at'] = wp_date( 'Y-m-d H:i:s' );

		PaymentModel::update(
			$paymentData,
			array(
				'order_id' => $ccb_calculator['order_id'],
			)
		);

		OrdersModel::update_order(
			array(
				'payment_method' => self::$paymentMethod,
				'form_details'   => wp_json_encode( $ccb_calculator['ccb_billing'] ),
			),
			$ccb_calculator['order_id']
		);
	}

	public static function calc_add_item_meta( $item, $cart_item_value, $values, $order ) {
		if ( isset( $values['ccb_calculator'] ) ) {
			$item->add_meta_data( 'ccb_calculator', $values['ccb_calculator'] );
		}
	}

	protected static function getLabels( $options ) {
		if ( empty( $options[0]['temp'] ) ) {
			return false;
		}

		if ( version_compare( phpversion(), '7.4', '>=' ) ) {
			return ' (' . implode( ',', array_column( $options, 'label' ) ) . ') ';
		} else {
			return ' (' . implode( array_column( $options, 'label' ), ',' ) . ') ';
		}
	}

	public static function calc_get_item_data( $data_meta, $value ) {

		$str = '';
		if ( ! empty( $value['ccb_calculator']['calc_data'] ) && array_key_exists( 'ccb_calculator', $value ) ) {
			foreach ( $value['ccb_calculator']['calc_data'] as $field ) {
				if ( isset( $field['label'] ) && isset( $field['value'] ) ) {
					$str .= sanitize_text_field( $field['label'] ) . ' ' . $field['value'] . PHP_EOL;
				}
			}
		}


//$str .= sanitize_text_field( $field['label'] ) . ' ' . $field['value'] . PHP_EOL;
		if ( ! empty( $str ) ) {
			$data_meta[] = array(
				'name'  => sanitize_text_field( $value['ccb_calculator']['item_name'] ),
				'value' => $str,
			);
		}

		return $data_meta;
	}

	public static function calc_check_cart_items() {
		foreach ( wc()->cart->get_cart() as $key => $value ) {
			if ( isset( $wc_item['ccb_calculator'] ) ) {
				wc()->cart->set_quantity( $key, 1, false );
			}
		}
	}

	public static function inner_calc_total( $totals ) {
		$result = 0;
		foreach ( $totals as $total ) {
			if ( isset( $total['total'] ) ) {
				$result += floatval( $total['total'] );
			} elseif ( isset( $total['value'] ) ) {
				$result += floatval( $total['value'] );
			}
		}
		return $result;
	}

	public static function calc_total( $items ) {
		$data = self::get_calc_data();
		if ( ! empty( $data ) ) {
			foreach ( $data as $calc_id => $calc_data ) {
				foreach ( $items->cart_contents as $value ) {
					if ( ! empty( $value['ccb_calculator']['product_id'] ) && intval( $value['ccb_calculator']['product_id'] ) === $calc_id ) {
						$total = self::inner_calc_total( $calc_data['ccb_total_and_label'] );
						$value['data']->set_price( $calc_data['woo_info']['replace_product'] ? $total : $value['ccb_calculator']['ccb_total'] );
					}
				}
			}
		} else {
			$cartData = WC()->cart->get_cart();

			foreach ( $items->cart_contents as $key => $value ) {
				if ( ! empty( $value['ccb_calculator']['product_id'] ) && array_key_exists( $key, $cartData ) ) {
					$value['data']->set_price( $cartData[ $key ]['ccb_calculator']['ccb_total'] );
				}
			}
		}
	}

	public static function calc_order_item_meta( $item_id ) {
		$data = wc_get_order_item_meta( $item_id, 'ccb_calculator' );
		if ( isset( $data['product_id'] ) && isset( $data['calc_data'] ) ) {
			foreach ( $data['calc_data'] as $field ) {
				if ( isset( $field['label'] ) && isset( $field['value'] ) ) {
					echo '<p class="item"><span>' . $field['label'] . '</span> <span class="woocommerce-Price-amount amount">' . nl2br( $field['value'] ) . '</span></p>'; // phpcs:ignore
				}
			}
		}

		self::calc_delete_calc_data();
	}

	public static function calc_remove_cart_item( $removed_cart_item_key, $cart ) {
		$product_id = ( ! empty( $cart->removed_cart_contents[ $removed_cart_item_key ]['product_id'] ) ) ?
			$cart->removed_cart_contents[ $removed_cart_item_key ]['product_id'] :
			null;

		if ( $product_id ) {
			self::calc_delete_calc_data( $product_id );
		}
	}

	public static function set_total_info( $totals, $params ) {
		$params['ccb_total_and_label'] = array();

		if ( is_array( $totals ) && count( $totals ) > 0 ) {
			foreach ( $totals as $total ) {
				if ( isset( $params['woo_info']['formulas'] ) ) {
					foreach ( $params['woo_info']['formulas'] as $formula ) {
						if ( isset( $formula['alias'] ) && $formula['alias'] === $total['alias'] ) {
							if ( isset( $total['total'] ) ) {
								$params['ccb_total_and_label'][] = array(
									'total' => $total['total'],
									'label' => $total['label'],
								);
							} elseif ( isset( $total['value'] ) ) {
								$params['ccb_total_and_label'][] = array(
									'total' => $total['value'],
									'label' => $total['label'],
								);
							}
						} elseif ( 1 === count( $params['woo_info']['formulas'] ) && ! isset( $formula['alias'] ) ) {
							$params['ccb_total_and_label'][] = array(
								'total' => $total['total'],
								'label' => $total['label'],
							);
						}
					}
				}
			}
		}

		return $params;
	}

	public static function clearCart( $product_id ) {
		foreach ( WC()->cart->get_cart() as $cart_key => $cart_value ) {
			if ( intval( $cart_value['product_id'] ) === intval( $product_id ) ) {
				WC()->cart->remove_cart_item( $cart_key );
			}
		}
	}
}
