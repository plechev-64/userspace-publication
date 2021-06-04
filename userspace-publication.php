<?php
/*
  Plugin Name: UserSpace Publication
  Plugin URI: http://user-space.com/
  Description: Functionality of publications on your site.
  Version: 1.0.0
  Author: Plechev Andrey
  Author URI: http://user-space.com/
  Text Domain: userspace-publication
  License: GPLv2 or later (license.txt)
 */


if ( ! defined( 'USPP_PATH' ) ) {
    define( 'USPP_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'USPP_URL' ) ) {
    define( 'USPP_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
}

// set the first settings
register_activation_hook( __FILE__, 'uspp_activate' );
function uspp_activate() {
    // get id publication page
    $publication_page = get_site_option( 'uspp_publication_page' );

    if ( $publication_page && get_post_status( $publication_page ) == 'publish' )
        return;

    /* start create page */

    global $user_ID;

    $args = [
        'post_title'   => __( 'Form of publication', 'userspace-publication' ),
        'post_content' => '[uspp-public-form]',
        'post_name'    => 'uspp-postedit',
        'post_status'  => 'publish',
        'post_author'  => $user_ID,
        'post_type'    => 'page'
    ];

    $page_id = wp_insert_post( $args );

    if ( ! $page_id )
        return;

    // write id publication page
    update_site_option( 'uspp_publication_page', $page_id );

    // UserSpace plugin update new data
    $userspace_options = get_site_option( 'usp_global_options' );

    $userspace_options['uspp_public_form_page'] = $page_id;

    update_site_option( 'usp_global_options', $userspace_options );
}

/**
 * Check if UserSpace is active
 * */
if ( in_array( 'userspace/userspace.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    require_once 'uspp-init.php';
} else {
    add_action( 'admin_notices', 'uspp_plugin_not_install' );
    function uspp_plugin_not_install() {
        ?>
        <div class="notice notice-error">
            <p>Плагин UserSpace не установлен!</p>
            <p>Перейдите на страницу <a href="/wp-admin/plugin-install.php?s=UserSpace&tab=search&type=term">Плагины</a>
                - установите и активируйте плагин UserSpace</p>
        </div>
        <?php
    }

}
