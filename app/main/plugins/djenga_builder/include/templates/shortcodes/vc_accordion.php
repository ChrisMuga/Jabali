<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Shortcode attributes
 * @var $atts
 * @var $title
 * @var $el_class
 * @var $collapsible
 * @var $disable_keyboard
 * @var $active_tab
 * @var $content - shortcode content
 * Shortcode class
 * @var $this DJengaShortCode_VC_Accordion
 */
$title = $el_class = $collapsible = $disable_keyboard = $active_tab = '';
$atts = vc_map_get_attributes( $this->getShortcode(), $atts );
extract( $atts );

wp_enqueue_script( 'jquery-ui-accordion' );
$el_class = $this->getExtraClass( $el_class );
$css_class = apply_filters( VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, 'dj_accordion dj_content_element ' . $el_class . ' not-column-inherit', $this->settings['base'], $atts );

$output = '
	<div class="' . esc_attr( $css_class ) . '" data-collapsible="' . esc_attr( $collapsible ) . '" data-vc-disable-keydown="' . ( esc_attr( ( 'yes' === $disable_keyboard ? 'true' : 'false' ) ) ) . '" data-active-tab="' . $active_tab . '">
		<div class="dj_wrapper dj_accordion_wrapper ui-accordion">
' . dj_widget_title( array( 'title' => $title, 'extraclass' => 'dj_accordion_heading' ) ) . '
' . dj_js_remove_wpautop( $content ) . '
		</div>
	</div>
';

echo $output;
