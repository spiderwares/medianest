<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Radio image field html
 */
?>
<td>
    <?php if ( isset( $field['options'] ) ) : ?>
        <div class="wpmn-radio-field" <?php echo isset( $field['data_hide'] ) ? 'data-hide="' . esc_attr( $field['data_hide'] ) . '"' : ''; ?>>
            <?php foreach ( $field['options'] as $optionKey => $optionImg ) : ?>
                <p class="wpmn-image-control <?php echo in_array( $optionKey, $field['disabled_options'] ?? array() ) ? 'wpmn-disabled-option' : ''; ?>">
                    <input 
                        type="radio" 
                        name="<?php echo esc_attr( $field['name'] ); ?>"
                        value="<?php echo esc_attr( $optionKey ); ?>"
                        id="<?php echo esc_attr( $field['name'] . '_' . $optionKey ); ?>"
                        <?php checked( $optionKey, $field_Val ); ?>
                        <?php echo in_array( $optionKey, $field['disabled_options'] ?? array() ) ? 'disabled' : ''; ?>
                        data-show="<?php echo esc_attr( $field['data_show_map'][ $optionKey ] ?? '' ); ?>"
                    >

                    <label for="<?php echo esc_attr( $field['name'] . '_' . $optionKey ); ?>">
                        <img 
                            width="250" 
                            src="<?php echo esc_url( WPMN_URL . 'assets/img/' . $optionImg ); ?>" 
                            alt="<?php echo esc_attr( $optionKey ); ?>"
                            style="<?php echo in_array( $optionKey, $field['disabled_options'] ?? array() ) ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>"
                        >
                        <?php echo esc_html( $optionKey ); ?>
                    </label>
                </p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</td>

