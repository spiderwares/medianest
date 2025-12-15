<?php 
/**
 * Setting Forms Template
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<form method="post" action="options.php" enctype="multipart/form-data">
    <table class="wpmn-form form-table">
        <tr class="heading">
            <th colspan="2">
                <?php echo esc_html( $title ); ?>
            </th>
        </tr>
        <tr>
        <?php
            wpmn_get_template(
                'fields/manage-field.php',
                array(
                    'metaKey' => $metaKey,
                    'fields'  => $fields,
                    'options' => $options,
                ),
            );
        ?>
        </tr>
        <tr class="submit">
            <th colspan="2">
                <?php settings_fields( $metaKey );
                if ( ! isset( $wpmn_show_submit ) ) :
                    $wpmn_show_submit = true;
                endif;  
                if ( $wpmn_show_submit ) :
                    submit_button(); 
                endif;
                settings_errors( 'wpmn_settings' ); ?>
            </th>
        </tr>
    </table>
</form>