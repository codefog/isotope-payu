<?php

/**
 * isotope_payu extension for Contao Open Source CMS
 * 
 * Copyright (C) 2012 Codefog
 * 
 * @package isotope_payu
 * @link    http://codefog.pl
 * @author  Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @license LGPL
 */


/**
 * Class PaymentPayU
 * 
 * Provide a payment method "PayU" for Isotope.
 */
class PaymentPayU extends IsotopePayment
{

	/**
	 * Process checkout payment
	 * @return mixed
	 */
	public function processPayment()
	{
		return true;
	}


	/**
	 * Process Transaction URL notification
	 */
	public function processPostSale()
	{
		if ($this->Input->post('pos_id') == $this->payu_id)
		{
			$strHash = md5($this->payu_id . $this->Input->post('session_id') . $this->Input->post('ts') . $this->payu_key2);

			if ($this->Input->post('sig') == $strHash)
			{
				$objOrder = new IsotopeOrder();
				$session_id = explode('_', $this->Input->post('session_id'));

				if ($objOrder->findBy('id', $session_id[0]))
				{
					$time = time();
					$arrData = array
					(
						'pos_id' => $this->payu_id,
						'session_id' => $this->Input->post('session_id'),
						'ts' => $time,
						'sig' => md5($this->payu_id . $this->Input->post('session_id') . $time . $this->payu_key1)
					);

					$strParams = http_build_query($arrData);
					$strHeaders = 'POST /paygw/UTF/Payment/get/txt HTTP/1.0' . "\r\n" .
'Host: www.platnosci.pl' . "\r\n".
'Content-Type: application/x-www-form-urlencoded' . "\r\n".
'Content-Length: ' . strlen($strParams) . "\r\n".
'Connection: close' . "\r\n\r\n";

					if ($fp = @fsockopen('ssl://www.platnosci.pl', 443, $errno, $errstr, 30))
					{
						fputs($fp, $strHeaders . $strParams);
						$strResponse = '';

						// Get the response
						while (!feof($fp))
						{
							$strLine = fgets($fp, 1024);

							if (stripos($strLine, 'trans_') !== false || stripos($strLine, 'status') !== false)
							{
								$strResponse .= $strLine;
							}
						}

						fclose($fp);

						// Parse the response
						$arrResponse = $this->parseResponse($strResponse);
						$strHash = md5($this->payu_id . $arrResponse['trans_session_id'] . $arrResponse['trans_order_id'] . $arrResponse['trans_status'] . $arrResponse['trans_amount'] . $arrResponse['trans_desc'] . $arrResponse['trans_ts'] . $this->payu_key2);

						if ($arrResponse['status'] == 'OK' && $arrResponse['trans_sig'] == $strHash && $arrResponse['trans_status'] == 99)
						{
							if (!$objOrder->checkout())
							{
								$this->log('PayU checkout for order ID "' . $objOrder->id . '" failed', 'PaymentPayU processPostSale()', TL_ERROR);
								return;
							}

							// Store the payment data
							$arrPayment = deserialize($objOrder->payment_data, true);
							$arrPayment['POSTSALE'][] = $arrResponse;
							$objOrder->payment_data = $arrPayment;

							$objOrder->date_paid = $time;
							$objOrder->save();

							$this->log('PayU data accepted for order ID ' . $objOrder->id . ' (status: ' . $arrResponse['trans_status'] . ')', 'PaymentPayU processPostSale()', TL_GENERAL);
						}
					}
					else
					{
						$this->log('PayU could not connect to server', 'PaymentPayU processPostSale()', TL_ERROR);
					}
				}
			}
		}

		die('OK');
	}


	/**
	 * HTML form for checkout
	 * @return string
	 */
	public function checkoutForm()
	{
		$strProducts = '';
		$objOrder = new IsotopeOrder();
		$objOrder->findBy('cart_id', $this->Isotope->Cart->id);

		foreach ($this->Isotope->Cart->getProducts() as $objProduct)
		{
			$strOptions = '';
			$arrOptions = $objProduct->getOptions();

			if (is_array($arrOptions) && count($arrOptions))
			{
				$options = array();

				foreach ($arrOptions as $option)
				{
					$options[] = $option['label'] . ': ' . $option['value'];
				}

				$strOptions = ' (' . implode(', ', $options) . ')';
			}

			$strProducts .= sprintf('%s (SKU %s) x%s - %s', specialchars($objProduct->name . $strOptions), $objProduct->sku, $objProduct->quantity_requested, (round($objProduct->price, 2) * 100)) . "\r\n";
		}

		$time = time();
		$session_id = $objOrder->id . '_' . uniqid();
		list($endTag, $startScript, $endScript) = IsotopeFrontend::getElementAndScriptTags();
		$intPrice = $this->Isotope->Cart->grandTotal * 100;
		$strHash = md5($this->payu_id . ($this->debug ? 't' : '') . $session_id . $this->payu_authKey . $intPrice . $strProducts . $objOrder->uniqid . $this->Isotope->Cart->billingAddress['firstname'] . $this->Isotope->Cart->billingAddress['lastname'] . $this->Isotope->Cart->billingAddress['street_1'] . $this->Isotope->Cart->billingAddress['city'] . $this->Isotope->Cart->billingAddress['postal'] . $this->Isotope->Cart->billingAddress['country'] . $this->Isotope->Cart->billingAddress['email'] . $this->Isotope->Cart->billingAddress['phone'] . $GLOBALS['TL_LANGUAGE'] . $this->Environment->remoteAddr . $time . $this->payu_key1);

		$strBuffer .= '
<h2>' . $GLOBALS['TL_LANG']['MSC']['pay_with_payu'][0] . '</h2>
<p class="message">' . $GLOBALS['TL_LANG']['MSC']['pay_with_payu'][1] . '</p>
<form id="payment_form" action="https://www.platnosci.pl/paygw/UTF/NewPayment" method="post">
' . ($this->debug ? '<input type="hidden" name="pay_type" value="t"' . $endTag : '') . '
<input type="hidden" name="pos_id" value="' . $this->payu_id . '"' . $endTag . '
<input type="hidden" name="pos_auth_key" value="' . $this->payu_authKey . '"' . $endTag . '
<input type="hidden" name="session_id" value="' . $session_id . '"' . $endTag . '
<input type="hidden" name="ts" value="' . $time . '"' . $endTag . '
<input type="hidden" name="sig" value="' . $strHash . '"' . $endTag . '
<input type="hidden" name="client_ip" value="' . $this->Environment->remoteAddr . '"' . $endTag . '
<input type="hidden" name="order_id" value="' . $objOrder->uniqid . '"' . $endTag . '
<input type="hidden" name="amount" value="' . $intPrice . '"' . $endTag . '
<input type="hidden" name="desc" value="' . $strProducts . '"' . $endTag . '
<input type="hidden" name="first_name" value="' . $this->Isotope->Cart->billingAddress['firstname'] . '"' . $endTag . '
<input type="hidden" name="last_name" value="' . $this->Isotope->Cart->billingAddress['lastname'] . '"' . $endTag . '
<input type="hidden" name="email" value="' . $this->Isotope->Cart->billingAddress['email'] . '"' . $endTag . '
<input type="hidden" name="street" value="' . $this->Isotope->Cart->billingAddress['street_1'] . '"' . $endTag . '
<input type="hidden" name="post_code" value="' . $this->Isotope->Cart->billingAddress['postal'] . '"' . $endTag . '
<input type="hidden" name="city" value="' . $this->Isotope->Cart->billingAddress['city'] . '"' . $endTag . '
<input type="hidden" name="country" value="' . $this->Isotope->Cart->billingAddress['country'] . '"' . $endTag . '
<input type="hidden" name="phone" value="' . $this->Isotope->Cart->billingAddress['phone'] . '"' . $endTag . '
<input type="hidden" name="language" value="' . $GLOBALS['TL_LANGUAGE'] . '"' . $endTag . '
<input type="submit" value="' . specialchars($GLOBALS['TL_LANG']['MSC']['pay_with_payu'][2]) . '"' . $endTag . '
</form>

' . $startScript . '
window.addEvent( \'domready\' , function() {
  $(\'payment_form\').submit();
});
' . $endScript;

		return $strBuffer;
	}


	/**
	 * Return a list of valid credit card types for this payment module
	 * @return array
	 */
	public function getAllowedCCTypes()
	{
		return array();
	}


	/**
	 * Parse the response and return it as array
	 * @param string
	 * @return array
	 */
	protected function parseResponse($strResponse)
	{
		$arrResponse = array();
		$arrLines = trimsplit("\r\n", $strResponse);

		foreach ($arrLines as $strLine)
		{
			list($key, $value) = array_map('trim', explode(':', $strLine, 2));
			$arrResponse[$key] = $value;
		}

		return $arrResponse;
	}
}
