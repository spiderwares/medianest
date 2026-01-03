<?php
/**
 * Render file for Media Directory Gallery Block
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $mddr_attributes['selectedFolder'] ) ) :
    return '';
endif;

$mddr_attributes = wp_parse_args( $mddr_attributes, array(
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

$mddr_ids = array_map( 'intval', (array) $mddr_attributes['selectedFolder'] );
if ( empty( $mddr_ids ) ) :
    return '';
endif;

// Query Params
$mddr_args = array(
    'post_type'      => 'attachment',
    'posts_per_page' => -1,
    'tax_query'      => array(
        array(
            'taxonomy' => 'mddr_media_folder',
            'field'    => 'term_id',
            'terms'    => $mddr_ids,
            'operator' => 'IN',
            'include_children' => false
        ),
    ),
    'post_status'    => 'inherit',
);

if ( 'file_name' !== $mddr_attributes['sortBy'] ) :
    $mddr_args['orderby'] = sanitize_text_field( $mddr_attributes['sortBy'] );
    $mddr_args['order']   = sanitize_text_field( $mddr_attributes['sortType'] );
endif;

$mddr_query = new \WP_Query( $mddr_args );
$mddr_posts = $mddr_query->get_posts();

if ( 'file_name' === $mddr_attributes['sortBy'] ) :
    usort( $mddr_posts, function( $img1, $img2 ) use ( $mddr_attributes ) {
        $val1 = basename( $img1->guid );
        $val2 = basename( $img2->guid );
        return ( $mddr_attributes['sortType'] === 'ASC' ) ? strcmp( $val1, $val2 ) : strcmp( $val2, $val1 );
    } );
endif;

$mddr_ulClass = 'mddr_block_media_gallery';
switch ( $mddr_attributes['layout'] ) :
    case 'flex':     $mddr_ulClass .= ' wp-block-gallery blocks-gallery-grid'; break;
    case 'grid':     $mddr_ulClass .= ' layout-grid'; break;
    case 'masonry':  $mddr_ulClass .= ' layout-masonry'; break;
    case 'carousel': $mddr_ulClass .= ' layout-carousel'; break;
endswitch;

$mddr_ulClass .= ! empty( $mddr_attributes['className'] ) ? ' ' . esc_attr( $mddr_attributes['className'] ) : '';
$mddr_ulClass .= ' columns-' . esc_attr( $mddr_attributes['columns'] );
$mddr_ulClass .= $mddr_attributes['isCropped'] ? ' is-cropped' : '';
$mddr_ulClass .= $mddr_attributes['hasLightbox'] ? ' is-lightbox' : '';

if ( ! empty( $mddr_attributes['imageHoverAnimation'] ) && 'none' !== $mddr_attributes['imageHoverAnimation'] ) :
    $mddr_ulClass .= ' mddr-block-hover-animation-' . esc_attr( $mddr_attributes['imageHoverAnimation'] );
endif;

$mddr_styles  = '--columns: ' . esc_attr( $mddr_attributes['columns'] ) . ';';
$mddr_styles .= '--space: ' . esc_attr( $mddr_attributes['spaceAroundImage'] ) . 'px;';
$mddr_styles .= '--min-width: ' . esc_attr( $mddr_attributes['imgMinWidth'] ) . 'px;';

$images = [];
foreach ( $posts as $post ) :
    if ( ! wp_attachment_is_image( $post ) ) continue;
    
    $srcKey = ( 'masonry' === $mddr_attributes['layout'] || 'list' === $mddr_attributes['layout'] ) ? 'full' : 'large';
    $imageSrc = wp_get_attachment_image_src( $post->ID, $srcKey );
    
    if ( ! $imageSrc ) continue;

    $href = '';
    switch ( $mddr_attributes['linkTo'] ) :
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
        'caption' => $mddr_attributes['hasCaption'] ? $post->post_excerpt : '',
        'class'   => "wp-image-{$post->ID}"
    );

    if ( $mddr_attributes['hasLightbox'] && empty( $images[ count($images) - 1 ]['link'] ) ) :
         $images[ count($images) - 1 ]['link'] = $imageSrc[0];
    endif;
endforeach;

include __DIR__ . '/media-directory-gallery.php';
