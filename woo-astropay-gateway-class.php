<?php
if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    //Woocommerce is not active.
    return;
}

class WC_AstroPay_Gateway extends WC_Payment_Gateway {

    protected $ASTROPAY_URL_SANDBOX	 = "https://sandbox-api.astropaycard.com";
    protected $ASTROPAY_URL_LIVE		 = "https://api.astropaycard.com";
    protected $ASTROPAY_PAYMENTACTION	 = "AUTH_CAPTURE";
    protected $order			 = null;
    protected $transactionId		 = null;
    protected $transactionErrorMessage	 = null;
    protected $usesandboxapi		 = true;
    protected $securitycodehint		 = true;
    protected $astropayxlogin			 = '';
    protected $astropayxtranskey			 = '';
    protected $astropaysecretkey			 = '';
	
    protected $astropaystgxlogin			 = '';
    protected $astropaystgxtranskey			 = '';
    protected $astropaystgsecretkey			 = '';


    public function __construct() {
	$this->id		 = 'astropay'; //ID needs to be ALL lowercase or it doens't work
	$this->GATEWAYNAME	 = 'AstroPay';
	$this->method_title	 = 'AstroPay';
	$this->method_description = 'Accepts payment using AstroPay card'; // will be displayed on the options page
	$this->has_fields	 = true;

	$this->init_form_fields();
	$this->init_settings();

	$this->description	 = $this->settings[ 'description' ];
	$this->usesandboxapi	 = strcmp( $this->settings[ 'debug' ], 'yes' ) == 0;
	// ok, let's display some description before the payment form
	// you can instructions for test mode, I mean test card numbers etc.
	if ( $this->usesandboxapi ) {
		$this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="https://developers.astropay.com/?php#test-cards" target="_blank" rel="noopener noreferrer">documentation</a>.';
		$this->description  = trim( $this->description );
	}
	// display the description with <p> tags etc.
	
	
	//If the field is populated, it will grab the value from there and will not be translated.  If it is empty, it will use the default and translate that value
	$this->title		 = strlen( $this->settings[ 'title' ] ) > 0 ? $this->settings[ 'title' ] : 'AstroPay Card Payment';
	$this->astropayxlogin	 = $this->settings[ 'astropayxlogin' ];
	$this->astropayxtranskey	 = $this->settings[ 'astropayxtranskey' ];
	$this->astropaysecretkey	 = $this->settings[ 'astropaysecretkey' ];

	$this->astropaystgxlogin	 = $this->settings[ 'astropaystgxlogin' ];
	$this->astropaystgxtranskey	 = $this->settings[ 'astropaystgxtranskey' ];
	$this->astropaystgsecretkey	 = $this->settings[ 'astropaystgsecretkey' ];



	add_filter( 'http_request_version', array( &$this, 'get_http_ver' ) );
	add_action( 'admin_notices', array( &$this, 'admin_notice_msg' ) );
	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
    }

    public function admin_options() {
	?>
	<h3><?php _e( 'AstroPay', 'woo-astropay-card-payment' ); ?></h3>
	<p><?php _e( 'Allows Credit Card Payments via the AstroPay gateway. Make sure to add your server\'s IP address in your merchant control panel. For more information visit <a href="https://developers.astropay.com/#getting-started" target="_blank" rel="noopener noreferrer">here </a>.', 'woo-astropay-card-payment' ); ?></p>

	<table class="form-table">
	    <?php
	    //Render the settings form according to what is specified in the init_form_fields() function
	    $this->generate_settings_html();
	    ?>
	</table>
	<?php
    }

    public function init_form_fields() {
	$this->form_fields = array(
	    'enabled'		 => array(
		'title'		 => __( 'Enable/Disable', 'woo-astropay-card-payment' ),
		'type'		 => 'checkbox',
		'label'		 => __( 'Enable AstroPay Gateway', 'woo-astropay-card-payment' ),
		'default'	 => 'yes'
	    ),
	    'debug'			 => array(
		'title'		 => __( 'Sandbox Mode', 'woo-astropay-card-payment' ),
		'type'		 => 'checkbox',
		'label'		 => __( 'Enable Sandbox Mode', 'woo-astropay-card-payment' ),
		'default'	 => 'no'
	    ),
	    'title'			 => array(
		'title'		 => __( 'Title', 'woo-astropay-card-payment' ),
		'type'		 => 'text',
		'description'	 => __( 'The title for this checkout option.', 'woo-astropay-card-payment' ),
		'default'	 => __( 'AstroPay Card Payment', 'woo-astropay-card-payment' )
	    ),
	    'description' => array(
			'title'       => 'Description',
			'type'        => 'textarea',
			'description' => 'This controls the description which the user sees during checkout.',
			'default'     => 'Pay with your AstroPay card via our payment gateway.',
		),
		'livekeys'     => array(
			'title'       => __( 'Live Environment Keys', 'woo-astropay-card-payment' ),
			'type'        => 'title',
			'description' => 'Following keys are for live/production environment',
		),
	    'astropayxlogin'	 => array(
		'title'		 => __( 'AstroPay x_login', 'woo-astropay-card-payment' ),
		'type'		 => 'text',
		'description'	 => __( 'Your AstroPay x_login.', 'woo-astropay-card-payment' ),
		'default'	 => __( '', 'woo-astropay-card-payment' )
	    ),
	    'astropayxtranskey'	 => array(
		'title'		 => __( 'AstroPay Transaction Key', 'woo-astropay-card-payment' ),
		'type'		 => 'text',
		'description'	 => __( 'Your AstroPay x_trans_key.', 'woo-astropay-card-payment' ),
		'default'	 => __( '', 'woo-astropay-card-payment' )
	    ),
	    'astropaysecretkey'	 => array(
		'title'		 => __( 'AstroPay Secret Key', 'woo-astropay-card-payment' ),
		'type'		 => 'text',
		'description'	 => __( 'Your AstroPay secret key.', 'woo-astropay-card-payment' ),
		'default'	 => __( '', 'woo-astropay-card-payment' )
	    ),
		'stagingkeys'     => array(
			'title'       => __( 'Staging Environment Keys', 'woocommerce-directa24-payment-gateway' ),
			'type'        => 'title',
			'description' => 'Following keys are for staging environment',
		),
	    'astropaystgxlogin'	 => array(
		'title'		 => __( 'AstroPay x_login', 'woo-astropay-card-payment' ),
		'type'		 => 'text',
		'description'	 => __( 'Your AstroPay x_login.', 'woo-astropay-card-payment' ),
		'default'	 => __( '', 'woo-astropay-card-payment' )
	    ),
	    'astropaystgxtranskey'	 => array(
		'title'		 => __( 'AstroPay Transaction Key', 'woo-astropay-card-payment' ),
		'type'		 => 'text',
		'description'	 => __( 'Your AstroPay x_trans_key.', 'woo-astropay-card-payment' ),
		'default'	 => __( '', 'woo-astropay-card-payment' )
	    ),
	    'astropaystgsecretkey'	 => array(
		'title'		 => __( 'AstroPay Secret Key', 'woo-astropay-card-payment' ),
		'type'		 => 'text',
		'description'	 => __( 'Your AstroPay secret key.', 'woo-astropay-card-payment' ),
		'default'	 => __( '', 'woo-astropay-card-payment' )
	    )


	);
    }

    function admin_notice_msg() {

		if ( ! is_admin() ) {
			return false;
		}
	
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	
			echo '<div class="error"><p>';
			echo sprintf( esc_html__( '%1$sPayment Gateway AstroPay Card for Woocommerce is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for it to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s', 'woo-astropay-card-payment' ), '<strong>', '</strong>', '<a href="https://wordpress.org/plugins/woocommerce/">', '</a>', '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' );
			echo '</p></div>';
			return false;
		}
	
		if ( version_compare( get_option( 'woocommerce_version' ), '3.0', '<' ) ) {
	
			echo '<div class="error"><p>';
			echo sprintf( esc_html__( '%1$sPayment Gateway AstroPay Card for Woocommerce is inactive.%2$s This version requires WooCommerce 3.0 or newer. Please %3$supdate WooCommerce to version 3.0 or newer &raquo;%4$s', 'woo-astropay-card-payment' ), '<strong>', '</strong>', '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' );
			echo '</p></div>';
			return false;
	
		}


		if ( ! $this->usesandboxapi && get_option( 'woocommerce_force_ssl_checkout' ) == 'no' && $this->enabled == 'yes' ) {
			$greater_than_33 = version_compare( '3.3', WC_VERSION );
			$wc_settings_url = admin_url( sprintf( 'admin.php?page=wc-settings&tab=%s', $greater_than_33 ? 'advanced' : 'checkout' ) );
			echo '<div class="error"><p>' . sprintf( __( '%s gateway requires SSL certificate for better security. The <a href="%s">Secure checkout</a> option is disabled on your site. Please ensure your server has a valid SSL certificate so you can enable the SSL option on your checkout page.', 'woo-astropay-card-payment' ), $this->GATEWAYNAME, $wc_settings_url ) . '</p></div>';
		}
    }

    /*
     * Validates the fields specified in the payment_fields() function.
     */

    public function validate_fields() {
		global $woocommerce;
	
		if ( ! WC_AstroPay_Utility::is_card_number_valid( sanitize_text_field($_POST[ 'astropay_billing_credircard' ] ) ) ) {
			wc_add_notice( __( 'AstroPay card number you entered is invalid.', 'woo-astropay-card-payment' ), 'error' );
		}
		if ( ! WC_AstroPay_Utility::is_expirydt_valid( sanitize_text_field($_POST[ 'astropay_billing_expdatemonth' ]), sanitize_text_field($_POST[ 'astropay_billing_expdateyear' ]) ) ) {
			wc_add_notice( __( 'AstroPay card expiration date is not valid.', 'woo-astropay-card-payment' ), 'error' );
		}
		if ( ! WC_AstroPay_Utility::is_cvv_valid( sanitize_text_field($_POST[ 'astropay_billing_ccvnumber' ] )) ) {
			wc_add_notice( __( 'AstroPay card verification number (CVV) is not valid. You can find this number on your card.', 'woo-astropay-card-payment' ), 'error' );
		}
    }

    /*
     * Render the credit card fields on the checkout page
     */

    public function payment_fields() 
	{
		$astropay_billing_credircard		 = isset( $_REQUEST[ 'astropay_billing_credircard' ] ) ? esc_attr( $_REQUEST[ 'astropay_billing_credircard' ] ) : '';
	?>
        <style>
		.wcastropay-logo-section img {width:200px;}
		.wcastropay-notice {font-style: italic;  font-weight: bold;}
		</style>
        <div class="wcastropay-logo-section"><img src="<?php echo WC_ASTROPAY_ADDON_URL; ?>/images/Logo-APC-Transparente.png" alt="AstroPay logo" /></div>
		<div class="clear"></div>
        
    <?php  echo wpautop( wp_kses_post( $this->description ) ); ?>
        <p class="wcastropay-notice"><?php _e( 'Exclusive use for AstroPay card.  Do not insert credit card details. To purchase an AstroPay card visit  <a href="https://web.astropaycard.com/" rel="nofollow" target="_blank">https://web.astropaycard.com/</a>', 'woo-astropay-card-payment' ); ?></p>
		
        <p class="form-row validate-required">
			<?php
			$card_number_field_placeholder	 = __( 'Card Number', 'woo-astropay-card-payment' );
			
			?>            
			<label><?php _e( 'Card Number', 'woo-astropay-card-payment' ); ?> <span class="required">*</span></label>
			<input class="input-text" type="text" size="19" maxlength="19" name="astropay_billing_credircard" value="<?php echo $astropay_billing_credircard; ?>" placeholder="<?php echo $card_number_field_placeholder; ?>" />
		</p>         
		<div class="clear"></div>
		<p class="form-row form-row-first">
			<label><?php _e( 'Expiration Date', 'woo-astropay-card-payment' ); ?> <span class="required">*</span></label>
			<select name="astropay_billing_expdatemonth">
			<option value=01>01</option>
			<option value=02>02</option>
			<option value=03>03</option>
			<option value=04>04</option>
			<option value=05>05</option>
			<option value=06>06</option>
			<option value=07>07</option>
			<option value=08>08</option>
			<option value=09>09</option>
			<option value=10>10</option>
			<option value=11>11</option>
			<option value=12>12</option>
			</select>
			<select name="astropay_billing_expdateyear">
			<?php
			$today				 = (int) date( 'Y', time() );
			for ( $i = 0; $i < 12; $i ++ ) {
				?>
				<option value="<?php echo $today; ?>"><?php echo $today; ?></option>
				<?php
				$today ++;
			}
			?>
			</select>            
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first validate-required">
			<?php
			$cvv_field_placeholder	 = __( 'Card Verification Number (CVV)', 'woo-astropay-card-payment' );
			$cvv_field_placeholder	 = apply_filters( 'wcpprog_cvv_field_placeholder', $cvv_field_placeholder );
			?>
			<label><?php _e( 'Card Verification Number (CVV)', 'woo-astropay-card-payment' ); ?> <span class="required">*</span></label>
			<input class="input-text" type="text" size="4" maxlength="4" name="astropay_billing_ccvnumber" value="" placeholder="<?php echo $cvv_field_placeholder; ?>" />
		</p>
        <div class="clear"></div>
	
<?php
    }

    public function process_payment( $order_id )
	{
		global $woocommerce;
		$this->order		 = new WC_Order( $order_id );
		if ( $this->verify_astropay_payment()) 
		{
			$this->do_order_complete_tasks();
	
			return array(
			'result'	 => 'success',
			'redirect'	 => $this->get_return_url( $this->order )
			);
		} else {
			$this->mark_as_failed_payment();
			wc_add_notice( __( '(Transaction Error) something is wrong.', 'woo-astropay-card-payment' ), 'error' );
		}
    }

    /*
     * Set the HTTP version for the remote posts
     * https://developer.wordpress.org/reference/hooks/http_request_version/
     */

    public function get_http_ver( $httpversion ) {
		return '1.1';
    }

    protected function mark_as_failed_payment() {
		$this->order->add_order_note( sprintf( "AstroPay card Payment Failed with message: '%s'", $this->transactionErrorMessage ) );
    }

    protected function do_order_complete_tasks() 
	{
		global $woocommerce;
		if ( $this->order->get_status() == 'completed' )
			return;
	
		$this->order->payment_complete();
		$woocommerce->cart->empty_cart();
		$this->order->add_order_note(
			sprintf( "AstroPay card payment completed with Transaction Id of '%s'", $this->transactionId )
		);
	
		unset( $_SESSION[ 'order_awaiting_payment' ] );
    }

    protected function verify_astropay_payment()
	{
		global $woocommerce;
		$erroMessage	 = "";
		$api_url	 = $this->usesandboxapi ? $this->ASTROPAY_URL_SANDBOX : $this->ASTROPAY_URL_LIVE;
		$api_url.='/verif/validator';
		
		$xlogin		 = $this->usesandboxapi ? $this->astropaystgxlogin : $this->astropayxlogin;
		$transkey	 = $this->usesandboxapi ? $this->astropaystgxtranskey : $this->astropayxtranskey;
		$secretkey	 = $this->usesandboxapi ? $this->astropaystgsecretkey : $this->astropaysecretkey;		
		
		$requestBody=array(
		  'x_login' => $xlogin,
		  'x_trans_key' => $transkey,
		  'x_type' => $this->ASTROPAY_PAYMENTACTION,
		  'x_card_num' => sanitize_text_field($_POST[ 'astropay_billing_credircard' ]),
		  'x_card_code' => sanitize_text_field($_POST[ 'astropay_billing_ccvnumber' ]),
		  'x_exp_date' => sprintf( '%s/%s', sanitize_text_field($_POST[ 'astropay_billing_expdatemonth' ]), sanitize_text_field($_POST[ 'astropay_billing_expdateyear' ]) ),
		  'x_amount' => $this->order->get_total(),
		  'x_currency' => get_woocommerce_currency(),
		  'x_unique_id' => $this->order->get_order_key(),
		  'x_invoice_num' => $this->order->get_order_number()
		);
		$requestHeader=array(
		  'cache-control' => 'no-cache',
		  'Connection' => 'keep-alive',
		  'Content-Length' => '244',
		  'Accept-Encoding' => 'gzip, deflate',
		  'Host' => parse_url($api_url,PHP_URL_HOST),
		  'Cache-Control' => 'no-cache',
		  'Accept' => '*/*',
		  'Content-Type' => 'application/x-www-form-urlencoded'
		);
		
		$request	 = array(
			'method'	 => 'POST',
			'timeout'	 => 45,
			'blocking'	 => true,
			'sslverify'	 => true,
			'headers' => $requestHeader,
			'body'		 => $requestBody
		);
	
		//print_r($request);
		$response = wp_remote_post( $api_url, $request );
		if (!is_wp_error($response)) 
		{
			$parsedResponse = $this->parse_astropay_response( $response );
			switch ( $parsedResponse[0] ) 
			{
				  case '1':
					  $this->transactionId = $parsedResponse[6]; //TransactionID
					  return true;
					  break;
				  case '2':
					  $this->transactionErrorMessage = $erroMessage = $parsedResponse[3]; //response_reason_text
					  break;
				  case '3':
					  $this->transactionErrorMessage = $erroMessage = $parsedResponse[3]; //response_reason_text
					  break;
	  
				  default:
					  $this->transactionErrorMessage = $erroMessage = $parsedResponse[3]; //response_reason_text
					  break;
			}

		} else {
			$erroMessage = 'Something went wrong while performing your request. '. print_r($response->errors, true);
		}


		wc_add_notice( $erroMessage, 'error' );
		return false;
    }

    protected function parse_astropay_response( $response ) 
	{
		$result		 = array();
		$result	 = explode( '|', $response[ 'body' ]);
		return $result;
    }


}

//End of class