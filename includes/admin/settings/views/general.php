<?php
/**
 * Settings Tab: General
 * Loads the General section in the plugin general page.
 * 
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the general fields from the General class.
 * @var array $fields Array of general fields.
 * 
 */
$wpmn_fields  = WPMN_Settings_Fields::general_field();

/**
 * Fetch the saved general from the WordPress options table.
 * @var array|false $options Retrieved general or false if not set.
 * 
 */
$wpmn_options = get_option( 'wpmn_settings', true );
?>

<form method="post" action="options.php" enctype="multipart/form-data">
    <table class="wpmn-form form-table">
        <tr class="heading">
            <th colspan="2">
                <?php echo esc_html__( 'General', 'medianest' ); ?>
            </th>
        </tr>
        <?php foreach ( $wpmn_fields as $wpmn_key => $wpmn_field ) : 
            $wpmn_val  = isset( $wpmn_options[ $wpmn_key ] ) ? $wpmn_options[ $wpmn_key ] : ( isset( $wpmn_field['default'] ) ? $wpmn_field['default'] : '' );
            $wpmn_type = isset( $wpmn_field['field_type'] ) ? $wpmn_field['field_type'] : '';
        ?>
        <tr class="<?php echo isset( $wpmn_field['extra_class'] ) ? esc_attr( $wpmn_field['extra_class'] ) : ''; ?>"
            <?php if ( isset( $wpmn_field['style'] ) && ! empty( $wpmn_field['style'] ) ) : 
                $wpmn_style = explode( '.', $wpmn_field['style'], 2 ); ?>
                style="<?php echo esc_attr( ( isset( $wpmn_options[ $wpmn_style[0] ] ) && $wpmn_options[ $wpmn_style[0] ] == $wpmn_style[1] ) ? '' : 'display: none;' ); ?>"
            <?php endif; ?>>

            <th scope="row" class="wpmn-label <?php echo esc_attr( $wpmn_type ); ?>" <?php echo ( $wpmn_type === 'wpmntitle' ) ? 'colspan="2"' : ''; ?>>
                <?php echo esc_html( $wpmn_field['title'] ); ?>
            </th>

            <?php if ( $wpmn_type !== 'wpmntitle' ) : ?>
                <td>
                    <?php switch ( $wpmn_type ) :
                        case 'wpmnswitch': ?>
                            <div class="wpmn_switch_field">
                                <input type="hidden" name="<?php echo isset( $wpmn_field['name'] ) ? esc_attr( $wpmn_field['name'] ) : 'wpmn_settings[' . esc_attr( $wpmn_key ) . ']'; ?>" value="no" />
                                <input type="checkbox" id="<?php echo esc_attr( $wpmn_key ); ?>" name="<?php echo isset( $wpmn_field['name'] ) ? esc_attr( $wpmn_field['name'] ) : ''; ?>" value="yes" <?php checked( $wpmn_val, 'yes' ); ?> />
                                <label for="<?php echo esc_attr( $wpmn_key ); ?>"><span class="wpmn_switch_icon">
                                    <svg class="wpmn_icon_on" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                    <svg class="wpmn_icon_off" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                </span></label>
                            </div>
                            <?php break;
                        
                        case 'wpmnradio': ?>
                            <div class="wpmn_radio_field">
                                <?php foreach ( $wpmn_field['options'] as $wpmn_field_option => $wpmn_img ) : 
                                    $wpmn_disabled = isset($wpmn_field['disabled_options']) && in_array($wpmn_field_option, $wpmn_field['disabled_options']); ?>
                                    <div class="wpmn_image_control <?php echo $wpmn_disabled ? 'wpmn_disabled_option' : ''; ?>">
                                        <input type="radio" id="<?php echo esc_attr( $wpmn_key . '_' . $wpmn_field_option ); ?>" name="<?php echo esc_attr( $wpmn_field['name'] ); ?>" value="<?php echo esc_attr( $wpmn_field_option ); ?>" <?php checked( $wpmn_val, $wpmn_field_option ); ?> <?php echo $wpmn_disabled ? 'disabled' : ''; ?> />
                                        <label for="<?php echo esc_attr( $wpmn_key . '_' . $wpmn_field_option ); ?>">
                                            <img width="250" src="<?php echo esc_url( WPMN_URL . 'assets/img/' . $wpmn_img ); ?>" alt="" />
                                            <span><?php echo esc_html( $wpmn_field_option ); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php break;

                        case 'wpmnselect': ?>
                            <div class="wpmn_select">
                                <select name="<?php echo isset( $wpmn_field['name'] ) ? esc_attr( $wpmn_field['name'] ) : ''; ?>">
                                    <?php foreach ( $wpmn_field['options'] as $wpmn_field_option => $wpmn_field_label ) : 
                                        $wpmn_disabled = isset($wpmn_field['disabled_options']) && in_array($wpmn_field_option, $wpmn_field['disabled_options']); ?>
                                        <option value="<?php echo esc_attr( $wpmn_field_option ); ?>" <?php selected( $wpmn_val, $wpmn_field_option ); ?> <?php echo $wpmn_disabled ? 'disabled' : ''; ?>><?php echo esc_html( $wpmn_field_label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php break;
                    endswitch; ?>
                    <?php if ( isset( $wpmn_field['desc'] ) ) : ?>
                        <p class="description"><?php echo wp_kses_post( $wpmn_field['desc'] ); ?></p>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <tr class="submit">
            <th colspan="2">
                <?php settings_fields( 'wpmn_settings' );
                submit_button(); ?>
            </th>
        </tr>
    </table>
</form>
<?php