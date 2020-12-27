<?php

global $usp_options;

if ( ! isset( $usp_options['uspp_public_form_page'] ) ) {
    if ( ! usp_isset_plugin_page( 'uspp-editpage' ) ) {
        $usp_options['uspp_public_form_page'] = usp_create_plugin_page( 'uspp-editpage', [
            'post_title'   => 'Форма публикации',
            'post_content' => '[public-form]',
            'post_name'    => 'uspp-postedit'
            ] );
    }
}

update_site_option( 'usp_global_options', $usp_options );
