<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Radio image field html
 */
?>
<td>
    <?php if ( isset( $field['options'] ) ) : ?>
        <div class="wpmn_radio_field" <?php echo isset( $field['data_hide'] ) ? 'data-hide="' . esc_attr( $field['data_hide'] ) . '"' : ''; ?>>
            <?php foreach ( $field['options'] as $wpmn_optionKey => $wpmn_optionImg ) : ?>
                <p class="wpmn_image_control <?php echo in_array( $wpmn_optionKey, $field['disabled_options'] ?? array() ) ? 'wpmn_disabled_option' : ''; ?>">
                    <input 
                        type="radio" 
                        name="<?php echo esc_attr( $field['name'] ); ?>"
                        value="<?php echo esc_attr( $wpmn_optionKey ); ?>"
                        id="<?php echo esc_attr( $field['name'] . '_' . $wpmn_optionKey ); ?>"
                        <?php checked( $wpmn_optionKey, $field_Val ); ?>
                        <?php echo in_array( $wpmn_optionKey, $field['disabled_options'] ?? array() ) ? 'disabled' : ''; ?>
                        data-show="<?php echo esc_attr( $field['data_show_map'][ $wpmn_optionKey ] ?? '' ); ?>"
                    >

                    <label for="<?php echo esc_attr( $field['name'] . '_' . $wpmn_optionKey ); ?>">
                        <img 
                            width="250" 
                            src="<?php echo esc_url( WPMN_URL . 'assets/img/' . $wpmn_optionImg ); ?>" 
                            alt="<?php echo esc_attr( $wpmn_optionKey ); ?>"
                            style="<?php echo in_array( $wpmn_optionKey, $field['disabled_options'] ?? array() ) ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>"
                        >
                        <?php echo esc_html( $wpmn_optionKey ); ?>
                    </label>
                </p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</td>

