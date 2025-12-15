<?php
/**
 * Template for Medianest Gallery Block
 *
 * @var array $attributes
 * @var array $images (prepared image data)
 * @var string $ulClass
 * @var string $styles
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $images ) ) :
    ?>
    <ul class="<?php echo esc_attr( $ulClass ); ?>" style="<?php echo esc_attr( $styles ); ?>">
        <li>
            <div class="components-notice is-error">
                <div class="components-notice__content">
                    <p><?php esc_html_e( 'This folder has no images, please choose another one.', 'medianest' ); ?></p>
                </div>
            </div>
        </li>
    </ul>
    <?php
    return;
endif;
?>

<ul class="<?php echo esc_attr( $ulClass ); ?>" style="<?php echo esc_attr( $styles ); ?>">
    <?php foreach ( $images as $wpmn_img ) : ?>
        <li class="blocks-gallery-item">
            <figure>
                <?php if ( ! empty( $wpmn_img['link'] ) ) : ?>
                    <a href="<?php echo esc_attr( $wpmn_img['link'] ); ?>" <?php echo ( ! empty( $attributes['hasLightbox'] ) ) ? 'data-size="' . esc_attr( $wpmn_img['width'] . 'x' . $wpmn_img['height'] ) . '" data-title="' . esc_attr( $wpmn_img['title'] ) . '"' : ''; ?>>
                        <img src="<?php echo esc_attr( $wpmn_img['src'] ); ?>" alt="<?php echo esc_attr( $wpmn_img['alt'] ); ?>" class="<?php echo esc_attr( $wpmn_img['class'] ); ?>"/>
                    </a>
                <?php else : ?>
                    <img src="<?php echo esc_attr( $wpmn_img['src'] ); ?>" alt="<?php echo esc_attr( $wpmn_img['alt'] ); ?>" class="<?php echo esc_attr( $wpmn_img['class'] ); ?>"/>
                <?php endif; ?>
                
                <?php if ( ! empty( $wpmn_img['caption'] ) ) : ?>
                    <figcaption class="blocks-gallery-item__caption"><?php echo wp_kses_post( $wpmn_img['caption'] ); ?></figcaption>
                <?php endif; ?>
            </figure>
        </li>
    <?php endforeach; ?>
</ul>
