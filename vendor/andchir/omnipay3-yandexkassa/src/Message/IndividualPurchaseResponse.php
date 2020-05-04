<?php

namespace Omnipay\YandexMoney\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * YandexMoney Purchase Response
 */
class IndividualPurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
  
	public function getEndpoint()
	{
		if ($this->getRequest()->getTestMode()) {
			return 'https://demomoney.yandex.ru/quickpay/confirm.xml';
		}else {
			return 'https://money.yandex.ru/quickpay/confirm.xml';
		}
	}

    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return true;
    }

    public function getRedirectUrl()
    {
        return $this->getEndpoint();
    }

    public function getRedirectMethod()
    {
        return 'POST';
    }

    public function getRedirectData()
    {
        return $this->data;
    }
}
