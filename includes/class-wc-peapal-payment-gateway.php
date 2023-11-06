<?php

class WC_Gateway_Pesapal extends WC_Payment_Gateway {
  public $consumer_key;
  public $consumer_secret;
  public $instructions;
  public $enable_for_methods;


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
      'Production Mode'=> array(
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
		);
	}
}
