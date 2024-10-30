<?php
/*
 * Plugin Name: Campay Woocommerce Payment Gateway
 * Plugin URI: https://campay.net/wordpress/campay-payment-gateway/
 * Description: Accept Mobile Money Payment using CamPay API Services.
 * Author: CamPay
 * Author URI: https://campay.net/
 * Version: 1.1.1
 */
 
 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

 /* check if woocommerce is active then create or plugin */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
 $all_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (stripos(implode($all_plugins), 'woocommerce.php') && is_plugin_active( 'woocommerce/woocommerce.php' )) {

add_filter( 'woocommerce_payment_gateways', 'campay_add_gateway_class' );
function campay_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_CamPay_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'campay_init_gateway_class' );
function campay_init_gateway_class() {
 
	class WC_CamPay_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
		public function __construct() {
	
			add_action("wp_footer", array(&$this, 'campay_payment_processing_modal' ));
			add_action('wp_footer', array(&$this, 'campay_checkout_form_submit'));
	 
			$this->id = 'campay'; // payment gateway plugin ID
			$this->icon = plugins_url( 'assets/img/logo-campay-momo.png', __FILE__ ); // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields = true; 
			$this->method_title = 'CamPay';
			$this->method_description = 'Description of CamPay gateway'; // will be displayed on the options page
			$this->testmode = $this->get_option( 'testmode' );
			$this->dollar_activated = $this->get_option( 'dollar_activated' ); //activate support of USD
			$this->euro_activated = $this->get_option( 'euro_activated' ); //activate support of EURO
			$this->usd_xaf = $this->get_option( 'usd_xaf' ); //get USD to XAF convertion rate set by user
			$this->euro_xaf = $this->get_option( 'euro_xaf' ); //get EURO to XAF convertion rate set by user
			// gateways can support subscriptions, refunds, saved payment methods,
			// but in this tutorial we begin with simple payments
			$this->supports = array(
				'products'
			);
		 
			// Method with all the options fields
			$this->init_form_fields();
		 
			// Load the settings.
			$this->init_settings();
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );

			//$this->testmode = 'yes' === $this->get_option( 'testmode' );
			$this->campay_username = $this->get_option( 'campay_username' );
			$this->campay_password = $this->get_option( 'campay_password' );
		 
			// This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		 
			// We need custom JavaScript to obtain a token
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_js' ) );
		 
			// You can also register a webhook here
			// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
		}
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
 
		$this->form_fields = array(
		'enabled' => array(
			'title'       => 'Enable/Disable',
			'label'       => 'Enable CamPay Payment Gateway',
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => 'Title',
			'type'        => 'text',
			'description' => 'This controls the title which the user sees during checkout.',
			'default'     => 'CamPay Mobile Money',
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => 'Description',
			'type'        => 'textarea',
			'description' => 'This controls the description which the user sees during checkout.',
			'default'     => 'Pay with your MoMo or OM via our super-cool payment gateway.'.PHP_EOL.'
To confirm Orange Money transaction dial #150*50# after placing your order. For MTN Mobile Money dial *126#'.PHP_EOL.'
Order will timeout after 2 minutes',
		),
	
		'testmode' => array(
			'title'       => 'Test mode',
			'label'       => 'Enable Test Mode',
			'type'        => 'checkbox',
			'description' => 'Place the payment gateway in test mode using test server.',
			'default'     => 'yes',
			'desc_tip'    => true,
		),		
		
		'dollar_activated' => array(
			'title'       => 'Support of USD',
			'label'       => 'Enable USD to XAF conversion',
			'type'        => 'checkbox',
			'description' => 'Allow gateway to convert USD to XAF before payment',
			'default'     => 'no',
			'desc_tip'    => true,
		),		
		'euro_activated' => array(
			'title'       => 'Support of EURO',
			'label'       => 'Enable EURO to XAF conversion',
			'type'        => 'checkbox',
			'description' => 'Allow gateway to convert EURO to XAF before payment',
			'default'     => 'no',
			'desc_tip'    => true,
		),
		'usd_xaf' => array(
			'title'       => 'USD to XAF conversion rate',
			'type'        => 'text',
			'default'     => '550',
		),
		'euro_xaf' => array(
			'title'       => 'EURO to XAF conversion rate',
			'type'        => 'text',
			'default'     => '650',
		),		
		'campay_username' => array(
			'title'       => 'App Username',
			'type'        => 'text'
		),
		'campay_password' => array(
			'title'       => 'App Password',
			'type'        => 'password'
		)
			
	);
		
		
	 	}
 
		/**
		 * You will need it if you want your customize field
		 */
		public function payment_fields() {
 
		// ok, let's display some description before the payment form
			if ( $this->description ) {
				/* you can instructions for test mode, I mean test card numbers etc.
				if ( $this->testmode ) {
					$this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#" target="_blank" rel="noopener noreferrer">documentation</a>.';
					
				}
				*/
				$this->description  = trim( $this->description );
				// display the description with <p> tags etc.
				echo wpautop( wp_kses_post( $this->description ) );
			}
 
		// I will echo() the form, but you can close PHP tags and print it directly in HTML
		echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-campay-gateway" style="background:transparent;">';
		// error message
		echo '<span id="campay-number-error">The number entered is not a valid MTN or ORANGE number</span>';
		// I recommend to use inique IDs, because other gateways could already use
		echo '<div class="form-row form-row-wide"><label>Valid MTN or ORANGE Money number <span class="required">*</span></label>
				<input id="campay_transaction_number" name="campay_transaction_number" type="text" placeholder="'.__("9 digits phone number accepted", "campay").'" max="999999999" oninput="this.value =this.value.replace(/[^0-9]/g, \'\').replace(/(\.*?)\.*/g, \'$1\');" onchange="validate_number(this)" >
			</div>
			<div class="clear"></div>';
	 
		echo '<div class="clear"></div></fieldset>';
 
 
		}
 
		
	 	public function payment_scripts() {

		// we need JavaScript to process a token only on cart/checkout pages, right?
		if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
			return;
		}
 
		// if our payment gateway is disabled, we do not have to enqueue JS too
		if ( 'no' === $this->enabled ) {
			return;
		}
 
		// no reason to enqueue JavaScript if API keys are not set
		if ( empty( $this->campay_username ) || empty( $this->campay_password ) ) {
			return;
		}
 
		// do not work with card detailes without SSL unless your website is in a test mode
		if ( ! is_ssl() ) {
			return;
		}

 
	 	}
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
 
			if( empty( $_POST[ 'campay_transaction_number' ]) ) {
				wc_add_notice(  'The mobile number entered for the transaction is not valid!', 'error' );
				return false;
			}
			else
			{
				if(!is_numeric($_POST['campay_transaction_number']) && strlen($_POST['campay_transaction_number'])!=9)
				{
					wc_add_notice(  'The mobile number entered for the transaction is not valid!', 'error' );
					return false;
				}
			}

			return true;
		}
		
		/*enqueue scripts */
		
		function payment_js()
		{
			
			wp_enqueue_script( 'woocommerce_campay_js', plugins_url( 'assets/js/campay.js', __FILE__ ), array( 'jquery'), false, true );
			wp_enqueue_style('woocommerce_campay_css', plugins_url( 'assets/css/campay.css', __FILE__ ), array(), '1.0.0', 'all' );
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
		
			if(!isset($_POST['campay_transaction_number']))
				return ;
			
			if($this->testmode=="yes")
				$server_uri = "https://demo.campay.net";
			else
				$server_uri = "https://www.campay.net";
			
			global $woocommerce;
 
			// we need it to get any order detailes
			$trans_number = rtrim(trim(sanitize_text_field($_POST['campay_transaction_number'])));
			$trans_number = "237".$trans_number;

			$order = wc_get_order( $order_id );
			$order_created_date = $order->get_date_created();
			$payment_timeout = 15;
			$order_expiry_time = $order_created_date;
			$order_expiry_time->add(new DateInterval("PT5M"));
			$price = $order->get_total();
			$currency = "XAF";
			$description = "Payment from : ".site_url()." for other : ".$order->get_id();
			$external_reference = $this->guidv4();
			$order_currency = $order->get_currency();
			$order_currency = strtoupper($order_currency);
			
			$default_usd_xaf = 550;
			$default_usd_xaf = 650;
			
			
			if($order_currency=="USD" && (!empty($this->usd_xaf) || $this->usd_xaf==0))
			{
				if($this->dollar_activated=="yes")
				{
					$conversion_rate = (int) sanitize_text_field($this->usd_xaf);
					$converted_price = round(($conversion_rate * $price), 0);
					$price = $converted_price;
				}
				else{		
					$converted_price = round(($default_usd_xaf * $price), 0);
					$price = $converted_price;
				}
			}

			if($order_currency=="EUR")
			{
				if($this->euro_activated=="yes" && (!empty($this->euro_xaf)|| $this->euro_xaf==0))
				{
					$conversion_rate = (int) sanitize_text_field($this->euro_xaf);
					$converted_price = round(($conversion_rate * $price), 0);
					$price = $converted_price;
				}
				else{		
					$converted_price = round(($default_euro_xaf * $price), 0);
					$price = $converted_price;
				}
			}

			$token = $this->get_token($server_uri);

			$params = array(
				"amount"=>$price,
				"currency"=>$currency,
				"from"=>$trans_number,
				"description"=>$description,
				"external_reference"=>$external_reference
			);
			
			$params = json_encode($params);
			
			$today = strtotime("now");
			
			$expiry = strtotime("+".$payment_timeout." minutes", $today);
			
			$trans = $this->execute_payment($token, $params, $server_uri);
			
			
			
			if(!empty($trans) && !is_object($trans))
			{
				$payment_completed = false;
				
				while(strtotime("now")<=$expiry)
				{
					sleep(5);
					$payment = $this->check_payment($token, $trans, $server_uri);
				
					if(!empty($payment))
					{
						if(strtoupper($payment->status)=="SUCCESSFUL")
						{
							$payment_completed = true;
							$order->update_status('completed', __('Payment received', 'campay'));
							$order->add_order_note( 'Transaction complete with ref : '.$payment->reference.PHP_EOL."Operator Ref : ".$payment->operator_reference.PHP_EOL."Operator : ".$payment->operator, true );
							// Reduce stock levels
							$order->reduce_order_stock();
							// Remove cart
							WC()->cart->empty_cart();	
							
							break;
						}
						if(strtoupper($payment->status)=="FAILED")
						{
							break;
						}
					}
					
					
				}
				
				if($payment_completed && strtoupper($payment->status)=="SUCCESSFUL")
				{
					return array(
						'result' => 'success',
						'redirect' => $this->get_return_url($order)
					);
				}	
				elseif(!$payment_completed && strtoupper($payment->status)=="PENDING")
				{
					wc_add_notice(  'The payment time out. Failed to proceed your order', 'error' );
					$order->update_status('failed', __('Payment not received received', 'campay'));
					$order->add_order_note( 'Transaction failed with ref : '.$payment->reference.PHP_EOL."Operator : ".$payment->operator, true );
				}
				else
				{
					wc_add_notice(  'Payment failed! Payment was declined by payer or insuficient funds', 'error' );
					$order->update_status('failed', __('Payment not received received', 'campay'));
					$order->add_order_note( 'Transaction failed with ref : '.$payment->reference.PHP_EOL."Operator : ".$payment->operator, true );
				}				
				
			}
			else
			{
				wc_add_notice(  'Failed to initiate transaction please try again later', 'error' );
				$order->update_status('failed', __('Payment received', 'campay'));
				
			}
	 	}
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
 
		
 
	 	}
		
		/*
		 * Get token from campay
		 */
		
		public function get_token($server_uri)
		{
			
			$user = $this->campay_username;
			$pass = $this->campay_password;
			
			$params = array("username"=>$user, "password"=>$pass);
			//$params = json_encode($params);
			
			$headers = array('Content-Type: application/json');
			
			$response = wp_remote_post($server_uri."/api/token/", array(
				"method"=>"POST",
				"sslverify"=>true,
				"headers"=>$headers,
				"body"=>$params
			));
		
			if(!is_wp_error($response))
			{
				$response_body = wp_remote_retrieve_body($response);
				$resp_array = json_decode($response_body);

				if(isset($resp_array->token) && !isset($resp_array->non_field_errors))
					return $resp_array->token;
				elseif(!isset($resp_array->token) && isset($resp_array->non_field_errors))
				    wc_add_notice(  $resp_array->non_field_errors[0], 'error' );
				else
					wc_add_notice(  'Unable to get access token', 'error' );
			}
			else
				wc_add_notice(  'Failed to initiate transaction please try again later', 'error' );
			
			
			
		}
		
		public function execute_payment($token, $params, $server_uri)
		{
			
			$headers = array(
				'Authorization' => 'Token '.$token,
				'Content-Type' => 'application/json'
				);
				
			$response = wp_remote_post($server_uri."/api/collect/", array(
				"method"=>"POST",
				"sslverify"=>true,
				"body"=>$params,				
				"headers"=>$headers,
				"data_format"=>"body"
			));			
			
			if(!is_wp_error($response))
			{
				$response_body = wp_remote_retrieve_body($response);
				$resp_array = json_decode($response_body);
				if(isset($resp_array->reference))
					return $resp_array->reference;
				if(!isset($resp_array->reference) && isset($resp_array->message))
					wc_add_notice(  $resp_array->message, 'error' );
			}
			else
				wc_add_notice(  'Failed to initiate transaction please try again later', 'error' );
			
		}
		
		public function check_payment($token, $trans, $server_uri)
		{
			
			$headers = array(
				'Authorization' => 'Token '.$token,
				'Content-Type' => 'application/json'
			);
			
			$response = wp_remote_get($server_uri."/api/transaction/".$trans."/", array(
				"sslverify"=>true,				
				"headers"=>$headers,
			));
			
			if(!is_wp_error($response))
			{
				$response_body = wp_remote_retrieve_body($response);
				$resp_array = json_decode($response_body);
				
				if(isset($resp_array->status))
					return $resp_array;
				else
					wc_add_notice(  'Invalid Transaction Reference', 'error' );
			}
			else
				wc_add_notice(  'Failed to initiate transaction please try again later', 'error' );			
			
		
		}
		
		public  function guidv4($data = null) {
			// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
			$data = $data ?? random_bytes(16);
			assert(strlen($data) == 16);

			// Set version to 0100
			$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
			// Set bits 6-7 to 10
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

			// Output the 36 character UUID.
			return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
		}	

		public function campay_payment_processing_modal()
		{
				if(is_checkout())
				{
					?>
					
					<div id="campay_modal_processing" class="modal">

					  <!-- Modal content -->
					  <div class="modal-content">
						<h3 style="text-align:center; text-decoration: underline">PAYMENT PROCESSING</h3>
							<p class="cp_payment_info">
								We are waiting for your payment. Please dial *126# for MTN and #150*50# for Orange.
							</p>
							<div class="cp_payment_waiting">
								<img src="<?php echo plugins_url( 'assets/img/wait.gif', __FILE__ ); ?>" />
							</div>
					  </div>

					</div>
					
					<?php
				}
		}
				
		public function campay_checkout_form_submit()
		{
				if(is_checkout())
				{
					?>
					<script>
					    var form = document.getElementsByName("checkout");
						
						if(form)
						{
							
							function checkCampay()
							{
								var payment_method = document.getElementsByName("payment_method");
								function isChecked(item)
								{
									if(item.checked)
									{
										if(item.value=="campay")
											document.getElementById("campay_modal_processing").style.display="block";
										
									}
								}
								
								payment_method.forEach(isChecked);
							
							}

							form[0].addEventListener("submit", checkCampay);
						}
						
					</script>
					<?php
				}
		}
		
 	}
	

}
 
}
else{
	return false;
}