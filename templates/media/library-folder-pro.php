<?php
/**
 * Media Library SideBar Folder Pro Template.
 *
 * @package Medianest
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="wpmn_context_menu_item" data-action="download">
    <img src="<?php echo esc_url( WPMN_URL . 'assets/img/download.svg'); ?>" alt="" class="wpmn_folder_content_download" />
    <span><?php echo esc_html__( 'Download', 'medianest_pro' ); ?></span>
</div>
