<?php
/**
 * Contact class file.
 *
 * @package hcaptcha-wp
 */

namespace HCaptcha\ClassifiedListing;

use HCaptcha\Helpers\HCaptcha;
use WP_Error;

/**
 * Class Contact.
 */
class Contact {

	/**
	 * Nonce action.
	 */
	const ACTION = 'hcaptcha_classified_listing_contact';

	/**
	 * Nonce name.
	 */
	const NONCE = 'hcaptcha_classified_listing_contact_nonce';

	/**
	 * Class constructor.
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
		add_action( 'rtcl_before_template_part', [ $this, 'before_template_part' ], 10, 3 );
		add_action( 'rtcl_after_template_part', [ $this, 'after_template_part' ], 10, 3 );
		add_action( 'rtcl_listing_seller_contact_form_validation', [ $this, 'verify' ], 10, 2 );
	}

	/**
	 * Start output buffer before template part.
	 *
	 * @param string $template_name Template name.
	 * @param string $located       Location.
	 * @param array  $template_args Arguments.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function before_template_part( string $template_name, string $located, array $template_args ) {
		if ( 'listing/email-to-seller-form' !== $template_name ) {
			return;
		}

		ob_start();
	}

	/**
	 * Stop output buffer after template part and add captcha.
	 *
	 * @param string $template_name Template name.
	 * @param string $located       Location.
	 * @param array  $template_args Arguments.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function after_template_part( string $template_name, string $located, array $template_args ) {
		if ( 'listing/email-to-seller-form' !== $template_name ) {
			return;
		}

		$template = ob_get_clean();

		$args = [
			'action' => self::ACTION,
			'name'   => self::NONCE,
			'id'     => [
				'source'  => HCaptcha::get_class_source( __CLASS__ ),
				'form_id' => 'contact',
			],
		];

		$search   = '<button type="submit"';
		$replace  = HCaptcha::form( $args ) . $search;
		$template = str_replace( $search, $replace, $template );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $template;
	}

	/**
	 * Verify contact form.
	 *
	 * @param WP_Error $error Error.
	 * @param array    $data  Form data.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function verify( WP_Error $error, array $data ) {
		$error_message = hcaptcha_verify_post(
			static::NONCE,
			static::ACTION
		);

		if ( null === $error_message ) {
			return;
		}

		$code = array_search( $error_message, hcap_get_error_messages(), true );
		$code = $code ?: 'fail';

		$error->add( $code, $error_message );
	}
}
