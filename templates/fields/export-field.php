<?php
/**
 *  Export Field Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <p><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></p>
    <?php $btn_class = isset( $field['btn_class'] ) ? $field['btn_class'] : 'wpmn_export_btn'; ?>
    <button type="button" class="button button-primary <?php echo esc_attr( $btn_class ); ?>" data-action="<?php echo esc_attr( $field['action'] ); ?>">
        <?php echo esc_html( $field['button_text'] ); ?>
    </button>
</td>
