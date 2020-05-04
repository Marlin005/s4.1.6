<?php

namespace Omnipay\YandexMoney\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * YandexMoney Complete Purchase Request
 */
class CompletePurchaseRequest extends AuthorizeRequest
{
   	
    public function sendData($data)
    {
		return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
