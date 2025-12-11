<?php
/**
 * Upload Folder Selector Template
 *
 * Outputs the HTML for the upload folder selector. JS and CSS are loaded
 * separately from assets.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div id="wpmn_upload_folder_selector" style="display: none;">
    <label for="wpmn_select_upload_folder">
        <?php echo esc_html__( 'Choose folder:', 'medianest' ); ?>
    </label>
    <select id="wpmn_select_upload_folder">
        <option value="all"><?php echo esc_html__( 'All Files', 'medianest' ); ?></option>
        <option value="uncategorized"><?php echo esc_html__( 'Uncategorized', 'medianest' ); ?></option>
    </select>
</div>
