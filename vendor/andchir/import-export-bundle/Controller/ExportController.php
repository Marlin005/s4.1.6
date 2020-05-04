<?php

namespace Andchir\ImportExportBundle\Controller;

use App\MainBundle\Document\FileDocument;
use App\MainBundle\Document\User;
use App\Controller\Admin\StorageControllerAbstract;
use Andchir\ImportExportBundle\Document\ExportConfiguration;
use Andchir\ImportExportBundle\Document\ImportConfiguration;
use Andchir\ImportExportBundle\Service\ImportExportService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class ExportController
 * @package App\Plugin\ImportExport\Controller
 * @Route("/admin/export")
 */
class ExportController extends StorageControllerAbstract
{

    /**
     * @param $data
     * @param int $itemId
     * @return array
     */
    public function validateData($data, $itemId = null)
    {
        if (empty($data)) {
            return ['success' => false, 'msg' => 'Data is empty.'];
        }
        if (empty($data['title'])) {
            return ['success' => false, 'msg' => 'Title is empty.'];
        }
        if (empty($data['type'])) {
            return ['success' => false, 'msg' => 'Export type is required.'];
        }
        if (!empty($data['fileData']) && !in_array(strtolower($data['fileData']['extension']), ImportConfiguration::$allowedFileExt)) {
            return ['success' => false, 'msg' => 'File type is not allowed.'];
        }
        return ['success' => true];
    }

    /**
     * @param $data
     * @param int $itemId
     * @return JsonResponse
     */
    public function createUpdate($data, $itemId = null)
    {
        $options = !empty($data['options']) && is_array($data['options']) ? $data['options'] : [];
        $fieldsOptions = !empty($data['fieldsOptions']) && is_array($data['fieldsOptions']) ? $data['fieldsOptions'] : [];

        if($itemId){
            /** @var ExportConfiguration $item */
            $item = $this->getRepository()->find($itemId);
            if(!$item){
                return $this->setError('Item not found.');
            }
        } else {
            $item = new ExportConfiguration();
        }

        $item
            ->setTitle($data['title'])
            ->setOptions($options)
            ->setFieldOptionsFromArray($fieldsOptions)
            ->setType($data['type'])
            ->setFileSize(0);

        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($item);
        $dm->flush();

        return $this->json($item, 200, [], ['groups' => ['details']]);
    }

    /**
     * @Route("/{id}/do_export", name="admin_do_export", methods={"POST"})
     * @IsGranted("ROLE_ADMIN_WRITE", statusCode="400", message="Your user has read-only permission.")
     * @param Request $request
     * @param ExportConfiguration $exportConfiguration
     * @return Response
     */
    public function exportDataAction(Request $request, ExportConfiguration $exportConfiguration)
    {
        header('Content-type: application/octet-stream');

        /** @var user $user */
        $user = $this->getUser();
        $requestContent = json_decode($request->getContent(), true);
        $options = $requestContent['options'] ?? [];
        $fieldsOptions = $requestContent['fieldsOptions'] ?? [];

        $exportConfiguration
            ->setOptions($options)
            ->setFieldOptionsFromArray($fieldsOptions);

        /** @var ImportExportService $importExportService */
        $importExportService = $this->get('plugin_import_export');

        $output = $importExportService->exportData($exportConfiguration, $user);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->setContent('');

        return $response;
    }

    /**
     * @return \App\ImportExportBundle\Repository\ExportConfigurationRepository
     */
    public function getRepository()
    {
        return $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository(ExportConfiguration::class);
    }
}
