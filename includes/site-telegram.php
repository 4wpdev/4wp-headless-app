<?php
/**
 * Branded site panel — Telegram notifications for contact forms.
 *
 * @package 4wp-headless-app
 */

defined( 'ABSPATH' ) || exit;

const FWP_HEADLESS_APP_SITE_TELEGRAM_SLUG   = '4wp-headless-site-telegram';
const FWP_HEADLESS_APP_SITE_OPTION_TELEGRAM = '4wp_headless_app_site_telegram';

add_action( 'admin_menu', 'fwp_headless_app_register_site_telegram_submenu', 13 );
add_action( 'admin_init', 'fwp_headless_app_register_site_telegram_settings' );
add_action( 'admin_enqueue_scripts', 'fwp_headless_app_enqueue_site_telegram_assets' );
add_action( 'wp_ajax_fwp_headless_app_telegram_test', 'fwp_headless_app_ajax_telegram_test' );
add_action( 'wp_ajax_fwp_headless_app_telegram_test_saved', 'fwp_headless_app_ajax_telegram_test_saved' );

/**
 * Whether a Telegram settings flag is on.
 *
 * @param array<string, mixed> $settings Settings array.
 * @param string               $key      Flag key.
 */
function fwp_headless_app_telegram_flag_is_on( $settings, $key ) {
	if ( ! is_array( $settings ) || ! array_key_exists( $key, $settings ) ) {
		return false;
	}

	$value = $settings[ $key ];

	return true === $value || 1 === $value || '1' === $value;
}

/**
 * Whether saved settings can deliver contact-form notifications.
 *
 * @param array<string, mixed>|null $settings Optional settings override.
 * @return bool
 */
function fwp_headless_app_telegram_forms_ready( $settings = null ) {
	$settings = is_array( $settings ) ? $settings : fwp_headless_app_get_telegram_settings();

	if ( ! fwp_headless_app_telegram_flag_is_on( $settings, 'enabled' ) ) {
		return false;
	}
	if ( ! fwp_headless_app_telegram_flag_is_on( $settings, 'notify_forms' ) ) {
		return false;
	}
	if ( '' === trim( (string) ( $settings['bot_token'] ?? '' ) ) ) {
		return false;
	}
	if ( empty( fwp_headless_app_parse_telegram_chat_ids( $settings['chat_ids'] ?? '' ) ) ) {
		return false;
	}

	return true;
}

/**
 * @param array<string, mixed> $settings Saved settings.
 * @return array<int, string>
 */
function fwp_headless_app_telegram_readiness_issues( $settings ) {
	$issues = array();

	if ( ! fwp_headless_app_telegram_flag_is_on( $settings, 'enabled' ) ) {
		$issues[] = __( 'Telegram notifications are disabled.', '4wp-headless-app' );
	}
	if ( ! fwp_headless_app_telegram_flag_is_on( $settings, 'notify_forms' ) ) {
		$issues[] = __( 'Contact form notifications are disabled.', '4wp-headless-app' );
	}
	if ( '' === trim( (string) ( $settings['bot_token'] ?? '' ) ) ) {
		$issues[] = __( 'Bot token is not saved.', '4wp-headless-app' );
	}
	if ( empty( fwp_headless_app_parse_telegram_chat_ids( $settings['chat_ids'] ?? '' ) ) ) {
		$issues[] = __( 'Chat ID is not saved.', '4wp-headless-app' );
	}

	return $issues;
}

/**
 * Register Telegram submenu.
 */
function fwp_headless_app_register_site_telegram_submenu() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	add_submenu_page(
		FWP_HEADLESS_APP_SITE_PANEL_SLUG,
		__( 'Telegram', '4wp-headless-app' ),
		__( 'Telegram', '4wp-headless-app' ),
		'manage_options',
		FWP_HEADLESS_APP_SITE_TELEGRAM_SLUG,
		'fwp_headless_app_render_site_telegram_page'
	);
}

/**
 * Register Telegram settings (separate option — token must not be in public API).
 */
function fwp_headless_app_register_site_telegram_settings() {
	if ( ! fwp_headless_app_should_show_site_panel() ) {
		return;
	}

	register_setting(
		'4wp_headless_app_site_telegram',
		FWP_HEADLESS_APP_SITE_OPTION_TELEGRAM,
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => 'fwp_headless_app_sanitize_site_telegram_settings',
		)
	);
}

/**
 * @return array<string, mixed>
 */
function fwp_headless_app_get_telegram_settings() {
	$settings = get_option( FWP_HEADLESS_APP_SITE_OPTION_TELEGRAM, array() );

	if ( is_string( $settings ) ) {
		$decoded  = json_decode( $settings, true );
		$settings = is_array( $decoded ) ? $decoded : array();
	}

	return is_array( $settings ) ? $settings : array();
}

/**
 * @param array<string, mixed> $input Raw POST.
 * @return array<string, mixed>
 */
function fwp_headless_app_sanitize_site_telegram_settings( $input ) {
	$existing = fwp_headless_app_get_telegram_settings();
	if ( ! is_array( $input ) ) {
		return $existing;
	}

	$output = array(
		'enabled'      => ( isset( $input['enabled'] ) && fwp_headless_app_telegram_flag_is_on( $input, 'enabled' ) ) ? 1 : 0,
		'notify_forms' => ( isset( $input['notify_forms'] ) && fwp_headless_app_telegram_flag_is_on( $input, 'notify_forms' ) ) ? 1 : 0,
		'bot_token'    => sanitize_text_field( $input['bot_token'] ?? ( $existing['bot_token'] ?? '' ) ),
		'chat_ids'     => sanitize_textarea_field( $input['chat_ids'] ?? ( $existing['chat_ids'] ?? '' ) ),
	);

	return $output;
}

/**
 * Parse chat IDs from textarea (one per line or comma-separated).
 *
 * @param string $raw Raw chat IDs.
 * @return array<int, string>
 */
function fwp_headless_app_parse_telegram_chat_ids( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return array();
	}

	$parts = preg_split( '/[\s,;]+/', $raw );
	$ids   = array();

	foreach ( $parts as $part ) {
		$part = trim( $part );
		if ( '' === $part ) {
			continue;
		}
		if ( preg_match( '/^-?\d+$/', $part ) ) {
			$ids[] = $part;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Send a plain-text message to configured Telegram chats.
 *
 * @param string               $text      Message body.
 * @param array<string, mixed> $overrides Optional token/chat_ids override (for test).
 * @return true|WP_Error
 */
function fwp_headless_app_send_telegram_message( $text, $overrides = array() ) {
	$settings = fwp_headless_app_get_telegram_settings();

	$token = isset( $overrides['bot_token'] ) ? (string) $overrides['bot_token'] : ( $settings['bot_token'] ?? '' );
	$token = trim( $token );

	if ( '' === $token ) {
		return new WP_Error(
			'fwp_headless_app_telegram_no_token',
			__( 'Telegram bot token is not configured.', '4wp-headless-app' )
		);
	}

	$chat_ids_raw = isset( $overrides['chat_ids'] ) ? (string) $overrides['chat_ids'] : ( $settings['chat_ids'] ?? '' );
	$chat_ids     = fwp_headless_app_parse_telegram_chat_ids( $chat_ids_raw );

	if ( empty( $chat_ids ) ) {
		return new WP_Error(
			'fwp_headless_app_telegram_no_chat',
			__( 'Telegram chat ID is not configured.', '4wp-headless-app' )
		);
	}

	$text = trim( (string) $text );
	if ( '' === $text ) {
		return new WP_Error(
			'fwp_headless_app_telegram_empty',
			__( 'Message is empty.', '4wp-headless-app' )
		);
	}

	$api_url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
	$errors  = array();

	foreach ( $chat_ids as $chat_id ) {
		$response = wp_remote_post(
			$api_url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode(
					array(
						'chat_id'                  => $chat_id,
						'text'                     => $text,
						'disable_web_page_preview' => true,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$errors[] = $response->get_error_message();
			continue;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $body['ok'] ) ) {
			$desc     = is_array( $body ) && ! empty( $body['description'] ) ? (string) $body['description'] : 'HTTP ' . $code;
			$errors[] = $desc;
		}
	}

	if ( ! empty( $errors ) ) {
		return new WP_Error(
			'fwp_headless_app_telegram_send_failed',
			implode( '; ', array_unique( $errors ) )
		);
	}

	return true;
}

/**
 * Build contact form notification text.
 *
 * @param array<string, mixed> $data Contact payload.
 * @return string
 */
function fwp_headless_app_format_contact_telegram_message( $data ) {
	$name        = sanitize_text_field( $data['name'] ?? '' );
	$email       = sanitize_email( $data['email'] ?? '' );
	$phone       = sanitize_text_field( $data['phone'] ?? '' );
	$message     = sanitize_textarea_field( $data['message'] ?? '' );
	$is_callback = ! empty( $data['is_callback'] );

	$site_name = get_bloginfo( 'name' );
	if ( defined( 'FWP_HEADLESS_APP_SITE_OPTION_SETTINGS' ) ) {
		$site_settings = get_option( FWP_HEADLESS_APP_SITE_OPTION_SETTINGS, array() );
		if ( is_array( $site_settings ) && ! empty( $site_settings['logo'] ) ) {
			$site_name = sanitize_text_field( $site_settings['logo'] );
		}
	}

	$lines = array(
		'📩 ' . $site_name . ' — contact form',
		'',
	);

	if ( $is_callback ) {
		$lines[] = 'Type: callback request';
		if ( $name !== '' ) {
			$lines[] = 'Name: ' . $name;
		}
		$lines[] = 'Phone: ' . $phone;
		if ( $message !== '' ) {
			$lines[] = '';
			$lines[] = 'Comment:';
			$lines[] = $message;
		}
	} else {
		$lines[] = 'Type: message';
		if ( $name !== '' ) {
			$lines[] = 'Name: ' . $name;
		}
		if ( $email !== '' ) {
			$lines[] = 'Email: ' . $email;
		}
		if ( $phone !== '' ) {
			$lines[] = 'Phone: ' . $phone;
		}
		$lines[] = '';
		$lines[] = $message;
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( $ip !== '' ) {
		$lines[] = '';
		$lines[] = 'IP: ' . $ip;
	}

	return implode( "\n", $lines );
}

/**
 * Send Telegram notification for a contact form submission.
 *
 * @param array<string, mixed> $data Contact payload.
 */
function fwp_headless_app_notify_contact_form_telegram( $data ) {
	$settings = fwp_headless_app_get_telegram_settings();

	if ( ! fwp_headless_app_telegram_forms_ready( $settings ) ) {
		error_log(
			'[4wp-headless-app] Telegram form notify skipped: '
			. implode( ' ', fwp_headless_app_telegram_readiness_issues( $settings ) )
		);
		return;
	}

	$text   = fwp_headless_app_format_contact_telegram_message( $data );
	$result = fwp_headless_app_send_telegram_message( $text );

	if ( is_wp_error( $result ) ) {
		error_log( '[4wp-headless-app] Telegram form notify failed: ' . $result->get_error_message() );
	}
}

/**
 * AJAX: send test message using saved settings (same path as contact form).
 */
function fwp_headless_app_ajax_telegram_test_saved() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Forbidden.', '4wp-headless-app' ) ), 403 );
	}

	check_ajax_referer( 'fwp_headless_app_telegram_test', 'nonce' );

	$settings = fwp_headless_app_get_telegram_settings();
	if ( ! fwp_headless_app_telegram_forms_ready( $settings ) ) {
		wp_send_json_error(
			array(
				'message' => implode(
					' ',
					fwp_headless_app_telegram_readiness_issues( $settings )
				),
			)
		);
	}

	$message = '✅ Saved settings test — ' . get_bloginfo( 'name' ) . ' contact form path is working!';
	$result  = fwp_headless_app_send_telegram_message( $message );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Saved settings test sent. Contact form uses this same configuration.', '4wp-headless-app' ),
		)
	);
}

/**
 * AJAX: send test message using current form values.
 */
function fwp_headless_app_ajax_telegram_test() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Forbidden.', '4wp-headless-app' ) ), 403 );
	}

	check_ajax_referer( 'fwp_headless_app_telegram_test', 'nonce' );

	$token    = isset( $_POST['bot_token'] ) ? sanitize_text_field( wp_unslash( $_POST['bot_token'] ) ) : '';
	$chat_ids = isset( $_POST['chat_ids'] ) ? sanitize_textarea_field( wp_unslash( $_POST['chat_ids'] ) ) : '';
	$message  = isset( $_POST['test_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['test_message'] ) ) : '';

	if ( '' === trim( $message ) ) {
		$message = '✅ Test ' . get_bloginfo( 'name' ) . ' — Telegram is working!';
	}

	$result = fwp_headless_app_send_telegram_message(
		$message,
		array(
			'bot_token' => $token,
			'chat_ids'  => $chat_ids,
		)
	);

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Draft test sent using the field values above. Click Save Settings so the contact form uses the same credentials.', '4wp-headless-app' ),
		)
	);
}

/**
 * @param string $hook_suffix Admin page hook.
 */
function fwp_headless_app_enqueue_site_telegram_assets( $hook_suffix ) {
	if ( 'grv-build_page_' . FWP_HEADLESS_APP_SITE_TELEGRAM_SLUG !== $hook_suffix ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( FWP_HEADLESS_APP_SITE_TELEGRAM_SLUG !== $page ) {
			return;
		}
	}

	$nonce = wp_create_nonce( 'fwp_headless_app_telegram_test' );

	$js = <<<JS
jQuery(function ($) {
  function runTest(action, \$status, \$btn) {
    \$status.text('').removeClass('notice-success notice-error');
    \$btn.prop('disabled', true);
    \$status.text('Sending…');

    $.post(ajaxurl, {
      action: action,
      nonce: '{$nonce}',
      bot_token: $('#fwp_grv_telegram_token').val(),
      chat_ids: $('#fwp_grv_telegram_chat_ids').val(),
      test_message: $('#fwp_grv_telegram_test_message').val()
    })
      .done(function (res) {
        if (res.success) {
          \$status.addClass('notice-success').text(res.data.message || 'OK');
        } else {
          \$status.addClass('notice-error').text(res.data && res.data.message ? res.data.message : 'Error');
        }
      })
      .fail(function () {
        \$status.addClass('notice-error').text('Network error');
      })
      .always(function () {
        \$btn.prop('disabled', false);
      });
  }

  $('#fwp_grv_telegram_test').on('click', function (e) {
    e.preventDefault();
    runTest('fwp_headless_app_telegram_test', $('#fwp_grv_telegram_test_status'), $(this));
  });

  $('#fwp_grv_telegram_test_saved').on('click', function (e) {
    e.preventDefault();
    runTest('fwp_headless_app_telegram_test_saved', $('#fwp_grv_telegram_test_saved_status'), $(this));
  });
});
JS;

	wp_add_inline_script( 'jquery', $js );
}

/**
 * Render Telegram settings page.
 */
function fwp_headless_app_render_site_telegram_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = fwp_headless_app_get_telegram_settings();
	$option   = FWP_HEADLESS_APP_SITE_OPTION_TELEGRAM;
	$ready    = fwp_headless_app_telegram_forms_ready( $settings );
	$issues   = fwp_headless_app_telegram_readiness_issues( $settings );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Telegram', '4wp-headless-app' ); ?></h1>
		<?php settings_errors(); ?>
		<p class="description">
			<?php esc_html_e( 'Contact form submission notifications. The bot token is never exposed in the public API.', '4wp-headless-app' ); ?>
		</p>

		<?php if ( $ready ) : ?>
			<div class="notice notice-success inline">
				<p><?php esc_html_e( 'Saved settings are ready. Contact form submissions will use the stored token and chat ID.', '4wp-headless-app' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Contact form notifications are not ready yet.', '4wp-headless-app' ); ?></strong>
					<?php echo esc_html( implode( ' ', $issues ) ); ?>
				</p>
				<p><?php esc_html_e( 'The draft Test button checks unsaved field values. After saving, use Test saved settings to verify the same path used by the contact form.', '4wp-headless-app' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( '4wp_headless_app_site_telegram' ); ?>

			<h2><?php esc_html_e( 'Bot settings', '4wp-headless-app' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled', '4wp-headless-app' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[enabled]" value="1" <?php checked( fwp_headless_app_telegram_flag_is_on( $settings, 'enabled' ) ); ?> />
							<?php esc_html_e( 'Send notifications to Telegram', '4wp-headless-app' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_telegram_token"><?php esc_html_e( 'Token', '4wp-headless-app' ); ?></label></th>
					<td>
						<input type="text" id="fwp_grv_telegram_token" class="large-text code" name="<?php echo esc_attr( $option ); ?>[bot_token]" value="<?php echo esc_attr( $settings['bot_token'] ?? '' ); ?>" autocomplete="off" />
						<p class="description">
							<?php esc_html_e( 'Token from @BotFather.', '4wp-headless-app' ); ?>
							<a href="https://core.telegram.org/bots#6-botfather" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'How to get a token', '4wp-headless-app' ); ?></a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_telegram_chat_ids"><?php esc_html_e( 'Chat ID', '4wp-headless-app' ); ?></label></th>
					<td>
						<textarea id="fwp_grv_telegram_chat_ids" class="large-text code" rows="3" name="<?php echo esc_attr( $option ); ?>[chat_ids]"><?php echo esc_textarea( $settings['chat_ids'] ?? '' ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'One or more IDs (one per line). Group IDs are negative.', '4wp-headless-app' ); ?>
							<a href="https://t.me/get_id_bot" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'How to get a Chat ID', '4wp-headless-app' ); ?></a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Forms', '4wp-headless-app' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[notify_forms]" value="1" <?php checked( fwp_headless_app_telegram_flag_is_on( $settings, 'notify_forms' ) ); ?> />
							<?php esc_html_e( 'Notify on contact form submissions', '4wp-headless-app' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="fwp_grv_telegram_test_message"><?php esc_html_e( 'Test message', '4wp-headless-app' ); ?></label></th>
					<td>
						<textarea id="fwp_grv_telegram_test_message" class="large-text" rows="3" placeholder="<?php esc_attr_e( '✅ Test — Telegram is working!', '4wp-headless-app' ); ?>"></textarea>
						<p class="description"><?php esc_html_e( 'Draft Test uses the field values above without saving. Save Settings first, then run Test saved settings to verify the contact form path.', '4wp-headless-app' ); ?></p>
						<p>
							<button type="button" class="button button-secondary" id="fwp_grv_telegram_test"><?php esc_html_e( 'Test draft values', '4wp-headless-app' ); ?></button>
							<span id="fwp_grv_telegram_test_status" style="margin-left:8px;"></span>
						</p>
						<p style="margin-top:16px;">
							<button type="button" class="button button-primary" id="fwp_grv_telegram_test_saved"><?php esc_html_e( 'Test saved settings', '4wp-headless-app' ); ?></button>
							<span id="fwp_grv_telegram_test_saved_status" style="margin-left:8px;"></span>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
