<?php
/**
 * Checkout Finland payments module
 * www.checkout.fi
 *
 * Use Guzzle HTTP client v6 installed with Composer https://github.com/guzzle/guzzle/
 *
 * REQUIRES PHP 7.2
 *
 * @package checkout
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Nida Verkkopalvelu / Krbuk 2020 Jan 9 Modified in v1.5.6c $
 */

require DIR_FS_CATALOG . DIR_WS_CLASSES . 'vendors/checkoutfinland/autoload.php';

class checkoutfinland extends base
{
	var $code, $title, $description, $enabled, $sort_order;
	public $moduleVersion = '1.5.6';
    protected $OpCheckoutApiVersion = '2.00'; 
	
    function __construct()
    {
        global $order;	
		$this->title = MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_TITLE;
		$this->code = 'checkoutfinland';
		$this->description = '<strong>Checkout Finland ' . $this->moduleVersion . '</strong><br><br>' .MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_DESCRIPTION;
		$this->enabled = ((MODULE_PAYMENT_CHECKOUTFINLAND_STATUS == 'Kylla') ? true : false);
		$this->sort_order = MODULE_PAYMENT_CHECKOUTFINLAND_SORT_ORDER;
        if (IS_ADMIN_FLAG === true) {
            $this->title = MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_TITLE;
            if (defined('MODULE_PAYMENT_CHECKOUTFINLAND_STATUS')) {
                if (MODULE_PAYMENT_CHECKOUTFINLAND_KAUPPIAS == '375917' && MODULE_PAYMENT_CHECKOUTFINLAND_MYYJALTAMYYJA == '695861' && MODULE_PAYMENT_CHECKOUTFINLAND_PAAMYYJA == '695874') $this->title .= '<span class="alert">' .MODULE_PAYMENT_CHECKOUTFINLAND_ALERT_TEST .'</span>';

            }
        }
		$this->form_action_url  = "https://api.checkout.fi/payments";
		$this->merchant_id = MODULE_PAYMENT_CHECKOUTFINLAND_KAUPPIAS;		
		$this->private_key = MODULE_PAYMENT_CHECKOUTFINLAND_TURVA_AVAIN;
		$this->aggregate_merchant_id = MODULE_PAYMENT_CHECKOUTFINLAND_MYYJALTAMYYJA;
		$this->aggregate_secret_key   = MODULE_PAYMENT_CHECKOUTFINLAND_MONITARKISTEAVAIN;
		$this->shop_in_Shop_merchant_id = MODULE_PAYMENT_CHECKOUTFINLAND_PAAMYYJA;	
		
		$this->order_reference = time().rand(0,9999);
		$this->order_reference = str_pad($this->order_reference, 9, "1", STR_PAD_RIGHT);		
		
		//$payment_amount =round($order->info['total'], 2); // 26.50
		$this->payment_amount = round($order->info['total'], 2); // 26.50
		$this->currency = $order->info['currency'];
		$this->languages = ($_SESSION['languages_code']== 'fi') ? 'FI' : 'EN';
		
		// Links
		$this->error_message = MODULE_PAYMENT_CHECKOUTFINLAND_ERROR;
		$this->return_address = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
		$this->cancel_address = zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL');
		
 		// Customer info
		$this->contact_firstname = trim(substr($order->customer['firstname'],0,40));
		$this->contact_lastname = trim(substr($order->customer['lastname'],0,40));
		$this->contact_email = $order->customer['email_address'];
		$this->contact_phone = $order->customer['telephone'];
		
		// Customer invoicing address
		$this->contact_invoicing_addr_street 	= trim(substr($order->billing['street_address'],0,50));
		$this->contact_invoicing_addr_postalCode= trim(substr($order->billing['city'],0,5));
		$this->contact_invoicing_addr_city 		= trim(substr($order->billing['postcode'],0,18)); 
		$this->contact_invoicing_addr_county	= trim(substr($order->billing['state'],0,10));
		$this->contact_invoicing_addr_country	= $order->billing['country']['iso_code_2'];		
		
		// Customer delivery address
		$this->contact_delivery_addr_street 	= trim(substr($order->delivery['street_address'],0,50));
		$this->contact_delivery_addr_postalCode	= trim(substr($order->delivery['city'],0,5));
		$this->contact_delivery_addr_city 		= trim(substr($order->delivery['postcode'],0,18));
		$this->contact_delivery_addr_county		= trim(substr($order->delivery['state'],0,10));
		$this->contact_delivery_addr_country 	= $order->delivery['country']['iso_code_2'];		
		
        if (null === $this->sort_order) return false;		
		 // determine order-status for transactions
		if ((int)MODULE_PAYMENT_CHECKOUTFINLAND_ORDER_STATUS_ID > 0)
		{
			$this->order_status = MODULE_PAYMENT_CHECKOUTFINLAND_ORDER_STATUS_ID;
		}
	
        $this->_logDir = DIR_FS_LOGS;

        // check for zone compliance and any other conditionals
        if (is_object($order)) 
		{ 
			$this->update_status();
        }		
	}

    function update_status()
    {
        global $order, $db;
        if ($this->enabled == false || (int)MODULE_PAYMENT_CHECKOUTFINLAND_ZONE == 0) {
            return;
        }
        if (isset($order->billing['country']) && isset($order->billing['country']['id'])) {
            $check_flag = false;
            $sql        = "SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . (int)MODULE_PAYMENT_CHECKOUTFINLAND_ZONE . "' AND zone_country_id = '" . (int)$order->billing['country']['id'] . "' ORDER BY zone_id";
            $checks     = $db->Execute($sql);
            foreach ($checks as $check) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }
            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
		//Only EUR orders accepted
		$currency = $order->info['currency'];
		if(!(in_array($currency, $this->allowed_currencies)))
			$this->enabled = false;
    }	
	
	function javascript_validation()
	{
	}

	function selection()
	{
		return array('id' => $this->code, 'module' => $this->title);
	}

	function pre_confirmation_check()
	{
		return false;
	}

	function confirmation()
	{
		return false;
	}
	
	function check()
	{
		global $db;

		if (!isset($this->_check))
		{
			$check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_CHECKOUTFINLAND_STATUS'");
			$this->_check = $check_query->RecordCount();
		}
		return $this->_check;
	}
	
	function process_button()
	{	
		global $order, $currencies, $db, $order_totals;
		
        $delivery_date = date("Y-m-d");		
		$method = 'POST';

		//Variable to compare product calculation to total amount of the order
		$total_check = 0;

		//Array for product breakdown
		$products = array();

		//Add products to product breakdown
		$order_items = $order->products;
		
		// Check if there is a group discount enabled
		$group_discount_amount = 0;
		$order_total_sub_total = 0;
		foreach ($order_totals as $o_total)
		{
			if(isset($o_total['code']) && $o_total['code'] == 'ot_loworderfee')
			{
				if(isset($o_total['value']) && $o_total['value'] > 0)
				{
					$query = "select * from " . TABLE_CONFIGURATION . " where configuration_key='MODULE_ORDER_TOTAL_LOWORDERFEE_TAX_CLASS'";
					$loworder_tax = $db->Execute($query);
					$loworder_tax_rate = zen_get_tax_rate($loworder_tax->fields['configuration_value'], $order->billing['country']['id'], $order->billing['zone_id']);
					
					if (DISPLAY_PRICE_WITH_TAX == 'true')
					{
						$loworderpretax_price = $currencies->get_value($order->info['currency']) * $o_total['value'] / ($loworder_tax_rate/100+1);
						$loworderprice = $currencies->get_value($order->info['currency']) * $o_total['value'];
					}
					else
					{
						$loworderpretax_price = $currencies->get_value($order->info['currency']) * $o_total['value'];
						$loworderprice = $currencies->get_value($order->info['currency']) * $o_total['value'] * ($loworder_tax_rate/100+1);
					}

					$product = array(
						'productCode' => MODULE_PAYMENT_CHECKOUTFINLAND_LOWORDER_TEXT,
						'units' => 1,
						'unitPrice' => ((int)($loworderprice * 100)),
						'vatPercentage' => intval(round($loworder_tax_rate)),
						'deliveryDate' => $delivery_date						
					);
					$total_check += $product['unitPrice'];					
					array_push($products, $product);
				}
			}
			else if(isset($o_total['code']) && $o_total['code'] == 'ot_group_pricing')
			{
				if(isset($o_total['value']) && $o_total['value'] > 0)
				{
					$group_discount_amount = $o_total['value'];
				}
			}
			else if(isset($o_total['code']) && $o_total['code'] == 'ot_subtotal')
			{
				if(isset($o_total['value']) && $o_total['value'] > 0)
				{
					$order_total_sub_total = $o_total['value'];
				}
			}
		}

		if(isset($group_discount_amount) && isset($order_total_sub_total))
			$group_discount_multiplier = 1 - $group_discount_amount / $order_total_sub_total;
		else
			$group_discount_multiplier = 1;

		foreach($order_items as $item) {
			$product_pretax_price = ($currencies->get_value($order->info['currency']) * $item['final_price']) * $group_discount_multiplier;
			$product_price = ($currencies->get_value($order->info['currency']) * ($item['final_price']*($item['tax']/100+1))) * $group_discount_multiplier;
			$product = array(
				'productCode' => $item['name'],
				'description' => $item['model'],
				'units' => $item['qty'],
				'unitPrice' => ((int)($product_price * 100)),
				'vatPercentage' => (int)(round($item['tax'], 0)),
				'deliveryDate' => $delivery_date
			);
			$total_check += $product['unitPrice']*$product['units'];			
			array_push($products, $product);
	 	}

	 	//Add shipping to product breakdown
	 	if($order->info['shipping_cost'] > 0){
	 		if (DISPLAY_PRICE_WITH_TAX != 'true'){
	 			$shipping_pretax_price = ($currencies->get_value($order->info['currency']) * $order->info['shipping_cost']);
	 			$shipping_price = ($currencies->get_value($order->info['currency']) * ($order->info['shipping_cost']+$order->info['shipping_tax']));
	 			$shipping_tax = ($order->info['shipping_tax']/$order->info['shipping_cost'])*100;
		 	}
		 	else{
		 		$shipping_pretax_price = ($currencies->get_value($order->info['currency']) * ($order->info['shipping_cost']-$order->info['shipping_tax']));
		 		$shipping_price = ($currencies->get_value($order->info['currency']) * ($order->info['shipping_cost']));
		 		$shipping_tax = ($order->info['shipping_tax']/($order->info['shipping_cost']-$order->info['shipping_tax']))*100;
		 	}
	 		$shipping = array(
				'productCode' => $order->info['shipping_method'],
				'units' => 1,
				'unitPrice' => ((int)($shipping_price * 100)),				
				'vatPercentage' => (int)(round($shipping_tax, 0)),
				'deliveryDate' => $delivery_date				
			);

			$total_check += $shipping['unitPrice'];			
			array_push($products, $shipping);
		}

		//Add discount and  coupon to product breakdown (if exists)
/*		if(isset($_SESSION['cc_id'])){
			$sql = "select * from " . TABLE_COUPONS . " where coupon_id=:couponID: and coupon_active='Y' ";
			$sql = $db->bindVars($sql, ':couponID:', $_SESSION['cc_id'], 'integer');
			$coupon = $db->Execute($sql);
			$coupon_amount = $currencies->get_value($order->info['currency']) * $coupon->fields['coupon_amount'];
			$coupon_amount_formatted = number_format($coupon_amount, 2, '.', '');
			$coupon_result = 0;

			switch ($coupon->fields['coupon_type']){
				case 'P':
				$coupon_result = ($coupon_amount_formatted/100)*($order->info['subtotal']);
				break;
				case 'F':
				$coupon_result = $coupon_amount_formatted;
				break;
			}
			$total_check -= number_format(($currencies->get_value($order->info['currency']) * ($coupon_result))*100, 0, '.', '');

			if ($coupon_result > 0 && $total_check <= $amount+10 && $total_check >= $amount-10) {
				$product = array(
					'productCode' => MODULE_PAYMENT_CHECKOUTFINLAND_COUPON_TEXT,
					'units' => 1,
					'unitPrice' =>  -number_format(($currencies->get_value($order->info['currency']) * ($coupon_result))*100, 0, '.', ''),
					'vatPercentage' => 0,
					'deliveryDate' => $delivery_date					
				);
				array_push($products, $product);
			}*/

			//Depending on zencart payment_total sort-order coupons might be calculated in a way that the couponresult wont match the actual discount
/*			if ($coupon_result > 0  && ($total_check > $amount+10 || $total_check < $amount-10)){
				$discounts = $amount - $total_check - number_format(($currencies->get_value($order->info['currency']) * ($coupon_result))*100, 0, '.', '');
				$product = array(
					'productCode' => MODULE_PAYMENT_CHECKOUTFINLAND_DISCOUNT_TEXT,
					'units' => 1,
					'unitPrice' => $discounts,
					'vatPercentage' => 0,
					'deliveryDate' => $delivery_date					
				);
				array_push($products, $product);
			}
		}		
	*/	
	/////////////////////////////////////////////////////////////	
		
        $headers = $this->getResponseHeaders($method);
        //$body = $this->getResponseBody($order);
        $body = json_encode(
            [
                'stamp' => hash('sha256', time() . $this->merchant_id),
                'reference' => $this->order_reference,
            //    'amount' => round($order->info['total'] * 100 ),
                'amount' => $this->payment_amount * 100,
                'currency' => $this->currency,
                'language' => $this->languages,
                'items' => $products,
                'customer' => [
                    'firstName' => $this->contact_firstname,
                    'lastName' => $this->contact_lastname,
                    'phone' => $this->contact_phone,
                    'email' => $this->contact_email,
                ],
				'deliveryAddress' =>  [
					'streetAddress' => $this->contact_invoicing_addr_street,
					'postalCode' => $this->contact_invoicing_addr_postalCode,
					'city' => $this->contact_invoicing_addr_city,
					'county' => $this->contact_invoicing_addr_county,
					'country' => $this->contact_invoicing_addr_country,					
				],
				'invoicingAddress' =>  [
					'streetAddress' => $this->contact_delivery_addr_street,
					'postalCode' => $this->contact_delivery_addr_postalCode,
					'city' => $this->contact_delivery_addr_city,
					'county' => $this->contact_delivery_addr_county,
					'country' => $this->contact_delivery_addr_country,					
				],				
				
                'redirectUrls' => [
                    'success' => $this->return_address,
                    'cancel' =>  $this->cancel_address,
                ],
                'callbackUrls' => [
                    'success' => $this->return_address,
                    'cancel' =>  $this->cancel_address,
                ],
            ],
            JSON_UNESCAPED_SLASHES
        );
			
        $headers['signature'] = $this->calculateHmac($headers, $body, $this->private_key);		
        $client = new \GuzzleHttp\Client(['headers' => $headers]);		
		$response = null;
        try {
            if ($method == 'POST') {
                $response = $client->post($this->form_action_url . $uri, ['body' => $body]);
            } else {
                $response = $client->get($this->form_action_url . $uri, ['body' => '']);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
				$response = $e->getResponse();
				echo "Unexpected HTTP status code: {$response->getStatusCode()}\n\n";
            }
            return $response;
        }		
		
		$responseBody = $response->getBody()->getContents();
		// Flatten Guzzle response headers
		$responseHeaders = array_column(array_map(function ($key, $value) {
			return [ $key, $value[0] ];
		}, 
		array_keys($response->getHeaders()), 
		array_values($response->getHeaders())), 1, 0);

        $responseHmac = $this->calculateHmac($responseHeaders, $responseBody, $this->private_key);		
		if ($responseHmac !== $response->getHeader('signature')[0]) {
			echo '<div style="color:red">'. MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_API_ERROR .'</div>';
		} 
		else {

		}
		
		// NIDA
		// tarkastus tiedot
/*		echo '<br><br> Request ID		: '	.$response->getHeader('cof-request-id')[0];	
		echo '<br><br> Tarkastus:<br> 	  ' .json_encode ($body);			
		echo '<br><br> reference		: '	.$this->order_reference;		
		echo '<br><br> Summa			: ' .$this->payment_amount .'&nbsp; / &nbsp;' .$this->payment_amount * 100;
		echo '<br><br> units price		: ' .$product['unitPrice'];		
		echo '<br><br> units		    : ' .$product['units'];	
		echo '<br><br> vatPercentage    : ' .(int)(round($item['tax'], 0));
		echo '<br><br> shipping_cost	: '  .$order->info['shipping_cost'] .'&nbsp; / &nbsp;' .$order->info['shipping_cost']* 100;	
		
		echo '<br><br> deliveri address	: ' .$contact_addr_street;
		echo '<br><br> urun bilgileri	: '  .$product['unitPrice'];
		echo '<br><br> urun bilgileri	: '  .$product;	
		echo '<br><br> urun bilgileri	: '  .$shipping_price;	
		echo '<br><br> indirim % 		: '  .$discounts;
		echo '<br><br> indirim kupon	: '  .$coupon_result;		

	   echo '<br><br> indirim kupon degeri : '  .$coupon->fields['coupon_amount'] .'&nbsp;/&nbsp;' .$coupon_amount_formatted;	*/	
		
	//	echo '<br><br>' .$body .'<br><br>';	
	//	echo '<br><br> Tarkastus	:<br>' .$responseBody;
		
		// checkout finland responseBody
		//return $responseBody;		
		//echo(json_encode(json_decode($responseBody), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		//return $this->getResponse;
		



		//return $this->getEnabledPaymentMethodGroups();  // error array
		
		//return $this->getEnabledPaymentMethodsByGroup();	
		
	    //return $this->getAllPaymentMethods();  // 404 Not Found The requested resource doesn't exist.	
		
		return $this->getEnabledPaymentMethodGroups();

	}

	function before_process()
	{
		return false;
	}
	
	function after_process()
	{
		return false;
	}


	function install()
	{
		global $db;
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Lajittelujärjestys', 'MODULE_PAYMENT_CHECKOUTFINLAND_SORT_ORDER', '0', 'Maksutavan lajittelujärjestys. Pienimmän luvun omaava on ylimpänä.', '6', '1', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Ota käyttöön Checkout', 'MODULE_PAYMENT_CHECKOUTFINLAND_STATUS', 'Kylla', 'Otetaanko maksumoduuli käyttöön?', '6', '2', 'zen_cfg_select_option(array(\'Kylla\', \'Ei\'), ', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Kauppiastunnus', 'MODULE_PAYMENT_CHECKOUTFINLAND_KAUPPIAS', '375917', 'TEST kauppiastunnus: 375917', '6', '3', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Turva-avain', 'MODULE_PAYMENT_CHECKOUTFINLAND_TURVA_AVAIN', 'SAIPPUAKAUPPIAS', 'Test turva-avain: SAIPPUAKAUPPIAS', '6', '4', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Yhdistelmä kauppiastunnus', 'MODULE_PAYMENT_CHECKOUTFINLAND_MYYJALTAMYYJA', '695861', 'TEST yhdistelmä kauppiatunnus: 695861', '6', '5', now())");	
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Yhdistelmä turva-avain', 'MODULE_PAYMENT_CHECKOUTFINLAND_MONITARKISTEAVAIN', 'MONISAIPPUAKAUPPIAS', 'Test yhdistelmä turva-avain: MONISAIPPUAKAUPPIAS', '6', '6', now())");	
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Myyjältä Myyjä kauppiastunnus', 'MODULE_PAYMENT_CHECKOUTFINLAND_PAAMYYJA', '695874', 'Test myyjältä myyjä kauppiastunnus: 695874', '6', '7', now())");			
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Maksumoduulin voimassaoloalue', 'MODULE_PAYMENT_CHECKOUTFINLAND_ZONE', '0', 'Jos alue on valittu, käytä tätä maksutapaa vain valitun alueen ostotapahtumille..', '6', '8', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Tilauksen tila', 'MODULE_PAYMENT_CHECKOUTFINLAND_ORDER_STATUS_ID', '0', 'Määritä tilauksen tila maksutapahtuman suorituksen jälkeen:', '6', '9', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Log Mode', 'MODULE_PAYMENT_CHECKOUTFINLAND_LOGGING', 'Kirjaudu sisään virheitä ja lähetä sähköpostiviesti virheistä', 'Haluatko ottaa virheenkorjaustilan käyttöön? Täydellinen yksityiskohtainen loki epäonnistuneista tapahtumista voidaan lähettää sähköpostitse myymälän omistajalle.', '6', '10', 'zen_cfg_select_option(array(\'Off\', \'Log Always\', \'Log on Failures\', \'Log Always and Email on Failures\', \'Log on Failures and Email on Failures\', \'Email Always\', \'Email on Failures\'), ', now())");
	}

	function remove()
	{
		global $db;
		$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	}

	function keys()
	{
		return array('MODULE_PAYMENT_CHECKOUTFINLAND_SORT_ORDER',
					 'MODULE_PAYMENT_CHECKOUTFINLAND_STATUS', 
					 'MODULE_PAYMENT_CHECKOUTFINLAND_KAUPPIAS', 
					 'MODULE_PAYMENT_CHECKOUTFINLAND_TURVA_AVAIN', 
					 'MODULE_PAYMENT_CHECKOUTFINLAND_MYYJALTAMYYJA', 
					 'MODULE_PAYMENT_CHECKOUTFINLAND_MONITARKISTEAVAIN',
					 'MODULE_PAYMENT_CHECKOUTFINLAND_PAAMYYJA', 
					 'MODULE_PAYMENT_CHECKOUTFINLAND_ZONE', 
					 'MODULE_PAYMENT_CHECKOUTFINLAND_ORDER_STATUS_ID',
					 'MODULE_PAYMENT_CHECKOUTFINLAND_LOGGING');		
	}
	
	function get_error()
	{
		global $_GET;
		$error = array('title' => MODULE_PAYMENT_CHECKOUTFINLAND_HEADER_ERROR,
				'error' => MODULE_PAYMENT_CHECKOUTFINLAND_TEXT_ERROR);
		return $error;
	}
/////////////////////////    Nida //////////////////////////////////////////////////
    public function getMerchantSecret()
    {
        return $this->private_key;
    }

    public function getMerchantId()
    {
        return $this->merchant_id;
    }	
    public function getResponse($uri, $order, $method, $refundId = null, $refundBody = null)
    {
        $method = strtoupper($method);
        $headers = $this->getResponseHeaders($method);
        $body = '';

        if ($method == 'POST' && !empty($order)) {
            $body = $this->getResponseBody($order);
        }
        if ($refundId) {
            $headers['checkout-transaction-id'] = $refundId;
            $body = $refundBody;
        }

        $headers['signature'] = $this->calculateHmac($headers, $body, $this->private_key);

        $client = new \GuzzleHttp\Client(['headers' => $headers]);

        $response = null;


        try {
            if ($method == 'POST') {
                $response = $client->post($this->form_action_url . $uri, ['body' => $body]);
            } else {
                $response = $client->get($this->form_action_url . $uri, ['body' => '']);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $response["data"] = $e->getMessage();
                $response["status"] = $e->getCode();
            }
            return $response;
        }

        $responseBody = $response->getBody()->getContents();

		$responseHeaders = array_column(array_map(function ($key, $value) {
			return [ $key, $value[0] ];
		}, 
		array_keys($response->getHeaders()), 
		array_values($response->getHeaders())), 1, 0);

        $responseHmac = $this->calculateHmac($responseHeaders, $responseBody, $this->private_key);
        $responseSignature = $response->getHeader('signature')[0];

        if ($responseHmac == $responseSignature) {
            $data = array(
                'status' => $response->getStatusCode(),
                'data' => json_decode($responseBody)
            );

            return $data;
        }
    }
    public function getEnabledPaymentMethodGroups()
    {
        $responseData = $this->getAllPaymentMethods();

        $groupData = $this->getEnabledPaymentGroups($responseData);
        $groups = [];

        foreach ($groupData as $group) {
            $groups[] = [
                'id' => $group,
                'title' => $group,
            ];
        }

        // Add methods to groups
        foreach ($groups as $key => $group) {
            $groups[$key]['methods'] = $this->getEnabledPaymentMethodsByGroup($responseData, $group['id']);

            // Remove empty groups
            if (empty($groups[$key]['methods'])) {
                unset($groups[$key]);
            }
        }

        return array_values($groups);
    }

    protected function getAllPaymentMethods()
    {
		global $order, $currencies, $db, $order_totals;		
		$grandTotal = round($order->info['total'], 2);
        $uri = '/merchants/payment-providers?amount=' . $grandTotal * 100;
        $method = 'get';
        $response = $this->getResponse($uri, '', $method);
        return $response['data'];
    }

    protected function getEnabledPaymentMethodsByGroup($responseData, $groupId)
    {
        $allMethods = [];

        foreach ($responseData as $provider) {
            $allMethods[] = [
                'value' => $provider->id,
                'label' => $provider->id,
                'group' => $provider->group,
                'icon' => $provider->svg
            ];
        }

        $i = 1;

        foreach ($allMethods as $key => $method) {
            if ($method['group'] == $groupId) {
                $methods[] = [
                    'checkoutId' => $method['value'],
                    'id' => $method['value'] . $i++,
                    'title' => $method['label'],
                    'group' => $method['group'],
                    'icon'  => $method['icon']
                ];
            }
        }

        return $methods;
    }

    protected function getEnabledPaymentGroups($responseData)
    {
        $allGroups = [];

        foreach ($responseData as $provider) {
            $allGroups[] = $provider->group;
        }

        return array_unique($allGroups);
    }	
	
	
    public function getResponseHeaders($method)
    {
		// Note: nonce and timestamp hardcoded for the expected HMAC output in comments below
		$t = explode(" ",microtime());
        return $headers = [
			'cof-plugin-version' => 'op-payment-service-for-zen-cart-'. $this->OpCheckoutApiVersion,
			'checkout-account' => $this->merchant_id,
			'checkout-algorithm' => 'sha256',
			'checkout-method' => strtoupper($method),
			'checkout-nonce' => uniqid(true),
			'checkout-timestamp' => date("Y-m-d\TH:i:s",$t[1]).substr((string)$t[0],1,4) .'Z',
			'content-type' => 'application/json; charset=utf-8',
		];
    }
	
    public function calculateHmac(array $params = [], $body = null, $secretKey = null)
    {
        // Keep only checkout- params, more relevant for response validation.
        $includedKeys = array_filter(array_keys($params), function ($key) {
            return preg_match('/^checkout-/', $key);
        });
        // Keys must be sorted alphabetically
        sort($includedKeys, SORT_STRING);
        $hmacPayload = array_map(
            function ($key) use ($params) {
                // Responses have headers in an array.
                $param = is_array($params[ $key ]) ? $params[ $key ][0] : $params[ $key ];
                return join(':', [ $key, $param ]);
            },
            $includedKeys
        );
        array_push($hmacPayload, $body);
        return hash_hmac('sha256', join("\n", $hmacPayload), $secretKey);
    }

    public function validateHmac(
        array $params = [],
        $body = null,
        $signature = null,
        $secretKey = null
    ) {
        $hmac = static::calculateHmac($params, $body, $secretKey);
        if ($hmac !== $signature) {
            $this->log->critical('Response HMAC signature mismatch!');
        }
    }	
	
/*
   public function getResponseBody($order)
    {
        $body = json_encode(
            [
                'stamp' => hash('sha256', time() . $this->merchant_id),
                'reference' => $this->order_reference,
				'amount' => $this->payment_amount * 100,
                'currency' => $this->currency,
                'language' => $this->languages,
				'items' => $this->getOrderItems($order),
                'customer' => [
                    'firstName' => $this->contact_firstname,
                    'lastName' => $this->contact_lastname,
                    'phone' => $this->contact_phone,
                    'email' => $this->contact_email,
                ],
				'deliveryAddress' =>  [
					'streetAddress' => $this->contact_invoicing_addr_street,
					'postalCode' => $this->contact_invoicing_addr_postalCode,
					'city' => $this->contact_invoicing_addr_city,
					'county' => $this->contact_invoicing_addr_county,
					'country' => $this->contact_invoicing_addr_country,					
				],
				'invoicingAddress' =>  [
					'streetAddress' => $this->contact_delivery_addr_street,
					'postalCode' => $this->contact_delivery_addr_postalCode,
					'city' => $this->contact_delivery_addr_city,
					'county' => $this->contact_delivery_addr_county,
					'country' => $this->contact_delivery_addr_country,					
				],				
				
                'redirectUrls' => [
                    'success' => $this->return_address,
                    'cancel' =>  $this->cancel_address,
                ],
                'callbackUrls' => [
                    'success' => $this->return_address,
                    'cancel' =>  $this->cancel_address,
                ],
            ],
            JSON_UNESCAPED_SLASHES
        );

        return $body;
		
    }
*/	
/*    public function getOrderItems($order)
    {
		global $order, $currencies, $db;
		foreach($order_items as $item) {
			$product_price = $item['final_price'];
			$product = array(
				'productCode' => $item['name'],
				'description' => $item['model'],
				'units' => $item['qty'],
				'unitPrice' => ($item['total'] * 100 ),
				'vatPercentage' => (int)(round($item['tax'], 0)),
				'deliveryDate' => $delivery_date
			);
			$total_check += $product['unitPrice']*$product['units'];			
			array_push($products, $product);
	 	}

    }*/


}
?>