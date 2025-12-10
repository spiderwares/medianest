<?php
/**
 *  Clear Data Field Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <p><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></p>
    <button type="button" class="button wpmn_clear_data_btn" data-action="<?php echo esc_attr( $field['action'] ); ?>">
        <?php echo esc_html( $field['button_text'] ); ?>
    </button>
</td>