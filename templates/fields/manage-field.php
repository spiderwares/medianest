<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

foreach ( $fields as $wpmn_field_key => $wpmn_field ) : 
    $wpmn_field_val  = isset( $options[ $wpmn_field_key ] ) ? $options[ $wpmn_field_key ] : ( isset( $wpmn_field['default'] ) ? $wpmn_field['default'] : '' );
    $wpmn_field_type = isset( $wpmn_field['field_type'] ) ? $wpmn_field['field_type'] : '';
?>

<tr class="<?php echo isset( $wpmn_field['extra_class'] ) ? esc_attr( $wpmn_field['extra_class'] ) : ''; ?>"

    <?php if ( isset( $wpmn_field['style'] ) && ! empty( $wpmn_field['style'] ) ) : 
        $wpmn_style = explode( '.', $wpmn_field['style'], 2 ); ?>
        style="<?php echo esc_attr( ( isset( $options[ $wpmn_style[0] ] ) && $options[ $wpmn_style[0] ] == $wpmn_style[1] ) ? '' : 'display: none;' ); ?>"
    <?php endif; ?>>

    <th scope="row" class="wpmn-label <?php echo esc_attr( $wpmn_field_type ); ?>" <?php echo ( $wpmn_field_type === 'wpmntitle' ) ? 'colspan="2"' : ''; ?>>
        <?php echo esc_html( $wpmn_field['title'] ); ?>
    </th>

    <?php
        switch ( $wpmn_field['field_type'] ) :

            case "wpmnswitch":
                wpmn_get_template(
                    'fields/switch-field.php',
                    array(
                        'field'     => $wpmn_field,
                        'field_Val' => $wpmn_field_val,
                        'field_Key' => $wpmn_field_key,
                    ),
                );
                break;

            case "wpmnradio":
                wpmn_get_template(
                    'fields/radio-field.php',
                    array(
                        'field'     => $wpmn_field,
                        'field_Val' => $wpmn_field_val,
                        'field_Key' => $wpmn_field_key,
                    ),
                );
                break;

            case "wpmncheckbox":
                wpmn_get_template(
                    'fields/checkbox-field.php',
                    array(
                        'field'     => $wpmn_field,
                        'field_Val' => $wpmn_field_val,
                        'field_Key' => $wpmn_field_key,
                    ),
                );
                break;

            case "wpmnselect":
                wpmn_get_template(
                    'fields/select-field.php',
                    array(
                        'field'     => $wpmn_field,
                        'field_Val' => $wpmn_field_val,
                        'field_Key' => $wpmn_field_key,
                    ),
                );
                break;

            case "wpmnrequest":
                wpmn_get_template(
                    'fields/clear-data-field.php',
                    array(
                        'field'     => $wpmn_field,
                        'field_Val' => $wpmn_field_val,
                        'field_Key' => $wpmn_field_key,
                    ),
                );
                break;

            case "wpmnexport":
                wpmn_get_template(
                    'fields/export-field.php',
                    array(
                        'field'     => $wpmn_field,
                        'field_Val' => $wpmn_field_val,
                        'field_Key' => $wpmn_field_key,
                    ),
                );
                break;

            case "wpmn_generate_size":
                wpmn_get_template(
                    'fields/export-field.php',
                    array(
                        'field'     => $wpmn_field,
                        'field_Val' => $wpmn_field_val,
                        'field_Key' => $wpmn_field_key,
                    ),
                );
                break;

        endswitch;
    ?>

</tr>

<?php endforeach; ?>
