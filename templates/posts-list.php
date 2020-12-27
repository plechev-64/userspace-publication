<?php

/*  Шаблон базового дополнения PublicPost (Публикация) http://user-space.com/
  Если вам нужно внести изменения в данный шаблон - скопируйте его в папку /wp-content/userspace/templates/
  - сделайте там в нём нужные вам изменения и он будет подключаться оттуда
  Подробно работа с шаблонами описана тут: http://user-space.com/
 */
?>
<?php global $post, $posts, $ratings; ?>

<?php

RCL()->use_module( 'table' );

$Table = new Rcl_Table( array(
    'cols'   => array(
        array(
            'align' => 'center',
            'title' => __( 'Date', 'usp-publication' ),
            'width' => 15
        ),
        array(
            'title' => __( 'Title', 'usp-publication' ),
            'width' => 65
        ),
        array(
            'align' => 'center',
            'title' => __( 'Status', 'usp-publication' ),
            'width' => 20
        )
    ),
    'zebra'  => true,
    'class'  => 'rcl_author_postlist',
    'border' => array( 'table', 'cols', 'rows' )
    ) );
?>


<?php foreach ( $posts as $postdata ) { ?>
    <?php

    foreach ( $postdata as $post ) {
        setup_postdata( $post );
        ?>
        <?php

        if ( $post->post_status == 'pending' )
            $status           = '<span class="status-pending">' . __( 'to be approved', 'usp-publication' ) . '</span>';
        elseif ( $post->post_status == 'trash' )
            $status           = '<span class="status-pending">' . __( 'deleted', 'usp-publication' ) . '</span>';
        elseif ( $post->post_status == 'draft' )
            $status           = '<span class="status-draft">' . __( 'draft', 'usp-publication' ) . '</span>';
        else
            $status           = '<span class="status-publish">' . __( 'published', 'usp-publication' ) . '</span>';
        ?>

        <?php $content          = ''; ?>
        <?php if ( empty( $post->post_title ) ) $post->post_title = "<i class='uspi fa-horizontal-ellipsis' aria-hidden='true'></i>"; ?>
        <?php $content          .= ($post->post_status == 'trash') ? $post->post_title : '<a target="_blank" href="' . $post->guid . '">' . $post->post_title . '</a>'; ?>

        <?php

        if ( function_exists( 'rcl_format_rating' ) ) {
            $rtng    = (isset( $ratings[$post->ID] )) ? $ratings[$post->ID] : 0;
            $content .= rcl_rating_block( array( 'value' => $rtng ) );
        }
        ?>
        <?php $content .= apply_filters( 'uspp_content_postslist', '' ); ?>

        <?php

        $Table->add_row( array(
            mysql2date( 'd.m.y', $post->post_date ),
            $content,
            $status
        ) );
        ?>

    <?php } ?>
<?php } ?>

<?php echo $Table->get_table(); ?>

