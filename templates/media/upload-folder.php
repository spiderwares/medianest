<?php
/**
 * Upload Folder Selector Template
 *
 * @package Media Directory
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="mddr_upload_folder_selector">
    <label>
        <?php echo esc_html( $mddr_labels['choose_folder'], 'media-directory' ); ?>
    </label>
    <select class="mddr_select_upload_folder">
        <option value="all"><?php echo esc_html( $mddr_labels['all'] ); ?></option>
        <option value="uncategorized"><?php echo esc_html( $mddr_labels['uncategorized'] ); ?></option>
    </select>
</div>
