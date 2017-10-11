<?php if ( ! defined('EVENT_ESPRESSO_VERSION') ) { exit('No direct script access allowed'); }

/**
 *
 * EE_PMT_Onsite
 *
 *
 * @package			Event Espresso
 * @subpackage		espresso-stripe-gateway
 * @author			Event Espresso
 *
 */

class EE_PMT_Stripe_Onsite extends EE_PMT_Base {

	/**
	 * path to the templates folder for the Stripe PM
	 * @var string
	 */
	protected $_template_path = NULL;


	/**
	 *
	 * @param EE_Payment_Method $pm_instance
	 * @throws \EE_Error
	 * @return \EE_PMT_Stripe_Onsite
	 */
	public function __construct( $pm_instance = NULL ) {
		$this->_pretty_name = __("Stripe", 'event_espresso');
		$this->_default_description = __( 'Click the "Pay Now" button to proceed with payment.', 'event_espresso' );
		$this->_template_path = dirname(__FILE__) . DS . 'templates' . DS;
		$this->_requires_https = FALSE;
		$this->_cache_billing_form = FALSE;
		$this->_default_button_url = EE_STRIPE_URL . 'payment_methods' . DS . 'Stripe_Onsite' . DS . 'lib' . DS . 'stripe-cc-logo.png';

		// Include Stripe API dependencies.
		require_once( EE_STRIPE_PATH . 'includes' . DS . 'stripe_dependencies' . DS . 'lib' . DS . 'Stripe.php' );

		require_once( $this->file_folder() . 'EEG_Stripe_Onsite.gateway.php' );
		$this->_gateway = new EEG_Stripe_Onsite();

		parent::__construct( $pm_instance );
		// Scripts for generating Stripe token.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_stripe_payment_scripts' ));
	}


	/**
	 * Generate a new payment settings form.
	 *
	 * @return EE_Payment_Method_Form
	 */
	public function generate_new_settings_form() {
		$pms_form = new EE_Payment_Method_Form( array(
			'extra_meta_inputs' => array(
				'stripe_secret_key' => new EE_Text_Input( array(
					'html_label_text' => sprintf( __("Stripe Secret Key %s", "event_espresso"), $this->get_help_tab_link() ),
					'required' => true
				)),
				'publishable_key' => new EE_Text_Input( array(
					'html_label_text' => sprintf( __("Stripe Publishable Key %s", "event_espresso"), $this->get_help_tab_link() ),
					'required' => true
				)),
				'validate_zip' => new EE_Yes_No_Input(
					array(
						'html_label_text'=> sprintf( __("Validate the billing ZIP code? %s", 'event_espresso'),  $this->get_help_tab_link() ),
						'default' => false,
						'required' => true
					)
				),
				'billing_address' => new EE_Yes_No_Input(
					array(
						'html_label_text'=> sprintf( __("Collect the user's billing address? %s", 'event_espresso'),  $this->get_help_tab_link() ),
						'default' => false,
						'required' => true
					)
				),
				'data_locale' => new EE_Select_Input(
					array(
						null 	=>	__('None', 'event_espresso'), 
						'auto' 	=>	__('Auto (Defaults to English)', 'event_espresso'),
						'zh'	=>	__('Simplified Chinese', 'event_espresso'),
						'da'	=>	__('Danish', 'event_espresso'),
						'nl'	=>	__('Dutch', 'event_espresso'),
						'en'	=>	__('English', 'event_espresso'),
						'fi'	=>	__('Finnish', 'event_espresso'),
						'fr'	=>	__('French', 'event_espresso'),
						'de'	=>	__('German', 'event_espresso'),
						'it'	=>	__('Italian', 'event_espresso'),
						'ja'	=>	__('Japanese', 'event_espresso'),
						'no'	=>	__('Norwegian', 'event_espresso'),
						'es'	=>	__('Spanish', 'event_espresso'),
						'sv'	=>	__('Swedish', 'event_espresso') 
					),
					array(
						'html_label_text' => sprintf( __( "Checkout locale %s", 'event_espresso' ), $this->get_help_tab_link() ),
						'html_help_text' => __( "This is the locale sent to Stripe to determine which language the checkout modal should use.", 'event_espresso' )
					)
				),
				'stripe_logo_url'=>new EE_Admin_File_Uploader_Input(array(
						'html_label_text'=>  sprintf(__("Logo URL %s", "event_espresso"),  $this->get_help_tab_link()),
						'default'=>  EE_Registry::instance()->CFG->organization->get_pretty( 'logo_url' ),
						'html_help_text'=>  __("(Logo shown on Stripe checkout)", 'event_espresso'),
					)
				)
			)
		));

		// Filtering the form contents.
		$pms_form = apply_filters( 'FHEE__EE_PMT_Stripe_Onsite__generate_new_settings_form__form_filtering', $pms_form, $this, $this->_pm_instance );

		return $pms_form;
	}


	/**
	 * Creates a billing form for this payment method type.
	 * @param \EE_Transaction $transaction
	 * @return \EE_Billing_Info_Form
	 */
	public function generate_new_billing_form( EE_Transaction $transaction = NULL, $extra_args = array() ) {
		EE_Registry::instance()->load_helper( 'Money' );
		$event_name = '';
		$email = '';
		if ( $transaction->primary_registration() instanceof EE_Registration ) {
			$event_name = $transaction->primary_registration()->event_name();
			if ( $transaction->primary_registration()->attendee() instanceof EE_Attendee ) {
				$email = $transaction->primary_registration()->attendee()->email();
			}
		}
		if ( isset( $extra_args['amount_owing' ] )) {
			$amount = $extra_args[ 'amount_owing' ] * 100;
		} else {
			// If this is a partial payment..
			$total = EEH_Money::convert_to_float_from_localized_money( $transaction->total() ) * 100;
			$paid = EEH_Money::convert_to_float_from_localized_money( $transaction->paid() ) * 100;
			$owning = $total - $paid;
			$amount = ( $owning > 0 ) ? $owning : $total;
		}

		return new EE_Billing_Info_Form(
			$this->_pm_instance,
			array(
				'name' => 'Stripe_Onsite_Billing_Form',
				'html_id'=> 'ee-Stripe-billing-form',
				'html_class'=> 'ee-billing-form',
				'subsections' => array(
					$this->generate_billing_form_debug_content(),
					$this->stripe_embedded_form(),
					'ee_stripe_token' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-stripe-token',
							'html_name' => 'EEA_stripeToken',
							'default' => ''
						)
					),
					'ee_stripe_transaction_email' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-stripe-transaction-email',
							'html_name' => 'eeTransactionEmail',
							'default' => $email,
							'validation_strategies' => array( new EE_Email_Validation_Strategy() )
						)
					),
					'ee_stripe_transaction_total' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-stripe-transaction-total',
							'html_name' => 'eeTransactionTotal',
							'default' => $amount,
							'validation_strategies' => array( new EE_Float_Validation_Strategy() )
						)
					),
					'ee_stripe_prod_description' => new EE_Hidden_Input(
						array(
							'html_id' => 'ee-stripe-prod-description',
							'html_name' => 'stripeProdDescription',
							'default' => apply_filters( 'FHEE__EE_PMT_Stripe_Onsite__generate_new_billing_form__description', $event_name, $transaction )
						)
					)
				)
			)
		);
	}


	/**
	 *  Possibly adds debug content to Stripe billing form.
	 *
	 * @return string
	 */
	public function generate_billing_form_debug_content() {
		if ( $this->_pm_instance->debug_mode() ) {
			return new EE_Form_Section_Proper(
				array(
					'layout_strategy' => new EE_Template_Layout(
						array(
							'layout_template_file' => $this->_template_path . 'stripe_debug_info.template.php',
							'template_args' => array()
						)
					)
				)
			);
		} else {
			return new EE_Form_Section_HTML();
		}
	}


	/**
	 *  Use Stripe's Embedded form.
	 *
	 * @return EE_Form_Section_Proper
	 */
	public function stripe_embedded_form() {
		$template_args = array();
		return new EE_Form_Section_Proper(
			array(
				'layout_strategy' => new EE_Template_Layout(
					array(
						'layout_template_file' => $this->_template_path . 'stripe_embedded_form.template.php',
						'template_args' => $template_args
					)
				)
			)
		);
	}


	/**
	 *  Load all the scripts needed for the Stripe checkout.
	 *
	 * @return void
	 */
	public function enqueue_stripe_payment_scripts() {
		wp_enqueue_style( 'espresso_stripe_payment_css', EE_STRIPE_URL . 'css' . DS . 'espresso_stripe.css' );
		wp_enqueue_script( 'stripe_payment_js', 'https://checkout.stripe.com/v2/checkout.js', array(), FALSE, TRUE );
		wp_enqueue_script( 'espresso_stripe_payment_js', EE_STRIPE_URL . 'scripts' . DS . 'espresso_stripe_onsite.js', array( 'stripe_payment_js', 'single_page_checkout' ), EE_STRIPE_VERSION, TRUE );

		// Data needed in the JS.
		$trans_args = array(
			'data_key' => $this->_pm_instance->get_extra_meta( 'publishable_key', TRUE ),
			'data_name' => EE_Registry::instance()->CFG->organization->get_pretty( 'name' ),
			'data_image' => $this->_pm_instance->get_extra_meta( 'stripe_logo_url', TRUE, EE_Registry::instance()->CFG->organization->get_pretty( 'logo_url' ) ),
			//note its expected that we're using string values for 'true' and 'false' here. That's what the Stripe API is working with
			'validate_zip' => $this->_pm_instance->get_extra_meta( 'validate_zip', true ) ? 'true' : 'false',
			'billing_address' => $this->_pm_instance->get_extra_meta( 'billing_address', true ) ? 'true' : 'false',
			'data_locale' => $this->_pm_instance->get_extra_meta( 'data_locale', true ),
			'data_currency' => EE_Registry::instance()->CFG->currency->code,
			'data_panel_label' =>  sprintf( __( 'Pay %1$s Now', 'event_espresso' ), '{{amount}}' ),
			'card_error_message' => __( 'Payment Error! Please refresh the page and try again or contact support.', 'event_espresso' ),
			'no_SPCO_error' => __( 'It appears the Single Page Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.', 'event_espresso' ),
			'no_StripeCheckout_error' => __( 'It appears the Stripe Checkout javascript was not loaded properly! Please refresh the page and try again or contact support.', 'event_espresso' ),
			'payment_method_slug' => $this->_pm_instance->slug(),
		);
		if ( $this->_pm_instance->debug_mode() ) {
			$trans_args['data_cc_number'] = '4242424242424242';
			$trans_args['data_exp_month'] = date('m');
			$trans_args['data_exp_year'] = date('Y') + 4;
			$trans_args['data_cvc'] = '248';
		}

		// Filter JS data.
		$trans_args = apply_filters( 'FHEE__EE_PMT_Stripe_Onsite__enqueue_stripe_payment_scripts__js_data', $trans_args, $this->_pm_instance );

			// Localize the script with our transaction data.
		wp_localize_script( 'espresso_stripe_payment_js', 'stripe_transaction_args', $trans_args);
	}


	/**
	 * Adds the help tab
	 *
	 * @see EE_PMT_Base::help_tabs_config()
	 * @return array
	 */
	public function help_tabs_config(){
		return array(
			$this->get_help_tab_name() => array(
				'title' => __( 'Stripe Settings', 'event_espresso' ),
				'filename' => 'payment_methods_overview_stripe'
			),
		);
	}


	/**
	 * Log Stripe TXN Error.
	 *
	 * @return void
	 */
	public static function log_stripe_error() {
		if ( isset($_POST['txn_id']) && ! empty($_POST['txn_id']) ) {
			$stripe_pm = EEM_Payment_method::instance()->get_one_of_type( 'Stripe_Onsite' );
			$transaction = EEM_Transaction::instance()->get_one_by_ID( $_POST['txn_id'] );
			$stripe_pm->type_obj()->get_gateway()->log( array('Stripe JS Error (Transaction: ' . $transaction->ID() . ')' => $_POST['message']), $transaction );
		}
	}
}

// End of file EE_PMT_Stripe_Onsite.pm.php
