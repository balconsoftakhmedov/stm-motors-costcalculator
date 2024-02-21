<?php

namespace Motors_E_W\Widgets;

use Motors_E_W\MotorsApp;
use STM_E_W\Helpers\Helper;
use STM_E_W\Widgets\Controls\ContentControls\NumberControl;
use STM_E_W\Widgets\Controls\ContentControls\Select2Control;
use STM_E_W\Widgets\Controls\ContentControls\SwitcherControl;
use STM_E_W\Widgets\Controls\ContentControls\TextControl;
use STM_E_W\Widgets\Controls\StyleControls\AlignControl;
use STM_E_W\Widgets\Controls\StyleControls\ColorControl;
use STM_E_W\Widgets\Controls\StyleControls\GroupTypographyControl;
use STM_E_W\Widgets\Controls\StyleControls\SliderControl;
use STM_E_W\Widgets\WidgetBase;

class CustomImageCategories extends WidgetBase {

	use Select2Control;
	use SwitcherControl;
	use NumberControl;
	use SliderControl;
	use AlignControl;
	use TextControl;
	use NumberControl;
	use ColorControl;
	use GroupTypographyControl;

	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );

		$this->stm_ew_enqueue(
			$this->get_name(),
			MOTORS_ELEMENTOR_WIDGETS_PATH,
			MOTORS_ELEMENTOR_WIDGETS_URL,
			MOTORS_ELEMENTOR_WIDGETS_PLUGIN_VERSION,
			array(
				'swiper',
				'elementor-frontend',
			)
		);

		$this->stm_ew_enqueue(
			$this->get_name() . '-slider',
			MOTORS_ELEMENTOR_WIDGETS_PATH,
			MOTORS_ELEMENTOR_WIDGETS_URL,
			MOTORS_ELEMENTOR_WIDGETS_PLUGIN_VERSION,
			array(
				'swiper',
				'elementor-frontend',
			)
		);

		/**
		 * this script contains both slider and ordinary logic
		 * it is needed for the edit page
		 */
		$this->stm_ew_admin_register_ss(
			$this->get_name() . '-admin',
			$this->get_name() . '-admin',
			MOTORS_ELEMENTOR_WIDGETS_PATH,
			MOTORS_ELEMENTOR_WIDGETS_URL,
			MOTORS_ELEMENTOR_WIDGETS_PLUGIN_VERSION,
			array(
				'swiper',
				'elementor-frontend',
			)
		);

	}


	public function get_style_depends(): array {
		return array( $this->get_name() );
	}

	public function get_script_depends() {
		return array(
			$this->get_name(),
			$this->get_name() . '-slider',
			$this->get_name() . '-admin',
		);
	}

	public function get_categories(): array {
		return array( MotorsApp::WIDGET_CATEGORY );
	}

	public function get_name(): string {
		return MotorsApp::STM_PREFIX . '-custom-image-categories';
	}

	public function get_title(): string {
		return esc_html__( 'Custom Image Categories', 'stm-motors-custom' );
	}

	public function get_icon(): string {
		return 'stmew-image-categories';
	}

	protected function register_controls() {

		$this->stm_start_content_controls_section( 'section_content', esc_html__( 'General', 'motors-elementor-widgets' ) );

		$listing_categories = stm_listings_attributes_child();

		if ( ! empty( $listing_categories ) ) {
			$listing_categories = array_column( $listing_categories, 'single_name', 'slug' );
		}

		$this->stm_ew_add_select_2(
			'taxonomy',
			array(
				'label'   => esc_html__( 'Select Category', 'motors-elementor-widgets' ),
				'options' => $listing_categories,
			)
		);

		$this->stm_ew_add_switcher(
			'items_count',
			array(
				'label'   => esc_html__( 'Items Count', 'motors-elementor-widgets' ),
				'default' => '',
			)
		);

		$this->stm_ew_add_switcher(
			'show_as_carousel',
			array(
				'label'   => esc_html__( 'Carousel', 'motors-elementor-widgets' ),
				'default' => '',
			)
		);

		$this->stm_ew_add_number(
			'limit',
			array(
				'label'     => esc_html__( 'Visible Items', 'motors-elementor-widgets' ),
				'condition' => array(
					'show_as_carousel' => '',
				),
			)
		);

		$this->stm_ew_add_align(
			'items_align',
			array(
				'{{WRAPPER}} .stm_listing_icon_filter' => 'text-align: {{VALUE}}',
				'{{WRAPPER}} .stm_listing_icon_filter .stm_listing_icon_filter_single .name' => 'text-align: {{VALUE}}',
			),
			esc_html__( 'Items Align', 'motors-elementor-widgets' ),
			array(
				'condition' => array(
					'show_as_carousel' => '',
				),
			)
		);

		$this->stm_ew_add_slider(
			'items_margin_bottom',
			array(
				'label'          => esc_html__( 'Items Margin Bottom', 'motors-elementor-widgets' ),
				'range'          => array(
					'px' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					),
				),
				'devices'        => array(
					'desktop',
					'tablet',
					'mobile',
				),
				'default'        => array(
					'size' => 44,
					'unit' => 'px',
				),
				'tablet_default' => array(
					'size' => 44,
					'unit' => 'px',
				),
				'mobile_default' => array(
					'size' => 44,
					'unit' => 'px',
				),
				'condition'      => array(
					'show_as_carousel' => '',
				),
				'selectors'      => array(
					'{{WRAPPER}} .stm_listing_icon_filter .stm_listing_icon_filter_single' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				),
			)
		);

		//px size is not used, it is needed only for the widget to work
		$this->stm_ew_add_responsive_slider(
			'per_row',
			array(
				'label'           => esc_html__( 'Columns', 'motors-elementor-widgets' ),
				'range'           => array(
					'px' => array(
						'min'  => 1,
						'max'  => 12,
						'step' => 1,
					),
				),
				'devices'         => array(
					'desktop',
					'tablet',
					'mobile',
				),
				'default'         => array(
					'size' => 4,
					'unit' => 'px',
				),
				'desktop_default' => array(
					'size' => 4,
					'unit' => 'px',
				),
				'tablet_default'  => array(
					'size' => 3,
					'unit' => 'px',
				),
				'mobile_default'  => array(
					'size' => 2,
					'unit' => 'px',
				),
				'condition'       => array(
					'show_as_carousel' => '',
				),
				'selectors'       => array(
					'{{WRAPPER}} .stm_listing_icon_filter .stm_listing_icon_filter_single' => 'width: calc(100% / {{SIZE}})',
				),
			)
		);

		$this->stm_ew_add_text(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'motors-elementor-widgets' ),
				'placeholder' => esc_html__( 'Browse by' ) . ' {{category}}',
				'default'     => __( 'Browse by' ) . ' {{category}}',
				'description' => __( 'Available replacement:' ) . ' {{category}}',
			)
		);

		$this->stm_ew_add_text(
			'show_all_text',
			array(
				'label'       => esc_html__( '"Show all" label', 'motors-elementor-widgets' ),
				'placeholder' => esc_html__( 'Show all' ) . ' {{category}}',
				'default'     => __( 'Show all' ) . ' {{category}}',
				'description' => __( 'Available replacement:' ) . ' {{category}}',
				'condition'   => array(
					'show_as_carousel' => '',
				),
			)
		);

		//px size is not used, it is needed only for the widget to work
		$this->stm_ew_add_responsive_slider(
			'slides_per_view',
			array(
				'label'           => esc_html__( 'Columns', 'motors-elementor-widgets' ),
				'range'           => array(
					'px' => array(
						'min'  => 1,
						'max'  => 12,
						'step' => 1,
					),
				),
				'devices'         => array(
					'desktop',
					'tablet',
					'mobile',
				),
				'desktop_default' => array(
					'size' => 4,
					'unit' => 'px',
				),
				'tablet_default'  => array(
					'size' => 3,
					'unit' => 'px',
				),
				'mobile_default'  => array(
					'size' => 2,
					'unit' => 'px',
				),
				'condition'       => array(
					'show_as_carousel' => 'yes',
				),
			)
		);

		$this->stm_ew_add_responsive_slider(
			'slides_per_transition',
			array(
				'label'           => esc_html__( 'Slides Per Transition', 'motors-elementor-widgets' ),
				'range'           => array(
					'px' => array(
						'min'  => 1,
						'max'  => 12,
						'step' => 1,
					),
				),
				'devices'         => array(
					'desktop',
					'tablet',
					'mobile',
				),
				'desktop_default' => array(
					'size' => 4,
					'unit' => 'px',
				),
				'tablet_default'  => array(
					'size' => 3,
					'unit' => 'px',
				),
				'mobile_default'  => array(
					'size' => 2,
					'unit' => 'px',
				),
				'condition'       => array(
					'show_as_carousel' => 'yes',
				),
			)
		);

		$this->stm_ew_add_switcher(
			'loop',
			array(
				'label'     => __( 'Infinite Loop', 'motors-elementor-widgets' ),
				'condition' => array(
					'show_as_carousel' => 'yes',
				),
			)
		);

		$this->stm_ew_add_switcher(
			'click_drag',
			array(
				'label'       => __( 'Click & Drag', 'motors-elementor-widgets' ),
				'description' => __( 'Accept mouse events like touch events (click and drag to change slides)', 'motors-elementor-widgets' ),
				'condition'   => array(
					'show_as_carousel' => 'yes',
				),
			)
		);

		$this->stm_ew_add_switcher(
			'autoplay',
			array(
				'label'     => __( 'Autoplay', 'motors-elementor-widgets' ),
				'condition' => array(
					'show_as_carousel' => 'yes',
				),
			)
		);

		$this->stm_ew_add_number(
			'transition_speed',
			array(
				'label'       => __( 'Animation Speed', 'motors-elementor-widgets' ),
				'min'         => 100,
				'step'        => 100,
				'default'     => 300,
				'description' => __( 'Speed of slide animation in milliseconds', 'motors-elementor-widgets' ),
				'condition'   => array(
					'show_as_carousel' => 'yes',
					'autoplay'         => 'yes',
				),
			)
		);

		$this->stm_ew_add_number(
			'delay',
			array(
				'label'       => __( 'Slide Duration', 'motors-elementor-widgets' ),
				'min'         => 100,
				'step'        => 100,
				'default'     => 3000,
				'condition'   => array(
					'autoplay'         => 'yes',
					'show_as_carousel' => 'yes',
				),
				'description' => __( 'Delay between transitions in milliseconds', 'motors-elementor-widgets' ),
			)
		);

		$this->stm_ew_add_switcher(
			'pause_on_mouseover',
			array(
				'label'       => __( 'Pause on Mouseover', 'motors-elementor-widgets' ),
				'condition'   => array(
					'autoplay'         => 'yes',
					'show_as_carousel' => 'yes',
				),
				'description' => __( 'When enabled autoplay will be paused on mouse enter over carousel container', 'motors-elementor-widgets' ),
			)
		);

		$this->stm_ew_add_switcher(
			'reverse',
			array(
				'label'       => __( 'Reverse Direction', 'motors-elementor-widgets' ),
				'condition'   => array(
					'autoplay'         => 'yes',
					'show_as_carousel' => 'yes',
				),
				'description' => __( 'Enables autoplay in reverse direction', 'motors-elementor-widgets' ),
			)
		);

		$this->stm_ew_add_switcher(
			'navigation',
			array(
				'label'     => __( 'Navigation', 'motors-elementor-widgets' ),
				'condition' => array(
					'show_as_carousel' => 'yes',
				),
			)
		);

		$this->stm_ew_add_select_2(
			'navigation_style',
			array(
				'label'     => __( 'Navigation Style', 'motors-elementor-widgets' ),
				'options'   => array(
					'default'    => 'Default (On both sides)',
					'in_heading' => 'In Heading',
				),
				'default'   => 'default',
				'condition' => array(
					'navigation' => 'yes',
				),
			)
		);

		$this->stm_end_control_section();

		/*Start style section*/
		$this->stm_start_style_controls_section( 'section_color', esc_html__( 'General', 'motors-elementor-widgets' ) );

		$this->stm_ew_add_group_typography(
			'title_typography',
			array(
				'label'          => esc_html__( 'Title Typography', 'motors-elementor-widgets' ),
				'exclude'        => array(
					'font_family',
					'font_style',
					'text_transform',
					'text_decoration',
					'word_spacing',
				),
				'fields_options' => array(
					'font_size'      => array(
						'size_units'     => array(
							'px',
							'em',
						),
						'default'        => array(
							'unit' => 'px',
							'size' => 26,
						),
						'tablet_default' => array(
							'unit' => 'px',
							'size' => 20,
						),
						'mobile_default' => array(
							'unit' => 'px',
							'size' => 16,
						),
					),
					'line_height'    => array(
						'size_units'     => array(
							'px',
							'em',
						),
						'default'        => array(
							'unit' => 'px',
							'size' => 32,
						),
						'tablet_default' => array(
							'unit' => 'px',
							'size' => 22,
						),
						'mobile_default' => array(
							'unit' => 'px',
							'size' => 18,
						),
					),
					'letter_spacing' => array(
						'size_units' => array(
							'px',
							'em',
						),
						'default'    => array(
							'unit' => 'px',
							'size' => 0,
						),
					),
				),
				'selector'       => '{{WRAPPER}} .stm_icon_filter_title > h3',
			)
		);

		$this->stm_ew_add_group_typography(
			'show_all_typography',
			array(
				'label'          => esc_html__( '"Show all" Label Typography', 'motors-elementor-widgets' ),
				'exclude'        => array(
					'font_family',
					'font_style',
					'word_spacing',
				),
				'fields_options' => array(
					'font_size'      => array(
						'size_units'     => array(
							'px',
							'em',
						),
						'default'        => array(
							'unit' => 'px',
							'size' => 14,
						),
						'tablet_default' => array(
							'unit' => 'px',
							'size' => 14,
						),
						'mobile_default' => array(
							'unit' => 'px',
							'size' => 12,
						),
					),
					'line_height'    => array(
						'size_units'     => array(
							'px',
							'em',
						),
						'default'        => array(
							'unit' => 'px',
							'size' => 17,
						),
						'tablet_default' => array(
							'unit' => 'px',
							'size' => 17,
						),
						'mobile_default' => array(
							'unit' => 'px',
							'size' => 14,
						),
					),
					'letter_spacing' => array(
						'size_units' => array(
							'px',
							'em',
						),
						'default'    => array(
							'unit' => 'px',
							'size' => 0,
						),
					),
				),
				'selector'       => '{{WRAPPER}} .stm_icon_filter_label',
				'condition'      => array(
					'show_as_carousel' => '',
				),
			)
		);

		$this->stm_ew_add_color(
			'show_all_color',
			array(
				'label'     => esc_html__( '"Show all" Label Color', 'motors-elementor-widgets' ),
				'default'   => '#777777',
				'selectors' => array(
					'{{WRAPPER}} .stm_icon_filter_label' => 'color: {{VALUE}}; border-bottom-color: {{VALUE}};',
				),
				'condition' => array(
					'show_as_carousel' => '',
				),
			)
		);

		$this->stm_ew_add_color(
			'show_all_color_hover',
			array(
				'label'     => esc_html__( '"Show all" Label Color On Hover', 'motors-elementor-widgets' ),
				'default'   => '#4e90cc',
				'selectors' => array(
					'{{WRAPPER}} .stm_icon_filter_label:hover'  => 'color: {{VALUE}}; border-bottom-color: {{VALUE}};',
					'{{WRAPPER}} .stm_icon_filter_label:active' => 'color: {{VALUE}}; border-bottom-color: {{VALUE}};',
				),
				'condition' => array(
					'show_as_carousel' => '',
				),
			)
		);

		$this->stm_ew_add_group_typography(
			'item_typography',
			array(
				'label'    => esc_html__( 'Item Typography', 'motors-elementor-widgets' ),
				'exclude'  => array(
					'font_family',
					'font_style',
					'word_spacing',
				),
				'selector' => '{{WRAPPER}} .stm_listing_icon_filter_single .name',
			)
		);

		$this->stm_ew_add_color(
			'item_color',
			array(
				'label'     => esc_html__( 'Item Font Color', 'motors-elementor-widgets' ),
				'default'   => '#777777',
				'selectors' => array(
					'{{WRAPPER}} .stm_listing_icon_filter_single .name' => 'color: {{VALUE}}',
				),
			)
		);

		$this->stm_ew_add_color(
			'item_color_hover',
			array(
				'label'     => esc_html__( 'Item Font Color on Hover', 'motors-elementor-widgets' ),
				'default'   => '#4e90cc',
				'selectors' => array(
					'{{WRAPPER}} .stm_listing_icon_filter_single:hover .name' => 'color: {{VALUE}}',
				),
			)
		);

		$this->stm_ew_add_color(
			'navigation_background_color',
			array(
				'label'     => esc_html__( 'Navigation Background Color', 'motors-elementor-widgets' ),
				'default'   => '#dddddd',
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-prev' => 'background: {{VALUE}}',
					'{{WRAPPER}} .swiper-button-next' => 'background: {{VALUE}}',
				),
				'condition' => array(
					'navigation' => 'yes',
				),
			)
		);

		$this->stm_ew_add_color(
			'navigation_background_color_hover',
			array(
				'label'     => esc_html__( 'Navigation Background Color On Hover', 'motors-elementor-widgets' ),
				'default'   => '#dddddd',
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-prev:hover' => 'background: {{VALUE}}',
					'{{WRAPPER}} .swiper-button-next:hover' => 'background: {{VALUE}}',
				),
				'condition' => array(
					'navigation' => 'yes',
				),
			)
		);

		$this->stm_ew_add_color(
			'navigation_icon_color',
			array(
				'label'     => esc_html__( 'Navigation Icon Color', 'motors-elementor-widgets' ),
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-prev > i' => 'color: {{VALUE}}',
					'{{WRAPPER}} .swiper-button-next > i' => 'color: {{VALUE}}',
				),
				'condition' => array(
					'navigation' => 'yes',
				),
			)
		);

		$this->stm_ew_add_color(
			'navigation_icon_color_hover',
			array(
				'label'     => esc_html__( 'Navigation Icon Color On Hover', 'motors-elementor-widgets' ),
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .swiper-button-prev:hover > i' => 'color: {{VALUE}}',
					'{{WRAPPER}} .swiper-button-next:hover > i' => 'color: {{VALUE}}',
				),
				'condition' => array(
					'navigation' => 'yes',
				),
			)
		);

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$is_edit = Helper::is_elementor_edit_mode();

		if ( 'yes' === $settings['show_as_carousel'] ) {
			if ( ! $is_edit ) {
				wp_deregister_script( 'motors-image-categories-admin' );
				wp_dequeue_script( 'motors-image-categories-admin' );
				wp_enqueue_script( 'motors-image-categories-slider' );
			}
			Helper::stm_ew_load_template( 'widgets/commercial-image-categories/image-categories-slider', MOTORS_ELEMENTOR_WIDGETS_PATH, $settings );

			return;
		}

		if ( ! $is_edit ) {
			wp_deregister_script( 'motors-image-categories-admin' );
			wp_dequeue_script( 'motors-image-categories-admin' );
			wp_enqueue_script( 'motors-image-categories' );
		}
		Helper::stm_ew_load_template( 'widgets/commercial-image-categories/image-categories', MOTORS_ELEMENTOR_WIDGETS_PATH, $settings );
	}

}
