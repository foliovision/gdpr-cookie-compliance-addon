<?php 
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	} // Exit if accessed directly

	$gdpr_default_content = new Moove_GDPR_Content();
	$option_name          = $gdpr_default_content->moove_gdpr_get_option_name();
	$gdpr_options         = get_option( $option_name );
	$wpml_lang            = $gdpr_default_content->moove_gdpr_get_wpml_lang();
	$gdpr_options         = is_array( $gdpr_options ) ? $gdpr_options : array();
if ( isset( $_POST ) && isset( $_POST['moove_gdpr_nonce'] ) ) :
	$nonce = sanitize_key( $_POST['moove_gdpr_nonce'] );
	if ( ! wp_verify_nonce( $nonce, 'moove_gdpr_nonce_field' ) ) :
		die( 'Security check' );
		else :

			if ( isset( $_POST['gdpr_google_site_kit'] ) && is_array( $_POST['gdpr_google_site_kit'] ) ) :
				$gdpr_gsk_values = array();
				foreach ( $_POST['gdpr_google_site_kit'] as $_gsk_slug ) :
					$gsk_slug 	= sanitize_text_field( wp_unslash( $_gsk_slug ) );
					$gsk_value 	= isset( $_POST['gdpr_google_site_kit_' . $gsk_slug ] ) && intval(  $_POST['gdpr_google_site_kit_' . $gsk_slug ] ) ? intval(  $_POST['gdpr_google_site_kit_' . $gsk_slug ] ) : 1;
					$gdpr_gsk_values[ $gsk_slug ] = $gsk_value;
				endforeach;
				$gdpr_options['gsk_values'] = json_encode( $gdpr_gsk_values );
				
				update_option( $option_name, $gdpr_options );
				$gdpr_options = get_option( $option_name );
				do_action( 'gdpr_cookie_filter_settings' );

				?>
					<script>
						jQuery('#moove-gdpr-setting-error-settings_updated').show();
					</script>
				<?php
			endif;
		endif;
	endif;
	$gsk_values = isset( $gdpr_options['gsk_values'] ) ? json_decode( $gdpr_options['gsk_values'], true ) : array();

	$nav_label_1 = isset( $gdpr_options[ 'moove_gdpr_strictly_necessary_cookies_tab_title' . $wpml_lang ] ) && $gdpr_options[ 'moove_gdpr_strictly_necessary_cookies_tab_title' . $wpml_lang ] ? $gdpr_options[ 'moove_gdpr_strictly_necessary_cookies_tab_title' . $wpml_lang ] : __( 'Strictly Necessary Cookies', 'gdpr-cookie-compliance' );

	$nav_label_2 = isset( $gdpr_options[ 'moove_gdpr_performance_cookies_tab_title' . $wpml_lang ] ) && $gdpr_options[ 'moove_gdpr_performance_cookies_tab_title' . $wpml_lang ] ? $gdpr_options[ 'moove_gdpr_performance_cookies_tab_title' . $wpml_lang ] : __( '3rd Party Cookies', 'gdpr-cookie-compliance' );

	$nav_label_3 = isset( $gdpr_options[ 'moove_gdpr_advanced_cookies_tab_title' . $wpml_lang ] ) && $gdpr_options[ 'moove_gdpr_advanced_cookies_tab_title' . $wpml_lang ] ? $gdpr_options[ 'moove_gdpr_advanced_cookies_tab_title' . $wpml_lang ] : __( 'Additional Cookies', 'gdpr-cookie-compliance' );

	$active_gsk_modules 		= array();
	$restricted_gsk_slugs 	= array( 'site-verification' );
	$sitekit_active 					= false;
	if ( class_exists('Google\Site_Kit\Core\REST_API\REST_Routes') ) :
		$request 	= new WP_REST_Request( 'GET', '/google-site-kit/v1/core/modules/data/list' );
		$response = rest_do_request( $request );
		$server 	= rest_get_server();
		$data 		= $server->response_to_data( $response, false );
		$json 		= wp_json_encode( $data );
		$sitekit_active = true;

		if ( ! is_wp_error( $json ) ) :
			$gsk_response = json_decode ( $json, true );
			if ( is_array( $gsk_response ) ) :
				foreach ( $gsk_response as $gsk_module ) :
					if ( isset( $gsk_module['active'] ) && $gsk_module['active'] && ! in_array( $gsk_module['slug'], $restricted_gsk_slugs ) ) :
						$active_gsk_modules[ $gsk_module['slug'] ] = $gsk_module['name'];
					endif;
				endforeach;
			endif;
		endif;
	endif;
?>

<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>?page=moove-gdpr&amp;tab=google-site-kit" method="post" id="moove_gdpr_tab_ifb_settings">
	<?php wp_nonce_field( 'moove_gdpr_nonce_field', 'moove_gdpr_nonce' ); ?>
	<h2><?php esc_html_e( 'Google Site Kit - Integration', 'gdpr-cookie-compliance-addon' ); ?></h2>
	<hr />
	<?php if ( ! $sitekit_active ) : ?>
		<h4 class="descriotion">Google Site Kit is not installed on this site.</h4>
		<p>Please download, install and setup the official <a href="https://wordpress.org/plugins/google-site-kit/" target="_blank" class="gdpr_admin_link">Google Site Kit</a> plugin and then return to this page.
		</p>
		<!-- .descriotion -->
	<?php else : ?>
		<h4 class="description">Improve your cookie compliance by connecting Google Site Kit modules to the cookie categories used by this plugin. <br/> Using the settings below, you can ensure that the tracking scripts from your active Google Site Kit modules are loaded only if your website visitors accept the selected cookie category.</h4>
		<table class="form-table">
			<tbody>

				<tr>
					<td colspan="2" style="padding: 0;">
						<table class="gdpr-gsk-table">
							<thead>
								<tr>
									<th>Google Site Kit - Module Name</th>
									<th>GDPR - Integration Status</th>
									<th>GDPR - Cookie Category</th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $active_gsk_modules ) && is_array( $active_gsk_modules ) ) : ?>
									<?php foreach ( $active_gsk_modules as $_gsk_module_slug => $_gsk_module_name ) : ?>
										<tr>
											<td><strong><?php echo $_gsk_module_name; ?></strong></td>
											<td>
												<label class="gdpr-checkbox-toggle">
												<input type="checkbox" id="gdpr_google_site_kit_<?php echo $_gsk_module_slug; ?>" name="gdpr_google_site_kit[]" <?php echo isset( $gsk_values[$_gsk_module_slug] ) ? 'checked' : ''; ?> id="gdpr_google_site_kit" value="<?php echo $_gsk_module_slug; ?>" >
												<span class="gdpr-checkbox-slider" data-enable="<?php esc_html_e( 'Enabled', 'gdpr-cookie-compliance' ); ?>" data-disable="<?php esc_html_e( 'Disabled', 'gdpr-cookie-compliance' ); ?>"></span>
											</label>
											</td>
											<td>
												<div class="gdpr-conditional-field" data-dependency="#gdpr_google_site_kit_<?php echo $_gsk_module_slug; ?>">
													<select name="gdpr_google_site_kit_<?php echo $_gsk_module_slug; ?>" id="gdpr_google_site_kit_<?php echo $_gsk_module_slug; ?>">
														<option value="1" <?php echo isset( $gsk_values[$_gsk_module_slug] ) && intval( $gsk_values[$_gsk_module_slug] ) === 1 ? 'selected' : ''; ?> ><?php echo $nav_label_1; ?></option>
														<option value="2" <?php echo isset( $gsk_values[$_gsk_module_slug] ) && intval( $gsk_values[$_gsk_module_slug] ) === 2 ? 'selected' : ''; ?> ><?php echo $nav_label_2; ?></option>
														<option value="3" <?php echo isset( $gsk_values[$_gsk_module_slug] ) && intval( $gsk_values[$_gsk_module_slug] ) === 3 ? 'selected' : ''; ?>><?php echo $nav_label_3; ?></option>
													</select>
													<!-- # -->
												</div>
												<!-- .gdpr-conditional-field -->
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td colspan="3">Please activate at least one Google Site Kit module</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>

		<br />
		<hr />
		<br />
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'gdpr-cookie-compliance-addon' ); ?></button>
		<?php do_action( 'gdpr_cc_banner_buttons_settings' ); ?>
	<?php endif; ?>
</form>
