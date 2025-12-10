<?php
/**
 *  Switch Field Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <div class="wpmn_switch_field">
        <input type="hidden" name="<?php echo isset( $field['name'] ) && ! empty( $field['name'] ) ? esc_attr( $field['name'] ) : 'wpmn_settings[' . esc_attr( $field_Key ) . ']'; ?>" value="no" />
        <input type="checkbox" 
               id="<?php echo esc_attr( $field_Key ); ?>" 
               name="<?php echo isset( $field['name'] ) ? esc_attr( $field['name'] ) : ''; ?>" 
               value="yes" <?php checked( $field_Val, 'yes' ); ?>
               data-show="<?php echo isset( $field['data_show'] ) ? esc_attr( $field['data_show'] ) : ''; ?>" />
        <label for="<?php echo esc_attr( $field_Key ); ?>">
        <span class="wpmn_switch_icon">
            <svg class="wpmn_icon_on" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" role="img">
				<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
			</svg>
            <svg class="wpmn_icon_off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" role="img">
				<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
			</svg>
        </span>
        </label>
    </div>
    
    <p><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></p>
</td>