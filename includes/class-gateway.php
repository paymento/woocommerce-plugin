<?php

/**
 * Created by PhpStorm.
 * User: Ali Azarmi
 * Date: 6/18/17
 * Time: 7:02 PM
 */
if (!class_exists('WC_Payment_Gateway')) {
	return;
}
class WC_PAYMENTO_Gateway extends WC_Payment_Gateway {

	private $api_key;


	/**
	 * Loads the class, runs on init
	 *
	 * @return void
	 */
	public static function load() {
		add_action( 'rest_api_init', array( __CLASS__, 'wk_register_custom_routes' ) );
	}


	public function __construct()
	{
		//    		wp_register_style( 'new_style', PAYMENTOGW_URL.'assets/css/style.css', array(), '1.0.0', true);
  	//              wp_enqueue_style( 'new_style' );

		$this->id = 'paymento_gateway';
		$this->icon = PAYMENTOGW_URL.'assets/images/Paymento_Logo-03.webp';
		$this->has_fields = true;
		$this->method_title = __('Paymento Crypto gateway', 'paymento');
		$this->method_description = __('Paymento electronic payment gateway for Woocommerce', 'paymento');

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->api_key = $this->get_option('api_key');
		$this->secret_key = $this->get_option('secret_key');
		$this->confirmation = $this->get_option('confirmation');
		$this->debug = $this->get_option('debug');

		

		// Actions
		add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_receipt_'.$this->id, array($this, 'send_to_bank'));
		add_action('woocommerce_api_'.strtolower(get_class($this)), array($this, 'return_from_bank'));
		add_filter('woocommerce_get_order_item_totals', array($this, 'show_transaction_in_order'), 10, 2 );
		add_filter( 'woocommerce_available_payment_gateways', array($this, 'filter_woocommerce_available_payment_gateways'), 10, 1 ); 

		add_action('wp_enqueue_scripts', array($this,'register_script'));
		add_action('paymento_result_action', array($this,'paymento_result_action_callback'), 20, 2);
		add_action( 'init', array($this,'register_shipped_order_status') );
		add_filter( 'wc_order_statuses', array($this,'custom_order_status'));

		add_action('admin_footer',  array($this,'paymento_custom_admin_js'));
	}

	function register_shipped_order_status() {
    register_post_status( 'wc-waiting-to-confirm', array(
        'label'                     => 'Waiting To Confirm',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Waiting To Confirm <span class="count">(%s)</span>', 'Waiting To Confirm <span class="count">(%s)</span>' )
    ) );
}

function paymento_custom_admin_js()
{
    ?>
      <style>
      </style>

      <script type="text/javascript">
        jQuery(function($) {
					var data = {
						'Api-Key' :  '<?php echo $this->get_option('api_key') ?>',
					};
					var paymento_helth_check = document.getElementById("paymento_helth_check");
					var req = $.get({
						url : '/wp-json/paymento/health', 
						data,
						headers: {
							'Content-Type': 'application/json',
							'Access-Control-Allow-Origin' : '*',
							'Access-Control-Allow-Credentials': 'true'
            },
						error: function(XMLHttpRequest, textStatus, errorThrown){
							paymento_helth_check.innerHTML = '<span style="padding:5px 10px; background-color:#f52f57; color:#fff;border-radius:5px;">Error</span>';
						},
						success:  function(response) {
							if(response.success)
								paymento_helth_check.innerHTML = '<span style="padding:5px 10px; background-color:#83f28f;border-radius:5px;">Good</span>';
							else
								paymento_helth_check.innerHTML = '<span style="padding:5px 10px; background-color:#f52f57;color:#fff;border-radius:5px;">Bad</span>';
						}
					});

					var paymento_merchant_name = document.getElementById("paymento_merchant_name");
					var req2 = $.get({
						url : '/wp-json/paymento/merchant', 
						data,
						headers: {
							'Api-Key' :  '<?php echo $this->get_option('api_key') ?>',
							'Content-Type': 'application/json',
            },
						error: function(XMLHttpRequest, textStatus, errorThrown){
							paymento_merchant_name.innerHTML = '<span style="padding:5px 10px; background-color:#f52f57; color:#fff;border-radius:5px;">Error</span>';
						},
						success:  function(response) {
							console.log(response.body);
							if(response.success == true){
								var status = 'Not Active';
							 if(response.body.isActive)
								 status = 'Active';
								 // paymento_merchant_name.innerHTML = '<span style="padding:5px 10px; background-color:#83f28f;border-radius:5px;">' + response.body.name + '( ' + (response.body.isActive) ? 'active' : 'not active' + ' )' + '</span>';
								 paymento_merchant_name.innerHTML = '<span style="padding:5px 10px; background-color:#83f28f;border-radius:5px;">' + response.body.name  + ' (' + status  + ') </span>';
							}
							else
								paymento_merchant_name.innerHTML = '<span style="padding:5px 10px; background-color:#f52f57;color:#fff;border-radius:5px;">Bad</span>';
						}
					});
			
					var handle_description = (data) => {
						var desc_to_change = document.getElementById("woocommerce_paymento_gateway_confirmation_description");
						if(data == 0)
								desc_to_change.innerHTML = '</br>Users will be redirected to your site immediately after making the payment. The invoice status will be set to "On Hold" until the transaction is confirmed. ';
							else if(data == 1)
								desc_to_change.innerHTML = '</br>Users will remain on the Paymento page until the transaction is confirmed. They will be redirected to your site once the payment is verified. ';
							else if(data == 2)
								desc_to_change.innerHTML = '</br>Users will be redirected to your site immediately after making the payment. The invoice status will be marked as "Paid" once the transaction is broadcasted before confirmation.';
							else
								desc_to_change.innerHTML = 'else';   
					}

					var x = document.getElementById("woocommerce_paymento_gateway_confirmation").parentElement;
					x.innerHTML += '<div id="woocommerce_paymento_gateway_confirmation_description" style="width: 50%;text-align: justify;"></div>';
					var data= $('select#woocommerce_paymento_gateway_confirmation').val();
					handle_description(data); 

					$('select#woocommerce_paymento_gateway_confirmation').change(function(){
						var data= $(this).val();
						handle_description(data); 
					});
        });
    </script>


    <?php
}

function custom_order_status( $order_statuses ) {
    $order_statuses['wc-waiting-to-confirm'] = _x( 'Waiting To Confirm', 'Order status', 'woocommerce' ); 
    return $order_statuses;
}

	public static	function wk_register_custom_routes() {

		register_rest_route( 'paymento', '/health', array(
			'methods' => 'GET',
			'callback' => array(__CLASS__,'wk_get_health_callback') ,
			'permission_callback' => '__return_true'
			) );
			register_rest_route( 'paymento', '/merchant', array(
				'methods' => 'GET',
				'callback' => array(__CLASS__,'wk_get_merchant_callback') ,
				'permission_callback' => '__return_true'
				) );
		register_rest_route( 'paymento', '/result', array(
			'methods' => 'POST',
			'callback' => array(__CLASS__,'wk_get_post_callback') ,
			'permission_callback' => '__return_true'
			) );

	}
	
	public static function wk_get_health_callback ($request){
		$args = array(
			// Increase the timeout from the default of 5 to 10 seconds
			'timeout'    => 10,
		
			// Overwrite the default: "WordPress/5.8;www.mysite.tld" header:
			'user-agent' => 'My special WordPress installation',
		
			// Add a couple of custom HTTP headers
			'headers'    => array(
				 'X-Custom-Id' => 'ABC123',
				 'X-Secret-Thing' => 'secret',
			),
		
			// Skip validating the HTTP servers SSL cert;
			'sslverify' => false,
		);
		
		$response = wp_remote_get( 'https://api.paymento.io/v1/ping/', $args );
		return new WP_REST_Response(json_decode($response['body']));
	}

	public static function wk_get_merchant_callback ($request){
		
		$headers = getallheaders();

		$args = array(
			// Add a couple of custom HTTP headers
			'headers'    => array(
				'Content-Type' => 'application/json',
				 'Api-Key'  => $headers['Api-Key'],
			),
		
			// Skip validating the HTTP servers SSL cert;
			'sslverify' => false,
		);
		
		$response = wp_remote_get( 'https://api.paymento.io/v1/ping/merchant/', $args );
		return new WP_REST_Response(json_decode($response['body']));

		// return new WP_REST_Response($request);
	}





	public static function wk_get_post_callback ($request){
		$headers = getallheaders();
		do_action( 'paymento_result_action', $request->get_json_params(), $headers);
		return new WP_REST_Response('good');
	}

	
	public function paymento_result_action_callback($result, $headers) {
		//"{\"Token\":\"fe3024f2b7f64b24ad822bca22341e70\",\"PaymentId\":244,\"OrderId\":\"957\",\"OrderStatus\":3,\"AdditionalData\":[]}"

		// $this->update_option( 'debug', 'callback headers: ' . json_encode($headers));


		if ( isset($result['OrderId']) ) {
			$order_id = absint( $result['OrderId'] );
		}
		if ( isset($order_id) && !empty($order_id) ) {
			$order = wc_get_order($order_id);
			if ($order->get_status() !== 'completed') {

				// Get data from bank
				$OrderId = isset($result['OrderId']) ? $result['OrderId'] : '';
				$OrderStatus = isset($result['OrderStatus']) ? $result['OrderStatus'] : '';
				
				if( $OrderStatus == 7 ) {
					// BOOM! Payment completed!

					$payment_token = get_post_meta( $order_id, 'paymento-payment-token', true );

					$payload = array(
						'token' 	=> $payment_token,
					);

					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, 'https://api.paymento.io/v1/payment/verify');
					curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
					curl_setopt($curl, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Api-Key:' . $this->get_option('api_key'))
					);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLINFO_HEADER_OUT, true);
					curl_setopt($curl, CURLOPT_POST, true);
					
					$curl_res = curl_exec($curl);
					$result = json_decode($curl_res,true);
					
					if($result["success"] && $result["body"]["token"] == $payment_token){
						// $this->update_option( 'debug', 'verify result: true' . $order_id);
						wc_reduce_stock_levels($order_id);
						$message = sprintf(
							__('Payment was successful %s token: %s', 'paymento'),
							'<br />',
							$payment_token
							);
						$order->add_order_note($message, 1);
						$order->add_payment_token($payment_token);
		
						$order->payment_complete();
						return new WP_REST_Response('good');
 
						// $successful_page = add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) );
						// wp_redirect( $successful_page );
						// exit();
						
					}else{			
						$message = sprintf(
							__('Payment Verification was unsuccessful.', 'paymento'),
							'<br />',
							$payment_token
						);
						$order->add_order_note($message, 1);
						wc_add_notice( __('Payment error:', 'paymento') . $message, 'error' );
						wp_redirect(wc_get_checkout_url(), 301);
						return new WP_REST_Response('good');
					}
				} else {
					// OOPS! Something wrong
					$error_message =  "Paymento failed payment";
					wc_add_notice( __('Payment error:', 'paymento') . $error_message, 'error' );
					wp_redirect( wc_get_checkout_url() );
					return new WP_REST_Response('good');
				}
			}
		}
	}

	public function register_script() {
		wp_register_style( 'new_style', PAYMENTOGW_URL.'assets/css/style.css', array(), '1.0.0', true);
		wp_enqueue_style( 'new_style' );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'paymento'),
				'type' => 'checkbox',
				'label' => __('Enable paymento Payments', 'paymento'),
				'default' => 'yes',
			),
			'status' => array(
				'title'   => 'Ping Status',
				'type' => 'title',
				'description' => sprintf('<span id="paymento_helth_check">Loading</span>'),
		),
		'Merchant' => array(
			'title'   => 'Merchant Name',
			'type' => 'title',
			'description' => sprintf('<span id="paymento_merchant_name">Loading</span>'),
		),
			'title' => array(
				'title' => __('Title', 'paymento'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'paymento'),
				'default' => __('paymento gateway for Woocommerce', 'paymento'),
				'desc_tip' => true,
			),
			'description' => array(
				'title' => __('Description', 'paymento'),
				'type' => 'text',
				'description' => __('Payment method description that the customer will see on your checkout.', 'paymento'),
				'default' => __('Official paymento electronic payment gateway', 'paymento'),
				'desc_tip' => true,
			),
			'api_key' => array(
				'title' => __('api_key', 'paymento'),
				'type' => 'text',
				'description' => __('merchant access token', 'paymento'),
			),
			'secret_key' => array(
				'title' => __('secret_key', 'paymento'),
				'type' => 'text',
				'description' => __('merchant secret key', 'paymento'),
			),
			// 'speed' => array(
			// 	'title' => __('speed', 'paymento'),
			// 	'description' => __('merchant speed', 'paymento'),
			// 	'type' => 'select',
			// 	'options' => array(
      //       '1' => 'High',
      //       '0' => 'Low',
      //   ),
			// 	'default' => '0'
			// ),
			'confirmation' => array(
				'title' => __('confirmation type', 'paymento'),
				// 'description' => __('merchant confirmation type', 'paymento'),
				'type' => 'select',
				'options' => array(
					'0' => 'Redirect Immediately and Hold Invoice',
					'1' => 'Wait for Payment Confirmation',
					'2' => 'Accept Payment in Mempool'
        ),
				'default' => '0'
			),
			'debug' => array(
				'title' => __('debug', 'paymento'),
				'type' => 'text',
				'description' => __('merchant responses', 'paymento'),
			),
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		return array(
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url($order),
		);
	}


	public function get_payment_token($order_id){
		$callback_url = add_query_arg('wc_order', $order_id, WC()->api_request_url('WC_PAYMENTO_Gateway'));
		$confirmation =  $this->get_option('confirmation');

		$order = wc_get_order( $order_id );
		$currency = $order->get_order_currency();
		$cart_hash = $order->get_cart_hash();
		$total = $order->get_total();
		$billing_phone  = $order->get_billing_phone();
		if ( strtolower($currency) == strtolower('USD') ){
			$currency = 'USD';
		}else if( strtolower($currency) == strtolower('EUR')){
			$currency = 'EUR';
		}

		$payload = array(
			"fiatAmount"=> $total,
			"fiatCurrency"=> $currency,
			"ReturnUrl" => $callback_url,
			"orderId" => $order_id,
			"speed" => ($confirmation == 1) ? 0 : 1,
			"cryptoAmount" => array(),
			"additionalData" => array()
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://api.paymento.io/v1/payment/request');
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Api-Key:' . $this->get_option('api_key'))
		);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		curl_setopt($curl, CURLOPT_POST, true);
		
		$curl_res = curl_exec($curl);
		
		// $this->update_option( 'debug', 'get payment token: ' . $curl_res);


		$result = json_decode($curl_res,true);
		// $this->update_option( 'debug', $callback_url);
		
		$order = wc_get_order( $order_id );
		$order->update_meta_data( 'paymento-payment-token', $result['body'] );
		$order->save();
		$send_to_bank_url = add_query_arg('token' , $result['body'], 'https://app.paymento.io/gateway');

		
		wp_redirect($send_to_bank_url, 301);
	}
	
	/**
	 * Make ready for send to bank.
	 */
	public function send_to_bank($order_id)
	{
		_e('Thank you for your payment. redirecting to bank...', 'paymento');
		$this->get_payment_token($order_id);
	}

	public function return_from_bank() {

		//{"wc_order":"788","token":"33d5dea154364656ac68af4a2a6ff007","orderId":"788","status":"7","woocommerce-login-nonce":null,"_wpnonce":null,"woocommerce-reset-password-nonce":null}
		// $this->update_option( 'debug', 'rfb: ' . json_encode($_REQUEST));
		if ( isset($_GET['wc_order']) ) {
			$order_id = absint( $_GET['wc_order'] );
		}
		$confirmation_type = $this->get_option('confirmation');

		if ( isset($order_id) && !empty($order_id) ) {
			$order = wc_get_order($order_id);
			if ($order->get_status() !== 'completed') {

				// Get data from bank
				$OrderId = isset($_REQUEST['OrderId']) ? $_REQUEST['OrderId'] : '';
				$OrderStatus = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
				$payment_token = get_post_meta( $order_id, 'paymento-payment-token', true );

				if( $OrderStatus == 7 && ( $confirmation_type == 1 ||  $confirmation_type == 2) ) {
					// BOOM! Payment completed!
						// wc_reduce_stock_levels($order_id);
						WC()->cart->empty_cart();
						WC()->session->delete_session( 'paymento_order_id' );
						$message = sprintf(
							__('Payment was successful %s token: %s', 'paymento'),
							'<br />',
							$payment_token
						);
						$order->add_payment_token($payment_token);
						$order->add_order_note($message, 1);
						$order->payment_complete();
						$successful_page = add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) );
						wp_redirect( $successful_page );
						exit();
				}
				elseif( $OrderStatus == 7 && $confirmation_type == 0 ) {
					// BOOM! Payment completed!
						// wc_reduce_stock_levels($order_id);
						WC()->cart->empty_cart();
						WC()->session->delete_session( 'paymento_order_id' );
						$message = sprintf(
							__('Payment was successful %s token: %s', 'paymento'),
							'<br />',
							$payment_token
						);
						$order->add_payment_token($payment_token);
						$order->add_order_note($message, 1);
						$order->payment_complete();
						// $order->set_status( 'on-hold', 'Waiting for Gateway Confirmation' );

						$order->update_status( 'on-hold' );
						$successful_page = add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) );
						wp_redirect( $successful_page );
						exit();
				} elseif( $OrderStatus == 3 ) {
					// BOOM! Payment completed!
						$message = sprintf(
							__('Payment Waiting To Confirm', 'paymento'));
						$order->add_payment_token($payment_token);
						$order->add_order_note($message, 1);
						$successful_page = add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) );
						wp_redirect( $successful_page );
						exit();	
				} else {
					// OOPS! Something wrong
					$error_message =  "Paymento failed payment";
					wc_add_notice( __('Payment error:', 'paymento') . $error_message, 'error' );
					wp_redirect( wc_get_checkout_url() ,301);
					exit();
				}
			}
		}
	}

	public function get_error_message( $token ) {
		switch ($token) {
			case 'soap':
				return __('SOAP Client does not loaded in your server', 'paymento');
				break;
			case 'bank_connection':
				return __('Connection to bank failed.', 'paymento');
				break;
			default:
				return __('Unknown error', 'paymento');
		}
	}

	public function show_transaction_in_order($total_rows, $order) {
		$gateway = $order->get_payment_method();
		if ($gateway === $this->id) {
			$trace_number = get_post_meta( $order->id, 'paymento_payment_id', true );
			$total_rows['trace_number'] = array(
				'label' => __( 'Tracking Code:', 'paymento' ),
				'value' => $trace_number
			);
		}
		return $total_rows;
	}

	public function filter_woocommerce_available_payment_gateways($available_gateways) {
		return $available_gateways;
	}

	public static function computeHexStringHmac($message, $hexStringKey)
	{
			if (empty($key)) {
					throw new \InvalidArgumentException('$key cannot be null or empty');
			}

			if (empty($message)) {
					throw new \InvalidArgumentException('$message cannot be null or empty');
			}

			$hexStringKey = "";

			// Valid hex pattern including 0x prefix
			if (preg_match("/^(0x)?[0-9A-Fa-f]*$/", $key)) {
					$hexStringKey = $key;
			} elseif (preg_match("/^[A-Za-z0-9+\/]*={0,2}$/", $key)) {
					// Convert base64 to hex (assuming you have a function to handle this)
					$hexStringKey = self::base64ToHex($key);
			} else {
					throw new \InvalidArgumentException("Key is not a valid hex string or base64 format.");
			}

			if (substr($hexStringKey, 0, 2) === "0x") {
					$hexStringKey = substr($hexStringKey, 2);
			}

			// Convert hex string key to byte array
			$keyBytes = [];
			for ($i = 0; $i < strlen($hexStringKey) / 2; $i++) {
					$keyBytes[$i] = hexdec(substr($hexStringKey, $i * 2, 2));
			}

			$messageBytes = utf8_encode($message);

			$hmac = hash_hmac('sha256', $messageBytes, pack('C*', ...$keyBytes), true);

			return bin2hex($hmac);

	}

}



WC_PAYMENTO_Gateway::load();