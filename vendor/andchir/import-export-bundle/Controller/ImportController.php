<?php

namespace Andchir\ImportExportBundle\Controller;

use App\MainBundle\Document\FileDocument;
use App\MainBundle\Document\User;
use App\Controller\Admin\StorageControllerAbstract;
use Andchir\ImportExportBundle\Document\FieldOption;
use Andchir\ImportExportBundle\Service\ImportExportService;
use Andchir\ImportExportBundle\Document\ImportConfiguration;
use Andchir\ImportExportBundle\Repository\ImportConfigurationRepository;
use App\Service\UtilsService;
use Doctrine\ODM\MongoDB\DocumentManager;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Worksheet\Column;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class ImportController
 * @package App\Plugin\ImportExport\Controller
 * @Route("/admin/import")
 */
class ImportController extends StorageControllerAbstract
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
        if (empty($data['fileData']) || empty($data['fileData']['extension'])) {
            return ['success' => false, 'msg' => 'File is required.'];
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
        /** @var ImportExportService $importExportService */
        $importExportService = $this->get('plugin_import_export');

        $options = !empty($data['options']) && is_array($data['options']) ? $data['options'] : [];
        $fieldsOptions = !empty($data['fieldsOptions']) && is_array($data['fieldsOptions']) ? $data['fieldsOptions'] : [];

        if($itemId){
            /** @var ImportConfiguration $item */
            $item = $this->getRepository()->find($itemId);
            if(!$item){
                return $this->setError('Item not found.');
            }
        } else {
            $item = new ImportConfiguration();
        }

        $item
            ->setTitle($data['title'])
            ->setFieldOptionsFromArray($fieldsOptions);

        // Update last row number
        if (empty($options['rowNumberLast'])
            && !empty($data['fileData'])
            && !empty($data['fileData']['fileId'])) {
                $options['rowNumberLast'] = $importExportService->getSpreadsheetHighestRow('', $item);
        }

        $item->setOptions($options);
        try {
            $importExportService->updateImportStepsOptions($item);
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }

        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($item);
        $dm->flush();

        return $this->json($item, 200, [], ['groups' => ['details']]);
    }

    /**
     * @Route("/upload", name="admin_import_export_upload", methods={"POST"})
     * @IsGranted("ROLE_ADMIN_WRITE", statusCode="400", message="Your user has read-only permission.")
     * @param Request $request
     * @param DocumentManager $dm
     * @param ImportExportService $importExportService
     * @return JsonResponse
     */
    public function uploadAction(Request $request, DocumentManager $dm, ImportExportService $importExportService)
    {
        /** @var User $user */
        $user = $this->getUser();
        $repository = $this->getRepository();

        $itemId = (int) $request->get('itemId', 0);
        /** @var ImportConfiguration $item */
        $item = $repository->find($itemId);
        if (!$item) {
            return $this->setError('Import Configuration not found.');
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('fileData');
        if (empty($file)) {
            return $this->setError('File is required.');
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ImportConfiguration::$allowedFileExt)) {
            return $this->setError('File type is not allowed.');
        }

        // Upload directory
        $filesDirPath = $this->getParameter('app.files_dir_path');
        if (!is_dir($filesDirPath)) {
            mkdir($filesDirPath);
        }

        // Delete old file record
        $oldFileDocument = $dm->getRepository(FileDocument::class)->findOneBy([
            'ownerType' => ImportConfiguration::OWNER_NAME,
            'ownerId' => (string) $itemId
        ]);
        if ($oldFileDocument) {
            $dm->remove($oldFileDocument);
            $dm->flush();
        }

        // Create new file record
        $fileDocument = new FileDocument();
        $fileDocument
            ->setUploadRootDir($filesDirPath)
            ->setCreatedDate(new \DateTime())
            ->setOwnerType(ImportConfiguration::OWNER_NAME)
            ->setOwnerId($itemId)
            ->setUserId($user->getId())
            ->setFile($file);

        $dm->persist($fileDocument);
        $dm->flush();

        $filePath = $fileDocument->getUploadedPath();
        $fileSize = filesize($filePath);

        // Update import configuration data
        $item
            ->setFileData($fileDocument->getRecordData())
            ->setFileSize(round(($fileSize / 1024 / 1024), 2))
            ->setType($fileDocument->getExtension());

        $highestRow = $importExportService->getSpreadsheetHighestRow('', $item);
        $importExportService->updateSpreadsheet($item, 1, 2);

        $item
            ->setRowsQuantity($highestRow)
            ->setSheetsQuantity($importExportService->getSheetsCount())
            ->setSheetsNames($importExportService->getSheetsNames())
            ->setOptionValue('rowNumberLast', $highestRow)
            ->setOptionValue('stepsNumber', 1)
            ->setOptionValue('step', 1)
            ->setStepsOptions([]);
        $dm->flush();

        return $this->json($item, 200, [], ['groups' => ['details']]);
    }

    /**
     * @Route("/{id}/properties", name="admin_import_export_sheet_properties", methods={"POST"})
     * @IsGranted("ROLE_ADMIN_WRITE", statusCode="400", message="Your user has read-only permission.")
     * @param Request $request
     * @param ImportConfiguration $importConfiguration
     * @param ImportExportService $importExportService
     * @return JsonResponse
     */
    public function getSheetPropertiesAction(Request $request, ImportConfiguration $importConfiguration, ImportExportService $importExportService)
    {
        $output = [
            'fieldsOptions' => []
        ];
        $requestContent = json_decode($request->getContent(), true);
        $options = $requestContent['options'] ?? [];
        $fieldsOptions = $requestContent['fieldsOptions'] ?? [];

        $sheetName = $options['sheetName'] ?? '';
        $rowNumber = (int) ($options['rowNumberHeaders'] ?? 1);
        $rowNumber = max(1, $rowNumber);

        $importConfiguration->setOptions($options);
        $filePath = $importExportService->getFilePath($importConfiguration->getFileData());
        if (!$filePath || !file_exists($filePath)) {
            return $this->setError('File not found.');
        }

        list($rowNumberFirst, $rowNumberLast) = $importConfiguration->getStepRange();

        $result = $importExportService->updateSpreadsheet($importConfiguration, 1, $rowNumberLast);
        if (!$result['success']) {
            return $this->setError($result['message']);
        }

        $spreadsheet = $importExportService->getSpreadsheet();
        /** @var Worksheet $sheet */
        $sheet = $spreadsheet->getSheetByName($sheetName);
        if (!$sheet) {
            return $this->setError('Sheet not found.');
        }

        /** @var Row $row */
        foreach ($sheet->getRowIterator($rowNumber, $rowNumber) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE);
            /** @var Cell $cell */
            foreach ($cellIterator as $cellIndex => $cell) {
                $cellValue = $cell->getValue()
                    ? trim($cell->getCalculatedValue())
                    : $cellIndex;

                $optIndex = array_search($cellIndex, array_column($fieldsOptions, 'sourceName'));
                $defaultOptions = $optIndex !== false ? $fieldsOptions[$optIndex] : [];
                $fieldOption = new FieldOption();
                $fieldOption
                    ->setSourceName($cellIndex)
                    ->setSourceTitle($cellValue);
                $fieldOptionArr = $fieldOption->toArray();
                if (!empty($defaultOptions)) {
                    $fieldOptionArr['targetAction'] = $defaultOptions['targetAction'];
                    $fieldOptionArr['targetName'] = $defaultOptions['targetName'];
                    $fieldOptionArr['targetTitle'] = $defaultOptions['targetTitle'];
                    $fieldOptionArr['separator'] = $defaultOptions['separator'];
                    $fieldOptionArr['options'] = $defaultOptions['options'];
                }
                $output['fieldsOptions'][] = $fieldOptionArr;
            }
        }
        $output['rowsQuantity'] = $importExportService->getSpreadsheetHighestRow('', $importConfiguration);

        return $this->json($output, 200, [], ['groups' => ['details']]);
    }

    /**
     * @Route("/{id}/do_import_test", name="admin_do_import_test", methods={"POST"})
     * @IsGranted("ROLE_ADMIN_WRITE", statusCode="400", message="Your user has read-only permission.")
     * @param Request $request
     * @param ImportConfiguration $importConfiguration
     * @param ImportExportService $importExportService
     * @param DocumentManager $dm
     * @return JsonResponse
     */
    public function testDataAction(
        Request $request,
        ImportConfiguration $importConfiguration,
        ImportExportService $importExportService,
        DocumentManager $dm
    )
    {
        $timeStart = microtime(true);
        $requestContent = json_decode($request->getContent(), true);
        $options = $requestContent['options'] ?? [];
        $fieldsOptions = $requestContent['fieldsOptions'] ?? [];

        $importConfiguration
            ->setOptions($options)
            ->setFieldOptionsFromArray($fieldsOptions);

        if ($importExportService->updateImportStepsOptions($importConfiguration)) {
            $dm->flush();
        }

        try {
            $output = $importExportService->importData($importConfiguration, true);
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }

        list($rowNumberFirst, $rowNumberLast) = $importConfiguration->getStepRange($importConfiguration->getOptionValue('step'));

        $timeEnd = microtime(true);
        $output['memory_peak_usage'] = UtilsService::sizeFormat(memory_get_peak_usage());
        $output['time_execution'] = round($timeEnd - $timeStart, 2);
        $output['row_number_first'] = $rowNumberFirst;
        $output['row_number_last'] = $rowNumberLast;

        return $this->json($output);
    }

    /**
     * @Route("/{id}/do_import", name="admin_do_import", methods={"POST"})
     * @IsGranted("ROLE_ADMIN_WRITE", statusCode="400", message="Your user has read-only permission.")
     * @param Request $request
     * @param ImportConfiguration $importConfiguration
     * @param ImportExportService $importExportService
     * @param DocumentManager $dm
     * @return JsonResponse|Response
     */
    public function importDataAction(
        Request $request,
        ImportConfiguration $importConfiguration,
        ImportExportService $importExportService,
        DocumentManager $dm
    )
    {
        header('Content-type: application/octet-stream');

        $requestContent = json_decode($request->getContent(), true);
        $options = $requestContent['options'] ?? [];
        $fieldsOptions = $requestContent['fieldsOptions'] ?? [];

        $importConfiguration
            ->setOptions($options)
            ->setFieldOptionsFromArray($fieldsOptions);

        if ($importExportService->updateImportStepsOptions($importConfiguration)) {;
            $dm->flush();
        }

        $output = $importExportService->importData($importConfiguration);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->setContent(is_array($output) ? json_encode($output) : $output);

        return $response;
    }

    /**
     * @Route("/{id}/percent", name="admin_percent", methods={"GET"})
     * @param ImportConfiguration $importConfiguration
     * @param SessionInterface $session
     * @return JsonResponse
     */
    public function getPercentAction(ImportConfiguration $importConfiguration, SessionInterface $session)
    {
        $data = $session->get($importConfiguration->getLogSessionKey());

        $percent = 0;
        if (empty($data)) {
            $percent = 100;
        } else {
            $maxValue = $data[1] - $data[0];
            $currValue = $data[2] - $data[0];
            $percent = $currValue / $maxValue * 100;
        }

        return $this->json(['percent' => $percent]);
    }

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
