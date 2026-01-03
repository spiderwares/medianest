<?php
/**
 *  Button Field Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <p><?php echo isset( $field['desc'] ) ? wp_kses_post( $field['desc'] ) : ''; ?></p>
    
    <?php if ( isset( $field['action'] ) && 'mddr_generate_api_key' === $field['action'] ) : ?>
        <div class="mddr_api_key_wrapper">
            <input type="text"
                id="<?php echo esc_attr( $field_Key ); ?>"
                name="<?php echo esc_attr( $field['name'] ); ?>"
                value="<?php echo esc_attr( $field_Val ); ?>"
                class="regular-text mddr_api_key_input"
                readonly
            />
            <button type="button"class="button button-primary mddr_generate_api_btn"data-action="<?php echo esc_attr( $field['action'] ); ?>">
               <?php echo esc_html( $field['button_text'] ); ?>
            </button>
        </div>

    <?php elseif ( isset( $field['action'] ) && 'mddr_import_folders' === $field['action'] ) : ?>
        <div>
            <input type="file" id="mddr_import_file" accept=".csv" class="mddr_import_input" />
            <button type="button" class="button button-primary mddr_import_btn" data-action="<?php echo esc_attr( $field['action'] ); ?>">
                <?php echo esc_html( $field['button_text'] ); ?>
            </button>
        </div>
    <?php else :
        $mddr_btn_class = isset( $field['btn_class'] ) ? $field['btn_class'] : 'mddr_export_btn'; ?>
        <button type="button" class="button button-primary <?php echo esc_attr( $mddr_btn_class ); ?>" data-action="<?php echo esc_attr( $field['action'] ); ?>">
            <?php echo esc_html( $field['button_text'] ); ?>
        </button>
    <?php endif; ?>
</td>