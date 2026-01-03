<?php 

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="mddr_page mddr_settings_page wrap">

    <h2 class="mddr_notice_wrapper"></h2>

    <!-- Navigation tabs for plugin settings -->
    <div class="mddr_settings_page_nav">
        <h2 class="nav-tab-wrapper">

            <!-- Settings settings tab -->
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cosmic-mddr&tab=general' ) ); ?>" 
               class="<?php echo esc_attr( $active_tab === 'general' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
                <img src="<?php echo esc_url( MDDR_URL . 'assets/img/general.svg'); ?>" />
                <?php echo esc_html__( 'General', 'media-directory' ); ?>
            </a>

            <?php do_action( 'mddr_settings_tabs', $active_tab ); ?>

            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cosmic-mddr&tab=tools' ) ); ?>" 
               class="<?php echo esc_attr( $active_tab === 'tools' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
                <img src="<?php echo esc_url( MDDR_URL . 'assets/img/tools.svg'); ?>" />
                <?php echo esc_html__( 'Tools', 'media-directory' ); ?>
            </a>

            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cosmic-mddr&tab=import-export' ) ); ?>" 
               class="<?php echo esc_attr( $active_tab === 'import-export' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
                <img src="<?php echo esc_url( MDDR_URL . 'assets/img/import-export.svg'); ?>" />
                <?php echo esc_html__( 'Import/Export', 'media-directory' ); ?>
            </a>

        </h2>
    </div>

    <!-- Content area for the active settings tab -->
    <div class="mddr_settings_page_content">
        <?php
        require_once MDDR_PATH . 'includes/admin/settings/views/' . $active_tab . '.php';
        ?>
    </div>
</div>
