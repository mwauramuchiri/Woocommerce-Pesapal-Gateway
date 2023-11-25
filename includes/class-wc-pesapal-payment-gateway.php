<?php
class WC_Gateway_Pesapal extends WC_Payment_Gateway {
  public $consumer_key;
  public $consumer_secret;
  public $instructions;
  public $enable_for_methods;
	public $contact_us_whatsapp = '+254 714013670';

	private $pesapal_endpoint_demo_url = 'https://cybqa.pesapal.com/pesapalv3';
	private $pesapal_endpoint_production_url = 'https://pay.pesapal.com/v3';
	private $authentication_api_url = '/api/Auth/RequestToken';
  private $register_ipn_api_url = '/api/URLSetup/RegisterIPN';
  private $get_ipn_list_api_url = '/api/URLSetup/GetIpnList';
  private $submit_order_request_api_url = '/api/Transactions/SubmitOrderRequest';
  private $get_transaction_status_api_url = '/api/Transactions/GetTransactionStatus';

  /**
	 * Constructor for the gateway.
	 */
	public function __construct() {
    // Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
    $this->consumer_key       = $this->get_option( 'consumer_key' );
		$this->consumer_secret    = $this->get_option( 'consumer_secret' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );

    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'woocommerce_pay_order_before_payment_' . $this->id, array( $this, 'process_pesapal_iframe_url' ), 1, 1 );
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'pesapal_ipn_handler' ));
	}

  /**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'pesapal';
		$this->icon               = apply_filters( 'woocommerce_pesapal_icon', plugins_url('../assets/logo.png', __FILE__ ) );
		$this->method_title       = __( 'Pesapal', 'woocommerce-pesapal-gateway' );
		$this->consumer_key       = __( 'Add Consumer Key', 'woocommerce-pesapal-gateway' );
		$this->consumer_secret    = __( 'Add Consumer Secret', 'woocommerce-pesapal-gateway' );
		$this->method_description = __( 'Have your customers pay with Pesapal', 'woocommerce-pesapal-gateway' );
		$this->has_fields         = false;
	}

  /**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-pesapal-gateway' ),
				'label'       => __( 'Enable Pesapal', 'woocommerce-pesapal-gateway' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'woocommerce-pesapal-gateway' ),
				'type'        => 'text',
				'description' => __( 'Pesapal method description that the customer will see on your checkout.', 'woocommerce-pesapal-gateway' ),
				'default'     => __( 'Pesapal', 'woocommerce-pesapal-gateway' ),
				'desc_tip'    => true,
			),
			'consumer_key'  => array(
				'title'       => __( 'Consumer Key', 'woocommerce-pesapal-gateway' ),
				'type'        => 'text',
				'description' => __( 'Add your Consumer key', 'woocommerce-pesapal-gateway' ),
				'desc_tip'    => true,
			),
			'consumer_secret'=> array(
				'title'       => __( 'Consumer Secret', 'woocommerce-pesapal-gateway' ),
				'type'        => 'text',
				'description' => __( 'Add your Consumer Secret', 'woocommerce-pesapal-gateway' ),
				'desc_tip'    => true,
			),
      'production_mode'=> array(
				'title'       => __( 'Production Mode', 'woocommerce-pesapal-gateway' ),
				'label'       => __( 'Go Live', 'woocommerce-pesapal-gateway' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'description'        => array(
				'title'       => __( 'Description', 'woocommerce-pesapal-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Pesapal method description that the customer will see on your website.', 'woocommerce-pesapal-gateway' ),
				'default'     => __( 'Pesapal before delivery.', 'woocommerce-pesapal-gateway' ),
				'desc_tip'    => true,
			),
			'instructions'       => array(
				'title'       => __( 'Instructions', 'woocommerce-pesapal-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce-pesapal-gateway' ),
				'default'     => __( 'Pesapal before delivery.', 'woocommerce-pesapal-gateway' ),
				'desc_tip'    => true,
			),
			'dev_ipn_url'       => array(
				'title'       => __( 'Dev IPN URL', 'woocommerce-pesapal-gateway' ),
				'type'        => 'text',
				'description' => __( 'IPN URL that pesapal API will call', 'woocommerce-pesapal-gateway' ),
				'desc_tip'    => true,
			),
		);
	}

	public function get_pesapal_endpoint_url() {
		if ( 'yes' === $this->get_option( 'production_mode' ) ) {
			return $this->pesapal_endpoint_production_url;
		}

		return $this->pesapal_endpoint_demo_url;
	}

	public function get_pesapal_ipn_url() {
		if ( 'yes' === $this->get_option( 'production_mode' ) ) {
			return home_url() . '/wc-api/' . $this->id;
		}

	 	return $this->get_option( 'dev_ipn_url', home_url() . '/wc-api/' . $this->id );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			return $this->pesapal_order_processing( $order );
		}

		return array(
			'result' => 'success',
		);
	}

	private function pesapal_order_processing( $order ) {
		$order->update_status( 'Pending payment' );

		return array(
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url(),
		);
	}

	public function process_pesapal_iframe_url( $order ) {
		$payment = get_payment_by_order_id( $order->get_id() );
	
		if (
			false !== $payment && !empty($payment)
			&& 1 === (int) $payment->status_code
			&& 'Completed' === $payment->payment_status_description
		) {
			$order->update_status( 'Processing' );
			WC()->cart->empty_cart();
			// redirect to order received
			wp_redirect( wc_get_endpoint_url( 'order-received', $order->get_id() ) );

			return;
		}

		$authenticationResponse = $this->authenticate();

		if ( false === $authenticationResponse->ok ) {
			do_action( 'woocommerce_add_pesapal_iframe_error', $authenticationResponse->message );
			return;
		}

		if ( false !== $payment && !empty($payment)) {
			$options = (object) array(
				'token' => $authenticationResponse->data->token,
				'orderTrackingId' => $payment->order_tracking_id
			);

			$orderStatusResponse = $this->get_order_status($options);

			if ( false === $orderStatusResponse->ok ) {
				do_action( 'woocommerce_add_pesapal_iframe_error', $orderStatusResponse->message );
				return;
			}

			$paymentOtherData = (object) array(
				"amount_paid" 								=> $orderStatusResponse->data->amount, // update this too
				"payment_method" 							=> $orderStatusResponse->data->payment_method,
				"payment_account" 						=> $orderStatusResponse->data->payment_account,
				"payment_status_description" 	=> $orderStatusResponse->data->payment_status_description,
				"status_code" 								=> $orderStatusResponse->data->status_code
			);

			update_pesapal_payment( $payment->id, $paymentOtherData );

			if (
				1 === (int) $orderStatusResponse->data->status_code
				&& 'Completed' === $orderStatusResponse->data->payment_status_description
			) {
				$order->update_status( 'Processing' );
				WC()->cart->empty_cart();

				// redirect to order received
				wp_redirect( wc_get_endpoint_url( 'order-received', $order->get_id() ) );
				return;
			}

			// ! hell ikifika hapa. dk what to do
		}

		$registerIpnResponse = $this->register_ipn($authenticationResponse->data->token);

		if ( false === $registerIpnResponse->ok ) {
			do_action( 'woocommerce_add_pesapal_iframe_error', $registerIpnResponse->message );
			return;
		}

		$order_description = get_bloginfo( 'name' ) . ' Checkout - ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

		$options = (object) array(
      "amount" => $order->get_total(),
      "callback_url" => wc_get_endpoint_url( 'order-received', $order->get_id() ),
      "currency" => $order->get_currency(),
      "description" => $order_description,
      "id" => $order->get_order_key() . '_' . time(), // needs to be unique
      "notification_id" => $registerIpnResponse->data->ipn_id,
			// extras
			"billing_address" => array(
        "email_address" => $order->get_billing_email()
      ),
    );

		$orderRequestResponse = $this->submit_order_request( $options, $authenticationResponse->data->token );

		if ( false === $orderRequestResponse->ok ) {
			do_action( 'woocommerce_add_pesapal_iframe_error', $orderRequestResponse->message );
			return;
		}
		
		error_log( json_encode( $orderRequestResponse ) );

		$payment = get_payment_by_order_id( $order->get_id() );

		if ( false === $payment || empty( $payment ) ) {
			$newPaymentData = (object) array(
				'order_id' 					=> $order->get_id(),
				'order_tracking_id' => $orderRequestResponse->data->order_tracking_id,
				'merchant_reference'=> $orderRequestResponse->data->merchant_reference, // same as ($order->get_order_key() + date()->now())
				'token' 						=> $authenticationResponse->data->token,
				'amount_paid' 			=> $order->get_total(),
				'currency' 					=> $order->get_currency(),
			);
	
			add_pesapal_payment( $newPaymentData );
		}

		if ( !isset($orderRequestResponse->data->redirect_url) ) {
			!do_action( 'woocommerce_add_pesapal_iframe_error', 'Error loading Pesapal' );
			return;
		}

		do_action( 'woocommerce_add_pesapal_iframe', $orderRequestResponse->data->redirect_url );
	}

	public function pesapal_ipn_handler() {
		$payment = get_payment_by_order_tracking_id( $_GET['OrderTrackingId'] );

		if ( false === $payment || empty($payment) ) {
			error_log('pesapal_ipn_handler::payment::false::start');
			error_log('');
			error_log( json_encode( $_GET ));
			error_log('');
			error_log('pesapal_ipn_handler::payment::false::end');
			return;
		}

		$order = wc_get_order( $payment->order_id );

		$options = (object) array(
      'token' => $payment->token,
      'orderTrackingId' => $payment->order_tracking_id
    );

		$orderStatusResponse = $this->get_order_status( $options );

		if ( false !== $orderStatusResponse->ok ) {
			$paymentOtherData = (object) array(
				"amount_paid" => $orderStatusResponse->data->amount, // update this too
				"payment_method" => $orderStatusResponse->data->payment_method,
				"payment_account" => $orderStatusResponse->data->payment_account,
				"payment_status_description" => $orderStatusResponse->data->payment_status_description,
				"status_code" => $orderStatusResponse->data->status_code
			);

			update_pesapal_payment( $payment->id, $paymentOtherData );

			if (
				1 === (int) $orderStatusResponse->data->status_code
				&& 'Completed' === $orderStatusResponse->data->payment_status_description
			) {
				$order->update_status( 'Processing' );
				WC()->cart->empty_cart();
			}
		} else {
			error_log('pesapal_ipn_handler::orderStatusResponse->ok::false::start');
			error_log('');
			error_log( json_encode( $orderStatusResponse ) );
			error_log('');
			error_log('pesapal_ipn_handler::orderStatusResponse->ok::false::end');
		}
	}

	private function authenticate() {
		$body = array(
			'consumer_key' => $this->consumer_key,
			'consumer_secret' => $this->consumer_secret
		);

		$response = wp_remote_post( $this->get_pesapal_endpoint_url() . $this->authentication_api_url, array(
			'body' => json_encode( $body ),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			),
		));

		if (is_wp_error($response)) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => $response->get_error_message(),
			);
		}

		if ( 200 != wp_remote_retrieve_response_code($response) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => wp_remote_retrieve_response_message($response),
			);
		}
		
		// Request was successful
		$body = wp_remote_retrieve_body($response);

		$errors = $this->get_error_from_body( json_decode($body) );

		if ( isset($errors) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => $errors,
			);
		}

		return (object) array(
			'ok' => true,
			'data' => json_decode($body)
		);
	}

	private function register_ipn(String $token) {
		$body = array(
			"url" => $this->get_pesapal_ipn_url(),
      "ipn_notification_type" => "GET",
		);

		$response = wp_remote_post( $this->get_pesapal_endpoint_url() . $this->register_ipn_api_url, array(
			'body' => json_encode( $body ),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'Authorization' => 'Bearer ' . $token
			),
		));

		if (is_wp_error($response)) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => $response->get_error_message(),
			);
		}

		if ( 200 != wp_remote_retrieve_response_code($response) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => wp_remote_retrieve_response_message($response),
			);
		}

		// Request was successful
		$body = wp_remote_retrieve_body($response);

		$errors = $this->get_error_from_body( json_decode($body) );

		if ( isset($errors) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => $errors,
			);
		}

		return (object) array(
			'ok' => true,
			'data' => json_decode($body)
		);
	}

	private function submit_order_request(Object $body, String $token) {
		$response = wp_remote_post( $this->get_pesapal_endpoint_url() . $this->submit_order_request_api_url, array(
			'body' => json_encode( $body ),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'Authorization' => 'Bearer ' . $token
			),
			'verify' => FALSE,
		));

		if ( is_wp_error($response) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => $response->get_error_message(),
			);
		}

		if ( 200 != wp_remote_retrieve_response_code($response) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => wp_remote_retrieve_response_message($response),
			);
		}

		// Request might be successful
		$body = wp_remote_retrieve_body($response);

		$errors = $this->get_error_from_body( json_decode($body) );

		if ( isset($errors) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => $errors,
			);
		}

		return (object) array(
			'ok' => true,
			'data' => json_decode($body)
		);
	}

	private function get_order_status( $options ) {
		$url = $this->get_pesapal_endpoint_url() . $this->get_transaction_status_api_url . '?orderTrackingId=' . $options->orderTrackingId;
		
		$response = wp_remote_get($url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
				'Authorization' => 'Bearer ' . $options->token
			),
			'verify' => FALSE,
		));

		if (is_wp_error($response)) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => $response->get_error_message(),
			);
		}

		if ( 200 != wp_remote_retrieve_response_code($response) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => wp_remote_retrieve_response_message($response),
			);
		}
		
		// Request was successful
		$body = wp_remote_retrieve_body($response);

		$errors = $this->get_error_from_body( json_decode($body) );

		if ( isset($errors) ) {
			// Handle the error
			return (object) array(
				'ok' => false,
				'message' => $errors,
			);
		}

		return (object) array(
			'ok' => true,
			'data' => json_decode($body)
		);
	}

	private function get_error_from_body( $body ) {
		// var_dump($body);

		if ( 200 !== (int) $body->status ) {
			return $body->error->message;
		}
	}
}
