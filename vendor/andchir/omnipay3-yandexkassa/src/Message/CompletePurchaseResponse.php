<?php

namespace Omnipay\YandexMoney\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * YandexMoney Complete Purchase Response
 */
class CompletePurchaseResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return ($this->data['code'] === 0);
    }

    public function isRedirect()
    {
        return false;
    }

    public function getMessage()
    {
      $xmlMessage  = '<?xml version="1.0" encoding="UTF-8"?>';
	  $xmlMessage .= '<paymentAvisoResponse performedDatetime="' . date("c") . '" '
				   . 'code="' . $this->data['code'] . '" '
				   . 'invoiceId="' . $this->data['invoiceId'] .'" '
				   . 'shopId="'.$this->data['shopId'] . '"/>';
	  return $xmlMessage;
	}

	 public function getTransactionReference(){
		return $this->data['orderNumber'];
	 }
}
