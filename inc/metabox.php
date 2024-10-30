<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
$meta = new Brasa_Slider_Metabox(
    'brasa-slider-metabox', // Metabox slug
    'Configuration', // Metabox name
    'brasa_slider_cpt', // post type
    'side', //location
    'low' //priority
);
$default = '{"dots": true,"infinite": true,"speed": 3000, "autoplay":true, "autoplaySpeed": 5000, "slidesToShow": 1}';
$sizes = array();
$sizes['brasa_slider_img'] = __('Default size','brasa-slider');
$get_intermediate_image_sizes = get_intermediate_image_sizes();
foreach($get_intermediate_image_sizes as $size){
	$sizes[$size] = $size;
}
$meta->set_fields(
    array(
        /**
         * set meta field to active plugin in post
         */
        array(
            'id'          => 'brasa-slider-cfg', // Required
            'label'       => __( 'Configure Slick JS <br>', 'brasa-slider' ), // Required
            'type'        => 'textarea', // Required
            'attributes' => array(
            	'style' => 'display:block;width:100%;'
            ), // Optional (html input elements)
            'default'    => $default, // Optional
            'description' => __( 'Read official Slick website for exemples & documentation: <a href="http://kenwheeler.github.io/slick/">http://kenwheeler.github.io/slick/</a>', 'brasa-slider' ), // Optional
        ),
        array(
           'id'            => 'brasa_slider_size', // Required
           'label'         => __( 'Select image size', 'odin' ), // Required
           'type'          => 'select', // Required
           'default'       => 'brasa_slider_img', // optional
           'options'       => $sizes
        )
    )
);
