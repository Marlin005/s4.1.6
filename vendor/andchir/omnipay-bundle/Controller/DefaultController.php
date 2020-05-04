<?php

namespace Andchir\OmnipayBundle\Controller;

use Andchir\OmnipayBundle\Document\PaymentInterface;
use Andchir\OmnipayBundle\Repository\OrderRepositoryInterface;
use Andchir\OmnipayBundle\Repository\PaymentRepositoryInterface;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\AbstractRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Andchir\OmnipayBundle\Service\OmnipayService;
use App\Controller\Admin\OrderController;
use App\Service\SettingsService;
use App\MainBundle\Document\Payment;
use App\MainBundle\Document\Setting;
use App\MainBundle\Document\User;
use App\MainBundle\Document\Order;

class DefaultController extends Controller
{
    /**
     * @Route("/omnipay_start/{id}", name="omnipay_start")
     * @param Request $request
     * @param Order $order
     * @return Response|NotFoundHttpException|AccessDeniedException
     */
    public function indexAction(Request $request, Order $order)
    {
        $output = '';

        /** @var User $user */
        $user = $this->getUser();
        $userId = $user ? $user->getId() : 0;
        /** @var \Doctrine\Common\Persistence\ObjectManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        if ($order->getUserId() !== $userId || $order->getIsPaid()) {
            throw $this->createAccessDeniedException();
        }
        $gatewayName = $order->getPaymentValue();

        /** @var OmnipayService $omnipayService */
        $omnipayService = $this->get('omnipay');
        if (!$gatewayName || !$omnipayService->create($gatewayName)) {
            $omnipayService->logInfo("Payment gateway ({$gatewayName}) not found. Order ID: {$order->getId()}", 'start');
            throw $this->createNotFoundException('Payment gateway not found.');
        };

        $paymentDescription = 'Order #' . $order->getId();

        // Create payment
        $payment = new Payment();
        $payment
            ->setUserId($userId)
            ->setEmail($order->getEmail())
            ->setOrderId($order->getId())
            ->setCurrency($order->getCurrency())
            ->setAmount($order->getPrice())
            ->setDescription($paymentDescription)
            ->setStatus(PaymentInterface::STATUS_CREATED)
            ->setOptions(['gatewayName' => $gatewayName]);

        $dm->persist($payment);
        $dm->flush();

        $omnipayService->initialize($payment);

        //if ($omnipayService->getGatewaySupportsAuthorize()) {
        if ($gatewayName === 'Sberbank') {

            /** @var AbstractRequest $authRequest */
            $authRequest = $omnipayService->getGateway()->authorize(
                    [
                        'orderNumber' => $payment->getId(),
                        'amount' => $payment->getAmount() * 100, // The amount of payment in kopecks (or cents)
                        'returnUrl' => $omnipayService->getConfigUrl('success'),
                        'description' => $paymentDescription
                    ]
                )
                ->setUserName(uniqid('', true))
                ->setPassword(uniqid('', true));

            /** @var AbstractResponse $response */
            $response = $authRequest->send();

            if (!$response->isSuccessful()) {
                $output = $response->getMessage();
            }

            if ($response->isRedirect()) {
                $response->redirect();
            }

        } else {

            $omnipayService->sendPurchase($payment);

        }

        return new Response($output);
    }

    /**
     * @Route("/omnipay_return", name="omnipay_return")
     * @param Request $request
     * @return Response
     */
    public function returnAction(Request $request)
    {
        /** @var OmnipayService $omnipayService */
        $omnipayService = $this->get('omnipay');

        /** @var PaymentInterface $payment */
        $payment = $omnipayService->getPaymentByRequest($request);
        if (!$payment || !$this->getOrder($payment)) {
            $omnipayService->logInfo('Order not found. ', 'return');
            $this->logRequestData($request, 0, 'return');
            return new Response('');
        } else if ($payment->getStatus() !== PaymentInterface::STATUS_CREATED) {
            return new Response('');
        }

        $gatewayName = $payment->getOptionValue('gatewayName');
        if (!$gatewayName || !$omnipayService->create($gatewayName)) {
            $omnipayService->logInfo("Payment gateway ({$gatewayName}) not found.", 'return');
            return new Response('');
        };

        $this->logRequestData($request, $payment->getId(), 'return');

        $orderData = $omnipayService->getGatewayConfigParameters($payment, 'complete');

        try {

            $response = $omnipayService->getGateway()->authorize($orderData)->send();
            $responseData = $response->getData();

            if ($response->isSuccessful()){
                $omnipayService->logInfo('PAYMENT SUCCESS. ' . $response->getMessage(), 'return');
                return $this->createResponse($response->getMessage());
            } else if ($response->isRedirect()) {
                $omnipayService->logInfo('PAYMENT REDIRECT. ' . json_encode($responseData), 'return');
                $response->redirect();
            } else {
                $omnipayService->logInfo("PAYMENT FAIL. MESSAGE: {$response->getMessage()}" . json_encode($responseData), 'return');
                $this->paymentUpdateStatus($payment->getId(), $payment->getEmail(), PaymentInterface::STATUS_ERROR);
                return $this->createResponse($response->getMessage());
            }

        } catch (\Exception $e) {
            $omnipayService->logInfo('OMNIPAY ERROR: '. $e->getMessage(), 'return');
        }

        return new Response('');
    }

    /**
     * @Route("/omnipay_notify", name="omnipay_notify")
     * @param Request $request
     * @return Response|RedirectResponse
     */
    public function notifyAction(Request $request)
    {
        $this->logRequestData($request, 0, 'notify');

        /** @var OmnipayService $omnipayService */
        $omnipayService = $this->get('omnipay');

        /** @var PaymentInterface $payment */
        $payment = $omnipayService->getPaymentByRequest($request);
        if (!$payment) {
            $paymentData = $request->getSession()->get('paymentData');
            $paymentId = !empty($paymentData['transactionId'])
                ? (int) $paymentData['transactionId']
                : 0;
            $paymentEmail = !empty($paymentData['email'])
                ? $paymentData['email']
                : '';
            $payment = $this->getPayment($paymentId, $paymentEmail);
        } else if ($payment->getStatus() !== PaymentInterface::STATUS_CREATED) {
            $payment = null;
        }
        if (!$payment || !$this->getOrder($payment)) {
            $omnipayService->logInfo('Order not found. ', 'notify');
            $this->logRequestData($request, 0, 'notify');
            return new Response('');
        }

        $gatewayName = $payment->getOptionValue('gatewayName');
        if (!$gatewayName || !$omnipayService->create($gatewayName)) {
            $omnipayService->logInfo("Payment gateway ({$gatewayName}) not found.", 'notify');
            return new Response('');
        };

        $orderData = $omnipayService->getGatewayConfigParameters($payment, 'complete');

        $this->logRequestData($request, $payment->getId(), 'notify');

        try {

            $response = $omnipayService->getGateway()->completePurchase($orderData)->send();
            $responseData = $response->getData();

            if ($response->isSuccessful()){
                $this->paymentUpdateStatus($payment->getId(), $payment->getEmail(), PaymentInterface::STATUS_COMPLETED);
                $this->setOrderPaid($payment);

                $message = $response->getMessage();
                if (!$message) {
                    return new RedirectResponse($omnipayService->getConfigUrl('success'));
                }
                return $this->createResponse($message);
            }
            if ($response->isRedirect()) {
                $response->redirect();
            }
            if (!$response->isSuccessful()){
                $omnipayService->logInfo("PAYMENT FAIL. ERROR: {$response->getMessage()} " . json_encode($responseData), 'notify');
                $this->paymentUpdateStatus($payment->getId(), $payment->getEmail(), PaymentInterface::STATUS_ERROR);

                $message = $response->getMessage();
                if (!$message) {
                    return new RedirectResponse($omnipayService->getConfigUrl('fail'));
                }
                return $this->createResponse($message);
            }

        } catch (\Exception $e) {
            $omnipayService->logInfo('OMNIPAY ERROR: ' . $e->getMessage(), 'notify');
        }

        return new Response('');
    }

    /**
     * @Route("/omnipay_cancel", name="omnipay_cancel")
     * @param Request $request
     * @return Response|NotFoundHttpException|AccessDeniedException
     */
    public function cancelAction(Request $request)
    {
        /** @var OmnipayService $omnipayService */
        $omnipayService = $this->get('omnipay');
        $paymentData = $request->getSession()->get('paymentData');
        $paymentId = !empty($paymentData['transactionId'])
            ? (int) $paymentData['transactionId']
            : 0;

        $this->logRequestData($request, $paymentId, 'cancel');

        return $this->redirect($omnipayService->getConfigUrl('fail'));
    }

    /**
     * Update order status in Shopkeeper app
     * Update order status
     * @param PaymentInterface $payment
     * @return bool
     */
    public function setOrderPaid(PaymentInterface $payment)
    {
        $paymentStatusAfterNumber = (int) $this->getParameter('app.payment_status_after_number');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('app.settings');
        /** @var Setting $statusSetting */
        $statusSetting = $settingsService->getOrderStatusByNumber($paymentStatusAfterNumber);
        if (!$statusSetting) {
            return false;
        }
        $orderController = new OrderController();
        $orderController->setContainer($this->container);
        return $orderController->updateItemProperty(
            $payment->getOrderId(),
            'status',
            $statusSetting->getName()
        );
    }

    /**
     * Get payment object
     * @param int $paymentId
     * @param string $customerEmail
     * @param null $statusName
     * @return object
     */
    public function getPayment($paymentId, $customerEmail, $statusName = null)
    {
        if (!$statusName) {
            $statusName = PaymentInterface::STATUS_CREATED;
        }
        return $this->getRepository()->findOneBy([
            'id' => $paymentId,
            'email' => $customerEmail,
            'status' => $statusName
        ]);
    }

    /**
     * @param PaymentInterface $payment
     * @return object
     */
    public function getOrder(PaymentInterface $payment)
    {
        return $this->getOrderRepository()->findOneBy([
            'id' => $payment->getOrderId(),
            'userId' => $payment->getUserId(),
            'email' => $payment->getEmail()
        ]);
    }

    /**
     * @param int $paymentId
     * @param string $customerEmail
     * @param string $statusName
     */
    public function paymentUpdateStatus($paymentId, $customerEmail, $statusName)
    {
        /** @var \Doctrine\Common\Persistence\ObjectManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();

        /** @var PaymentInterface $payment */
        $payment = $this->getPayment($paymentId, $customerEmail);
        if (!$payment) {
            return;
        }
        $payment->setStatus($statusName);
        $dm->flush();
    }

    /**
     * @param Request $request
     * @param $paymentId
     * @param string $source
     */
    public function logRequestData(Request $request, $paymentId = 0, $source = 'request')
    {
        /** @var OmnipayService $omnipayService */
        $omnipayService = $this->get('omnipay');
        $postData = $request->request->all() ?: [];
        $getData = $request->query->all() ?: [];
        $message = $paymentId
            ? "Payment ({$paymentId}). REQUEST DATA: "
            : 'REQUEST DATA: ';
        $omnipayService->logInfo(
            $message . json_encode(array_merge($postData, $getData)),
            $source
        );
    }

    /**
     * @param $content
     * @return Response
     */
    public function createResponse($content)
    {
        $response = new Response($content);
        if (strpos($content, '<?xml') === 0) {
            $response->headers->set('Content-Type', 'application/xml');
        } else {
            $response->headers->set('Content-Type', 'text/html');
        }
        return $response;
    }

    /**
     * @return OrderRepositoryInterface
     */
    public function getOrderRepository()
    {
        return $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository('AppMainBundle:Order');
    }

    /**
     * @return PaymentRepositoryInterface
     */
    public function getRepository()
    {
        return $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository('AppMainBundle:Payment');
    }
}
