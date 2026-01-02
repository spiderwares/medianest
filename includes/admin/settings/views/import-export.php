<?php
/**
 * Import/Export Tab: Import/Export
 * Loads the Import/Export section in the plugin settings page.
 * 
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the import/export fields from the Settings class.
 * @var array $fields Array of import/export settings fields.
 * 
 */
$wpmn_fields = WPMN_Settings_Fields::import_export_field();

/**
 * Fetch the saved settings from the WordPress options table.
 * @var array|false $options Retrieved settings or false if not set.
 * 
 */
$wpmn_options = get_option( 'wpmn_settings', true );
?>

<form method="post" action="options.php" enctype="multipart/form-data">
    <table class="wpmn-form form-table">
        <tr class="heading">
            <th colspan="2">
                <?php echo esc_html__( 'Import/Export', 'medianest' ); ?>
            </th>
        </tr>
        <?php foreach ( $wpmn_fields as $wpmn_key => $wpmn_field ) : 
            $wpmn_val  = isset( $wpmn_options[ $wpmn_key ] ) ? $wpmn_options[ $wpmn_key ] : ( isset( $wpmn_field['default'] ) ? $wpmn_field['default'] : '' );
            $wpmn_type = isset( $wpmn_field['field_type'] ) ? $wpmn_field['field_type'] : '';
        ?>
        <tr class="<?php echo isset( $wpmn_field['extra_class'] ) ? esc_attr( $wpmn_field['extra_class'] ) : ''; ?>">
            <th scope="row" class="wpmn-label <?php echo esc_attr( $wpmn_type ); ?>">
                <?php echo esc_html( $wpmn_field['title'] ); ?>
            </th>
            <td>
                <?php if ( isset( $wpmn_field['desc'] ) ) : ?>
                    <p><?php echo wp_kses_post( $wpmn_field['desc'] ); ?></p>
                <?php endif; ?>

                <?php if ( 'wpmn_import_folders' === $wpmn_field['action'] ) : ?>
                    <div>
                        <input type="file" id="wpmn_import_file" accept=".csv" class="wpmn_import_input" />
                        <button type="button" class="wpmn_import_btn" data-action="wpmn_import_folders">
                            <?php echo esc_html( $wpmn_field['button_text'] ); ?>
                        </button>
                    </div>
                <?php else : ?>
                    <button type="button" class="wpmn_export_btn" data-action="<?php echo esc_attr( $wpmn_field['action'] ); ?>">
                        <?php echo esc_html( $wpmn_field['button_text'] ); ?>
                    </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr class="submit">
            <th colspan="2">
                <?php settings_fields( 'wpmn_settings' ); ?>
            </th>
        </tr>
    </table>
</form>

