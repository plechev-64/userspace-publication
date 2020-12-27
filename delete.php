<?php

global $usp_options;
unset( $usp_options['user_public_access_recall'] );
unset( $usp_options['id_parent_category'] );
unset( $usp_options['media_downloader_recall'] );
unset( $usp_options['moderation_public_post'] );
unset( $usp_options['info_author_recall'] );
update_site_option( 'rcl_global_options', $usp_options );
?>