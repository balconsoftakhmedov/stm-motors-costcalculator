<?php
// TODO mv all logic to controller
use cBuilder\Classes\Appearance\CCBAppearanceHelper;

if ( ! isset( $calc_id ) ) {
	return;
}

/** if language not set, use en as default */
if ( ! isset( $language ) ) {
	$language = 'en';
}

if ( ! isset( $translations ) ) {
	$translations = array();
}

$container_style = 'v-container';

if ( ! isset( $settings ) ) {
	$settings = get_option( 'stm_ccb_form_settings_' . $calc_id );
}

if ( ! isset( $general_settings ) ) {
	$general_settings = get_option( 'ccb_general_settings' );
}

$ccb_sync         = ccb_sync_settings_from_general_settings( $settings, $general_settings, true );
$settings         = $ccb_sync['settings'];
$general_settings = $ccb_sync['general_settings'];

if ( ! empty( $settings ) && isset( $settings[0] ) && isset( $settings[0]['general'] ) ) {
	$settings = $settings[0];
}

if ( empty( $settings['general'] ) ) {
	$settings = \cBuilder\Classes\CCBSettingsData::settings_data();
}

$settings['calc_id'] = $calc_id;
$settings['title']   = get_post_meta( $calc_id, 'stm-name', true );

if ( ! empty( $general_settings['payment_gateway']['cards']['use_in_all'] ) && ! empty( $general_settings['payment_gateway']['cards']['card_payments']['razorpay']['enable'] ) ) {
	$settings['payment_gateway']['cards']['card_payments']['razorpay']['keyId']     = $general_settings['payment_gateway']['cards']['card_payments']['razorpay']['keyId'];
	$settings['payment_gateway']['cards']['card_payments']['razorpay']['secretKey'] = $general_settings['payment_gateway']['cards']['card_payments']['razorpay']['secretKey'];
}

if ( ! empty( $general_settings['payment_gateway']['cards']['use_in_all'] ) && ! empty( $general_settings['payment_gateway']['cards']['card_payments']['stripe']['enable'] ) ) {
	$settings['payment_gateway']['cards']['card_payments']['stripe']['publishKey'] = $general_settings['payment_gateway']['cards']['card_payments']['stripe']['publishKey'];
	$settings['payment_gateway']['cards']['card_payments']['stripe']['secretKey']  = $general_settings['payment_gateway']['cards']['card_payments']['stripe']['secretKey'];
}

if ( ! empty( $settings['formFields']['body'] ) ) {
	$settings['formFields']['body'] = str_replace( '<br>', PHP_EOL, $settings['formFields']['body'] );
}

if ( ! empty( $settings['thankYouPage']['page_id'] ) ) {
	$page_id = $settings['thankYouPage']['page_id'];
	$page    = get_post( $page_id );

	$pos = strpos( $page->post_content, 'stm-thank-you-page' );
	if ( false === $pos ) {
		$updated_page = array(
			'ID'           => $page_id,
			'post_content' => $page->post_content . '[stm-thank-you-page id="' . $calc_id . '"]',
		);

		wp_update_post( $updated_page );
	}


	$settings['thankYouPage']['page_url'] = get_permalink( $settings['thankYouPage']['page_id'] );
}

if ( ! empty( $settings['sendFormFields'] ) && ! empty( $settings['formFields'] ) && ! empty( $settings['sendFormRequires'] ) && ! empty( $settings['texts']['form_fields'] ) ) {
	$settings['sendFormFields']       = apply_filters( 'ccb_contact_form_add_sendform_fields', $settings['sendFormFields'] );
	$settings['sendFormRequires']     = apply_filters( 'ccb_contact_form_add_requires', $settings['sendFormRequires'] );
	$settings['texts']['form_fields'] = apply_filters( 'ccb_contact_form_add_text_form_fields', $settings['texts']['form_fields'] );
}

if ( ! empty( $settings['formFields']['submitBtnText'] ) ) {
	$settings['formFields']['submitBtnText'] = apply_filters( 'ccb_contact_form_submit_label', $settings['formFields']['submitBtnText'], $calc_id );
}
$settings['thankYouPage'] = apply_filters( 'ccb_customize_confirmation_page', $settings['thankYouPage'], $calc_id );
$preset_key               = get_post_meta( $calc_id, 'ccb_calc_preset_idx', true );
$preset_key               = empty( $preset_key ) ? 0 : $preset_key;
$appearance               = CCBAppearanceHelper::get_appearance_data( $preset_key );
$loader_idx               = 0;

if ( ! empty( $appearance ) ) {
	$appearance = $appearance['data'];

	if ( isset( $appearance['desktop']['others']['data']['calc_preloader']['value'] ) ) {
		$loader_idx = $appearance['desktop']['others']['data']['calc_preloader']['value'];
	}
}

$fields = get_post_meta( $calc_id, 'stm-fields', true ) ?? array();
if ( ! empty( $fields ) ) {
	array_walk(
		$fields,
		function ( &$field_value, $k ) {
			if ( array_key_exists( 'required', $field_value ) ) {
				$field_value['required'] = $field_value['required'] ? 'true' : 'false';
			}
		}
	);
}

$data = array(
	'id'           => $calc_id,
	'settings'     => $settings,
	'currency'     => ccb_parse_settings( $settings ),
	'fields'       => $fields,
	'formula'      => get_post_meta( $calc_id, 'stm-formula', true ),
	'conditions'   => apply_filters( 'calc-render-conditions', array(), $calc_id ), // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	'language'     => $language,
	'appearance'   => $appearance,
	'dateFormat'   => get_option( 'date_format' ),
	'pro_active'   => ccb_pro_active(),
	'default_img'  => CALC_URL . '/frontend/dist/img/default.png',
	'error_img'    => CALC_URL . '/frontend/dist/img/error.png',
	'success_img'  => CALC_URL . '/frontend/dist/img/success.png',
	'translations' => $translations,
);

$custom_defined = false;
if ( isset( $is_preview ) ) {
	$custom_defined = true;
}

$styles = array(
	array(
		'label' => __( 'Two columns', 'cost-calculator-builder' ),
		'icon'  => 'ccb-icon-Union-27',
		'key'   => 'two_column',
	),
	array(
		'label' => __( 'Vertical', 'cost-calculator-builder' ),
		'icon'  => 'ccb-icon-Union-26',
		'key'   => 'vertical',
	),
	array(
		'label' => __( 'Horizontal', 'cost-calculator-builder' ),
		'icon'  => 'ccb-icon-Union-25',
		'key'   => 'horizontal',
	),
);

$invoice_texts = array(
	'order'          => esc_html__( 'Order', 'cost-calculator-builder' ),
	'total_title'    => esc_html__( 'Total Summary', 'cost-calculator-builder' ),
	'payment_method' => esc_html__( 'Payment method:', 'cost-calculator-builder' ),
	'contact_title'  => esc_html__( 'Contact Information', 'cost-calculator-builder' ),
	'contact_form'   => array(
		'name'    => esc_html__( 'Name', 'cost-calculator-builder' ),
		'email'   => esc_html__( 'Email', 'cost-calculator-builder' ),
		'phone'   => esc_html__( 'Phone', 'cost-calculator-builder' ),
		'message' => esc_html__( 'Message', 'cost-calculator-builder' ),
	),
	'total_header'   => array(
		'name'  => esc_html__( 'Name', 'cost-calculator-builder' ),
		'unit'  => esc_html__( 'Composition', 'cost-calculator-builder' ),
		'total' => esc_html__( 'Total', 'cost-calculator-builder' ),
	),
);

$send_pdf_texts = array(
	'title'          => esc_html__( 'Email Quote', 'cost-calculator-builder' ),
	'name'           => esc_html__( 'Name', 'cost-calculator-builder' ),
	'name_holder'    => esc_html__( 'Enter name', 'cost-calculator-builder' ),
	'email'          => esc_html__( 'Email', 'cost-calculator-builder' ),
	'email_holder'   => esc_html__( 'Enter Email', 'cost-calculator-builder' ),
	'message'        => esc_html__( 'Message', 'cost-calculator-builder' ),
	'message_holder' => esc_html__( 'Enter message', 'cost-calculator-builder' ),
	'submit'         => isset( $general_settings['invoice']['submitBtnText'] ) ? $general_settings['invoice']['submitBtnText'] : esc_html__( 'Send', 'cost-calculator-builder' ),
	'close'          => isset( $general_settings['invoice']['closeBtn'] ) ? $general_settings['invoice']['closeBtn'] : esc_html__( 'Close', 'cost-calculator-builder' ),
	'success_text'   => isset( $general_settings['invoice']['successText'] ) ? $general_settings['invoice']['successText'] : esc_html__( 'Email Quote Successfully Sent!', 'cost-calculator-builder-pro' ),
	'error_message'  => isset( $general_settings['invoice']['errorText'] ) ? $general_settings['invoice']['errorText'] : esc_html__( 'Fill in the required fields correctly.', 'cost-calculator-builder-pro' ),
);

if ( ( isset( $general_settings['payment_gateway']['cards']['card_payments'] ) && ! empty( $general_settings['payment_gateway']['cards']['card_payments']['use_in_all'] ) ) || ( isset( $settings['payment_gateway']['cards']['card_payments'] ) && ! empty( $settings['payment_gateway']['cards']['card_payments']['stripe']['enable'] ) ) ) {
	wp_enqueue_script( 'calc-stripe', 'https://js.stripe.com/v3/', array(), CALC_VERSION, false );
}

if ( ( isset( $general_settings['payment_gateway']['cards']['twoCheckout'] ) && ! empty( $general_settings['payment_gateway']['cards']['card_payments']['use_in_all'] ) ) || ( isset( $settings['payment_gateway']['cards']['card_payments'] ) && ! empty( $settings['payment_gateway']['cards']['card_payments']['twoCheckout']['enable'] ) ) ) {
	wp_enqueue_script( 'calc-twoCheckout', CALC_URL . '/frontend/dist/libs/2out.min.js', array(), CALC_VERSION, false );
}

if ( ( isset( $general_settings['payment_gateway']['cards']['razorpay'] ) && ! empty( $general_settings['payment_gateway']['cards']['card_payments']['use_in_all'] ) ) || ( isset( $settings['payment_gateway']['cards']['card_payments'] ) && ! empty( $settings['payment_gateway']['cards']['card_payments']['razorpay']['enable'] ) ) ) {
	wp_enqueue_script( 'calc-razorpay', 'https://checkout.razorpay.com/v1/checkout.js', null, null ); // phpcs:ignore
}

wp_localize_script( 'calc-builder-main-js', 'calc_data_' . $calc_id, $data );
$get_date_format = get_option( 'date_format' );
?>
<?php if ( ! isset( $is_preview ) ) : ?>
<div class="calculator-settings ccb-front ccb-wrapper-<?php echo esc_attr( $calc_id ); ?>">
<?php endif; ?>
	<calc-builder-front custom="<?php echo esc_attr( $custom_defined ); ?>" :content="<?php echo esc_attr( wp_json_encode( $data, 0, JSON_UNESCAPED_UNICODE ) ); ?>" inline-template :id="<?php echo esc_attr( $calc_id ); ?>">
		<div class="calc-container-wrapper">

			<?php if ( defined( 'CCB_PRO_PATH' ) ) : ?>
			<calc-thank-you-page  class="calc-hidden" v-if="hideThankYouPage" @invoice="getInvoice" @send-pdf="showSendPdf" @reset="resetCalc" :invoice_status="false" inline-template>
				<component :is="getWrapper" :order="getOrder" :settings="getSettings">
					<?php echo \cBuilder\Classes\CCBProTemplate::load( 'frontend/partials/thank-you-page', array( 'invoice' => $general_settings['invoice'] ) ); // phpcs:ignore ?>
				</component>
			</calc-thank-you-page>
			<?php endif; ?>

			<div v-show="hideCalculator" ref="calc" class="calc-container" data-calc-id="<?php echo esc_attr( $calc_id ); ?>" :class="[boxStyle, {demoSite: showDemoBoxStyle}]">
				<loader-wrapper v-if="loader" idx="<?php echo esc_attr( $loader_idx ); ?>" width="60px" height="60px" scale="0.9" :front="true"></loader-wrapper>
				<div class="ccb-demo-box-styles" :class="{active: showDemoBoxStyle}">
					<div class="ccb-box-styles">
						<?php foreach ( $styles as $style ) : ?>
							<div class="ccb-box-style-inner" :class="{'ccb-style-active': boxStyle === '<?php echo esc_attr( $style['key'] ); ?>'}" @click="changeBoxStyle('<?php echo esc_html( $style['key'] ); ?>')">
								<i class="<?php echo esc_attr( $style['icon'] ); ?>"></i>
								<span><?php echo esc_html( $style['label'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="calc-fields calc-list calc-list__indexed" :class="{loaded: !loader, 'payment' : getHideCalc}">
					<div class="calc-list-inner">
						<div class="calc-item-title">
							<div class="ccb-calc-heading">
								<?php echo esc_attr( $settings['title'] ); ?>
							</div>
						</div>
						<div v-if="calc_data" class="calc-fields-container">
							<template v-for="field in calc_data.fields">
								<template v-if="field && field.alias && field.type !== 'Total' && !field.alias.includes('group')">
									<component
											format="<?php esc_attr( $get_date_format ); ?>"
											text-days="<?php esc_attr_e( 'days', 'cost-calculator-builder' ); ?>"
											v-if="fields[field.alias] && !field.group_id"
											:is="field._tag"
											:id="calc_data.id"
											:field="field"
											:converter="currencyFormat"
											:disabled="fields[field.alias].disabled"
											v-model="fields[field.alias].value"
											v-on:change="change"
											v-on:[field._event]="change"
											v-on:condition-apply="renderCondition"
											@delete-repeater="deleteRepeater"
											@add-repeater="deleteRepeater"
											:key="!field.hasNextTick ? field.alias : field.alias + '_' + fields[field.alias].nextTickCount"
									>
									</component>
								</template>
								<template v-if="field.alias && field.alias.includes('group')">
									<component
										format="<?php esc_attr( $get_date_format ); ?>"
										text-days="<?php esc_attr_e( 'days', 'cost-calculator-builder' ); ?>"
										v-if="fields[field.alias] && !field.group_id"
										:is="field._tag"
										:id="calc_data.id"
										:field="field"
										:converter="currencyFormat"
										:disabled="fields[field.alias].disabled"
										v-model="fields[field.alias].value"
										v-on:change="change"
										v-on:[field._event]="change"
										v-on:condition-apply="renderCondition"
										@delete-repeater="deleteRepeater"
										@add-repeater="deleteRepeater"
										:key="!field.hasNextTick ? field.alias : field.alias + '_' + fields[field.alias].nextTickCount"
									>
										<slot>
											<template v-for="element in calc_data.fields">
												<component
													format="<?php esc_attr( $get_date_format ); ?>"
													text-days="<?php esc_attr_e( 'days', 'cost-calculator-builder' ); ?>"
													v-if="fields[element.alias] && element.group_id === field.alias"
													:is="element._tag"
													:id="calc_data.id"
													:field="element"
													:converter="currencyFormat"
													:disabled="fields[element.alias].disabled"
													v-model="fields[element.alias].value"
													v-on:change="change"
													v-on:[element._event]="change"
													v-on:condition-apply="renderCondition"
													@delete-repeater="deleteRepeater"
													@add-repeater="deleteRepeater"
													:key="!element.hasNextTick ? element.alias : element.alias + '_' + fields[element.alias].nextTickCount"
												>
												</component>
											</template>
										</slot>
									</component>
								</template>
								<template v-else-if="field && !field.alias && field.type !== 'Total'">
									<component
											:id="calc_data.id"
											style="boxStyle"
											:is="field._tag"
											:field="field"
									>
									</component>
								</template>
							</template>
						</div>
					</div>
				</div>

				<div class="calc-subtotal calc-list" :id="getTotalStickyId" :class="{loaded: !loader}">
					<div class="calc-subtotal-wrapper">
						<div class="calc-list-inner">
							<div class="calc-item-title calc-accordion">
								<div class="ccb-calc-heading">
									<?php echo isset( $settings['general']['header_title'] ) ? esc_html( $settings['general']['header_title'] ) : ''; ?>
								</div>
								<?php if ( isset( $settings['general']['descriptions'] ) ? esc_html( $settings['general']['descriptions'] ) : '' ) : ?>
									<span class="calc-accordion-btn" ref="calcAccordionToggle" @click="toggleAccordion">
									<i class="ccb-icon-Path-3485" :style="{top: '1px', transform: currentAccordionHeight === '0px' ? 'rotate(0)' : 'rotate(180deg)'}"></i>
								</span>
								<?php endif; ?>
							</div>

							<div class="calc-subtotal-list" :class="{ 'show-unit': showUnitInSummary }">
								<div class="calc-subtotal-list-accordion" ref="calcAccordion" :style="{maxHeight: currentAccordionHeight}">
									<div class="calc-subtotal-list-header" v-if="showUnitInSummary">
										<span class="calc-subtotal-list-header__name"><?php esc_html_e( 'Name', 'cost-calculator-builder' ); ?></span>
										<span class="calc-subtotal-list-header__value"><?php esc_html_e( 'Total', 'cost-calculator-builder' ); ?></span>
									</div>

									<template v-for="(field) in getTotalSummaryFields" v-if="(!field.inRepeater || field.alias.includes('repeater')) && field.alias.indexOf('total') === -1 && settings && settings.general.descriptions">
										<template v-if="field.alias.includes('repeater')">
											<div class="calc-repeater-subtotal" v-for="(f, idx) in Object.values(field?.resultGrouped)">
												<template v-if="getRepeaterFields(f)?.length">
													<div class="calc-repeater-subtotal-header" :data-index="idx" @click="toggleRepeater(idx)">
														<i class="ccb-icon-Path-3514"></i>
														<span>{{field.label}}</span>
													</div>
													<div class="calc-repeater-subtotal-fields" :data-index="idx">
														<template v-for="(innerField) in getRepeaterFields(f)">
															<calc-total-summary :field="innerField" :style="{'padding-top': '6px'}"></calc-total-summary>
															<calc-total-summary :field="innerField" :unit="true" v-if="innerField.option_unit" :style="{'padding-top': '5px'}"></calc-total-summary>
															<calc-total-summary :field="innerField" :style="{'padding-top': '5px'}" :multi="true" v-if="['checkbox', 'toggle', 'checkbox_with_img'].includes(innerField.alias.replace(/\_field_id.*/,'')) && innerField.options?.length"></calc-total-summary>
														</template>
													</div>
												</template>
											</div>
										</template>
										<template v-else>
											<calc-total-summary :field="field"></calc-total-summary>
											<template v-if="field.option_unit">
												<template v-if="field.hasOwnProperty('allowPrice')">
													<calc-total-summary :field="field" :unit="true" v-if="field.allowPrice"></calc-total-summary>
												</template>
												<template v-else>
													<calc-total-summary :field="field" :unit="true"></calc-total-summary>
												</template>
											</template>
											<calc-total-summary :field="field" :multi="true" v-if="['checkbox', 'toggle', 'checkbox_with_img'].includes(field.alias.replace(/\_field_id.*/,'')) && field.options?.length"></calc-total-summary>
										</template>
									</template>
								</div>
							</div>

							<div class="calc-subtotal-list totals" style="margin-top: 20px; padding-top: 10px;" ref="calcTotals" :class="{'unit-enable': showUnitInSummary}">
								<template v-for="item in getRepeaterTotals">
									<div style="display: flex" class="sub-list-item total">
										<span class="sub-item-title">{{ item.label }}</span>
										<span class="sub-item-value" style="white-space: nowrap">{{ item.converted }}</span>
									</div>
									<cost-total v-else :value="item.total" :field="item.data" :id="calc_data.id" v-on:condition-apply="renderCondition"></cost-total>
								</template>
								<template v-for="item in formulaConst">
									<div v-if="formulaConst.length === 1 && typeof formulaConst[0].alias === 'undefined'" style="display: flex" class="sub-list-item total">
										<span class="sub-item-title"><?php esc_html_e( 'Total', 'cost-calculator-builder' ); ?></span>
										<span class="sub-item-value" style="white-space: nowrap">{{ item.data.converted }}</span>
									</div>
									<cost-total v-else :value="item.total" :field="item.data" :id="calc_data.id" v-on:condition-apply="renderCondition"></cost-total>
								</template>
							</div>

							<div class="calc-subtotal-list" v-if="getWooProductName">
								<div class="calc-woo-product">
									<div class="calc-woo-product__info">
										"{{getWooProductName}}"<?php echo esc_html__( ' has been added to your cart', 'cost-calculator-builder' ); ?>
									</div>
									<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
										<button class="calc-woo-product__btn">
											<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><span><?php echo esc_html__( 'View cart', 'cost-calculator-builder' ); ?></span></a>
										</button>
									<?php endif; ?>
								</div>
							</div>
							<div class="calc-checkbox calc-is-vertical default calc_checkbox_field_id_stmshowintotal" style="padding-top: 20px;">
									<div class="calc-checkbox-item">
										<input type="checkbox" id="option__stmshowintotal" value="true">
										<label for="option__stmshowintotal"><span>
												<span class="calc-checkbox-title">
													Show in Grand Total
												</span>
											</span>
										</label>
									</div>
								</div>
							<div class="calc-subtotal-list calc-buttons">

								<?php if ( ccb_pro_active() ) : ?>
									<cost-pro-features inline-template :settings="content.settings">
										<?php echo \cBuilder\Classes\CCBProTemplate::load( 'frontend/pro-features', array( 'settings' => $settings, 'general_settings' => $general_settings, 'invoice' => $general_settings['invoice'] ) ); // phpcs:ignore ?>
									</cost-pro-features>
								<?php endif; ?>
							</div>

						</div>

						<div class="calc-list-inner calc-notice" :class="noticeData.type" v-show="getStep === 'notice'" style="margin-top: 10px !important;">
							<calc-notices :notice="noticeData"/>
						</div>
					</div>
					<calc-invoice
							ref="invoice"
							company-name="<?php echo isset( $general_settings['invoice']['companyName'] ) ? esc_attr( $general_settings['invoice']['companyName'] ) : ''; ?>"
							company-info="<?php echo isset( $general_settings['invoice']['companyInfo'] ) ? esc_attr( $general_settings['invoice']['companyInfo'] ) : ''; ?>"
							company-logo='<?php echo esc_attr( $general_settings['invoice']['companyLogo'] ); ?>'
							date-format="<?php echo isset( $general_settings['invoice']['dateFormat'] ) ? esc_attr( $general_settings['invoice']['dateFormat'] ) : ''; ?>"
							static-texts='<?php echo esc_attr( wp_json_encode( $invoice_texts ) ); ?>'
							send-email-texts='<?php echo esc_attr( wp_json_encode( $send_pdf_texts ) ); ?>'
							send-pdf-from="<?php echo isset( $general_settings['invoice']['fromEmail'] ) ? esc_attr( $general_settings['invoice']['fromEmail'] ) : ''; ?>"
							send-pdf-fromname="<?php echo isset( $general_settings['invoice']['fromName'] ) ? esc_attr( $general_settings['invoice']['fromName'] ) : ''; ?>"
							:summary-fields="getTotalSummaryFields"
							site-lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>"
					/>
				</div>
			</div>
		</div>
	</calc-builder-front>
	<?php if ( ! isset( $is_preview ) ) : ?>
</div>
<?php endif; ?>
