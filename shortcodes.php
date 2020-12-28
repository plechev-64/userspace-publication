<?php

add_shortcode( 'public-form', 'rcl_publicform' );
function rcl_publicform( $atts, $content = null ) {

    if ( rcl_is_gutenberg() )
        return false;

    $form = new USPP_Public_Form( $atts );

    return $form->get_form();
}
