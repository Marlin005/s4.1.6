<?php

namespace Andchir\DigitalGoodsBundle\Command;

use App\Controller\ProductController;
use App\MainBundle\Document\Category;
use App\MainBundle\Document\Order;
use App\MainBundle\Document\OrderContent;
use App\MainBundle\Document\Setting;
use App\MainBundle\Document\User;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Service\SettingsService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImportCommand extends Command
{

    /** @var ParameterBagInterface */
    protected $params;
    /** @var DocumentManager */
    protected $dm;
    /** @var SettingsService */
    protected $settingsService;

//    public function __construct(ParameterBagInterface $params, DocumentManager $dm, SettingsService $settingsService)
//    {
//        parent::__construct();
//
//        $this->params = $params;
//        $this->dm = $dm;
//        $this->settingsService = $settingsService;
//    }

    protected function configure()
    {
        $this
            ->setName('app:digital_goods_import')
            ->setDescription('Import orders from TXT file.')
            ->setHelp('Arguments: filePath, productId')
            ->addArgument('filePath', InputArgument::REQUIRED, 'File path')
            ->addArgument('categoryId', InputArgument::REQUIRED, 'Category ID')
            ->addArgument('productId', InputArgument::REQUIRED, 'Product ID');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $time_start = microtime(true);

        $filePath = $input->getArgument('filePath');
        $categoryId = (int) $input->getArgument('categoryId');
        $productId = (int) $input->getArgument('productId');

        if (!file_exists($filePath)) {
            $io->error('File not found.');
        }

        $orderStatusSettings = $this->settingsService->getSettingsGroup(Setting::GROUP_ORDER_STATUSES);
        $paymentAfterStatusNumber = (int) $this->params->get('app.payment_status_after_number');
        /** @var Setting $paymentStatus */
        $paymentStatus = $orderStatusSettings[$paymentAfterStatusNumber - 1];

        $fileContent = file_get_contents($filePath);
        $emails = explode(PHP_EOL, $fileContent);
        $emails = array_map('trim', $emails);

        /** @var UserRepository $userRepository */
        $userRepository = $this->dm->getRepository(User::class);
        /** @var OrderRepository $orderRepository */
        $orderRepository = $this->dm->getRepository(Order::class);

        /** @var Category $category */
        $category = $this->dm->getRepository(Category::class)->find($categoryId);
        if (!$category) {
            $io->error('Category not found.');
            return;
        }

        $contentType = $category->getContentType();
        $collectionName = $contentType->getCollection();

        $productController = new ProductController();
        $productController->setContainer($this->getContainer());

        $collection = $productController->getCollection($collectionName);
        $productDocument = $collection->findOne([
            '_id' => $productId
        ]);

        if (!$productDocument) {
            $io->error('Product not found.');
            return;
        }

        $count = 0;

        foreach ($emails as $email) {
            /** @var User $user */
            $user = $userRepository->findOneBy(['email' => $email]);
            /** @var Order $order */
            $order = $orderRepository->createQueryBuilder()
                ->field('email')->equals($email)
                ->field('content.id')->equals($productId)
                ->field('status')->equals($paymentStatus->getName())
                ->getQuery()->getSingleResult();
            if ($order) {
                continue;
            }

            $order = new Order();
            $order
                ->setEmail($email)
                ->setCurrency('RUB')
                ->setCurrencyRate(1)
                ->setUserId($user ? $user->getId() : 0)
                ->setStatus($paymentStatus->getName());

            $uri = $category->getUri() . $productDocument['name'];
            $orderContent = new OrderContent();
            $orderContent
                ->setId($productDocument['_id'])
                ->setTitle($productDocument['title'])
                ->setCount(1)
                ->setPrice($productDocument['price'])
                ->setUri($uri)
                ->setContentTypeName($category->getContentTypeName());

            $order->addContent($orderContent);

            $this->dm->persist($order);
            $this->dm->flush();

            $count++;
        }

        $io->success("Imported orders: {$count}");

        $time_end = microtime(true);
        $time = round($time_end - $time_start, 3);

        $io->note("The operation has been processed in time {$time} sec.");
    }
}

