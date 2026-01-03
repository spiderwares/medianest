<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Radio image field html
 */
?>
<td>
    <?php if ( isset( $field['options'] ) ) : ?>
        <div class="mddr_radio_field" <?php echo isset( $field['data_hide'] ) ? 'data-hide="' . esc_attr( $field['data_hide'] ) . '"' : ''; ?>>
            <?php foreach ( $field['options'] as $mddr_optionKey => $mddr_optionImg ) : ?>
                <p class="mddr_image_control <?php echo in_array( $mddr_optionKey, $field['disabled_options'] ?? array() ) ? 'mddr_disabled_option' : ''; ?>">
                    <input 
                        type="radio" 
                        name="<?php echo esc_attr( $field['name'] ); ?>"
                        value="<?php echo esc_attr( $mddr_optionKey ); ?>"
                        id="<?php echo esc_attr( $field['name'] . '_' . $mddr_optionKey ); ?>"
                        <?php checked( $mddr_optionKey, $field_Val ); ?>
                        <?php echo in_array( $mddr_optionKey, $field['disabled_options'] ?? array() ) ? 'disabled' : ''; ?>
                        data-show="<?php echo esc_attr( $field['data_show_map'][ $mddr_optionKey ] ?? '' ); ?>"
                    >

                    <label for="<?php echo esc_attr( $field['name'] . '_' . $mddr_optionKey ); ?>">
                        <img 
                            width="250" 
                            src="<?php echo esc_url( MDDR_URL . 'assets/img/' . $mddr_optionImg ); ?>" 
                            alt="<?php echo esc_attr( $mddr_optionKey ); ?>"
                            style="<?php echo in_array( $mddr_optionKey, $field['disabled_options'] ?? array() ) ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>"
                        >
                        <?php echo esc_html( $mddr_optionKey ); ?>
                    </label>
                </p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</td>

