<?php
/**
 * Checkbox Field Template
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <div class="wpmn_checkbox_field">
        <?php if ( isset( $field['options'] ) && is_array( $field['options'] ) ) : ?>

            <?php
            // Get current value
            $wpmn_current_values = isset( $field_Val ) ? $field_Val : ( $field['default'] ?? array() );

            if ( ! is_array( $wpmn_current_values ) ) {
                $wpmn_current_values = array();
            }

            foreach ( $field['options'] as $wpmn_option_key => $wpmn_option_label ) :

                $wpmn_input_name = ! empty( $field['name'] )
                    ? $field['name'] . '[]'
                    : 'wpmn_settings[' . esc_attr( $field_Key ) . '][]';

                $wpmn_checkbox_id = esc_attr( $field_Key . '_' . $wpmn_option_key );
                $wpmn_is_checked  = in_array( $wpmn_option_key, $wpmn_current_values, true );
            ?>

                <div class="wpmn_checkbox_item">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr( $wpmn_checkbox_id ); ?>"
                        name="<?php echo esc_attr( $wpmn_input_name ); ?>"
                        value="<?php echo esc_attr( $wpmn_option_key ); ?>"
                        <?php checked( $wpmn_is_checked ); ?>
                    />

                    <label for="<?php echo esc_attr( $wpmn_checkbox_id ); ?>">
                        <?php echo esc_html( $wpmn_option_label ); ?>
                    </label>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>
    </div>

    <p><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></p>
</td>
