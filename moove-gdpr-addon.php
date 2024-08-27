<?php
/**
 *  Contributors: mooveagency
 *  Stable tag:
 *  Plugin Name: GDPR Cookie Compliance - Premium Add-On
 *  Plugin URI: https://www.mooveagency.com/wordpress-plugins/gdpr-cookie-compliance/
 *  Description: This Premium Add-on gives you additional powerful features that extend our GDPR Cookie Compliance plugin.
 *  Version: 3.3.0
 *  Author: Moove Agency
 *  Author URI: https://www.mooveagency.com
 *  License: GPLv3
 *  Text Domain: gdpr-cookie-compliance-addon
 */

define( 'GDPR_ADDON_VERSION', '3.3.0' );

register_activation_hook( __FILE__, 'moove_gdpr_addon_activate' );
register_deactivation_hook( __FILE__, 'moove_gdpr_addon_deactivate' );

/**
 * Functions on plugin activation, create relevant pages and defaults for settings page.
 */
function moove_gdpr_addon_activate() {

}

function gdpr_addon_get_activation_key( $option_key ) {
	$value = get_option( $option_key );
	if ( is_multisite() && ! $value ) :
		$_value = function_exists( 'get_site_option' ) ? get_site_option( $option_key ) : false;
		if ( $_value ) :
			$main_blog_id = get_main_site_id();
			if ( $main_blog_id ) :
				switch_to_blog( $main_blog_id );
				update_option(
					$option_key,
					$_value
				);
				restore_current_blog();
				delete_site_option( $option_key );
				$value = $_value;
			endif;
		endif;
	endif;	
	return $value;
}

/**
 * Function on plugin deactivation.
 */
function moove_gdpr_addon_deactivate() {
	try {
		if ( class_exists( 'Moove_GDPR_License_Manager' ) ) :
			$gdpr_default_content = new Moove_GDPR_Content();
			$option_key           = $gdpr_default_content->moove_gdpr_get_key_name();
			$gdpr_key             = gdpr_addon_get_activation_key( $option_key );

			if ( $gdpr_key && isset( $gdpr_key['key'] ) && isset( $gdpr_key['activation'] ) ) :
				$license_manager  		= new Moove_GDPR_License_Manager();
				$validate_license 		= $license_manager->validate_license( $gdpr_key['key'], 'gdpr', 'deactivate' );
				if ( $validate_license && isset( $validate_license['valid'] ) && true === $validate_license['valid'] ) :
					update_option(
						$option_key,
						array(
							'key'          => $gdpr_key['key'],
							'deactivation' => strtotime( 'now' ),
						)
					);
				endif;
			endif;
		endif;
	} catch (Exception $e) {
		
	}
	
}

/**
 * Loading Text Domain - for translations & localizations
 */
add_action( 'plugins_loaded', 'moove_gdpr_addon_load_textdomain' );
/**
 * Loading text domain
 */
function moove_gdpr_addon_load_textdomain() {
	load_plugin_textdomain( 'gdpr-cookie-compliance-addon', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Plugin Detail link
 */
if ( ! function_exists( 'gdpr_cookie_addon_add_plugin_meta_links' ) ) {
	/**
	 * Meta links visible in plugins page
	 *
	 * @param array  $meta_fields Meta fields.
	 * @param string $file Plugin path.
	 */
	function gdpr_cookie_addon_add_plugin_meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( plugin_basename( __FILE__ ) === $plugin_file ) :
			if ( is_array( $plugin_meta ) ) :
				unset( $plugin_meta[ count( $plugin_meta ) -1 ] );
				$plugin_meta[] = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $plugin_data['PluginURI'] ),
					__( 'Visit plugin site' )
				);
			endif;
		endif;
		return $plugin_meta;
	}
}
add_filter( 'plugin_row_meta', 'gdpr_cookie_addon_add_plugin_meta_links', 10, 4 );


add_action( 'gdpr_plugin_loaded', 'gdpr_cookie_compliance_addon_load_libs' );
function gdpr_cookie_compliance_addon_load_libs() {
	if ( ! function_exists( 'moove_gdpr_addon_get_plugin_dir' ) ) :
		function moove_gdpr_addon_get_plugin_dir() {
			return plugins_url( basename( dirname( __FILE__ ) ) );
		}
  endif;

	include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'moove-gdpr-addon-actions.php';
	include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'moove-gdpr-addon-controller.php';
	include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'moove-gdpr-addon-analytics.php';
	include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'moove-gdpr-addon-view.php';
	include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'moove-gdpr-addon-geoip.php';
	include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'class-moove-gdpr-updater.php';
	include_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'class-moove-gdpr-consent-log.php';
}

// Make plugin ready for client-facing projects by removing admin notices
add_action(
	'admin_head',
	function() {
		?>
		<style>
		a[href*="moove-gdpr_licence"],
		a[href*="moove-gdpr_support"],
		#moove_form_checker_wrap a[href*="moove-gdpr_licence"],
		a[href*="support.mooveagency.com/forum/gdpr-cookie-compliance"],
		.gdpr_premium_buy_link,
		.gdpr-cookie-update-alert,
		.gdpr-locked-section {
			display: none !important;
		}
		</style>
		<?php
	}
);

// Allow users to user their license slots
add_action(
	'plugins_loaded',
	function() {
		do_action( 'gdpr_plugin_loaded' );
	}
);
