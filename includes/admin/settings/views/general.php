<?php
/**
 * Settings Tab: General
 * Loads the General section in the plugin general page.
 * 
 * @package Media Directory
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the general fields from the General class.
 * @var array $fields Array of general fields.
 * 
 */
$mddr_fields  = MDDR_Settings_Fields::general_field();

/**
 * Fetch the saved general from the WordPress options table.
 * @var array|false $options Retrieved general or false if not set.
 * 
 */
$mddr_options = get_option( 'mddr_settings', [] );
?>

<table class="mddr-form form-table">
    <tr class="heading">
        <th colspan="2">
            <?php echo esc_html__( 'General', 'media-directory' ); ?>
        </th>
    </tr>
    <?php foreach ( $mddr_fields as $mddr_key => $mddr_field ) : 
        $mddr_val  = isset( $mddr_options[ $mddr_key ] ) ? $mddr_options[ $mddr_key ] : ( isset( $mddr_field['default'] ) ? $mddr_field['default'] : '' );
        $mddr_type = isset( $mddr_field['field_type'] ) ? $mddr_field['field_type'] : '';
    ?>
    <tr class="<?php echo isset( $mddr_field['extra_class'] ) ? esc_attr( $mddr_field['extra_class'] ) : ''; ?>"
        <?php if ( isset( $mddr_field['style'] ) && ! empty( $mddr_field['style'] ) ) : 
            $mddr_style = explode( '.', $mddr_field['style'], 2 ); ?>
            style="<?php echo esc_attr( ( isset( $mddr_options[ $mddr_style[0] ] ) && $mddr_options[ $mddr_style[0] ] == $mddr_style[1] ) ? '' : 'display: none;' ); ?>"
        <?php endif; ?>>

        <th scope="row" class="mddr-label <?php echo esc_attr( $mddr_type ); ?>" <?php echo ( $mddr_type === 'mddrtitle' ) ? 'colspan="2"' : ''; ?>>
            <?php echo esc_html( $mddr_field['title'] ); ?>
        </th>

        <?php if ( $mddr_type !== 'mddrtitle' ) : ?>
            <td>
                <?php switch ( $mddr_type ) :
                    case 'mddrswitch': ?>
                        <div class="mddr_switch_field">
                            <input type="hidden" name="<?php echo isset( $mddr_field['name'] ) ? esc_attr( $mddr_field['name'] ) : 'mddr_settings[' . esc_attr( $mddr_key ) . ']'; ?>" value="no" />
                            <input type="checkbox" id="<?php echo esc_attr( $mddr_key ); ?>" name="<?php echo isset( $mddr_field['name'] ) ? esc_attr( $mddr_field['name'] ) : ''; ?>" value="yes" <?php checked( $mddr_val, 'yes' ); ?> />
                            <label for="<?php echo esc_attr( $mddr_key ); ?>"><span class="mddr_switch_icon">
                                <svg class="mddr_icon_on" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                <svg class="mddr_icon_off" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </span></label>
                        </div>
                        <?php break;
                    
                    case 'mddrradio': ?>
                        <div class="mddr_radio_field">
                            <?php foreach ( $mddr_field['options'] as $mddr_field_option => $mddr_img ) : 
                                $mddr_disabled = isset($mddr_field['disabled_options']) && in_array($mddr_field_option, $mddr_field['disabled_options']); ?>
                                <div class="mddr_image_control <?php echo $mddr_disabled ? 'mddr_disabled_option' : ''; ?>">
                                    <input type="radio" id="<?php echo esc_attr( $mddr_key . '_' . $mddr_field_option ); ?>" name="<?php echo esc_attr( $mddr_field['name'] ); ?>" value="<?php echo esc_attr( $mddr_field_option ); ?>" <?php checked( $mddr_val, $mddr_field_option ); ?> <?php echo $mddr_disabled ? 'disabled' : ''; ?> />
                                    <label for="<?php echo esc_attr( $mddr_key . '_' . $mddr_field_option ); ?>">
                                        <img width="250" src="<?php echo esc_url( MDDR_URL . 'assets/img/' . $mddr_img ); ?>" alt="" />
                                        <span><?php echo esc_html( $mddr_field_option ); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php break;

                    case 'mddrselect': ?>
                        <div class="mddr_select">
                            <select name="<?php echo isset( $mddr_field['name'] ) ? esc_attr( $mddr_field['name'] ) : ''; ?>">
                                <?php foreach ( $mddr_field['options'] as $mddr_field_option => $mddr_field_label ) : 
                                    $mddr_disabled = isset($mddr_field['disabled_options']) && in_array($mddr_field_option, $mddr_field['disabled_options']); ?>
                                    <option value="<?php echo esc_attr( $mddr_field_option ); ?>" <?php selected( $mddr_val, $mddr_field_option ); ?> <?php echo $mddr_disabled ? 'disabled' : ''; ?>><?php echo esc_html( $mddr_field_label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php break;
                endswitch; ?>
                <?php if ( isset( $mddr_field['desc'] ) ) : ?>
                    <p class="description"><?php echo wp_kses_post( $mddr_field['desc'] ); ?></p>
                <?php endif; ?>
            </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <tr class="submit">
        <th colspan="2">
            <?php settings_fields( 'mddr_settings' );
            submit_button(); ?>
        </th>
    </tr>
</table>

