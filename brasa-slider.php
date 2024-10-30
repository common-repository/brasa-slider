<?php
/**
 *
 *
 * @package   Brasa Slider
 * @author    Matheus Gimenez <contato@matheusgimenez.com.br>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Matheus Gimenez
 *
 * @wordpress-plugin
 * Plugin Name:       Brasa Slider
 * Plugin URI:        http://brasa.art.br
 * Description:       Brasa Slider
 * Version:           2.0.2
 * Author:            Matheus Gimenez
 * Plugin URI:        http://brasa.art.br
 * Text Domain:       brasa-slider
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/brasadesign/brasa-slider
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return HTML template
 * @param string $template_name
 */
function brasa_slider_get_template_html( $template_name ) {
	ob_start();
	brasa_slider_locate_template( array( $template_name ), true );
	return ob_get_clean();
}
/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * Taken from bbPress
 *
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true.
 *                            Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function brasa_slider_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// Check child theme first
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'brasa/' . $template_name ) ) {
			$located = trailingslashit( get_stylesheet_directory() ) . 'brasa/' . $template_name;
			break;

		// Check parent theme next
		} elseif ( file_exists( trailingslashit( get_template_directory() ) . 'brasa/' . $template_name ) ) {
			$located = trailingslashit( get_template_directory() ) . 'brasa/' . $template_name;
		} else {
			// load plugin file
			$located = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'templates/' . $template_name;
		}
	}

	if ( ( true == $load ) && ! empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}
/**
 * Retrieves a template part
 *
 * @since v1.5
 *
 * Taken from bbPress
 *
 * @param string $slug
 * @param string $name Optional. Default null
 *
 * @uses  load_template()
 * @uses  get_template_part()
 */
function brasa_slider_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
	$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parts to be filtered
	$templates = apply_filters( 'brasa_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return brasa_slider_locate_template( $templates, $load, false );
}

/**
 * Brasa_Slider Class
 */
class Brasa_Slider {
	/**
	 * Constructor: define things & add actions
	 * @return null
	 */
	public function __construct() {
		define(			'BRASA_SLIDER_URL', plugin_dir_url( __FILE__ ) );
		define(			'BRASA_SLIDER_DIR', plugin_dir_path( __FILE__ ) );
		add_image_size(	'brasa_slider_img', 1006, 408, true );

		add_action(		'init',				array( $this, 'init' ) ); //init
		add_action(		'admin_init', 		array( $this, 'admin_scripts' ), 9999999 );
		add_action(		'add_meta_boxes',	array( $this, 'add_boxes' ) );
		add_action(		'save_post',		array( $this, 'save' ) );
		add_action(		'plugins_loaded',	array( $this, 'text_domain' ) );
		add_shortcode(	'brasa_slider',		array( $this, 'shortcode' ) );

		// add notice to show shortcode on edit slider screen
		add_action( 'admin_notices', array( $this, 'show_shortcode_edit' ) );
	}
	/**
	 * Add notice to show shortcode on edit slider screen
	 * @return null
	 */
	public function show_shortcode_edit() {
		$page = get_current_screen();
		if ( $page->id == 'brasa_slider_cpt' && $_GET[ 'action' ] == 'edit' ) {
			global $post;
			$shortcode = sprintf( '[brasa_slider id="%s"]', $post->ID );
			$text = sprintf( __( 'Shortcode: %s', 'brasa-slider' ), $shortcode );
			printf( '<div class="notice notice-success"><p>%s</p></div>', $text );
		}
	}
	/**
	 * Load text domain
	 * @return null
	 */
	public function text_domain() {
		load_plugin_textdomain( 'brasa-slider', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	/**
	 * Init things
	 * @return null
	 */
	public function init() {
		if( isset( $_GET['brasa_slider_ajax'] ) && $_GET['brasa_slider_ajax'] == 'true' && current_user_can( 'edit_posts' ) ) {
			$this->ajax_search();
		}
		if( ! defined( 'BRASA_SLIDER_REMOVE_FRONTEND' ) || BRASA_SLIDER_REMOVE_FRONTEND === false ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script(
				'brasa_slider_jqueryui_js',
				BRASA_SLIDER_URL . 'assets/js/slick.min.js',
				array('jquery')
			);
			wp_enqueue_style( 'brasa_slider_css_frontend', BRASA_SLIDER_URL . 'assets/css/slick.css' );
		}
		$this->register_cpt();
	}
	/**
	 * Register post type
	 * @return null
	 */
	private function register_cpt(){
		$labels = array(
			'name'                => _x( 'Brasa Sliders', 'Post Type General Name', 'brasa-slider' ),
			'singular_name'       => _x( 'Brasa Slider', 'Post Type Singular Name', 'brasa-slider' ),
			'menu_name'           => __( 'Brasa Slider', 'brasa-slider' ),
			'parent_item_colon'   => __( 'Slider parent', 'brasa-slider' ),
			'all_items'           => __( 'All sliders', 'brasa-slider' ),
			'view_item'           => __( 'View slider', 'brasa-slider' ),
			'add_new_item'        => __( 'Add New Slider', 'brasa-slider' ),
			'add_new'             => __( 'Add New', 'brasa-slider' ),
			'edit_item'           => __( 'Edit Slider', 'brasa-slider' ),
			'update_item'         => __( 'Update Slider', 'brasa-slider' ),
			'search_items'        => __( 'Search Slider', 'brasa-slider' ),
			'not_found'           => __( 'Not found', 'brasa-slider' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'brasa-slider' ),
			);
		$args = array(
			'label'               => __( 'brasa_slider_cpt', 'brasa-slider' ),
			'description'         => __( 'Brasa Slider', 'brasa-slider' ),
			'labels'              => $labels,
			'supports'            => array( 'title', ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-images-alt',
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capability_type'     => 'page',
			);
		register_post_type( 'brasa_slider_cpt', $args );
	}
	/**
	 * Load scripts on dashboard
	 * @return null
	 */
	public function admin_scripts() {
		if( isset( $_GET['post'] ) ) {
			$post = get_post( intval( $_GET['post'] ) ) ;
		}
		if( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'brasa_slider_cpt' || isset( $post ) && $post->post_type == 'brasa_slider_cpt' ) {
			wp_enqueue_style( 'brasa_slider_css', BRASA_SLIDER_URL . 'assets/css/admin.css' );
			wp_enqueue_script('jquery');
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script(
				'brasa_slider_all_js',
				BRASA_SLIDER_URL . 'assets/js/all.js',
				array('jquery')
			);
			$admin_params = array(
				'media_element_title' => __( 'Select a image', 'brasa-slider' )
				);
			wp_localize_script( 'brasa_slider_all_js', 'brasa_slider_admin_params', $admin_params );

		}

		// add metabox
		require_once BRASA_SLIDER_DIR . 'inc/odin-metabox.php';
		require_once BRASA_SLIDER_DIR . 'inc/metabox.php';

	}
	/**
	 * Add metaboxes
	 * @return null;
	 */
	public function add_boxes() {
		add_meta_box(
			'brasa_slider_search'
			,__( 'Search Posts by Name', 'brasa-slider' )
			,array( $this, 'render_search_meta' )
			,'brasa_slider_cpt'
			,'advanced'
			,'core'
			);
		add_meta_box(
			'brasa_slider_sortable'
			,__( 'Order Slider', 'brasa-slider' )
			,array( $this, 'render_sortable_meta' )
			,'brasa_slider_cpt'
			,'advanced'
			,'core'
			);
	}
	/**
	 * Render search meta
	 * @param object $post
	 * @return write
	 */
	public function render_search_meta($post){
		_e('<input type="text" id="search_brasa_slider" placeholder="Search.. ">','brasa-slider');
		_e('<a id="search-bt-slider" class="button button-primary button-large">Search!</a>','brasa-slider');
		_e('<a class="button button-primary button-large select-image-brasa">Or select image</a>','brasa-slider');
		echo '<div id="brasa_slider_result" data-url="'.home_url().'"></div>';
	}
	/**
	 * Render sortable meta
	 * @param object $post
	 * @return write
	 */
	public function render_sortable_meta($post){
		global $pagenow;

		echo '<input type="text" name="brasa_slider_input" id="brasa_slider_hide" style="display:none">';
		echo '<ul id="brasa_slider_sortable_ul">';
		if ( is_string( $pagenow ) && $pagenow == 'post-new.php' ) {
			_e( '<span class="notice_not_item">No items added to the Slider. Use the \'Search Posts by Name\' to search for items and add to Slider.</span>','brasa-slider');
			echo '</ul>';
			return;
		}
		$ids = get_post_meta( $post->ID, 'brasa_slider_ids', true );
		$ids = explode( ',', $ids );
		$ids = array_filter( $ids );
		if( !empty($ids) && is_array( $ids ) ){
			foreach ($ids as $id) {
				echo '<li class="brasa_slider_item is_item" data-post-id="'.$id.'" id="'.$id.'">';
				echo '<div class="title_item">';
	      		echo get_the_title($id);
	   			echo '</div><!-- title_item -->';
				echo '<div class="thumb_item">';
				if(get_post_type($id) == 'attachment'){
					$image_attributes = wp_get_attachment_image_src($id,'medium',false);
					echo '<img src="'.$image_attributes[0].'">';
				}
				else{
					echo get_the_post_thumbnail($id, 'medium');
				}
			   	echo sprintf(__('<a class="rm-item" data-post-id="%s">Remove this</a>','brasa-slider'),$id);
			    echo '</div><!-- thumb_item -->';
	   		    echo '<div class="container_brasa_link" style="width:70%;margin-left:30%;">';
	      		echo '<label class="link">Link (URL):</label><br>';
	      		echo '<input class="link_brasa_slider" type="text" name="brasa_slider_link_'.$id.'" placeholder="'.__('Link','brasa-slider').'" value="'.esc_url(get_post_meta($post->ID, 'brasa_slider_id'.$id, true )).'">';
	 			echo '</div><!-- container_brasa_link -->';
	   			echo '</li><!-- brasa_slider_item -->';
			}
		} else {
			_e( '<span class="notice_not_item">No items added to the Slider. Use the \'Search Posts by Name\' to search for items and add to Slider.</span>','brasa-slider');
		}
		echo '</ul>';
	}
	/**
	 * Run AJAX posts search
	 * @return null
	 */
	private function ajax_search(){
		$key = $_GET['key'];
	      	/**
			 * The WordPress Query class.
			 * @link http://codex.wordpress.org/Function_Reference/WP_Query
			 *
			 */
	      	$args = array(
				//Type & Status Parameters
	      		'post_type'	=> 'any',
	      		's'			=> $key
	      	);

	      	$query = new WP_Query( $args );

	      	if ( $query->have_posts() ) {
	      		_e( '<h2>Click to select</h2>', 'brasa-slider' );
	      		while ( $query->have_posts() ) {
	      			$query->the_post();
	      			echo '<div class="brasa_slider_item" data-post-id="' . get_the_ID() . '">';
	      			the_post_thumbnail( 'medium' );
	      			echo '<div class="title_item">';
	      			the_title();
	      			echo '</div><!-- .title_item -->';
	      			echo '<div class="container_brasa_link">';
	      			echo '<label>Link:</label><br>';
	      			echo '<input class="link_brasa_slider" type="text" name="brasa_slider_link_' . get_the_ID() . '" placeholder="' . __( 'Link (Destination URL)', 'brasa-slider' ) . '" value="' . get_permalink( get_the_ID() ) . '">';
	      			echo '</div>';
	      			_e('<a class="rm-item" data-post-id="' . get_the_ID() . '">Remove this</a>', 'brasa-slider' );
	      			echo '</div>';
	      		}
	      	}
	      	else{
	      		_e( 'Not found', 'brasa-slider' );
	      	}
	    die();
	}
	/**
	 * Save slider
	 * @param int $post_id
	 * @return null
	 */
	public function save( $post_id ) {
		if( isset( $_POST['brasa_slider_input'] ) ) {
			$ids = esc_textarea( $_POST['brasa_slider_input'] );
			$all_ids = explode( ',', $ids );
			$all_ids = array_filter( $all_ids );
			if( is_array( $all_ids ) && ! empty( $all_ids ) ) {
				update_post_meta( $post_id, 'brasa_slider_ids', $ids );
				foreach ( $all_ids as $id ) {
					update_post_meta( $post_id, 'brasa_slider_id' . $id, esc_url( $_POST['brasa_slider_link_' . $id] ) );
				}
			} else {
			    delete_post_meta( $post_id, 'brasa_slider_ids' );
			}
		}
		/* Delete transients in save_post */
		delete_transient( 'brasa_slider_cache_'	. sanitize_title( get_the_title( $post_id ) ) );
		delete_transient( 'brasa_slider_json_'	. sanitize_title( get_the_title( $post_id ) ) );
	}

	/**
	 * Add slider shortcode
	 * @param array $atts
	 * @return string|null
	 */
	public function shortcode( $atts ) {
		$html = '';
		// Attributes
		$atts = shortcode_atts(
				array(
					'name' => '',
					'size' => '',
					'json' => '',
					'id'   => ''
				), $atts
			);

		if ( $atts[ 'id' ] != '' ) {
			$slider = get_post( $atts[ 'id' ] );
		} else {
			/* Get transient */
			$brasa_slider_transient = get_transient( 'brasa_slider_cache_' . sanitize_title( $atts['name'] ) );
			if ( false === ( $brasa_slider_transient ) ) {
				$slider = get_page_by_title( $atts['name'], OBJECT, 'brasa_slider_cpt' );
				/* Create transient for this slider */
				set_transient( 'brasa_slider_cache_' . sanitize_title( $atts['name'] ), $slider, DAY_IN_SECONDS );
				} else {
					$slider = $brasa_slider_transient;
				}
		}


		$GLOBALS['slider']	= $slider;
		$GLOBALS['atts']	= $atts;

		if ( ! empty( $slider ) && isset( $slider ) ) {
			$html = brasa_slider_get_template_html( 'slider.php' );
		    return $html;
		} else {
			return false;
		}
	}
}
new Brasa_Slider();

// Add class to support Rest API
require_once( BRASA_SLIDER_DIR . 'inc/rest-api-class.php' );
