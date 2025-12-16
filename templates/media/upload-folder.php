<?php
/**
 * Upload Folder Selector Template
 *
 * Outputs the HTML for the upload folder selector. JS and CSS are loaded
 * separately from assets.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div id="wpmn_upload_folder_selector">
    <?php $wpmn_labels = WPMN_Helper::wpmn_get_folder_labels(); ?>
    <label>
        <?php echo esc_html__( $wpmn_labels['choose_folder'], 'medianest' ); ?>
    </label>
    <select id="wpmn_select_upload_folder">
        <option value="all"><?php echo esc_html( $wpmn_labels['all'] ); ?></option>
        <option value="uncategorized"><?php echo esc_html( $wpmn_labels['uncategorized'] ); ?></option>
    </select>
</div>
