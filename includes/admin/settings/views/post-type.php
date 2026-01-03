<?php
/**
 * Post Type Tab: Post Type
 * Loads the Post Type section in the plugin settings page.
 * 
 * @package Media Directory
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the post type fields from the Settings class.
 * @var array $fields Array of post type settings fields.
 * 
 */
$mddr_fields = MDDR_Settings_Fields::post_type_field();

/**
 * Fetch the saved settings from the WordPress options table.
 * @var array|false $options Retrieved settings or false if not set.
 * 
 */
$mddr_options = get_option( 'mddr_settings', [] );
?>

<table class="mddr-form form-table">
    <tr class="heading">
        <th colspan="2">
            <?php echo esc_html__( 'Post Type', 'media-directory' ); ?>
        </th>
    </tr>

    <?php foreach ( $mddr_fields as $mddr_key => $mddr_field ) : 
        $mddr_val  = isset( $mddr_options[ $mddr_key ] ) ? $mddr_options[ $mddr_key ] : ( isset( $mddr_field['default'] ) ? $mddr_field['default'] : '' );
        $mddr_type = isset( $mddr_field['field_type'] ) ? $mddr_field['field_type'] : '';
        if ( $mddr_type === 'mddrcheckbox' ) : 
    ?>

    <tr class="<?php echo esc_attr( $mddr_field['extra_class'] ?? '' ); ?>">
        <th scope="row" class="mddr-label mddrcheckbox">
            <?php echo esc_html( $mddr_field['title'] );
                echo wp_kses_post(
                    apply_filters( 'mddr_checkbox_field', '', $mddr_field, $mddr_val, $mddr_key )
                ); 
            ?>
        </th>
    </tr>
    <?php endif;
    endforeach; ?>

    <tr class="submit">
        <th colspan="2">
            <?php
            settings_fields( 'mddr_settings' );
            submit_button();
            ?>
        </th>
    </tr>
</table>
