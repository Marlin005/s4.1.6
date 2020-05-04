<?php

namespace Andchir\DigitalGoodsBundle\Service;

use App\Controller\CatalogController;
use App\Event\CategoryUpdatedEvent;
use Andchir\ImportExportBundle\Document\ExportConfiguration;
use Andchir\ImportExportBundle\Document\ImportConfiguration;
use App\MainBundle\Document\Order;
use App\Repository\PaymentRepository;
use App\Service\UtilsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\GenericEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DigitalGoodsService
{

    /** @var ContainerInterface */
    private $container;
    /** @var array */
    protected $config;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        array $config = []
    )
    {
        $this->container = $container;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param $str
     * @param string $logFilePath
     * @param array $options
     * @return bool
     */
    public function logging($str, $logFilePath = '', $options = [])
    {
        if (is_array($str)) {
            $str = json_encode($str);
        }

        if (isset($options['max_log_size'])
            && file_exists($logFilePath)
            && filesize($logFilePath) >= $options['max_log_size']) {
                unlink($logFilePath);
        }

        $fp = fopen($logFilePath, 'a');

        $str = PHP_EOL . $str;
        if (!empty($options['write_date'])) {
            $str = PHP_EOL . PHP_EOL . date('d/m/Y H:i:s') . $str;
        }

        fwrite($fp, $str);
        fclose($fp);

        return true;
    }

    /**
     * @param string $email
     * @param string $siteUrl
     * @return bool
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function sendPurchaseEmail($email, $siteUrl)
    {
        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        /** @var UtilsService $utilsServie */
        $utilsService = $this->container->get('app.utils');
        /** @var TranslatorInterface $translator */
        $translator = $this->container->get('translator');

        $orders = $dm->getRepository(Order::class)->findBy([
            'email' => $email,
            'isPaid' => true
        ]);
        if (empty($orders)) {
            return false;
        }

        $secretCode = UtilsService::generatePassword(16);
        /** @var Order $order */
        foreach ($orders as $order) {
            $order->setOptionValue('secretCode', $secretCode);
        }
        $dm->flush();

        $downloadUrl = $siteUrl . "/digital_goods/my_purchases?email={$email}&sc={$secretCode}";
        $unsubscribeUrl = $siteUrl . "/digital_goods/unsubscribe?email={$email}";
        $emailBody = $this->renderView('@DigitalGoods/Default/email/email_my_purchases.html.twig', [
            'siteUrl' => $siteUrl,
            'secretCode' => $secretCode,
            'email' => $email,
            'downloadUrl' => $downloadUrl,
            'unsubscribeUrl' => $unsubscribeUrl
        ]);

        $utilsService->sendMail($translator->trans('Your purchases', [], 'e-store'), $emailBody, $email);

        return true;
    }

    /**
     * Returns a rendered view.
     *
     * @final
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        if ($this->container->has('templating')) {
            @trigger_error('Using the "templating" service is deprecated since version 4.3 and will be removed in 5.0; use Twig instead.', E_USER_DEPRECATED);

            return $this->container->get('templating')->render($view, $parameters);
        }
        if (!$this->container->has('twig')) {
            throw new \LogicException('You can not use the "renderView" method if the Templating Component or the Twig Bundle are not available. Try running "composer require symfony/twig-bundle".');
        }
        return $this->container->get('twig')->render($view, $parameters);
    }
}
