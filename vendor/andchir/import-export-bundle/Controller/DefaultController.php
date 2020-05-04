<?php

namespace Andchir\ImportExportBundle\Controller;

use App\Controller\Admin\StorageControllerAbstract;
use Andchir\ImportExportBundle\Document\ImportConfiguration;
use Andchir\ImportExportBundle\Repository\ImportConfigurationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DefaultController
 * @package App\Plugin\ImportExport\Controller
 * @Route("/admin/module/import-export/main")
 */
class DefaultController extends Controller
{


    /**
     * @return \App\ImportExportBundle\Repository\ImportConfigurationRepository
     */
    public function getRepository()
    {
        return $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository(ImportConfiguration::class);
    }
}
