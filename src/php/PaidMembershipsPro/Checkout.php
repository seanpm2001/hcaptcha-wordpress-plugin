<?php
/**
 * Checkout class file.
 *
 * @package hcaptcha-wp
 */

namespace HCaptcha\PaidMembershipsPro;

use HCaptcha\Helpers\HCaptcha;

/**
 * Class Checkout.
 */
class Checkout {

	/**
	 * Nonce action.
	 */
	const ACTION = 'hcaptcha_pmpro_checkout';

	/**
	 * Nonce name.
	 */
	const NONCE = 'hcaptcha_pmpro_checkout_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 */
	protected function init_hooks() {
		add_action( 'pmpro_checkout_before_submit_button', [ $this, 'add_captcha' ] );
		add_action( 'pmpro_checkout_after_parameters_set', [ $this, 'verify' ] );
	}

	/**
	 * Add captcha.
	 *
	 * @return void
	 */
	public function add_captcha() {
		$args = [
			'action' => self::ACTION,
			'name'   => self::NONCE,
			'id'     => [
				'source'  => HCaptcha::get_class_source( __CLASS__ ),
				'form_id' => 'checkout',
			],
		];

		HCaptcha::form_display( $args );
	}

	/**
	 * Verify login form.
	 *
	 * @return void
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	public function verify() {
		global $pmpro_msg, $pmpro_msgt;

		$submit = pmpro_was_checkout_form_submitted();

		if ( ! $submit ) {
			return;
		}

		$error_message = hcaptcha_verify_post(
			self::NONCE,
			self::ACTION
		);

		if ( null === $error_message ) {
			return;
		}

		$pmpro_msg  = $error_message;
		$pmpro_msgt = 'pmpro_error';
	}
}
