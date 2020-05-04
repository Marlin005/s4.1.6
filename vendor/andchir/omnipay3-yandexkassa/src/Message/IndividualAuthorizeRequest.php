<?php

namespace Omnipay\YandexMoney\Message;

/**
 * YandexMoney Authorize Request
 */
class IndividualAuthorizeRequest extends \Omnipay\Common\Message\AbstractRequest
{
  
	public function getAccount()
    {
        return $this->getParameter('account');
    }

    public function setAccount($value)
    {
        return $this->setParameter('account', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    public function getMethod()
    {
        return $this->getParameter('method');
    }

	public function setMethod($value)
    {
        return $this->setParameter('method', $value);
    }
	
	
    public function getOrderNumber()
    {
        return $this->getParameter('orderNumber');
    }

	public function setCustomerNumber($value)
    {
        return $this->setParameter('customerNumber', $value);
    }
	 public function getCustomerNumber()
    {
        return $this->getParameter('customerNumber');
    }

	public function setOrderNumber($value)
    {
        return $this->setParameter('orderNumber', $value);
    }

	public function getOrderId()
    {
        return $this->getParameter('orderId');
    }

	public function setOrderId($value)
    {
        return $this->setParameter('orderId', $value);
    }
	public function getCurrencyNum()
    {
        return $this->getParameter('currencyNum');
    }

    public function setCurrencyNum($value)
    {
        return $this->setParameter('currencyNum', $value);
    }

	public function getAction()
	{
		 return $this->getParameter('action');
	}
	
	public function getNotificationType()
	{
		 return $this->getParameter('notification_type');
	}
	public function setNotificationType($value)
	{
		 return $this->setParameter('notification_type', $value);
	}
	public function getNotification_type()
	{
		 return $this->getParameter('notification_type');
	}
	public function setNotification_type($value)
	{
		 return $this->setParameter('notification_type', $value);
	}
	public function getOperationId()
	{
		 return $this->getParameter('operation_id');
	}
	public function setOperationId($value)
	{
		return $this->setParameter('operation_id', $value);
	}
	public function setOperation_id($value)
	{
		return $this->setParameter('operation_id', $value);
	}
	public function getOperation_id()
	{
		 return $this->getParameter('operation_id');
	}
	public function getAmount()
	{
		 return $this->getParameter('amount');
	}
	public function getCurrency()
	{
		 return $this->getParameter('currency');
	}
	public function getDatetime()
	{
		 return $this->getParameter('datetime');
	}
	public function setDatetime($value)
	{
		 return $this->setParameter('datetime', $value);
	}
	public function getSender()
	{
		 return $this->getParameter('sender');
	}
	public function setSender($value)
	{
		 return $this->setParameter('sender', $value);
	}
	public function getCodepro()
	{
		 return $this->getParameter('codepro');
	}
	public function setCodepro($value)
	{
		 return $this->setParameter('codepro', $value);
	}
	public function getLabel()
	{
		 return $this->getParameter('label');
	}
	public function setLabel($value)
	{
		return $this->setParameter('label', $value);
	}
	public function setSha1Hash($value)
	{		 
		return $this->setParameter('sha1_hash', $value);
	}	
	public function getSha1Hash()
	{
		return $this->getParameter('sha1_hash');
	}	
	public function setSha1_hash($value)
	{		 
		return $this->setParameter('sha1_hash', $value);
	}	
	public function getSha1_hash()
	{
		return $this->getParameter('sha1_hash');
	}	

	public function setReturnUrl($value)
	{		 
		return $this->setParameter('returnUrl', $value);
	}	
	public function getReturnUrl()
	{
		return $this->getParameter('returnUrl');
	}	
	public function setCancelUrl($value)
	{		 
		return $this->setParameter('cancelUrl', $value);
	}	
	public function getCancelUrl()
	{
		return $this->getParameter('cancelUrl');
	}		
    public function getData()
    {
				
        $data = array();
		if ($this->checkSign()) {
			$data['code'] = 0;
		}else {
			$data['code'] = 1;
		}
		$data['orderNumber'] = $this->getLabel();	
		return $data;
    }

	public function checkSign()
	{
		$string = $this->getNotificationType() . '&' 
				. $this->getOperationId() . '&'
				. $this->getAmount() . '&'
				. $this->getCurrency() . '&'
				. $this->getDatetime() . '&'
				. $this->getSender() . '&'
				. $this->getCodepro() . '&'
				. $this->getPassword() . '&'
				. $this->getLabel();
		return (sha1($string) == $this->getSha1Hash());
	}
	
    public function sendData($data)
    {
        return $this->response = new IndividualAuthorizeResponse($this, $data);
    }
}
