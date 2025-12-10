<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

foreach ( $fields as $field_Key => $field ) : 
    $field_Val  = isset( $options[ $field_Key ] ) ? $options[ $field_Key ] : ( isset( $field['default'] ) ? $field['default'] : '' ); 
    $field_type = isset( $field[ 'field_type' ] ) ? $field[ 'field_type' ] : ''; ?>

    <tr class="<?php echo isset( $field['extra_class'] ) ? esc_attr( $field['extra_class'] ) : '';  ?>"

        <?php if( isset($field[ 'style' ] ) && !empty( $field[ 'style' ] ) ): 
            $style = explode('.', $field['style'], 2); ?>
            style="<?php echo esc_attr( ( isset( $options[ $style[0] ] ) && $options[ $style[0] ] == $style[1] ) ? '' : 'display: none;' ); ?>" 
        <?php endif; ?> >

        <th scope="row" class="wpmn-label <?php echo esc_attr( $field_type ); ?>" <?php echo ( $field_type === 'wpmntitle' ) ? 'colspan="2"' : ''; ?>>
            <?php echo esc_html( $field['title'] ); ?>
        </th>
        
        <?php
        
            switch ( $field['field_type'] ) :

                case "wpmnswitch":
                    wpmn_get_template(
                        'fields/switch-field.php', 
                        array(
                            'field'     => $field,
                            'field_Val' => $field_Val,
                            'field_Key' => $field_Key,
                        ),
                    );
                    break;

                case "wpmnradio":
                    wpmn_get_template(
                        'fields/radio-field.php', 
                        array(
                            'field'     => $field,
                            'field_Val' => $field_Val,
                            'field_Key' => $field_Key,
                        ),
                    );
                    break;

                case "wpmnposttypes":
                    wpmn_get_template(
                        'fields/post-type-field.php',
                        array(
                            'field'     => $field,
                            'field_Val' => $field_Val,
                            'field_Key' => $field_Key,
                        ),
                    );
                    break;

                case "wpmncheckbox":
                    wpmn_get_template(
                        'fields/checkbox-field.php',
                        array(
                            'field'     => $field,
                            'field_Val' => $field_Val,
                            'field_Key' => $field_Key,
                        ),
                    );
                    break;

                case "wpmnrequest":
                    wpmn_get_template(
                        'fields/clear-data-field.php',
                        array(
                            'field'     => $field,
                            'field_Val' => $field_Val,
                            'field_Key' => $field_Key,
                        ),
                    );
                    break;
                
            endswitch;
        ?>
    </tr>

<?php endforeach; ?>