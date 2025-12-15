<?php
/**
 *  Button Field Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <p><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></p>
    
    <?php if ( isset( $field['action'] ) && 'wpmn_generate_api_key' === $field['action'] ) : ?>
        <div class="wpmn_api_key_wrapper">
            <input type="text"
                id="<?php echo esc_attr( $field_Key ); ?>"
                name="<?php echo esc_attr( $field['name'] ); ?>"
                value="<?php echo esc_attr( $field_Val ); ?>"
                class="regular-text wpmn_api_key_input"
                readonly
            />
            <button type="button"class="button button-primary wpmn_generate_api_btn"data-action="<?php echo esc_attr( $field['action'] ); ?>">
               <?php echo esc_html( $field['button_text'] ); ?>
            </button>
        </div>

    <?php elseif ( isset( $field['action'] ) && 'wpmn_import_folders' === $field['action'] ) : ?>
        <div>
            <input type="file" id="wpmn_import_file" accept=".csv" class="wpmn_import_input" />
            <button type="button" class="button button-primary wpmn_import_btn" data-action="<?php echo esc_attr( $field['action'] ); ?>">
                <?php echo esc_html( $field['button_text'] ); ?>
            </button>
        </div>
    <?php else :
        $wpmn_btn_class = isset( $field['btn_class'] ) ? $field['btn_class'] : 'wpmn_export_btn'; ?>
        <button type="button" class="button button-primary <?php echo esc_attr( $wpmn_btn_class ); ?>" data-action="<?php echo esc_attr( $field['action'] ); ?>">
            <?php echo esc_html( $field['button_text'] ); ?>
        </button>
    <?php endif; ?>
</td>