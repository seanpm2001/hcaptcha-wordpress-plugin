<?php
/**
 * Form class file.
 *
 * @package hcaptcha-wp
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedClassInspection */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

namespace HCaptcha\FluentForm;

use FluentForm\App\Models\Form as FluentForm;
use FluentForm\App\Modules\Form\FormFieldsParser;
use HCaptcha\Helpers\HCaptcha;
use HCaptcha\Main;
use stdClass;

/**
 * Class Form
 */
class Form {
	/**
	 * Nonce action.
	 */
	const ACTION = 'hcaptcha_fluentform';

	/**
	 * Nonce name.
	 */
	const NONCE = 'hcaptcha_fluentform_nonce';

	/**
	 * Script handle.
	 */
	const HANDLE = 'hcaptcha-fluentform';

	/**
	 * Conversational form id.
	 *
	 * @var int
	 */
	private $form_id;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 */
	private function init_hooks() {
		add_action( 'fluentform/render_item_submit_button', [ $this, 'add_captcha' ], 9, 2 );
		add_action( 'fluentform/validation_errors', [ $this, 'verify' ], 10, 4 );
		add_filter( 'fluentform/rendering_form', [ $this, 'fluentform_rendering_form_filter' ] );
		add_action( 'wp_print_footer_scripts', [ $this, 'enqueue_scripts' ], 9 );
	}

	/**
	 * Action that fires immediately before the submit button element is displayed.
	 *
	 * @param array    $submit_button Form data and settings.
	 * @param stdClass $form          Form data and settings.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_captcha( array $submit_button, stdClass $form ) {
		// Do not add if form has its own hcaptcha.
		if ( $this->has_own_hcaptcha( $form ) ) {
			return;
		}

		$args = [
			'action' => self::ACTION,
			'name'   => self::NONCE,
			'id'     => [
				'source'  => HCaptcha::get_class_source( __CLASS__ ),
				'form_id' => (int) $form->id,
			],
		];

		?>
		<div class="ff-el-group">
			<div class="ff-el-input--content">
				<div name="h-captcha-response">
					<?php HCaptcha::form_display( $args ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Filter errors during form validation.
	 *
	 * @param array      $errors Errors.
	 * @param array      $data   Sanitized entry fields.
	 * @param FluentForm $form   Form data and settings.
	 * @param array      $fields Form fields.
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function verify( array $errors, array $data, FluentForm $form, array $fields ): array {
		// Do not verify if form has its own hcaptcha.
		if ( $this->has_own_hcaptcha( $form ) ) {
			return $errors;
		}

		$hcaptcha_response           = $data['h-captcha-response'] ?? '';
		$_POST['hcaptcha-widget-id'] = $data['hcaptcha-widget-id'] ?? '';
		$error_message               = hcaptcha_request_verify( $hcaptcha_response );

		if ( null !== $error_message ) {
			$errors['h-captcha-response'] = [ $error_message ];
		}

		return $errors;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$min = hcap_min_suffix();

		wp_enqueue_script(
			self::HANDLE,
			HCAPTCHA_URL . "/assets/js/hcaptcha-fluentform$min.js",
			[ Main::HANDLE ],
			HCAPTCHA_VERSION,
			true
		);

		$args = [
			'action' => self::ACTION,
			'name'   => self::NONCE,
			'id'     => [
				'source'  => HCaptcha::get_class_source( __CLASS__ ),
				'form_id' => $this->form_id,
			],
		];

		$form = HCaptcha::form( $args );
		$form = str_replace(
			[
				'class="h-captcha"',
				'class="hcaptcha-widget-id"',
			],
			[
				'class="h-captcha-hidden" style="display: none;"',
				'class="h-captcha-hidden hcaptcha-widget-id"',
			],
			$form
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $form;
	}

	/**
	 * Fluentform load form assets hook.
	 *
	 * @param stdClass|mixed $form Form.
	 *
	 * @return stdClass|mixed
	 */
	public function fluentform_rendering_form_filter( $form ) {
		if ( ! $form instanceof stdClass ) {
			return $form;
		}

		static $has_own_captcha = false;

		if ( $this->has_own_hcaptcha( $form ) ) {
			$has_own_captcha = true;
		}

		$this->form_id = (int) $form->id;

		hcaptcha()->fluentform_support_required = ! $has_own_captcha;

		return $form;
	}

	/**
	 * Whether form has its own hcaptcha set in admin.
	 *
	 * @param FluentForm|stdClass $form Form data and settings.
	 *
	 * @return bool
	 */
	protected function has_own_hcaptcha( $form ): bool {
		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$auto_include = apply_filters( 'fluentform/has_hcaptcha', false );

		if ( $auto_include ) {
			return true;
		}

		FormFieldsParser::resetData();

		if ( FormFieldsParser::hasElement( $form, 'hcaptcha' ) ) {
			return true;
		}

		return false;
	}
}
