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
	 * Return a list of status options
	 * @return array
	 */
	public function statusOptions()
	{
		return array('pending', 'processing', 'complete', 'on_hold');
	}


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
		$arrProducts = array();
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

			$arrProducts[] = specialchars($objProduct->name . $strOptions);
		}

		list($endTag, $startScript, $endScript) = IsotopeFrontend::getElementAndScriptTags();

		$time = time();
		$session_id = $objOrder->id . '_' . uniqid();
		$intPrice = $this->Isotope->Cart->grandTotal * 100;
		$strProducts = implode(', ', $arrProducts);
		$objAddress = (ISO_VERSION < 1.4) ? (object) $this->Isotope->Cart->billingAddress : $this->Isotope->Cart->billingAddress;
		$strHash = md5($this->payu_id . ($this->debug ? 't' : '') . $session_id . $this->payu_authKey . $intPrice . $strProducts . $objOrder->uniqid . $objAddress->firstname . $objAddress->lastname . $objAddress->street_1 . $objAddress->city . $objAddress->postal . $objAddress->country . $objAddress->email . $objAddress->phone . $GLOBALS['TL_LANGUAGE'] . $this->Environment->remoteAddr . $time . $this->payu_key1);

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
<input type="hidden" name="first_name" value="' . $objAddress->firstname . '"' . $endTag . '
<input type="hidden" name="last_name" value="' . $objAddress->lastname . '"' . $endTag . '
<input type="hidden" name="email" value="' . $objAddress->email . '"' . $endTag . '
<input type="hidden" name="street" value="' . $objAddress->street_1 . '"' . $endTag . '
<input type="hidden" name="post_code" value="' . $objAddress->postal . '"' . $endTag . '
<input type="hidden" name="city" value="' . $objAddress->city . '"' . $endTag . '
<input type="hidden" name="country" value="' . $objAddress->country . '"' . $endTag . '
<input type="hidden" name="phone" value="' . $objAddress->phone . '"' . $endTag . '
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
	 * Return information or advanced features in the backend
	 * @param integer
	 * @return string
	 */
	public function backendInterface($orderId)
	{
		$objOrder = new IsotopeOrder();

		if (!$objOrder->findBy('id', $orderId))
		{
			return parent::backendInterface($orderId);
		}

		$arrPayment = $objOrder->payment_data;

		if (!is_array($arrPayment['POSTSALE']) || empty($arrPayment['POSTSALE']))
		{
			return parent::backendInterface($orderId);
		}

		$arrPayment = array_pop($arrPayment['POSTSALE']);
		ksort($arrPayment);
		$i = 0;

		$strBuffer = '
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=payment', '', $this->Environment->request)).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBT']).'">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">' . $this->name . ' (' . $GLOBALS['ISO_LANG']['PAY'][$this->type][0] . ')' . '</h2>

<table class="tl_show">
<tbody>';

		foreach ($arrPayment as $k => $v)
		{
			if (is_array($v))
			{
				continue;
			}

			$strBuffer .= '
  <tr>
    <td' . ($i%2 ? '' : ' class="tl_bg"') . '><span class="tl_label">' . $k . ': </span></td>
    <td' . ($i%2 ? '' : ' class="tl_bg"') . '>' . $v . '</td>
  </tr>';

			++$i;
        }

        $strBuffer .= '
</tbody></table>
</div>';

		return $strBuffer;
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
