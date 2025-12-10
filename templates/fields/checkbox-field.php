<?php
/**
 *  Checkbox Field Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <div class="wpmn_checkbox_field">
        <?php if ( isset( $field['options'] ) && is_array( $field['options'] ) ) : ?>
            <?php 
            // Get current value
            $current_values = isset( $field_Val ) ? $field_Val : ( isset( $field['default'] ) ? $field['default'] : array() );
            if ( ! is_array( $current_values ) ) {
                $current_values = array();
            }

            foreach ( $field['options'] as $option_key => $option_label ) : 
                $input_name = isset( $field['name'] ) && ! empty( $field['name'] ) 
                    ? $field['name'] . '[]' 
                    : 'wpmn_settings[' . esc_attr( $field_Key ) . '][]';
                
                $checkbox_id = esc_attr( $field_Key . '_' . $option_key );
                $is_checked = in_array( $option_key, $current_values, true );
            ?>
                <div class="wpmn_checkbox_item">
                    <input 
                        type="checkbox"
                        id="<?php echo $checkbox_id; ?>"
                        name="<?php echo esc_attr( $input_name ); ?>"
                        value="<?php echo esc_attr( $option_key ); ?>"
                        <?php checked( $is_checked ); ?>
                    />
                    <label for="<?php echo $checkbox_id; ?>">
                        <?php echo esc_html( $option_label ); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <p><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></p>
</td>
