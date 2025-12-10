<?php 

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="wpmn_page wpmn_settings_page wrap">

    <h2 class="wpmn_notice_wrapper"></h2>

    <!-- Navigation tabs for plugin settings -->
    <div class="wpmn_settings_page_nav">
        <h2 class="nav-tab-wrapper">

            <!-- Settings settings tab -->
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cosmic-wpmn&tab=settings' ) ); ?>" 
               class="<?php echo esc_attr( $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
                <img src="<?php echo esc_url( WPMN_URL . 'assets/img/setting.svg'); ?>" />
                <?php echo esc_html__( 'Settings', 'medianest' ); ?>
            </a>

            <a href="<?php echo esc_url( admin_url( 'admin.php?page=cosmic-wpmn&tab=tools' ) ); ?>" 
               class="<?php echo esc_attr( $active_tab === 'tools' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
                <img src="<?php echo esc_url( WPMN_URL . 'assets/img/tools.svg'); ?>" />
                <?php echo esc_html__( 'Tools', 'medianest' ); ?>
            </a>

        </h2>
    </div>

    <!-- Content area for the active settings tab -->
    <div class="wpmn_settings_page_content">
        <?php
        // Load the content for the currently active tab dynamically.
        require_once WPMN_PATH . 'includes/admin/settings/views/' . $active_tab . '.php';
        ?>
    </div>
</div>
