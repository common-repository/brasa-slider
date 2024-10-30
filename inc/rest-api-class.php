<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Add support to WP REST API
 */
class Brasa_Slider_API {
	/**
	 * Constructor
	 * @return object
	 */
	public function __construct() {
		/**
		 *
		 * Add brasa slider endpoint
		 *
		 */
		add_action( 'rest_api_init', array( &$this, 'rest_api_init' ) );
	}
	/**
	 * init rest api
	 * @return null
	 */
	public function rest_api_init() {
		register_rest_route( 'brasa-slider', '/id/(?P<slider>\d+)', array(
			'methods' => 'GET',
			'callback' => array( &$this, 'endpoint' )
		) );
		register_rest_route( 'brasa-slider', '/name', array(
			'methods' => 'GET',
			'callback' => array( &$this, 'endpoint' )
		) );
	}
	/**
	 * process endpoint data and return it
	 * @param object $request
	 * @return object
	 */
	public function endpoint( $request ) {
		// get parameters
		$parameters = $request->get_params();
		// check if image size exists
		if ( ! isset( $parameters['image_size'] ) || is_numeric( $parameters['image_size'] ) ) {
			$parameters['image_size'] = 'brasa_slider_img';
		}
		$parameters['image_size'] = wp_strip_all_tags( $parameters['image_size'] );
		// create array to receive response data
		$data = array();
		// check if slider param exists
		if ( ! isset( $parameters['slider'] ) ) {
			return new WP_Error( 'no_slider', 'Invalid slider ID or Name', array( 'status' => 404 ) );
		}
		// clean slider param;
		$parameters['slider'] = wp_strip_all_tags( $parameters['slider'] );
		// check if is ID or name
		if ( is_numeric( $parameters['slider'] ) ) {
			$parameters['slider'] = absint( $parameters['slider'] );
			// if is ID get it from DB
			$slider = get_post( $parameters['slider'] );
			// check if this post exist;
			if ( is_wp_error( $slider ) || ! is_object( $slider ) ) {
				return new WP_Error( 'no_slider', 'Invalid slider ID or Name', array( 'status' => 404 ) );
			}
		} else {
			/* Get transient */
			$brasa_slider_transient = get_transient( 'brasa_slider_json_' . sanitize_title( $parameters['slider'] ) );

			if ( false === ( $brasa_slider_transient ) ) {
				// if is name get slider from db by name
				$slider = get_page_by_title( $parameters['slider'], OBJECT, 'brasa_slider_cpt' );

				/* Create transient for this slider */
				set_transient( 'brasa_slider_json_' . sanitize_title( $parameters['slider'] ), $slider, DAY_IN_SECONDS );
			} else {
				$slider = $brasa_slider_transient;
			}

			if ( is_wp_error( $slider ) || ! is_object( $slider ) ) {
				return new WP_Error( 'no_slider', 'Invalid slider ID or Name', array( 'status' => 404 ) );
			}
		}
		if ( $slider->post_type != 'brasa_slider_cpt' ) {
			return new WP_Error( 'invalid_slider_id_cpt', 'Ooops: this post have another type', array( 'status' => 404 ) );
		}
		// get slider items
		$ids = esc_textarea( get_post_meta( $slider->ID, 'brasa_slider_ids', true ) );
		$ids = explode( ',', $ids );
		$ids = array_filter( $ids );
		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return new WP_Error( 'blank_slider', 'This slider is blank (0 items)', array( 'status' => 404 ) );
		}
		$data['items'] = array();
		foreach ( $ids as $index => $item_id ) {
			if(	get_post_type( $item_id ) == 'attachment' ) {
				$img = $item_id;
			} else {
				$img = get_post_thumbnail_id( $item_id );
			}
			$img = wp_get_attachment_image_src( $img, $parameters[ 'image_size'], false );
			$url = get_post_meta( $slider->ID, 'brasa_slider_id' . $item_id, true );
			if ( $url === false ) {
				$url = '';
			}
			$data['items'][ $index ] 			= array();
			$data['items'][ $index ]['image'] 	= $img;
			$data['items'][ $index ]['url'] 	= esc_url( $url );
		}
		$data['slider_size'] = count( $ids );
		$data['image_size'] = $parameters['image_size'];
		// show template parameter
		if ( isset( $parameters['print_template'] ) && $parameters['print_template'] == 'true' ) {
			$data['template'] = $this->print_template( $slider );
		}
		return new WP_REST_Response( $data );
	}
	/**
	 * Print template HTML
	 * @param object $slider
	 * @return string
	 */
	private function print_template( $slider) {
		$GLOBALS['slider'] 	= $slider;
		$GLOBALS['atts'] 	= array();

		if ( ! empty( $slider ) && isset( $slider ) ) {
			$html = brasa_slider_get_template_html( 'slider.php' );
		    return $html;
		}
	}
}
new Brasa_Slider_API();
