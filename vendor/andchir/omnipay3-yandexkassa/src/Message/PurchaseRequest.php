<?php

namespace Omnipay\YandexMoney\Message;

/**
 * YandexMoney Purchase Request
 */
class PurchaseRequest extends \Omnipay\Common\Message\AbstractRequest
{
    public function getPreparedParams(array $parameters = array())
    {
        if (isset($parameters['orderSumAmount'])) {
            $parameters['amount'] = $parameters['orderSumAmount'];
        }

        if (!isset($parameters['currency']) && $this->getCurrencyDefault()) {
            $parameters['currency'] = $this->getCurrencyDefault();
        }

        return $parameters;
    }

    public function getCurrencyDefault()
    {
        return getenv('YANDEXKASSA_CURRENCY_DEFAULT') ? getenv('YANDEX-YANDEXKASSA_CURRENCY_DEFAULT') : 'RUB';
    }

    public function initialize(array $parameters = array())
    {
        return parent::initialize(
            $this->getPreparedParams(
                $parameters
            )
        );
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

    public function getCustomerNumber()
    {
        return $this->getParameter('customerNumber');
    }

    public function setCustomerNumber($value)
    {
        return $this->setParameter('customerNumber', $value);
    }

    public function getOrderNumber()
    {
        return $this->getParameter('orderNumber');
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

    public function getShopId()
    {
        return $this->getParameter('shopid');
    }

    public function setShopId($value)
    {
        return $this->setParameter('shopid', $value);
    }

    public function getScid()
    {
        return $this->getParameter('scid');
    }

    public function setScid($value)
    {
        return $this->setParameter('scid', $value);
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

    public function setAction($value)
    {
        return $this->setParameter('action', $value);
    }


    public function getOrderSumAmount()
    {
        return $this->getParameter('orderSumAmount');
    }

    public function getOrderSumCurrencyPaycash()
    {
        return $this->getParameter('orderSumCurrencyPaycash');
    }

    public function getOrderSumBankPaycash()
    {
        return $this->getParameter('orderSumBankPaycash');
    }

    public function setOrderSumAmount($value)
    {
        return $this->setParameter('orderSumAmount', $value);
    }

    public function setOrderSumCurrencyPaycash($value)
    {
        return $this->setParameter('orderSumCurrencyPaycash', $value);
    }

    public function setOrderSumBankPaycash($value)
    {
        return $this->setParameter('orderSumBankPaycash', $value);
    }

    public function getInvoiceId()
    {
        return $this->getParameter('invoiceId');
    }

    public function setInvoiceId($value)
    {
        return $this->setParameter('invoiceId', $value);
    }

    public function getMd5()
    {
        return $this->getParameter('md5');
    }

    public function setMd5($value)
    {
        return $this->setParameter('md5', $value);
    }

    public function getReceipt()
    {
        return $this->getParameter('receipt');
    }

    public function setReceipt($value)
    {
        return $this->setParameter('receipt', $value);
    }


    public function getData()
    {
        $this->validate('shopid', 'scid', 'customerNumber', 'amount', 'orderId',
            'method', 'returnUrl', 'cancelUrl');

        $data = array();
        $data['scid'] = $this->getScid();
        $data['shopid'] = $this->getShopId();
        $data['customerNumber'] = $this->getCustomerNumber();
        $data['orderNumber'] = $this->getOrderId();
        $data['sum'] = $this->getAmount();
        $data['orderSumCurrencyPaycash'] = $this->getCurrencyNum();

        $data['paymentType'] = $this->getMethod();

        $data['shopSuccessURL'] = $this->getReturnUrl();
        $data['shopFailURL'] = $this->getCancelUrl();

        $receipt = $this->getReceipt();
        if (!empty($receipt)) {
            $data['ym_merchant_receipt'] = $receipt;
        }

        return $data;
    }

    public function sendData($data)
    {
        return $this->response = new PurchaseResponse($this, $data);
    }
}
