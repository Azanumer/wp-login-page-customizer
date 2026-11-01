<?php
/**
 * Plugin Name:       Login Page Customizer
 * Plugin URI:        https://github.com/Azanumer/wp-login-page-customizer
 * Description:       Brand wp-login.php without touching code: custom logo, background, button colours and extra CSS — all from one settings page, no theme edits.
 * Version:           1.0.0
 * Author:            Azan Umer
 * License:           MIT
 * Text Domain:       wp-login-page-customizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WLPC_VERSION', '1.0.0' );
define( 'WLPC_OPTION', 'wlpc_settings' );

/**
 * Default settings.
 *
 * @return array
 */
function wlpc_get_settings() {
	$defaults = array(
		'logo_url'        => '',
		'logo_width'      => 84,
		'logo_height'     => 84,
		'bg_color'        => '#f0f0f1',
		'form_bg'         => '#ffffff',
		'button_color'    => '#2271b1',
		'button_text'     => '#ffffff',
		'custom_css'      => '',
		'hide_shake'      => 0,
		'login_url'       => '',
		'login_title'     => '',
	);
	$saved = get_option( WLPC_OPTION, array() );
	return is_array( $saved ) ? array_merge( $defaults, $saved ) : $defaults;
}

/**
 * Validate a hex colour, falling back to a safe default.
 *
 * @param string $color   Candidate colour.
 * @param string $default Fallback.
 * @return string
 */
function wlpc_sanitize_hex( $color, $default ) {
	$color = trim( (string) $color );
	return preg_match( '/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color ) ? $color : $default;
}

/**
 * Print the custom login styles.
 */
function wlpc_login_styles() {
	$settings = wlpc_get_settings();
	$css = 'body.login { background-color: ' . esc_attr( $settings['bg_color'] ) . '; }';
	$css .= '#loginform { background-color: ' . esc_attr( $settings['form_bg'] ) . '; }';
	$css .= '.login #wp-submit { background-color: ' . esc_attr( $settings['button_color'] ) . '; border-color: ' . esc_attr( $settings['button_color'] ) . '; color: ' . esc_attr( $settings['button_text'] ) . '; }';

	if ( '' !== $settings['logo_url'] ) {
		$css .= '.login h1 a { background-image: url(' . esc_url( $settings['logo_url'] ) . '); background-size: contain; width: ' . (int) $settings['logo_width'] . 'px; height: ' . (int) $settings['logo_height'] . 'px; }';
	}

	if ( $settings['hide_shake'] ) {
		$css .= '.login #login_error, .login .message { box-shadow: none; }';
	}

	// Custom CSS is admin-entered; strip tags and @import to keep it to
	// plain rules (no <script>, no external imports).
	$custom = $settings['custom_css'];
	$custom = preg_replace( '/@import[^;]+;?/i', '', $custom );
	$custom = strip_tags( $custom );
	$css .= $custom;

	echo '<style type="text/css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'login_enqueue_scripts', 'wlpc_login_styles' );

/**
 * Point the logo link at the configured URL (default: home URL).
 *
 * @param string $url Default login header URL.
 * @return string
 */
function wlpc_login_header_url( $url ) {
	$settings = wlpc_get_settings();
	return '' !== $settings['login_url'] ? esc_url( $settings['login_url'] ) : $url;
}
add_filter( 'login_headerurl', 'wlpc_login_header_url' );

/**
 * Set the logo's title attribute.
 *
 * @param string $title Default title.
 * @return string
 */
function wlpc_login_header_text( $title ) {
	$settings = wlpc_get_settings();
	return '' !== $settings['login_title'] ? esc_attr( $settings['login_title'] ) : $title;
}
add_filter( 'login_headertitle', 'wlpc_login_header_text' );

/**
 * Settings page under Settings → Login Page.
 */
function wlpc_register_settings_page() {
	add_options_page(
		__( 'Login Page', 'wp-login-page-customizer' ),
		__( 'Login Page', 'wp-login-page-customizer' ),
		'manage_options',
		'wlpc-settings',
		'wlpc_render_settings_page'
	);
}
add_action( 'admin_menu', 'wlpc_register_settings_page' );

/**
 * Register the option with sanitisation.
 */
function wlpc_register_settings() {
	register_setting(
		'wlpc_settings_group',
		WLPC_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'wlpc_sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'wlpc_register_settings' );

/**
 * Sanitise settings input.
 *
 * @param array $input Raw input.
 * @return array
 */
function wlpc_sanitize_settings( $input ) {
	$clean = array();
	$clean['logo_url'] = isset( $input['logo_url'] ) ? esc_url_raw( wp_unslash( $input['logo_url'] ) ) : '';
	$clean['logo_width'] = max( 16, min( 500, (int) ( $input['logo_width'] ?? 84 ) ) );
	$clean['logo_height'] = max( 16, min( 500, (int) ( $input['logo_height'] ?? 84 ) ) );
	$clean['bg_color'] = wlpc_sanitize_hex( $input['bg_color'] ?? '', '#f0f0f1' );
	$clean['form_bg'] = wlpc_sanitize_hex( $input['form_bg'] ?? '', '#ffffff' );
	$clean['button_color'] = wlpc_sanitize_hex( $input['button_color'] ?? '', '#2271b1' );
	$clean['button_text'] = wlpc_sanitize_hex( $input['button_text'] ?? '', '#ffffff' );
	$clean['custom_css'] = isset( $input['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $input['custom_css'] ) ) : '';
	$clean['hide_shake'] = ! empty( $input['hide_shake'] ) ? 1 : 0;
	$clean['login_url'] = isset( $input['login_url'] ) ? esc_url_raw( wp_unslash( $input['login_url'] ) ) : '';
	$clean['login_title'] = isset( $input['login_title'] ) ? sanitize_text_field( wp_unslash( $input['login_title'] ) ) : '';
	return $clean;
}

/**
 * Render the settings page.
 */
function wlpc_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-login-page-customizer' ) );
	}
	$settings = wlpc_get_settings();
	$fields = array(
		'logo_url'     => array( 'label' => __( 'Logo image URL (leave empty to keep the WordPress logo)', 'wp-login-page-customizer' ), 'type' => 'url' ),
		'logo_width'   => array( 'label' => __( 'Logo width (px)', 'wp-login-page-customizer' ), 'type' => 'number' ),
		'logo_height'  => array( 'label' => __( 'Logo height (px)', 'wp-login-page-customizer' ), 'type' => 'number' ),
		'bg_color'     => array( 'label' => __( 'Page background colour', 'wp-login-page-customizer' ), 'type' => 'color' ),
		'form_bg'      => array( 'label' => __( 'Form background colour', 'wp-login-page-customizer' ), 'type' => 'color' ),
		'button_color' => array( 'label' => __( 'Button colour', 'wp-login-page-customizer' ), 'type' => 'color' ),
		'button_text'  => array( 'label' => __( 'Button text colour', 'wp-login-page-customizer' ), 'type' => 'color' ),
		'login_url'    => array( 'label' => __( 'Logo link URL (leave empty for the home page)', 'wp-login-page-customizer' ), 'type' => 'url' ),
		'login_title'  => array( 'label' => __( 'Logo title text', 'wp-login-page-customizer' ), 'type' => 'text' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Login Page Customizer', 'wp-login-page-customizer' ); ?></h1>
		<p><?php esc_html_e( 'Everything here applies to wp-login.php only — your theme and admin area are untouched.', 'wp-login-page-customizer' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'wlpc_settings_group' ); ?>
			<table class="form-table">
				<?php foreach ( $fields as $key => $field ) : ?>
					<tr>
						<th scope="row"><label for="wlpc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
						<td>
							<?php if ( 'color' === $field['type'] ) : ?>
								<input id="wlpc-<?php echo esc_attr( $key ); ?>" type="text" class="wlpc-color" name="<?php echo esc_attr( WLPC_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>">
							<?php else : ?>
								<input id="wlpc-<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" class="regular-text" name="<?php echo esc_attr( WLPC_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings[ $key ] ); ?>">
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Error box styling', 'wp-login-page-customizer' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( WLPC_OPTION ); ?>[hide_shake]" value="1" <?php checked( $settings['hide_shake'], 1 ); ?>> <?php esc_html_e( 'Remove the box shadow from error/notice boxes', 'wp-login-page-customizer' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="wlpc-css"><?php esc_html_e( 'Extra CSS', 'wp-login-page-customizer' ); ?></label></th>
					<td><textarea id="wlpc-css" rows="6" cols="60" name="<?php echo esc_attr( WLPC_OPTION ); ?>[custom_css]"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Plain CSS rules only — @import and HTML tags are stripped.', 'wp-login-page-customizer' ); ?></p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
