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
        $attachment_id = get_the_ID();
        $image_src     = wp_get_attachment_image_src( $attachment_id, $settings['size'] );
        $image_full    = wp_get_attachment_image_src( $attachment_id, 'full' );
        
        $link = '';
        switch ( $settings['link_to'] ) :
            case 'file':
                $link = $image_full[0] ?? '';
                break;
            case 'post':
                $link = get_attachment_link( $attachment_id );
                break;
        endswitch;
        ?>
        <div class="wpmn_gallery_item">
            <?php if ( $link ) : ?>
                <a href="<?php echo esc_url( $link ); ?>">
            <?php endif;
            
            if ( $image_src ) : ?>
                <img src="<?php echo esc_url( $image_src[0] ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" />
            <?php endif;
            
            if ( $link ) : ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    endwhile;
    ?>
</div>
