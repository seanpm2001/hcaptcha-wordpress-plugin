<?php
/**
 * Contact class file.
 *
 * @package hcaptcha-wp
 */

// phpcs:ignore Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpUndefinedClassInspection */

namespace HCaptcha\BeaverBuilder;

use stdClass;

/**
 * Class Contact.
 */
class Contact extends Base {
	/**
	 * Nonce action.
	 */
	const ACTION = 'hcaptcha_beaver_builder';

	/**
	 * Nonce name.
	 */
	const NONCE = 'hcaptcha_beaver_builder_nonce';

	/**
	 * Add hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		parent::init_hooks();
		add_action( 'fl_module_contact_form_before_send', [ $this, 'verify' ], 10, 5 );
	}

	/**
	 * Filters the Beaver Builder Contact Form submit button html and adds hcaptcha.
	 *
	 * @param string|mixed   $out    Button html.
	 * @param FLButtonModule $module Button module.
	 *
	 * @return string|mixed
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_hcaptcha( $out, FLButtonModule $module ) {
		// Process contact form only.
		if ( false === strpos( (string) $out, '<form class="fl-contact-form"' ) ) {
			return $out;
		}

		return $this->add_hcap_form( (string) $out, $module );
	}

	/**
	 * Verify request.
	 *
	 * @param string   $mailto   Email address.
	 * @param string   $subject  Email subject.
	 * @param string   $template Email template.
	 * @param string[] $headers  Email headers.
	 * @param stdClass $settings Settings.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function verify( string $mailto, string $subject, string $template, array $headers, stdClass $settings ) {

		$result = hcaptcha_verify_post( self::NONCE, self::ACTION );

		if ( null === $result ) {
			return;
		}

		$response = [
			'error'   => true,
			'message' => $result,
		];

		wp_send_json( $response );
	}
}
