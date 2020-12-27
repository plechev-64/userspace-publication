<?php
/*
  Plugin Name: UserSpace Publication
  Plugin URI: https://userspace.com/
  Description: Функционал публикаций на вашем сайте.
  Version: 1.0.0
  Author: Plechev Andrey
  Author URI: https://codeseller.ru/
  Text Domain: usp-publication
  License: GPLv2 or later (license.txt)
 */

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