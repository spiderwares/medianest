<?php
/**
 * Render file for Media Directory Gallery Block
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $attributes['selectedFolder'] ) ) :
    return '';
endif;

$attributes = wp_parse_args( $attributes, array(
    'sortBy'              => 'date',
    'sortType'            => 'DESC',
    'layout'              => 'flex',
    'columns'             => 3,
    'borderRadius'        => 0,
    'isCropped'           => true,
    'className'           => '',
    'spaceAroundImage'    => 10,
    'imgMinWidth'         => 200,
    'hasCaption'          => false,
    'hasLightbox'         => false,
    'linkTo'              => 'none',
    'imageHoverAnimation' => 'none'
) );

$ids = array_map( 'intval', (array) $attributes['selectedFolder'] );
if ( empty( $ids ) ) :
    return '';
endif;

// Query Params
$args = array(
    'post_type'      => 'attachment',
    'posts_per_page' => -1,
    'tax_query'      => array(
        array(
            'taxonomy' => 'mddr_media_folder',
            'field'    => 'term_id',
            'terms'    => $ids,
            'operator' => 'IN',
            'include_children' => false
        ),
    ),
    'post_status'    => 'inherit',
);

if ( 'file_name' !== $attributes['sortBy'] ) :
    $args['orderby'] = sanitize_text_field( $attributes['sortBy'] );
    $args['order']   = sanitize_text_field( $attributes['sortType'] );
endif;

$query = new \WP_Query( $args );
$posts = $query->get_posts();

if ( 'file_name' === $attributes['sortBy'] ) :
    usort( $posts, function( $img1, $img2 ) use ( $attributes ) {
        $val1 = basename( $img1->guid );
        $val2 = basename( $img2->guid );
        return ( $attributes['sortType'] === 'ASC' ) ? strcmp( $val1, $val2 ) : strcmp( $val2, $val1 );
    } );
endif;

$ulClass = 'mddr_block_media_gallery';
switch ( $attributes['layout'] ) :
    case 'flex':     $ulClass .= ' wp-block-gallery blocks-gallery-grid'; break;
    case 'grid':     $ulClass .= ' layout-grid'; break;
    case 'masonry':  $ulClass .= ' layout-masonry'; break;
    case 'carousel': $ulClass .= ' layout-carousel'; break;
endswitch;

$ulClass .= ! empty( $attributes['className'] ) ? ' ' . esc_attr( $attributes['className'] ) : '';
$ulClass .= ' columns-' . esc_attr( $attributes['columns'] );
$ulClass .= $attributes['isCropped'] ? ' is-cropped' : '';
$ulClass .= $attributes['hasLightbox'] ? ' is-lightbox' : '';

if ( ! empty( $attributes['imageHoverAnimation'] ) && 'none' !== $attributes['imageHoverAnimation'] ) :
    $ulClass .= ' mddr-block-hover-animation-' . esc_attr( $attributes['imageHoverAnimation'] );
endif;

$styles  = '--columns: ' . esc_attr( $attributes['columns'] ) . ';';
$styles .= '--space: ' . esc_attr( $attributes['spaceAroundImage'] ) . 'px;';
$styles .= '--min-width: ' . esc_attr( $attributes['imgMinWidth'] ) . 'px;';

$images = [];
foreach ( $posts as $post ) :
    if ( ! wp_attachment_is_image( $post ) ) continue;
    
    $srcKey = ( 'masonry' === $attributes['layout'] || 'list' === $attributes['layout'] ) ? 'full' : 'large';
    $imageSrc = wp_get_attachment_image_src( $post->ID, $srcKey );
    
    if ( ! $imageSrc ) continue;

    $href = '';
    switch ( $attributes['linkTo'] ) :
        case 'media':      $href = $imageSrc[0]; break;
        case 'attachment': $href = get_attachment_link( $post->ID ); break;
    endswitch;

    $alt = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
    $alt = empty( $alt ) ? $post->post_title : $alt;

    $images[] = array(
        'id'      => $post->ID,
        'title'   => get_the_title( $post->ID ),
        'src'     => $imageSrc[0],
        'width'   => $imageSrc[1],
        'height'  => $imageSrc[2],
        'alt'     => $alt,
        'link'    => $href,
        'caption' => $attributes['hasCaption'] ? $post->post_excerpt : '',
        'class'   => "wp-image-{$post->ID}"
    );

    if ( $attributes['hasLightbox'] && empty( $images[ count($images) - 1 ]['link'] ) ) :
         $images[ count($images) - 1 ]['link'] = $imageSrc[0];
    endif;
endforeach;

include __DIR__ . '/media-directory-gallery.php';
