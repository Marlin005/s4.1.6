<?php

namespace Omnipay\YandexMoney\Message;

use Omnipay\Common\Exception\InvalidResponseException;

/**
 * Yandexmoney Autorize Request
 */
class AuthorizeRequest extends PurchaseRequest
{
    public function getData()
    {
		$this->validate('action', 'orderSumAmount', 'orderSumCurrencyPaycash', 'orderSumBankPaycash', 
						'shopid', 'invoiceId', 'customerNumber', 'password');

		$data = array();
		$data['action'] = $this->getAction();
		if ($this->checkSign()) {
		    if (number_format($this->getOrderSumAmount(),2) == number_format($this->getAmount(), 2)){
                $data['code'] = 0;
            }else{
                $data['code'] = 100;
            }
		}else {
			$data['code'] = 1;
		}
		$data['shopId'] = $this->getShopId();
		$data['invoiceId'] = $this->getInvoiceId();
		$data['orderNumber'] = $this->getOrderNumber();
		
        return $data;
    }

	public function checkSign()
	{
		$string = $this->getAction() . ';' 
				. $this->getOrderSumAmount() . ';' 
				. $this->getOrderSumCurrencyPaycash() . ';' 
				. $this->getOrderSumBankPaycash() . ';' 
				. $this->getShopId() . ';' 
				. $this->getInvoiceId() . ';' 
				. $this->getCustomerNumber() . ';' 
				. $this->getPassword();
		$md5 = strtoupper(md5($string));
		return ($md5 == $this->getMd5());
	}

    public function sendData($data)
    {
        return $this->response = new AuthorizeResponse($this, $data);
    }
}
