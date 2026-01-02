<?php
/**
 * Post Type Tab: Post Type
 * Loads the Post Type section in the plugin settings page.
 * 
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the post type fields from the Settings class.
 * @var array $fields Array of post type settings fields.
 * 
 */
$wpmn_fields = WPMN_Settings_Fields::post_type_field();

/**
 * Fetch the saved settings from the WordPress options table.
 * @var array|false $options Retrieved settings or false if not set.
 * 
 */
$wpmn_options = get_option( 'wpmn_settings', true );
?>

<table class="wpmn-form form-table">
    <tr class="heading">
        <th colspan="2">
            <?php echo esc_html__( 'Post Type', 'medianest' ); ?>
        </th>
    </tr>

    <?php foreach ( $wpmn_fields as $wpmn_key => $wpmn_field ) : 
        $wpmn_val  = isset( $wpmn_options[ $wpmn_key ] ) ? $wpmn_options[ $wpmn_key ] : ( isset( $wpmn_field['default'] ) ? $wpmn_field['default'] : '' );
        $wpmn_type = isset( $wpmn_field['field_type'] ) ? $wpmn_field['field_type'] : '';
        if ( $wpmn_type === 'wpmncheckbox' ) : 
    ?>

    <tr class="<?php echo esc_attr( $wpmn_field['extra_class'] ?? '' ); ?>">
        <th scope="row" class="wpmn-label wpmncheckbox">
            <?php echo esc_html( $wpmn_field['title'] );
                echo wp_kses_post(
                    apply_filters( 'wpmn_checkbox_field', '', $wpmn_field, $wpmn_val, $wpmn_key )
                ); 
            ?>
        </th>
    </tr>
    <?php endif;
    endforeach; ?>

    <tr class="submit">
        <th colspan="2">
            <?php
            settings_fields( 'wpmn_settings' );
            submit_button();
            ?>
        </th>
    </tr>
</table>
