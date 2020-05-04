<?php

namespace Andchir\DigitalGoodsBundle\EventSubscriber;

use Andchir\DigitalGoodsBundle\Service\DigitalGoodsService;
use Andchir\SiteCreatorBundle\Service\SiteCreatorService;
use App\MainBundle\Document\Order;
use App\Events;
use App\MainBundle\Document\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class OrderSubscriber implements EventSubscriberInterface
{

    /** @var ContainerInterface */
    protected $container;
    /** @var  RequestStack */
    protected $requestStack;

    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::ORDER_CREATED => 'onOrderCreated',
            Events::ORDER_STATUS_UPDATED => 'onStatusUpdated'
        ];
    }

    /**
     * @param GenericEvent $event
     */
    public function onOrderCreated(GenericEvent $event)
    {
        /** @var Order $order */
        $order = $event->getSubject();
        $payAfterCheckout = $this->container->hasParameter('app.pay_after_checkout')
            ? $this->container->getParameter('app.pay_after_checkout')
            : false;
        $order->updatePriceTotal();
        if ($order->getPrice() > 0 && $order->getPaymentValue() && $payAfterCheckout) {
            $redirectUrl = $this->container->get('router')->generate('digital_goods_payment_start', [
                'orderId' => $order->getId()
            ]);
            $response = new RedirectResponse($redirectUrl);
            $response->send();
        }
    }

    /**
     * @param GenericEvent $event
     */
    public function onStatusUpdated(GenericEvent $event)
    {
        /** @var Order $order */
        $order = $event->getSubject();
        $sendEmailAfterPayment = $this->container->hasParameter('app.digilal_goods_send_email')
            ? $this->container->getParameter('app.digilal_goods_send_email')
            : false;

        if ($order->getIsPaid() && $sendEmailAfterPayment) {
            /** @var TranslatorInterface $translator */
            $translator = $this->container->get('translator');
            /** @var DigitalGoodsService $digitalGoodsService */
            $digitalGoodsService = $this->container->get('plugin_digital_goods');
            $request = $this->requestStack->getCurrentRequest();
            $siteUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();

            if ($digitalGoodsService->sendPurchaseEmail($order->getEmail(), $siteUrl)) {
                $flashBag = $request->getSession()->getFlashBag();
                $flashBag->clear();
                $flashBag->add('messages', $translator->trans('You have been sent an email with a link to download your purchases.', [], 'e-store'));
            }
        }
    }
}
