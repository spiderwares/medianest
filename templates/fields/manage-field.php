<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

foreach ( $fields as $mddr_field_key => $mddr_field ) : 
    $mddr_field_val  = isset( $options[ $mddr_field_key ] ) ? $options[ $mddr_field_key ] : ( isset( $mddr_field['default'] ) ? $mddr_field['default'] : '' );
    $mddr_field_type = isset( $mddr_field['field_type'] ) ? $mddr_field['field_type'] : '';
?>

<tr class="<?php echo isset( $mddr_field['extra_class'] ) ? esc_attr( $mddr_field['extra_class'] ) : ''; ?>"

    <?php if ( isset( $mddr_field['style'] ) && ! empty( $mddr_field['style'] ) ) : 
        $mddr_style = explode( '.', $mddr_field['style'], 2 ); ?>
        style="<?php echo esc_attr( ( isset( $options[ $mddr_style[0] ] ) && $options[ $mddr_style[0] ] == $mddr_style[1] ) ? '' : 'display: none;' ); ?>"
    <?php endif; ?>>

    <th scope="row" class="mddr-label <?php echo esc_attr( $mddr_field_type ); ?>" <?php echo ( $mddr_field_type === 'mddrtitle' ) ? 'colspan="2"' : ''; ?>>
        <?php echo esc_html( $mddr_field['title'] ); ?>
    </th>

    <?php
        switch ( $mddr_field['field_type'] ) :

            case "mddrswitch":
                mddr_get_template(
                    'fields/switch-field.php',
                    array(
                        'field'     => $mddr_field,
                        'field_Val' => $mddr_field_val,
                        'field_Key' => $mddr_field_key,
                    ),
                );
                break;

            case "mddrradio":
                mddr_get_template(
                    'fields/radio-field.php',
                    array(
                        'field'     => $mddr_field,
                        'field_Val' => $mddr_field_val,
                        'field_Key' => $mddr_field_key,
                    ),
                );
                break;

            case "mddrselect":
                mddr_get_template(
                    'fields/select-field.php',
                    array(
                        'field'     => $mddr_field,
                        'field_Val' => $mddr_field_val,
                        'field_Key' => $mddr_field_key,
                    ),
                );
                break;

            case "mddrbutton":
                mddr_get_template(
                    'fields/button-field.php',
                    array(
                        'field'     => $mddr_field,
                        'field_Val' => $mddr_field_val,
                        'field_Key' => $mddr_field_key,
                    ),
                );
                break;

            case "mddrcheckbox":
                ob_start();
                $mddr_html = ob_get_clean();
    
                // Apply Pro filter only for srwctime field
                echo wp_kses_post( apply_filters( 'mddr_checkbox_field', $mddr_html, $mddr_field, $mddr_field_val, $mddr_field_key ) );
                break;

        endswitch;
    ?>
</tr>

<?php endforeach; ?>
