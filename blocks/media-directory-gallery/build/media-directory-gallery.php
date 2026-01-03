<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $images ) ) :
?>
    <ul class="<?php echo esc_attr( $ulClass ); ?>" style="<?php echo esc_attr( $styles ); ?>">
        <li>
            <div class="components-notice is-error">
                <div class="components-notice__content">
                    <p><?php esc_html_e( 'This folder has no images, please choose another one.', 'media-directory' ); ?></p>
                </div>
            </div>
        </li>
    </ul>
    <?php 
    return; 
endif;
?>

<ul class="<?php echo esc_attr( $ulClass ); ?>" style="<?php echo esc_attr( $styles ); ?>">
    <?php foreach ( $images as $mddr_img ) : ?>
        <li class="mddr_block_gallery_item">
            <figure>
                <?php if ( ! empty( $mddr_img['link'] ) ) : ?>
                    <a href="<?php echo esc_attr( $mddr_img['link'] ); ?>" <?php echo ( ! empty( $attributes['hasLightbox'] ) ) ? 'data-size="' . esc_attr( $mddr_img['width'] . 'x' . $mddr_img['height'] ) . '" data-title="' . esc_attr( $mddr_img['title'] ) . '"' : ''; ?>>
                        <img src="<?php echo esc_attr( $mddr_img['src'] ); ?>" alt="<?php echo esc_attr( $mddr_img['alt'] ); ?>" class="<?php echo esc_attr( $mddr_img['class'] ); ?>"/>
                    </a>
                <?php else : ?>
                    <img src="<?php echo esc_attr( $mddr_img['src'] ); ?>" alt="<?php echo esc_attr( $mddr_img['alt'] ); ?>" class="<?php echo esc_attr( $mddr_img['class'] ); ?>"/>
                <?php endif;
                
                if ( ! empty( $mddr_img['caption'] ) ) : ?>
                    <figcaption class="mddr_block_gallery_item__caption"><?php echo wp_kses_post( $mddr_img['caption'] ); ?></figcaption>
                <?php endif; ?>
            </figure>
        </li>
    <?php endforeach; ?>
</ul>
