<?php
/**
 * Upload Folder Selector Template
 *
 * @package Medianest
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="wpmn_upload_folder_selector">
    <label>
        <?php echo esc_html( $wpmn_labels['choose_folder'], 'medianest' ); ?>
    </label>
    <select class="wpmn_select_upload_folder">
        <option value="all"><?php echo esc_html( $wpmn_labels['all'] ); ?></option>
        <option value="uncategorized"><?php echo esc_html( $wpmn_labels['uncategorized'] ); ?></option>
    </select>
</div>
