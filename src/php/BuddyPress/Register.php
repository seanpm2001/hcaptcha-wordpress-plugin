<?php
/**
 * Register class file.
 *
 * @package hcaptcha-wp
 */

namespace HCaptcha\BuddyPress;

use HCaptcha\Helpers\HCaptcha;

/**
 * Class Register.
 */
class Register {

	/**
	 * Nonce action.
	 */
	const ACTION = 'hcaptcha_bp_register';

	/**
	 * Nonce name.
	 */
	const NAME = 'hcaptcha_bp_register_nonce';

	/**
	 * Register constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'bp_before_registration_submit_buttons', [ $this, 'add_captcha' ] );
		add_action( 'bp_signup_validate', [ $this, 'verify' ] );
	}

	/**
	 * Add captcha to the register form.
	 *
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function add_captcha() {
		global $bp;

		if ( ! empty( $bp->signup->errors['hcaptcha_response_verify'] ) ) {
			$output = '<div class="error">';

			$output .= $bp->signup->errors['hcaptcha_response_verify'];
			$output .= '</div>';

			echo wp_kses_post( $output );
		}

		$args = [
			'action' => self::ACTION,
			'name'   => self::NAME,
			'id'     => [
				'source'  => HCaptcha::get_class_source( __CLASS__ ),
				'form_id' => 'register',
			],
		];

		HCaptcha::form_display( $args );
	}

	/**
	 * Verify register form captcha.
	 *
	 * @return bool
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function verify(): bool {
		global $bp;

		$error_message = hcaptcha_get_verify_message(
			self::NAME,
			self::ACTION
		);

		if ( null !== $error_message ) {
			$bp->signup->errors['hcaptcha_response_verify'] = $error_message;

			return false;
		}

		return true;
	}
}
