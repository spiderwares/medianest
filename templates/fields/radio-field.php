<?php 
/**
 * Radio Image Field HTML - Medianest
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<td>
    <?php if ( isset( $field['options'] ) ) : ?>
        <?php
        $has_image_option = false;
        foreach ( $field['options'] as $optionValue ) {
            $option_is_array = is_array( $optionValue );
            $raw_image       = $option_is_array ? ( $optionValue['img'] ?? '' ) : $optionValue;
            if ( is_string( $raw_image ) && preg_match( '/\.(svg|png|jpe?g|gif|webp)$/i', $raw_image ) ) {
                $has_image_option = true;
                break;
            }
        }
        ?>
        <div class="wpmn_radio_image_field <?php echo $has_image_option ? 'wpmn_radio_image_field_has_image' : 'wpmn_radio_image_field--text'; ?>" 
             <?php echo isset( $field['data_hide'] ) ? 'data-hide="' . esc_attr( $field['data_hide'] ) . '"' : ''; ?>>

            <?php foreach ( $field['options'] as $optionKey => $optionValue ) : 
                $disabled      = isset( $field['disabled_options'] ) && in_array( $optionKey, $field['disabled_options'], true );
                $data_show     = $field['data_show_map'][ $optionKey ] ?? '';
                $input_name    = ( isset( $field['name'] ) && ! empty( $field['name'] ) )
                    ? $field['name']
                    : 'wpmn_settings[' . $field_Key . ']';

                $option_is_array = is_array( $optionValue );
                $raw_image       = $option_is_array ? ( $optionValue['img'] ?? '' ) : $optionValue;
                $is_image_file   = is_string( $raw_image ) && preg_match( '/\.(svg|png|jpe?g|gif|webp)$/i', $raw_image );
                $img_src         = $is_image_file ? WPMN_URL . 'assets/img/' . $raw_image : '';
                $label_text      = $option_is_array
                    ? ( $optionValue['label'] ?? ucfirst( str_replace( '_', ' ', $optionKey ) ) )
                    : ( $is_image_file ? ucfirst( str_replace( '_', ' ', $optionKey ) ) : $optionValue );
                $description     = $option_is_array ? ( $optionValue['description'] ?? '' ) : '';
            ?>
                <p class="wpmn_image_control <?php echo $disabled ? 'wpmn-disabled-option' : ''; ?>">
                    
                    <input 
                        type="radio"
                        name="<?php echo esc_attr( $input_name ); ?>"
                        value="<?php echo esc_attr( $optionKey ); ?>"
                        id="<?php echo esc_attr( $field_Key . '_' . $optionKey ); ?>"
                        <?php checked( $optionKey, $field_Val ); ?>
                        <?php echo $disabled ? 'disabled' : ''; ?>
                        data-show="<?php echo esc_attr( $data_show ); ?>"
                    >

                    <label for="<?php echo esc_attr( $field_Key . '_' . $optionKey ); ?>">
                        <?php if ( $is_image_file ) : ?>
                            <img 
                                width="200" 
                                src="<?php echo esc_url( $img_src ); ?>" 
                                alt="<?php echo esc_attr( $optionKey ); ?>"
                                style="<?php echo $disabled ? 'opacity:0.5; cursor:not-allowed;' : ''; ?>"
                            >
                        <?php endif; ?>
                        <span><?php echo esc_html( $label_text ); ?></span>
                    </label>

                </p>
            <?php endforeach; ?>

        </div>
    <?php endif; ?>
</td>
