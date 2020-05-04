<?php

namespace Andchir\DigitalGoodsBundle\EventSubscriber;

use App\Event\UserRegisteredEvent;
use App\Events;
use App\MainBundle\Document\Order;
use App\MainBundle\Document\User;
use App\Service\UtilsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Translation\TranslatorInterface;

class UserSubscriber implements EventSubscriberInterface
{

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserRegisteredEvent::NAME => 'onUserCreated',
            Events::USER_EMAIL_CONFIRMED => 'onUserEmailConfirmed'
        ];
    }

    /**
     * @param UserRegisteredEvent $event
     */
    public function onUserCreated(UserRegisteredEvent $event)
    {
        /** @var User $user */
        $user = $event->getUser();
        /** @var Request $request */
        $request = $event->getRequest();
        /** @var TranslatorInterface $translator */
        $translator = $this->container->get('translator');
        /** @var UtilsService $utilsServie */
        $utilsService = $this->container->get('app.utils');

        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $secretCode = UtilsService::generatePassword(12);
        $user
            ->setSecretCode($secretCode)
            ->setIsActive(false);

        $dm->flush();

        $siteURL = ($request->server->get('HTTPS') ? 'https' : 'http')
            . "://{$request->server->get('HTTP_HOST')}/";

        $emailBody = $this->container->get('twig')->render('email/email_user_confirm_email.html.twig', [
            'email' => $user->getEmail(),
            'siteUrl' => $siteURL,
            'confirmCode' => $secretCode
        ]);

        $utilsService->sendMail(
            $this->container->getParameter('app.name') . ' - ' . $translator->trans('email_confirm.mail_subject'),
            $emailBody,
            $user->getEmail()
        );

        /** @var FlashBag $flashBag */
        $flashBag = $request->getSession()->getFlashBag();
        $flashBag->clear();
        $flashBag->add('messages', $translator->trans('An email has been sent to you to confirm your email address.', [], 'e-store'));
    }

    /**
     * @param GenericEvent $event
     */
    public function onUserEmailConfirmed(GenericEvent $event)
    {
        /** @var user $user */
        $user = $event->getSubject();

        $userEmail = $user->getEmail();

        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $orders = $dm->getRepository(Order::class)->findBy([
            'email' => $userEmail
        ]);
        /** @var Order $order */
        foreach ($orders as $order) {
            $order->setUserId($user->getId());
        }
        $dm->flush();
    }

}
