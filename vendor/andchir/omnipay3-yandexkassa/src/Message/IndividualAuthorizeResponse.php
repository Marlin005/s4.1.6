<?php

namespace Omnipay\YandexMoney\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * YandexMoney Authorize Response
 */
class IndividualAuthorizeResponse extends AbstractResponse 
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
      if ($this->isSuccessful()) {
		  return false;
	  }else {
		  return 'HTTP/1.0 401 Unauthorized';
	  }
    }

	 public function getTransactionReference(){
		return $this->data['orderNumber'];
	 }
   
}
