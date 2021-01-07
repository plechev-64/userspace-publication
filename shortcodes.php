<?php

add_shortcode( 'public-form', 'uspp_publicform' );
function uspp_publicform( $atts, $content = null ) {

    if ( usp_is_gutenberg() )
        return false;

    $form = new USPP_Public_Form( $atts );

    return $form->get_form();
}
