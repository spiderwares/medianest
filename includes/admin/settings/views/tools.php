<?php
/**
 * Tools Tab: Tools
 * Loads the Tools section in the plugin settings page.
 * 
 * @package Media Directory
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the tools fields from the Tools class.
 * @var array $fields Array of email api settings fields.
 * 
 */
$mddr_fields = MDDR_Settings_Fields::tools_field();

/**
 * Fetch the saved settings from the WordPress options table.
 * @var array|false $options Retrieved settings or false if not set.
 * 
 */
$mddr_options = get_option( 'mddr_settings', true );
?>

<table class="mddr-form form-table">
    <tr class="heading">
        <th colspan="2">
            <?php echo esc_html__( 'Tools', 'media-directory' ); ?>
        </th>
    </tr>
    <?php foreach ( $mddr_fields as $mddr_key => $mddr_field ) : 
        $mddr_val  = isset( $mddr_options[ $mddr_key ] ) ? $mddr_options[ $mddr_key ] : ( isset( $mddr_field['default'] ) ? $mddr_field['default'] : '' );
        $mddr_type = isset( $mddr_field['field_type'] ) ? $mddr_field['field_type'] : '';
    ?>
    <tr class="<?php echo isset( $mddr_field['extra_class'] ) ? esc_attr( $mddr_field['extra_class'] ) : ''; ?>">
        <th scope="row" class="mddr-label <?php echo esc_attr( $mddr_type ); ?>">
            <?php echo esc_html( $mddr_field['title'] ); ?>
        </th>
        <td>
            <?php if ( isset( $mddr_field['desc'] ) ) : ?>
                <p><?php echo wp_kses_post( $mddr_field['desc'] ); ?></p>
            <?php endif;

            if ( 'mddr_generate_api_key' === $mddr_field['action'] ) : ?>
                <div class="mddr_api_key_wrapper">
                    <input type="text" id="<?php echo esc_attr( $mddr_key ); ?>" name="<?php echo esc_attr( $mddr_field['name'] ); ?>" value="<?php echo esc_attr( $mddr_val ); ?>" class="regular-text mddr_api_key_input" readonly />
                    <button type="button" class="mddr_generate_api_btn" data-action="mddr_generate_api_key"><?php echo esc_html( $mddr_field['button_text'] ); ?></button>
                </div>
            <?php else :
                $mddr_btn_class = isset( $mddr_field['btn_class'] ) ? $mddr_field['btn_class'] : 'mddr_export_btn'; ?>
                <button type="button" class="<?php echo esc_attr( $mddr_btn_class ); ?>" data-action="<?php echo esc_attr( $mddr_field['action'] ); ?>">
                    <?php echo esc_html( $mddr_field['button_text'] ); ?>
                </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr class="submit">
        <th colspan="2">
            <?php settings_fields( 'mddr_settings' ); ?>
        </th>
    </tr>
</table>