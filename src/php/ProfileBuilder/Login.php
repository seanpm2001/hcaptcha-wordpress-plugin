<?php
/**
 * Login class file.
 *
 * @package hcaptcha-wp
 */

namespace HCaptcha\ProfileBuilder;

use HCaptcha\Abstracts\LoginBase;
use HCaptcha\Helpers\HCaptcha;
use WP_Error;
use WP_User;

/**
 * Class Login.
 */
class Login extends LoginBase {

	/**
	 * Nonce action.
	 */
	const ACTION = 'hcaptcha_login';

	/**
	 * Nonce name.
	 */
	const NONCE = 'hcaptcha_login_nonce';

	/**
	 * Init hooks.
	 */
	protected function init_hooks() {
		parent::init_hooks();

		add_filter( 'wppb_login_form_before_content_output', [ $this, 'add_captcha' ], 10, 2 );
		add_filter( 'wp_authenticate_user', [ $this, 'verify' ], 10, 2 );
	}

	/**
	 * Add captcha.
	 *
	 * @param string|mixed $login_form Login form html.
	 * @param array        $form_args  Form arguments.
	 *
	 * @return string|mixed
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_captcha( $login_form, array $form_args ) {
		if ( ! $this->is_login_limit_exceeded() ) {
			return $login_form;
		}

		$login_form = (string) $login_form;

		$args = [
			'action' => self::ACTION,
			'name'   => self::NONCE,
			'id'     => [
				'source'  => HCaptcha::get_class_source( __CLASS__ ),
				'form_id' => 'login',
			],
		];

		$search = '<p class="login-submit">';

		return str_replace( $search, HCaptcha::form( $args ) . $search, $login_form );
	}

	/**
	 * Verify login form.
	 *
	 * @param WP_User|WP_Error $user     WP_User or WP_Error object if a previous
	 *                                   callback failed authentication.
	 * @param string           $password Password to check against the user.
	 *
	 * @return WP_User|WP_Error
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function verify( $user, string $password ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$wppb_login_form_used = isset( $_POST['wppb_login'] ) ?
			sanitize_text_field( wp_unslash( $_POST['wppb_login'] ) ) :
			'';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! $wppb_login_form_used ) {
			return $user;
		}

		if ( ! $this->is_login_limit_exceeded() ) {
			return $user;
		}

		$error_message = hcaptcha_verify_post(
			self::NONCE,
			self::ACTION
		);

		if ( null === $error_message ) {
			return $user;
		}

		$code = array_search( $error_message, hcap_get_error_messages(), true );
		$code = $code ?: 'fail';

		return new WP_Error( $code, $error_message, 400 );
	}
}
