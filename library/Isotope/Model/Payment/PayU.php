<?php

/**
 * isotope_payu extension for Contao Open Source CMS
 *
 * Copyright (C) 2013 Codefog
 *
 * @package isotope_payu
 * @author  Codefog <http://codefog.pl>
 * @author  Kamil Kuzminski <kamil.kuzminski@codefog.pl>
 * @license LGPL
 */

namespace Isotope\Model\Payment;

use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Isotope;
use Isotope\Interfaces\IsotopePayment;
use Isotope\Model\Product;
use Isotope\Model\ProductCollection\Order;

/**
 * Class PayU
 *
 * Provide a payment method "PayU" for Isotope.
 */
class PayU extends Postsale implements IsotopePayment
{

    /**
     * Process Transaction URL notification
     * @param IsotopeProductCollection
     */
    public function processPostSale(IsotopeProductCollection $objOrder)
    {
        if (\Input::post('pos_id') == $this->payu_id)
        {
            $strHash = md5($this->payu_id . \Input::post('session_id') . \Input::post('ts') . $this->payu_key2);

            if (\Input::post('sig') == $strHash)
            {
                $time = time();
                $arrData = array
                (
                    'pos_id' => $this->payu_id,
                    'session_id' => \Input::post('session_id'),
                    'ts' => $time,
                    'sig' => md5($this->payu_id . \Input::post('session_id') . $time . $this->payu_key1)
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
                            \System::log('PayU checkout for order ID "' . $objOrder->id . '" failed', __METHOD__, TL_ERROR);
                            die('OK');
                        }

                        // Store the payment data
                        $arrPayment = deserialize($objOrder->payment_data, true);
                        $arrPayment['POSTSALE'][] = $arrResponse;
                        $objOrder->payment_data = $arrPayment;
                        $objOrder->date_paid = $time;
                        $objOrder->updateOrderStatus($this->new_order_status);
                        $objOrder->save();

                        \System::log('PayU data accepted for order ID ' . $objOrder->id . ' (status: ' . $arrResponse['trans_status'] . ')', __METHOD__, TL_GENERAL);
                    }
                }
                else
                {
                    \System::log('PayU could not connect to server', __METHOD__, TL_ERROR);
                }
            }
        }

        die('OK');
    }


    /**
     * Get the postsale order
     * @return object
     */
    public function getPostsaleOrder()
    {
        $session_id = explode('_', \Input::post('session_id'));
        $objOrder = Order::findByPk($session_id[0]);

        if ($objOrder === null || !($objOrder instanceof IsotopeProductCollection)) {
            \System::log('Order ' . $session_id . ' not found', __METHOD__, TL_ERROR);
            die('OK');
        }

        return $objOrder;
    }


    /**
     * HTML form for checkout
     * @param object
     * @param object
     * @return string
     */
    public function checkoutForm(IsotopeProductCollection $objOrder, \Module $objModule)
    {
        $arrProducts = array();

        foreach ($objOrder->getItems() as $objItem)
        {
            // Set the active product for insert tags replacement
            Product::setActive($objItem->getProduct());

            $strOptions = '';
            $arrOptions = Isotope::formatOptions($objItem->getOptions());

            Product::unsetActive();

            if (!empty($arrOptions))
            {
                $options = array();

                foreach ($arrOptions as $option)
                {
                    $options[] = $option['label'] . ': ' . $option['value'];
                }

                $strOptions = ' (' . implode(', ', $options) . ')';
            }

            $arrProducts[] = specialchars($objItem->getName() . $strOptions);
        }

        $time = time();
        $strSessionId = $objOrder->id . '_' . uniqid();
        $objAddress = $objOrder->getBillingAddress();
        $intPrice = (round($objOrder->getTotal(), 2) * 100);
        $strProducts = implode(', ', $arrProducts);

        $objTemplate = new \Isotope\Template('iso_payment_payu');
        $objTemplate->setData($this->arrData);

        $objTemplate->id = $this->id;
        $objTemplate->order_id = $objOrder->uniqid;
        $objTemplate->ts = $time;
        $objTemplate->amount = $intPrice;
        $objTemplate->session_id = $strSessionId;
        $objTemplate->desc = $strProducts;
        $objTemplate->sig = md5($this->payu_id . ($this->debug ? 't' : '') . $strSessionId . $this->payu_authKey . $intPrice . $strProducts . $objOrder->uniqid . $objAddress->firstname . $objAddress->lastname . $objAddress->street_1 . $objAddress->city . $objAddress->postal . $objAddress->country . $objAddress->email . $objAddress->phone . $GLOBALS['TL_LANGUAGE'] . \Environment::get('ip') . $time . $this->payu_key1);
        $objTemplate->ip = \Environment::get('ip');
        $objTemplate->language = $GLOBALS['TL_LANGUAGE'];
        $objTemplate->address = $objAddress;
        $objTemplate->headline = $GLOBALS['TL_LANG']['MSC']['pay_with_payu'][0];
        $objTemplate->message = $GLOBALS['TL_LANG']['MSC']['pay_with_payu'][1];
        $objTemplate->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['pay_with_payu'][2]);

        return $objTemplate->parse();
    }


    /**
     * Return information or advanced features in the backend
     * @param integer
     * @return string
     */
    public function backendInterface($orderId)
    {
        if (($objOrder = Order::findByPk($orderId)) === null)
        {
            return parent::backendInterface($orderId);
        }

        $arrPayment = deserialize($objOrder->payment_data, true);

        if (!is_array($arrPayment['POSTSALE']) || empty($arrPayment['POSTSALE']))
        {
            return parent::backendInterface($orderId);
        }

        $arrPayment = array_pop($arrPayment['POSTSALE']);
        ksort($arrPayment);
        $i = 0;

        $strBuffer = '
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=payment', '', \Environment::get('request'))).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBT']).'">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">' . $this->name . ' (' . $GLOBALS['TL_LANG']['MODEL']['tl_iso_payment.payu'][0] . ')' . '</h2>

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
