<?php

namespace Omnipay\YandexMoney;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Exception\BadMethodCallException;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\RequestInterface;


/**
 * YandexMoney Gateway Class
 * Mothods no supported:
 * @see GatewayInterface section for IDE
 *
 * @method completeAuthorize(array $options = array()) - not support
 * @method capture(array $options = array())           - not support
 * @method refund(array $options = array())            - not support
 * @method void(array $options = array())              - not support
 * @method createCard(array $options = array())        - not support
 * @method updateCard(array $options = array())        - not support
 * @method deleteCard(array $options = array())        - not support
 */
class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'YandexMoney';
    }

    public function getDefaultParameters()
    {
        return array(
            'password' => '',
            'shopid' => '',
            'scid' => '',
            'method' => '',
            'orderId' => '',
            'invoiceId' => '',
            'md5' => '',
            'currencyNum' => '',
            'orderNumber' => '',
            'customerNumber' => '',
            'returnUrl' => '',
            'cancelUrl' => '',
            'receipt' => '',
        );
    }

    public function getReceipt($decode = false)
    {
        $receipt = $this->getParameter('receipt');
        if ($decode) {
            $receipt = json_decode($receipt, true);
        }

        return $receipt;
    }

    public function setReceipt($value, $encode = false)
    {
        if (!$encode && is_array($value)) {
            throw new BadMethodCallException('technical error: use encode for setReceipt(array) ');
        }

        if ($encode) {
            $value = json_encode($value);
            if (json_last_error()) {
                throw new BadMethodCallException('technical error: json_encode with error');
            }
        }

        return $this->setParameter('receipt', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
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

    public function getOrderId()
    {
        return $this->getParameter('orderId');
    }

    public function setOrderId($value)
    {
        return $this->setParameter('orderId', $value);
    }

    public function getOrderNumber()
    {
        return $this->getParameter('orderNumber');
    }

    public function setOrderNumber($value)
    {
        return $this->setParameter('orderNumber', $value);
    }

    public function getCustomerNumber()
    {
        return $this->getParameter('customerNumber');
    }

    public function setCustomerNumber($value)
    {
        return $this->setParameter('customerNumber', $value);
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

    public function getMethod()
    {
        return $this->getParameter('method');
    }

    public function setMethod($value)
    {
        return $this->setParameter('method', $value);
    }

    public function getReturnUrl()
    {
        return $this->getParameter('returnUrl');
    }

    public function setReturnUrl($value)
    {
        return $this->setParameter('returnUrl', $value);
    }

    public function getCancelUrl()
    {
        return $this->getParameter('cancelUrl');
    }

    public function setCancelUrl($value)
    {
        return $this->setParameter('cancelUrl', $value);
    }

    public function getCurrencyNum()
    {
        return $this->getParameter('currencyNum');
    }

    public function setCurrencyNum($value)
    {
        return $this->setParameter('currencyNum', $value);
    }

    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\YandexMoney\Message\AuthorizeRequest', $parameters);
    }

    /**
     * @param array $parameters
     * @return RequestInterface
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\YandexMoney\Message\PurchaseRequest', $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\YandexMoney\Message\CompletePurchaseRequest', $parameters);
    }
}
