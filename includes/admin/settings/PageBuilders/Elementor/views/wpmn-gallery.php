<?php

/**
 * MediaNest Gallery Template for Elementor
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="wpmn_gallery wpmn_gallery_columns_<?php echo esc_attr( $settings['columns'] ); ?>">
    <?php
    while ( $query->have_posts() ) :
        $query->the_post();
        $wpmn_attachment_id = get_the_ID();
        $wpmn_image_src     = wp_get_attachment_image_src( $wpmn_attachment_id, $settings['size'] );
        $wpmn_image_full    = wp_get_attachment_image_src( $wpmn_attachment_id, 'full' );
        
        $wpmn_link = '';
        switch ( $settings['link_to'] ) :
            case 'file':
                $wpmn_link = isset( $wpmn_image_full[0] ) ? $wpmn_image_full[0] : '';
                break;
            case 'post':
                $wpmn_link = get_attachment_link( $wpmn_attachment_id );
                break;
        endswitch;
        ?>
        <div class="wpmn_gallery_item">
            <?php if ( $wpmn_link ) : ?>
                <a href="<?php echo esc_url( $wpmn_link ); ?>">
            <?php endif;
            
            if ( $wpmn_image_src ) : ?>
                <img src="<?php echo esc_url( $wpmn_image_src[0] ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" />
            <?php endif;
            
            if ( $wpmn_link ) : ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    endwhile;
    ?>
</div>
