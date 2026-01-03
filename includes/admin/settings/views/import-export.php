<?php
/**
 * Import/Export Tab: Import/Export
 * Loads the Import/Export section in the plugin settings page.
 * 
 * @package Media Directory
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the import/export fields from the Settings class.
 * @var array $fields Array of import/export settings fields.
 * 
 */
$mddr_fields = MDDR_Settings_Fields::import_export_field();

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
            <?php echo esc_html__( 'Import/Export', 'media-directory' ); ?>
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

            if ( 'mddr_import_folders' === $mddr_field['action'] ) : ?>
                <div>
                    <input type="file" id="mddr_import_file" accept=".csv" class="mddr_import_input" />
                    <button type="button" class="mddr_import_btn" data-action="mddr_import_folders">
                        <?php echo esc_html( $mddr_field['button_text'] ); ?>
                    </button>
                </div>
            <?php else : ?>
                <button type="button" class="mddr_export_btn" data-action="<?php echo esc_attr( $mddr_field['action'] ); ?>">
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

