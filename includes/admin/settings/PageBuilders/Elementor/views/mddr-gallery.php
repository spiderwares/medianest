<?php

/**
 * Media Directory Gallery Template for Elementor
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="mddr_gallery mddr_gallery_columns_<?php echo esc_attr( $settings['columns'] ); ?>">
    <?php
    while ( $query->have_posts() ) :
        $query->the_post();
        $mddr_attachment_id = get_the_ID();
        $mddr_image_src     = wp_get_attachment_image_src( $mddr_attachment_id, $settings['size'] );
        $mddr_image_full    = wp_get_attachment_image_src( $mddr_attachment_id, 'full' );
        
        $mddr_link = '';
        switch ( $settings['link_to'] ) :
            case 'file':
                $mddr_link = isset( $mddr_image_full[0] ) ? $mddr_image_full[0] : '';
                break;
            case 'post':
                $mddr_link = get_attachment_link( $mddr_attachment_id );
                break;
        endswitch;
        ?>
        <div class="mddr_gallery_item">
            <?php if ( $mddr_link ) : ?>
                <a href="<?php echo esc_url( $mddr_link ); ?>">
            <?php endif;
            
            if ( $mddr_image_src ) : ?>
                <img src="<?php echo esc_url( $mddr_image_src[0] ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" />
            <?php endif;
            
            if ( $mddr_link ) : ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    endwhile;
    ?>
</div>
