<?php

namespace Andchir\DigitalGoodsBundle\Controller;

use Andchir\DigitalGoodsBundle\Form\MyPurchasesType;
use Andchir\DigitalGoodsBundle\Service\DigitalGoodsService;
use App\Controller\ProductController;
use App\MainBundle\Document\ContentType;
use App\MainBundle\Document\FileDocument;
use App\MainBundle\Document\Order;
use App\MainBundle\Document\OrderContent;
use App\MainBundle\Document\User;
use App\MainBundle\Document\Setting;
use App\MainBundle\Document\Category;
use App\Repository\ContentTypeRepository;
use App\Service\SettingsService;
use App\Service\ShopCartService;
use App\Repository\OrderRepository;
use App\Service\UtilsService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DefaultController
 * @package Andchir\DigitalGoodsBundle\Controller
 * @Route("/digital_goods")
 */
class DefaultController extends Controller
{

    /**
     * @Route("/payment_start/{orderId}", name="digital_goods_payment_start", methods={"GET","POST"})
     * @param Request $request
     * @param $orderId
     * @return Response
     */
    public function startPayment(Request $request, $orderId)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('app.settings');
        /** @var ShopCartService $shopCartService */
        $shopCartService = $this->get('app.shop_cart');
        $orderStatusSettings = $settingsService->getSettingsGroup(Setting::GROUP_ORDER_STATUSES);
        $paymentStatusNumber = (int) $this->container->getParameter('app.payment_status_number');
        /** @var Setting $paymentStatus */
        $paymentStatus = $orderStatusSettings[$paymentStatusNumber - 1] ?? current($orderStatusSettings);

        /** @var FlashBag $flashBag */
        $flashBag = $request->getSession()->getFlashBag();
        $flashBag->clear();

        $order = $this->getOrdersRepository()->findOneBy([
            'id' => $orderId,
            'status' => $paymentStatus->getName()
        ]);
        if (!$order) {
            throw $this->createNotFoundException();
        }

        if (!$order->getPaymentValue()) {
            return $this->redirectToRoute('homepage');
        }

        $receipt = $shopCartService->createReceipt($order);

        return $this->render('@DigitalGoods/Default/payment_form.html.twig', [
            'order' => $order,
            'receiptOptionName' => $this->container->hasParameter('app.receipt_option_name')
                ? $this->getParameter('app.receipt_option_name')
                : 'receipt',
            'receipt' => $receipt,
            'receiptJSON' => json_encode($receipt, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * @Route("/download/{orderId}/{productId}", name="digital_goods_download", methods={"GET"})
     * @param $orderId
     * @param $productId
     * @return Response
     */
    public function downloadProductAction($orderId, $productId)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('app.settings');
        /** @var User $user */
        $user = $this->getUser();
        $filesDirPath = $this->container->getParameter('app.files_dir_path');

        $orderStatusSettings = $settingsService->getSettingsGroup(Setting::GROUP_ORDER_STATUSES);
        $paymentAfterStatusNumber = (int) $this->container->getParameter('app.payment_status_after_number');
        /** @var Setting $paymentStatus */
        $paymentStatus = $orderStatusSettings[$paymentAfterStatusNumber - 1] ?? '';

        /** @var Order $order */
        $order = $this->getOrdersRepository()->findOneBy([
            'id' => (int) $orderId,
            'status' => $paymentStatus ? $paymentStatus->getName() : '',
            'userId' => $user->getId()
        ]);
        if (!$order) {
            throw $this->createNotFoundException();
        }

        $orderContent = $order->getContentArray();
        $index = array_search((int) $productId, array_column($orderContent, 'id'));

        if ($index === false) {
            throw $this->createNotFoundException();
        }

        $contentTypeName = $orderContent[$index]['contentTypeName'];

        /** @var FileDocument $fileDocument */
        $fileDocument = $this->getProductFileDocument($contentTypeName, (int) $productId);

        if ($fileDocument) {
            $fileDocument->setUploadRootDir($filesDirPath);
            return UtilsService::downloadFile($fileDocument->getUploadedPath(), $fileDocument->getTitle());
        }

        return new Response('');
    }

    /**
     * @Route(
     *     "/my_purchases/{page}",
     *     name="digital_goods_my_purchases",
     *     requirements={"page"},
     *     defaults={"page": 1},
     *     methods={"GET", "POST"}
     *     )
     * @param Request $request
     * @param UtilsService $utilsService
     * @param DigitalGoodsService $digitalGoodsService
     * @param $page
     * @return Response
     */
    public function myPurchasesAction(Request $request, UtilsService $utilsService, DigitalGoodsService $digitalGoodsService, $page = 1)
    {
        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        /** @var Category $category */
        $category = $dm->getRepository(Category::class)->find(0);
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $pageLimit = 10;

        $requestEmail = $request->get('email');
        $requestSecretCode = $request->get('sc');

        if (!$category) {
            throw $this->createNotFoundException();
        }

        $contentType = $category->getContentType();
        $collectionName = $contentType->getCollection();

        $productController = new ProductController();
        $productController->setContainer($this->container);

        $collection = $productController->getCollection($collectionName);
        $currentPage = $collection->findOne([
            'isActive' => true,
            'href' => '/digital_goods/my_purchases'
        ]);

        $mode = 'form';
        /** @var Form $form */
        $form = $this->createForm(MyPurchasesType::class);
        $form->handleRequest($request);

        // User purchases
        if ($this->isGranted('ROLE_USER')) {

            $mode = 'user_purchases';
            $purchases = $this->getPurchasesByEmail($this->getUser()->getEmail());

            $pagesOptions = UtilsService::getPagesOptions([
                'page' => $page,
                'limit' => $pageLimit
            ], count($purchases));

            $purchases = array_slice($purchases, $pagesOptions['skip'], $pagesOptions['limit']);

            return $this->render('@DigitalGoods/Default/my_purchases.html.twig', [
                'form' => $form->createView(),
                'currentPage' => $currentPage,
                'activeCategoriesIds' => $currentPage ? [0-$currentPage['_id']] : [],
                'purchases' => $purchases,
                'mode' => $mode,
                'requestEmail' => $this->getUser()->getEmail(),
                'requestSecretCode' => $requestSecretCode,
                'pagesOptions' => $pagesOptions
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $email = $formData['email'];
            $siteUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();

            if ($digitalGoodsService->sendPurchaseEmail($email, $siteUrl)) {
                $request->getSession()
                    ->getFlashBag()
                    ->add('messages', $translator->trans('You have been sent an email with a link to download your purchases.', [], 'e-store'));

                $this->redirectToRoute('digital_goods_my_purchases');
            } else {
                $form->addError(new FormError($translator->trans(
                    'Purchases not found.',
                    [], 'e-store'
                )));
            }
        }

        $purchases = [];
        if ($requestEmail && $requestSecretCode) {
            $mode = 'purchases';
            $purchases = $this->getPurchasesByEmail($requestEmail, $requestSecretCode);
        }

        $pagesOptions = UtilsService::getPagesOptions([
            'page' => $page,
            'limit' => $pageLimit
        ], count($purchases));

        $purchases = array_slice($purchases, $pagesOptions['skip'], $pagesOptions['limit']);

        return $this->render('@DigitalGoods/Default/my_purchases.html.twig', [
            'form' => $form->createView(),
            'currentPage' => $currentPage,
            'activeCategoriesIds' => $currentPage ? [0-$currentPage['_id']] : [],
            'purchases' => $purchases,
            'mode' => $mode,
            'requestEmail' => $requestEmail,
            'requestSecretCode' => $requestSecretCode,
            'pagesOptions' => $pagesOptions
        ]);
    }

    /**
     * @Route("/my_purchases_download/{productId}", name="digital_goods_my_purchases_download", methods={"POST"})
     * @param Request $request
     * @param $productId
     * @return Response
     */
    public function myPurchaseDownloadsAction(Request $request, $productId)
    {
        $requestEmail = $request->get('email');
        $requestSecretCode = $request->get('sc');
        $filesDirPath = $this->container->getParameter('app.files_dir_path');

        $purchases = $this->getPurchasesByEmail($requestEmail, $requestSecretCode);

        if (!count($purchases)) {
            throw $this->createNotFoundException();
        }

        $foundItems = array_filter($purchases, function($purchase) use ($productId) {
            /** @var OrderContent $purchase */
            return $purchase->getId() === (int) $productId;
        });

        if (!count($foundItems)) {
            throw $this->createNotFoundException();
        }

        /** @var OrderContent $orderContent */
        $orderContent = current($foundItems);
        $contentTypeName = $orderContent->getContentTypeName();

        /** @var FileDocument $fileDocument */
        $fileDocument = $this->getProductFileDocument($contentTypeName, (int) $productId);

        if ($fileDocument) {
            $fileDocument->setUploadRootDir($filesDirPath);
            return UtilsService::downloadFile($fileDocument->getUploadedPath(), $fileDocument->getTitle());
        }

        return new Response('');
    }

    /**
     * @param $requestEmail
     * @param $requestSecretCode
     * @return array
     */
    public function getPurchasesByEmail($requestEmail, $requestSecretCode = '')
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('app.settings');
        $orderStatusSettings = $settingsService->getSettingsGroup(Setting::GROUP_ORDER_STATUSES);
        $paymentAfterStatusNumber = (int) $this->container->getParameter('app.payment_status_after_number');
        /** @var Setting $paymentStatus */
        $paymentStatus = $orderStatusSettings[$paymentAfterStatusNumber - 1];

        if ($this->isGranted('ROLE_USER')) {
            /** @var User $user */
            $user = $this->getUser();
            $purchasesOrders = $this->getOrdersRepository()
                ->findBy([
                    'email' => $requestEmail,
                    'userId' => $user->getId(),
                    'status' => $paymentStatus->getName()
                ]);
        } else {
            if (!$requestEmail || !$requestSecretCode) {
                return [];
            }
            $purchasesOrders = $this->getOrdersRepository()
                ->createQueryBuilder()
                ->field('email')->equals($requestEmail)
                ->field('options.name')->equals('secretCode')
                ->field('options.value')->equals($requestSecretCode)
                ->field('status')->equals($paymentStatus->getName())
                ->getQuery()->execute();
        }

        $purchases = [];
        /** @var Order $purchasesOrder */
        foreach ($purchasesOrders as $purchasesOrder) {
            $content = $purchasesOrder->getContent();
            /** @var OrderContent $orderContent */
            foreach ($content as $orderContent) {
                $foundItems = array_filter($purchases, function($purchase) use ($orderContent) {
                    /** @var OrderContent $purchase */
                    /** @var OrderContent $orderContent */
                    return $purchase->getId() === $orderContent->getId();
                });
                if (!count($foundItems)) {
                    $purchases[] = $orderContent;
                }
            }
        }

        return $purchases;
    }

    /**
     * @param $contentTypeName
     * @param $productId
     * @return FileDocument|null
     * @internal param ContentType $contentType
     */
    public function getProductFileDocument($contentTypeName, $productId)
    {
        $contentType = $this->getContentTypeRepository()->findOneBy([
            'name' => $contentTypeName
        ]);
        if (!$contentType) {
            return null;
        }

        $productController = new ProductController();
        $productController->setContainer($this->container);

        $collectionName = $contentType->getCollection();
        $collection = $productController->getCollection($collectionName);

        $product = $collection->findOne([
            '_id' => (int) $productId
        ]);
        if (empty($product) || empty($product['file'])) {
            return null;
        }
        /** @var FileDocument $fileDocument */
        $fileDocument = $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository(FileDocument::class)
            ->findOneBy([
                'id' => $product['file']['fileId']
            ]);
        return $fileDocument;
    }

    /**
     * @return string
     */
    public function getRootPath()
    {
        return realpath($this->container->getParameter('kernel.root_dir').'/../..');
    }

    /**
     * @return ContentTypeRepository
     */
    public function getContentTypeRepository()
    {
        return $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository(ContentType::class);
    }

    /**
     * @return OrderRepository
     */
    public function getOrdersRepository()
    {
        return $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository(Order::class);
    }
}
