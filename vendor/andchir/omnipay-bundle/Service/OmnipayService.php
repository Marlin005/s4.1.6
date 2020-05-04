<?php

namespace Andchir\OmnipayBundle\Service;

use Andchir\OmnipayBundle\Document\PaymentInterface;
use Andchir\OmnipayBundle\Repository\PaymentRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Omnipay\Common\AbstractGateway;
use Omnipay\Omnipay as OmnipayCore;
use Omnipay\Omnipay;
use Psr\Log\LoggerInterface;

class OmnipayService
{
    /** @var AbstractGateway */
    protected $gateway;
    /** @var ContainerInterface */
    private $container;
    /** @var array */
    protected $config;
    /** @var LoggerInterface */
    private $logger;
    /** @var Session */
    private $session;

    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        Session $session,
        array $config = []
    )
    {
        $this->container = $container;
        $this->config = $config;
        $this->logger = $logger;
        $this->session = $session;
    }

    /**
     * @param $gatewayName
     * @return bool
     */
    public function create($gatewayName)
    {
        if (!isset($this->config['gateways'][$gatewayName])) {
            return false;
        }
        $this->gateway = Omnipay::create($gatewayName);
        return true;
    }

    /**
     * @param PaymentInterface $payment
     */
    public function initialize(PaymentInterface $payment)
    {
        $parameters = $this->getGatewayConfigParameters($payment);
        $this->gateway->initialize($parameters);
    }

    /**
     * @return AbstractGateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @param PaymentInterface $payment
     * @param string $configKey
     * @return array
     */
    public function getGatewayConfigParameters(PaymentInterface $payment, $configKey = 'parameters')
    {
        $opts = [
            'CUSTOMER_EMAIL' => $payment->getEmail(),
            'PAYMENT_ID' => $payment->getId(),
            'ORDER_ID' => $payment->getOrderId(),
            'RETURN_URL' => $this->getConfigUrl('return'),
            'NOTIFY_URL' => $this->getConfigUrl('notify'),
            'CANCEL_URL' => $this->getConfigUrl('cancel'),
            'SUCCESS_URL' => $this->getConfigUrl('success'),
            'FAIL_URL' => $this->getConfigUrl('fail'),
            'AMOUNT' => self::toDecimal($payment->getAmount()),
            'CURRENCY' => $payment->getCurrency(),
            'CUSTOMER_IP_ADDR' => $this->getClientIp()
        ];

        $gatewayName = $this->gateway->getShortName();
        $parameters = isset($this->config['gateways'][$gatewayName][$configKey])
            ? $this->config['gateways'][$gatewayName][$configKey]
            : [];

        $start_parameters = [];
        if ($configKey !== 'parameters') {
            $start_parameters = isset($this->config['gateways'][$gatewayName]['parameters'])
                ? $this->config['gateways'][$gatewayName]['parameters']
                : [];
        }

        $request = Request::createFromGlobals();
        $postData = $request->request->all();

        foreach($parameters as $paramName => &$value){
            if (!is_null($value)) {
                $value = str_replace(array_keys($opts), array_values($opts), $value);
            }
            if (is_null($value) && isset($start_parameters[$paramName])) {
                $value = $start_parameters[$paramName];
            }
            if (is_null($value) && isset($postData[$paramName])) {
                $value = $postData[$paramName];
            }
            if (is_null($value)) {
                $value = '';
            }
        }

        return $parameters;
    }

    /**
     * @param PaymentInterface $payment
     * Set gateway parameters
     */
    public function setGatewayParameters(PaymentInterface $payment)
    {
        $parameters = $this->getGatewayConfigParameters($payment);

        $this->logInfo(json_encode($parameters, JSON_UNESCAPED_UNICODE), 'parameters');

        foreach($parameters as $paramName => $value){
            $methodName = 'set' . $paramName;
            if (!empty($value) && method_exists($this->gateway, $methodName)) {
                call_user_func(array($this->gateway, $methodName), $value);
            }
        }
    }

    /**
     * @param string $optionName
     * @param mixed $optionValue
     * @param string $gatewayName
     */
    public function setConfigOption($optionName, $optionValue, $gatewayName = '')
    {
        if (!$gatewayName) {
            $gatewayName = $this->gateway->getShortName();
        }
        $this->config['gateways'][$gatewayName][$optionName] = $optionValue;
    }

    /**
     * @param $options
     * @param string $gatewayName
     */
    public function setConfigOptions($options, $gatewayName = '')
    {
        foreach ($options as $optionName => $optionValue) {
            $this->setConfigOption($optionName, $optionValue, $gatewayName);
        }
    }

    /**
     * @param $type
     * @return mixed|string
     */
    public function getConfigUrl($type)
    {
        $request = Request::createFromGlobals();
        $host = $request->getSchemeAndHttpHost();
        return isset($this->config[$type.'_url'])
            ? $host . $this->config[$type.'_url']
            : $host . $this->config['fail_url'];
    }

    /**
     * @return null|string
     */
    public function getClientIp()
    {
        $request = Request::createFromGlobals();
        return $request->getClientIp();
    }

    /**
     * @param $optionName
     * @return string
     */
    public function getConfigOption($optionName)
    {
        $gatewayName = $this->gateway->getShortName();
        return isset($this->config['gateways'][$gatewayName][$optionName])
            ? $this->config['gateways'][$gatewayName][$optionName]
            : '';
    }

    /**
     * @return array
     */
    public function getGatewayParameters()
    {
        return $this->gateway->getParameters();
    }

    /**
     * @return string
     */
    public function getGatewayName()
    {
        return $this->gateway->getName();
    }

    /**
     * @return boolean
     */
    public function getGatewaySupportsAuthorize()
    {
        return $this->gateway->supportsAuthorize();
    }

    /**
     * @return array
     */
    public function getGatewayDefaultParameters()
    {
        return $this->gateway->getDefaultParameters();
    }

    /**
     * @param PaymentInterface $payment
     * @return \Omnipay\Common\Message\RequestInterface
     */
    public function createPurchase(PaymentInterface $payment)
    {
        $parameters = $this->getGatewayConfigParameters($payment, 'purchase');
        return $this->gateway->purchase($parameters);
    }

    /**
     * @param PaymentInterface $payment
     * @return bool
     */
    public function sendPurchase(PaymentInterface $payment)
    {
        $purchase = $this->createPurchase($payment);
        $this->logInfo("Purchase data: " . json_encode($purchase->getData(), JSON_UNESCAPED_UNICODE), 'start');
        $this->logInfo("Purchase data: " . print_r($purchase->getData(), true), 'start');
        $response = $purchase->send();

        // Save data in session
        $paymentData = [
            'transactionId' => $payment->getId(),
            'email' => $payment->getEmail(),
            'userId' => $payment->getUserId(),
            'amount' => $payment->getAmount(),
            'currency' => $payment->getCurrency(),
            'gatewayName' => $this->getGatewayName()
        ];
        $this->session->set('paymentData', $paymentData);

        $this->logInfo(json_encode($paymentData, JSON_UNESCAPED_UNICODE) . " Order ID: {$payment->getId()}", 'start');

        // Process response
        if ($response->isRedirect()) {
            $response->redirect();
        } else {
            // Payment failed
            echo $response->getMessage();
            return false;
        }
        return true;
    }

    /**
     * @param Request $request
     * @return PaymentInterface|null
     */
    public function getPaymentByRequest(Request $request)
    {
        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        /** @var PaymentRepositoryInterface $paymentRepository */
        $paymentRepository = $dm->getRepository('AppMainBundle:Payment');

        $requestData = array_merge($request->query->all(), $request->request->all());

        // Search payment ID
        $paymentId = 0;
        $paymentIdKeys = $this->config['data_keys']['paymentId'];
        $tmpArr = array_filter($requestData, function($v, $k) use ($paymentIdKeys) {
            return in_array($k, $paymentIdKeys) && !empty($v);
        }, ARRAY_FILTER_USE_BOTH);
        if (!empty($tmpArr)) {
            $tmpArr = array_values($tmpArr);
            $paymentId = (int) current($tmpArr);
        }
        if (!empty($paymentId)) {
            $payments = $paymentRepository->findLastById($paymentId);
            return !empty($payments) ? $payments->getSingleResult() : null;
        }

        // Search customer email
        $customerEmail = '';
        $emailKeys = $this->config['data_keys']['customerEmail'];
        $tmpArr = array_filter($requestData, function($v, $k) use ($emailKeys) {
            return in_array($k, $emailKeys) && !empty($v);
        }, ARRAY_FILTER_USE_BOTH);
        if (!empty($tmpArr)) {
            $tmpArr = array_values($tmpArr);
            $customerEmail = current($tmpArr);
        }
        if (!empty($customerEmail)) {
            $payments = $paymentRepository->findLastByEmail($customerEmail);
            return !empty($payments) ? $payments->getSingleResult() : null;
        }

        return null;
    }

    /**
     * @param $message
     * @param $source
     */
    public function logInfo($message, $source)
    {
        $this->logger->notice($message, ['omnipay' => $source]);
    }

    /**
     * Number to decimal
     * @param $number
     * @return string
     */
    public static function toDecimal($number)
    {
        $number = number_format($number, 2, '.', '');
        return $number;
    }
}
