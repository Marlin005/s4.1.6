<?php

namespace Andchir\ImportExportBundle\Service;

use App\Controller\CatalogController;
use App\MainBundle\Document\ContentType;
use App\MainBundle\Document\Category;
use App\MainBundle\Document\User;
use App\MainBundle\Document\FileDocument;
use App\Event\CategoryUpdatedEvent;
use Andchir\ImportExportBundle\Document\ExportConfiguration;
use Andchir\ImportExportBundle\Document\ImportConfiguration;
use App\Service\CatalogService;
use App\Service\UtilsService;
use Behat\Transliterator\Transliterator;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Column;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Events;
use Psr\Log\LoggerInterface;

class ImportExportService
{

    /** @var ContainerInterface */
    private $container;
    /** @var array */
    protected $config;
    /** @var LoggerInterface */
    private $logger;
    /** @var SessionInterface */
    private $session;
    /** @var Spreadsheet */
    private $spreadsheet;
    /** @var CatalogService */
    private $catalogService;
    /** @var IReader|CsvReader */
    private $reader;
    private $categories = [];
    /** @var \MongoDB\Collection */
    private $collection;
    private $cache = [];
    private $errorMessage = '';
    private $isError = false;

    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        SessionInterface $session,
        CatalogService $catalogService,
        array $config = []
    )
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->session = $session;
        $this->catalogService = $catalogService;
        $this->config = $config;
    }

    /**
     * @param ImportConfiguration $importConfiguration
     * @param int|null $rowNumberFirst
     * @param int|null $rowNumberLast
     * @return array
     * @internal param $filePath
     */
    public function updateSpreadsheet(ImportConfiguration $importConfiguration, $rowNumberFirst = null, $rowNumberLast = null) {
        $filePath = $this->getFilePath($importConfiguration->getFileData());
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($filePath);
        $sheetName = $importConfiguration->getOptionValue('sheetName', '');
        try {
            $this->reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            if ($importConfiguration->getType() == 'csv') {
                /** @var CsvReader $reader */
                $options = $importConfiguration->getOptions();
                if (!empty($options['csvSeparator'])) {
                    $options['csvSeparator'] = str_replace('\t', "\t", $options['csvSeparator']);
                    $this->reader->setDelimiter($options['csvSeparator']);
                }
                if (!empty($options['csvEncoding'])) {
                    $this->reader->setInputEncoding($options['csvEncoding']);
                }
                if (isset($options['csvEnclosure'])) {
                    $this->reader->setEnclosure($options['csvEnclosure']);
                }
                $this->reader->setSheetIndex(0);
            }
        } catch(\Exception $e) {
            return ['success' => false, 'message' => 'Error loading file: ' . $e->getMessage()];
        }

        if (!is_null($rowNumberFirst) && !is_null($rowNumberLast)) {
            // Split to chunk
            $chunkFilter = new ChunkReadFilter();
            $this->reader->setReadFilter($chunkFilter);
            if ($this->reader instanceof CsvReader) {
                $chunkFilter->setRows($rowNumberFirst, $rowNumberLast);
                $this->spreadsheet = new Spreadsheet();
                $this->reader->setSheetIndex(0);
                $this->reader->loadIntoExisting($filePath, $this->spreadsheet);
            } else {
                $chunkFilter->setRows($rowNumberFirst, $rowNumberLast, $sheetName);
                $this->spreadsheet = $this->reader->load($filePath);
            }
        } else {
            try {
                $this->spreadsheet = $this->reader->load($filePath);
            } catch (\Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }
        return ['success' => true];
    }

    /**
     * @return Spreadsheet
     */
    public function getSpreadsheet()
    {
        return $this->spreadsheet;
    }

    /**
     * @param string $sheetName
     * @param ImportConfiguration|null $importConfiguration
     * @return int
     */
    public function getSpreadsheetHighestRow($sheetName = '', $importConfiguration = null)
    {
        if ($importConfiguration instanceof ImportConfiguration) {
            if (!$sheetName) {
                $options = $importConfiguration->getOptions();
                $sheetName = $options['sheetName'] ?? '';
            }
            $filePath = $this->getFilePath($importConfiguration->getFileData());
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($filePath);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $worksheetData = $reader->listWorksheetInfo($filePath);
            if ($sheetName) {
                $index = array_search($sheetName, array_column($worksheetData, 'worksheetName'));
                if ($index !== false) {
                    return (int) $worksheetData[$index]['totalRows'];
                }
            }
            $total = 0;
            foreach ($worksheetData as $data) {
                $total += (int) $data['totalRows'];
            }
            return $total;
        }

        if (!$this->spreadsheet) {
            return 0;
        }
        if ($sheetName) {
            return $this->spreadsheet->getSheetByName($sheetName)->getHighestRow();
        } else {
            $total = 0;
            foreach ($this->spreadsheet->getWorksheetIterator() as $sheet) {
                $total += $sheet->getHighestRow();
            }
            return $total;
        }
    }

    /**
     * Get import steps options
     * @param ImportConfiguration $importConfiguration
     * @return bool
     */
    public function updateImportStepsOptions(ImportConfiguration &$importConfiguration)
    {
        $stepsNumber = $importConfiguration->getOptionValue('stepsNumber', 1);
        if ($stepsNumber == 1) {
            $importConfiguration->setStepsOptions(null);
            return false;
        }
        $rowNumberFirst = (int) $importConfiguration->getOptionValue('rowNumberFirst', 1);
        $rowNumberLast = (int) $importConfiguration->getOptionValue('rowNumberLast', 0);
        $currentStepsOptions = $importConfiguration->getStepsOptions();
        if (!empty($currentStepsOptions)
            && $currentStepsOptions['number'] == $stepsNumber
            && $currentStepsOptions['first'] == $rowNumberFirst
            && $currentStepsOptions['last'] == $rowNumberLast
            && !empty($currentStepsOptions['steps'])) {
                return false;
        }
        $stepsOptions = [
            'number' => $importConfiguration->getOptionValue('stepsNumber'),
            'first' => $rowNumberFirst,
            'last' => $rowNumberLast,
            'steps' => []
        ];

        if (!$rowNumberLast) {
            $rowNumberLast = $this->getSpreadsheetHighestRow('', $importConfiguration);
        }
        if ($rowNumberLast <= $rowNumberFirst) {
            return false;
        }

        $stepsOptions['steps'] = $this->getStepsArr($stepsOptions['number'], $rowNumberFirst, $rowNumberLast);

        // If there is no column with categories, then calculate the place of separation
        $fieldOptions = $importConfiguration->getFieldsOptionsArray();
        $categoryFields = array_filter($fieldOptions, function($item) {
            return $item['targetAction'] == 'category';
        });
        if (empty($categoryFields)) {
            $firstRowData = $this->importData($importConfiguration, true);
            if (!empty($firstRowData['categories'])) {
                $steps = $stepsOptions['steps'];
                $stepsOptions['steps'] = [];
                $rowFirst = $steps[0]['first'];
                $rowLast = $steps[0]['last'];
                foreach ($steps as $index => $step) {
                    if (is_null($rowLast)) {
                        $rowLast = $step['last'];
                    }
                    if ($rowLast == $rowNumberLast) {
                        $stepsOptions['steps'][] = [
                            'first' => $rowFirst,
                            'last' => $rowLast
                        ];
                        break;
                    }
                    $rowData = $this->importData($importConfiguration, true, $rowLast, true);
                    if (empty($rowData)) {
                        break;
                    }
                    $stepsOptions['steps'][] = [
                        'first' => $rowFirst,
                        'last' => $rowData['currentIndex'] - 2
                    ];
                    $rowLast = null;
                    $rowFirst = $rowData['currentIndex'] - 1;
                }
            }
        }

        $importConfiguration->setStepsOptions($stepsOptions);

        return true;
    }

    /**
     * @param $stepsNumber
     * @param $rowNumberFirst
     * @param $rowNumberLast
     * @return array
     */
    public function getStepsArr($stepsNumber, $rowNumberFirst, $rowNumberLast)
    {
        $steps = [];
        $rowsTotal = $rowNumberLast - $rowNumberFirst + 1;
        $stepSize = floor($rowsTotal / $stepsNumber);
        for ($i = 0; $i < $stepsNumber; $i++) {
            $steps[] = [
                'first' => $rowNumberFirst,
                'last' => $rowNumberFirst + $stepSize
            ];
            $rowNumberFirst += $stepSize + 1;
        }
        $steps[$stepsNumber-1]['last'] = $rowNumberLast;
        return $steps;
    }

    /**
     * @return int
     */
    public function getSheetsCount()
    {
        if (!$this->spreadsheet) {
            return 0;
        }
        return $this->spreadsheet->getSheetCount();
    }

    /**
     * @return array
     */
    public function getSheetsNames()
    {
        if (!$this->spreadsheet) {
            return [];
        }
        /** @var Worksheet[] $sheets */
        $sheets = $this->spreadsheet->getAllSheets();
        return array_map(function($sheet) {
            /** @var Worksheet $sheet */
            return $sheet->getTitle();
        }, $sheets);
    }

    /**
     * @param ImportConfiguration $importConfiguration
     * @param bool $isTest
     * @param int|null $rowNumberFirst
     * @param bool $findCategoriesRow
     * @return array
     */
    public function importData(ImportConfiguration $importConfiguration, $isTest = false, $rowNumberFirst = null, $findCategoriesRow = false)
    {
        $filePath = $this->getFilePath($importConfiguration->getFileData());
        $output = [];
        if (!$filePath || !file_exists($filePath)) {
            $output['success'] = false;
            $output['msg'] = 'File not found.';
            return $output;
        }

        $options = $importConfiguration->getOptions();
        $fieldsOptions = $importConfiguration->getFieldsOptionsArray();
        $sheetName = $options['sheetName'] ?? '';
        $rowNumberLast = (int) $importConfiguration->getOptionValue('rowNumberLast', 1);
        $stepNumber = (int) ($options['step'] ?? 1);
        $stepsNumber = (int) $importConfiguration->getOptionValue('stepsNumber', 1);
        if (is_null($rowNumberFirst)) {
            list($rowNumberFirst, $rowNumberLast) = $importConfiguration->getStepRange($stepNumber);
        }
        $categoriesOptions = array_filter($fieldsOptions, function($fieldsOpt) {
            return $fieldsOpt['targetAction'] == 'category';
        });

        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $contentTypeRepository = $dm->getRepository(ContentType::class);
        /** @var ContentType $contentType */
        $contentType = $contentTypeRepository->findOneBy([
            'name' => $options['contentType']
        ]);
        if (!$contentType) {
            $output['success'] = false;
            $output['msg'] = 'Content type not found.';
            return $output;
        }
        $options['priceFieldName'] = $contentType->getPriceFieldName();
        $options['parametersFields'] = $contentType->getByInputType('parameters');
        $options['filesFields'] = $contentType->getByInputType('file');
        $options['systemNameField'] = $contentType->getSystemNameField();
        $options['headerField'] = $contentType->getFieldByChunkName('header');

        $this->createContentTypeFields($contentType, $fieldsOptions);
        $this->updateSpreadsheet($importConfiguration, $rowNumberFirst, $rowNumberLast);
        $spreadsheet = $this->getSpreadsheet();
        /** @var Worksheet $sheet */
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if (!$sheet) {
            $output['success'] = false;
            $output['msg'] = 'Sheet not found.';
            return $output;
        }

        $rowsQuantity = $importConfiguration->getRowsQuantity();
        if (!$rowNumberLast || $rowNumberLast > $rowsQuantity) {
            $rowNumberLast = $rowsQuantity;
        }

        // Turn off output buffering
        if (!$isTest) {
            ini_set('max_execution_time', 0);
            set_time_limit(0);
            ini_set('output_buffering', 'off');
            ini_set('zlib.output_compression', false);
            ini_set('implicit_flush', true);
            ob_implicit_flush(true);
        }

        $this->collection = $this->catalogService->getCollection($contentType->getCollection());

        $data = [];
        $categories = [];
        $currentIndex = $rowNumberFirst;

        /** @var Row $row */
        foreach ($sheet->getRowIterator($rowNumberFirst, $rowNumberLast) as $row) {
            $currentIndex = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $values = [];
            /** @var Cell $cell */
            foreach ($cellIterator as $cellIndex => $cell) {
                $values[$cellIndex] = $cell->getValue()
                    ? trim($cell->getCalculatedValue())
                    : '';
            }
            unset($cellIndex);

            $valuesNonEmpty = array_filter($values);
            if (count($valuesNonEmpty) === 1) {
                $categories = $this->stringToArray(current($valuesNonEmpty), $options['categoriesSeparator']);
                continue;
            }

            $data = $this->getDocumentData($values, $fieldsOptions, $options, $categories);

            if (empty($data)) {
                continue;
            }
            $parentId = $this->getParentCategoryId($categories, $contentType, $options);
            if ($parentId === 0) {
                continue;
            }

            $document = $this->getDocument($parentId, $data, $options);

            if ($isTest) {
                if (empty($categories) && $findCategoriesRow) {
                    continue;
                }
                break;
            }

            if (!$document || empty($options['skipFound'])) {
                if (!$document) {
                    $docId = $this->createDocument($parentId, $data, $options, $contentType);
                } else {
                    $docId = $this->updateDocument($document, $data, $options, $contentType);
                }
            }
            if (!empty($categoriesOptions)) {
                $categories = [];
            }
            if (!$isTest) {
                echo json_encode([
                    'rowNumberFirst' => $rowNumberFirst,
                    'rowNumberLast' => $rowNumberLast,
                    'currentIndex' => $currentIndex
                ]);
                echo str_repeat(' ', 1024) . PHP_EOL;
                flush();
            }
        }

        if ($isTest) {
            $output['success'] = true;
            $output['categories'] = $categories;
            $output['currentIndex'] = $currentIndex;
            $output['data'] = $data;
        } else {

            $output['success'] = true;

        }

        return $output;
    }

    /**
     * @param ContentType $contentType
     * @param array $fieldsOptions
     */
    public function createContentTypeFields(ContentType &$contentType, $fieldsOptions)
    {
        $contentTypeFields = $contentType->getFields();
        $groups = $contentType->getGroups();
        $updated = false;

        foreach ($fieldsOptions as $fieldsOption) {
            $opts = [];
            if (!empty($fieldsOption['options'])) {
                $opts = array_filter($fieldsOption['options'], function($option) {
                    return $option['targetAction'] == 'new';
                });
            }
            if ($fieldsOption['targetAction'] == 'new') {
                $opts[] = $fieldsOption;
            }
            if (!empty($opts)) {
                foreach ($opts as $opt) {
                    if (empty($opt['targetName'])) {
                        continue;
                    }
                    $index = array_search($opt['targetName'], array_column($contentTypeFields, 'name'));
                    if ($index === false) {
                        $fieldTitle = !empty($fieldsOption['targetTitle'])
                            ? $fieldsOption['targetTitle']
                            : (!empty($fieldsOption['sourceTitle']) ? $fieldsOption['sourceTitle'] : '');

                        $contentTypeFields[] = [
                            'title' => $fieldTitle,
                            'name' => $opt['targetName'],
                            'description' => '',
                            'inputType' => 'text',
                            'outputType' => 'text',
                            'required' => false,
                            'group' => count($groups) > 1 ? $groups[1] : $groups[0],
                            'inputProperties' => [],
                            'outputProperties' => [],
                            'showInTable' => false,
                            'showInList' => false,
                            'isFilter' => false
                        ];
                        $updated = true;
                    }
                }
            }
        }
        if ($updated) {
            /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
            $dm = $this->container->get('doctrine_mongodb')->getManager();
            $contentType->setFields($contentTypeFields);
            $dm->flush();
        }
    }

    /**
     * @param array $values
     * @param array $fieldsOptions
     * @param array $options
     * @param array $categories
     * @return array
     */
    public function getDocumentData($values, $fieldsOptions, $options, &$categories = [])
    {
        $data = [];

        foreach ($values as $cellIndex => $value) {
            if (is_numeric($cellIndex)) {
                $dataIndex = isset($fieldsOptions[$cellIndex]) ? $cellIndex : false;
            } else {
                $dataIndex = array_search($cellIndex, array_column($fieldsOptions, 'sourceName'));
            }
            if ($dataIndex === false) {
                continue;
            }
            $opts = $fieldsOptions[$dataIndex];
            $dataAction = $opts['targetAction'];
            switch ($dataAction) {
                case ImportConfiguration::FIELD_TYPE_CATEGORY:
                    $categories = array_merge($categories, $this->stringToArray($value, $options['categoriesSeparator']));
                    break;
                case ImportConfiguration::FIELD_TYPE_NEW:
                case ImportConfiguration::FIELD_TYPE_FIELD:

                    if (!$opts['targetName']) {
                        break;
                    }

                    $fieldName = $opts['targetName'];
                    if ($fieldName == $options['priceFieldName']) {
                        $value = floatval(str_replace(',', '.', $value));
                    }

                    if (in_array($fieldName, $options['parametersFields'])) {
                        if (isset($data[$fieldName]) && !is_array($data[$fieldName])) {
                            $data[$fieldName] = [];
                        }
                        $data[$fieldName][] = [
                            'name' => !empty($opts['targetTitle'])
                                ? $opts['targetTitle']
                                : (!empty($opts['sourceTitle']) ? $opts['sourceTitle'] : ''),
                            'value' => $value
                        ];
                    } else {
                        $data[$fieldName] = $value;
                    }

                    break;
                case ImportConfiguration::FIELD_TYPE_SPLIT:

                    $valueArr = $this->stringToArray($value, $opts['separator']);
                    $tmpData = $this->getDocumentData($valueArr, $opts['options'], $options);

                    $this->mergeFieldsData($data, $tmpData);

                    break;
            }
        }

        $docTitle = $options['headerField'] && !empty($data[$options['headerField']]) ? $data[$options['headerField']] : '';
        if (!empty($options['aliasAdditionalFieldName']) && isset($data[$options['aliasAdditionalFieldName']])) {
            $docTitle .= ' ' . $data[$options['aliasAdditionalFieldName']];
        }
        if ($docTitle && $options['systemNameField'] && empty($data[$options['systemNameField']])) {
            $data[$options['systemNameField']] = Transliterator::transliterate($docTitle);
        }

        return $data;
    }

    /**
     * @param array $categories
     * @param ContentType $contentType
     * @param array $options
     * @param int $level
     * @return int
     */
    public function getParentCategoryId($categories, ContentType $contentType, $options = [], $level = 0) {
        if (empty($categories)) {
            return 0;
        }
        $parentId = isset($options['parentId']) ? $options['parentId'] : 0;
        if (empty($this->categories)) {
            $this->categories = $this->catalogService->getCategoriesTree($parentId);
        }

        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        if (empty($this->categories)) {
            $this->categories[] = [
                'id' => 0,
                'title' => 'Root',
                'children' => []
            ];
        }

        $currentParent = &$this->categories[0];
        foreach ($categories as $index => $categoryName) {
            $targetIndex = array_search($categoryName, array_column($currentParent['children'], 'title'));
            if ($targetIndex === false) {

                $alias = Transliterator::transliterate($categoryName);

                $category = new Category();
                $category
                    ->setTitle($categoryName)
                    ->setName($alias)
                    ->setDescription('')
                    ->setIsActive(true)
                    ->setParentId($currentParent['id'] ?? 0)
                    ->setMenuIndex(0)
                    ->setContentTypeName($contentType->getName())
                    ->setContentType($contentType);

                $dm->persist($category);
                $dm->flush();

                // Dispatch event
                $evenDispatcher = $this->container->get('event_dispatcher');
                $event = new CategoryUpdatedEvent($dm, $category);
                $evenDispatcher->dispatch($event, CategoryUpdatedEvent::NAME);

                $currentParent['children'][] = $category->getMenuData();
                $currentParent = &$currentParent['children'][count($currentParent['children']) - 1];
            } else {
                $currentParent = &$currentParent['children'][$targetIndex];
            }
        };

        return isset($currentParent['id']) ? $currentParent['id'] : 0;
    }

    /**
     * @param int $parentId
     * @param $data
     * @param array $options
     * @param bool $isCacheEnabled
     * @return array|null
     */
    public function getDocument($parentId, $data, $options, $isCacheEnabled = false)
    {
        $articulFieldName = $options['articulFieldName'] ?? '_id';
        if (!isset($data[$articulFieldName])) {
            return null;
        }
        if ($isCacheEnabled) {

            if (!isset($this->cache['categoryContent'])) {
                $this->cache['categoryContent'] = [
                    'id' => -1,
                    'atriculValues' => [],
                    'documents' => []
                ];
            }
            if ($this->cache['categoryContent']['id'] !== $parentId) {
                $documents = $this->collection->find([
                    'parentId' => $parentId,
                    $articulFieldName => ['$exists' => true]
                ])->toArray();
                $this->cache['categoryContent'] = [
                    'id' => -1,
                    'atriculValues' => array_map(function($document) use($articulFieldName) {
                        return $document[$articulFieldName] ?? '';
                    }, $documents),
                    'documents' => $documents
                ];
            }
            $index = array_search($data[$articulFieldName], $this->cache['categoryContent']['atriculValues']);
            return $index !== false ? $this->cache['categoryContent']['documents'][$index] : null;

        } else {
            return $this->collection->findOne([
                'parentId' => $parentId,
                $articulFieldName => $data[$articulFieldName]
            ]);
        }
    }

    /**
     * @param int $parentId
     * @param array $data
     * @param array $options
     * @param ContentType $contentType
     * @param bool $skipEvents
     * @param bool $isCacheEnabled
     * @return int
     */
    public function createDocument($parentId, $data, $options, ContentType $contentType, $skipEvents = false, $isCacheEnabled = false)
    {
        $docTitle = $options['headerField'] && !empty($data[$options['headerField']])
            ? $data[$options['headerField']]
            : '';

        $document = [
            '_id' => $this->catalogService->getNextId($this->collection->getCollectionName())
        ];
        $document['parentId'] = intval($parentId);
        $document['isActive'] = isset($data['isActive'])
            ? $data['isActive']
            : true;

        // Download files
        if ($options['filesDownload'] && !empty($options['filesFields'])) {
            $this->downloadDocumentFile($document['_id'], $document, $data, $options['filesFields'], $docTitle);
        }

        $document = array_merge($document, $data);
        try {
            $result = $this->collection->insertOne($document);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            $result = null;
        }

        // Update cached content
        if ($isCacheEnabled && $result) {
            $articulFieldName = $options['articulFieldName'] ?? '_id';
            if (!isset($this->cache['categoryContent'])) {
                $this->cache['categoryContent'] = [
                    'id' => -1,
                    'atriculValues' => [],
                    'documents' => []
                ];
            }
            $this->cache['categoryContent']['atriculValues'][] = $document[$articulFieldName] ?? '';
            $this->cache['categoryContent']['documents'][] = $document;
        }

        // Dispatch event
        if (!empty($result) && !$skipEvents) {
            $eventDispatcher = $this->container->get('event_dispatcher');
            $event = new GenericEvent($document, ['contentType' => $contentType]);
            $eventDispatcher->dispatch($event, Events::PRODUCT_CREATED);
        }

        return $result ? $document['_id'] : 0;
    }

    /**
     * @param array $document
     * @param array $data
     * @param array $options
     * @param ContentType $contentType
     * @param bool $skipEvents
     * @return int
     */
    public function updateDocument($document, $data, $options, ContentType $contentType, $skipEvents = false)
    {
        $docTitle = $options['headerField'] && !empty($data[$options['headerField']])
            ? $data[$options['headerField']]
            : ($options['headerField'] && !empty($document[$options['headerField']]) ? $document[$options['headerField']] : '');

        // Download files
        if ($options['filesDownload'] && !empty($options['filesFields'])) {
            $this->downloadDocumentFile($document['_id'], $document, $data, $options['filesFields'], $docTitle);
        }

        $document = array_merge($document, $data);
        if ($options['headerField'] && $options['systemNameField'] && empty($document[$options['systemNameField']])) {
            $document[$options['systemNameField']] = Transliterator::transliterate($document[$options['headerField']]);
        }
        try {
            $result = $this->collection->updateOne(
                ['_id' => $document['_id']],
                ['$set' => $document]
            );
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            $result = null;
        }

        return $result ? $document['_id'] : 0;
    }

    /**
     * @param $docId
     * @param array $document
     * @param array $data
     * @param array $filesFields
     * @param string $documentTitle
     * @return array
     */
    public function downloadDocumentFile($docId, $document, &$data, $filesFields, $documentTitle)
    {
        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $repository = $dm->getRepository(FileDocument::class);
        $filesDirPath = $this->container->getParameter('app.files_dir_path');
        $filesExtBlacklist = $this->container->getParameter('app.files_ext_blacklist');

        foreach ($filesFields as $fileFieldName) {
            if (empty($data[$fileFieldName])) {
                continue;
            }
            $fileUrl = trim($data[$fileFieldName]);
            if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                continue;
            }
            $fileExtension = UtilsService::getExtension($fileUrl);
            if (in_array($fileExtension, $filesExtBlacklist)) {
                continue;
            }
            $fileDocument = null;
            if (!empty($document[$fileFieldName])
                && is_array($document[$fileFieldName])
                && isset($document[$fileFieldName]['fileId'])) {
                    $fileDocument = $repository->find($document[$fileFieldName]['fileId']);
            }

            if (!$fileDocument) {
                $fileDocument = new FileDocument();
                $fileDocument
                    ->setCreatedDate(new \DateTime())
                    ->setOwnerType('products');
            }
            $fileDocument
                ->setUploadRootDir($filesDirPath)
                ->setTitle($documentTitle)
                ->setOwnerDocId($document['_id']);

            if ($fileDocument->getFileName()) {
                $filePath = $fileDocument->getUploadedPath();
                if ($filePath && file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $fileDocument
                ->setUniqueFileName()
                ->setExtension($fileExtension);

            $filePath = $fileDocument->getUploadedPath();
            if (file_put_contents($filePath, file_get_contents($fileUrl)) !== false) {
                if (!$fileDocument->getId()) {
                    $dm->persist($fileDocument);
                }
                $dm->flush();
                $data[$fileFieldName] = $fileDocument->getRecordData();
            }
        }

        return [];
    }

    /**
     * Export data
     * @param ExportConfiguration $exportConfiguration
     * @param User $user
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportData(ExportConfiguration $exportConfiguration, User $user)
    {
        $output = [];
        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $options = $exportConfiguration->getOptions();
        $fieldsOptions = $exportConfiguration->getFieldsOptionsArray();
        $fileType = $exportConfiguration->getType();
        $filesDirPath = realpath($this->container->getParameter('app.files_dir_path'));

        $fileData = $exportConfiguration->getFileData();
        $fileDocument = null;
        if (!empty($fileData)) {
            $fileDocument = $dm->getRepository(FileDocument::class)->findOneBy([
                'id' => $fileData['fileId'],
                'ownerType' => ExportConfiguration::OWNER_NAME
            ]);
        }
        if (!$fileDocument) {
            $fileDocument = new FileDocument();
            $fileDocument
                ->setUploadRootDir($filesDirPath)
                ->setCreatedDate(new \DateTime())
                ->setTitle($exportConfiguration->getTitle())
                ->setOwnerType(ExportConfiguration::OWNER_NAME)
                ->setOwnerId($exportConfiguration->getId())
                ->setUserId($user->getId())
                ->setExtension($fileType)
                ->setUniqueFileName();

            $dm->persist($fileDocument);
        }
        $fileDocument->setUploadRootDir($filesDirPath);
        $filePath = $fileDocument->getUploadedPath();

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        ;
        $contentTypeRepository = $dm->getRepository(ContentType::class);
        $categoriesRepository = $dm->getRepository(Category::class);
        /** @var ContentType $contentType */
        $contentType = $contentTypeRepository->findOneBy([
            'name' => $options['contentType']
        ]);
        if (!$contentType) {
            $output['success'] = false;
            $output['msg'] = 'Content type not found.';
            return $output;
        }

        $this->collection = $this->catalogService->getCollection($contentType->getCollection());

        $parentId = isset($options['parentId']) ? $options['parentId'] : 0;
        if (empty($this->categories)) {
            $this->categories = $this->catalogService->getCategoriesTree($parentId);
        }

        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = new Spreadsheet();
        /** @var Worksheet $sheet */
        $sheet = $spreadsheet->getActiveSheet();

        try {
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, ucfirst($fileType));
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            $output['success'] = false;
            $output['msg'] = $e->getMessage();
            return $output;
        }
        if ($fileType == 'csv') {
            /** @var \PhpOffice\PhpSpreadsheet\Writer\Csv $writer */
            if (!empty($options['csvSeparator'])) {
                $options['csvSeparator'] = str_replace('\t', "\t", $options['csvSeparator']);
                $writer->setDelimiter($options['csvSeparator']);
            }
            if (!empty($options['csvEncoding'])) {
                $writer->setInputEncoding($options['csvEncoding']);
            }
            if (isset($options['csvEnclosure'])) {
                $writer->setEnclosure($options['csvEnclosure']);
            }
        }

        $categoriesArr = $this->getCategoriesFromTree($this->categories);
        $categoriesIdsArr = array_keys($categoriesArr);
        $documentsTotal = $this->getDocumentsCount($categoriesIdsArr);

        $rowIndex = 1;
        foreach ($fieldsOptions as $index => $fieldsOption) {
            $value = $fieldsOption['targetTitle'] ?? '';
            $sheet->setCellValueExplicitByColumnAndRow($index + 1, $rowIndex, $value, DataType::TYPE_STRING);
        }
        unset($index, $fieldsOption, $value);

        $rowIndex++;
        foreach ($categoriesIdsArr as $index => $categoryId) {
            /** @var Category $category */
            $category = $categoriesRepository->find($categoryId);
            if (!$category) {
                continue;
            }

            /** @var ContentType $contentType */
            $contentType = $category->getContentType();
            $collection = $this->catalogService->getCollection($contentType->getCollection());
            if (!$collection) {
                continue;
            }
            $documents = $collection
                ->find([
                    'parentId' => $category->getId(),
                    'isActive' => true
                ], [
                    'sort' => ['_id' => 1]
                ])->toArray();

            if (count($documents) === 0) {
                continue;
            }

            $categories = $categoriesArr[$categoryId];

            // Write categories row
            if ($options['categoryType'] === 'row') {
                $categoriesSeparator = !empty($options['categoriesSeparator']) ? $options['categoriesSeparator'] : ' / ';
                $categoriesStr = implode($categoriesSeparator, $categories);
                $sheet->setCellValueExplicitByColumnAndRow(1, $rowIndex, $categoriesStr, DataType::TYPE_STRING);
                $rowIndex++;
            }

            foreach ($documents as $document) {
                $colIndex = 1;
                $data = $this->getExportData($document, $options, $fieldsOptions, $categories);
                if (empty($data)) {
                    continue;
                }
                foreach ($data as $value) {
                    $sheet->setCellValueExplicitByColumnAndRow($colIndex, $rowIndex, $value, DataType::TYPE_STRING);
                    $colIndex++;
                }
                $rowIndex++;
                echo json_encode([
                    'rowNumberFirst' => 1,
                    'rowNumberLast' => $documentsTotal,
                    'currentIndex' => $rowIndex
                ]);
                echo str_repeat(' ', 1024) . PHP_EOL;
                flush();
            }
        }

        $writer->save($filePath);

        $fileSize = filesize($filePath);
        $exportConfiguration
            ->setFileData($fileDocument->getRecordData())
            ->setFileSize(round(($fileSize / 1024 / 1024), 2));
        $dm->flush();

        return $output;
    }

    /**
     * @param array $document
     * @param array $options
     * @param array $fieldsOptions
     * @param array $categories
     * @return array
     */
    public function getExportData($document, $options, $fieldsOptions, $categories)
    {
        $data = [];
        foreach ($fieldsOptions as $fieldsOption) {
            switch ($fieldsOption['targetAction']) {
                case 'field':

                    $value = $document[$fieldsOption['targetName']] ?? '';
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $data[] = $value;

                    break;
                case 'category':

                    $data[] = !empty($categories) ? array_shift($categories) : '';

                    break;
                case 'categories_splitted':

                    $categoriesSeparator = !empty($options['categoriesSeparator']) ? $options['categoriesSeparator'] : ' / ';
                    $data[] = implode($categoriesSeparator, $categories);

                    break;
            }
        }
        return $data;
    }

    /**
     * @param $categoriesArr
     * @return int
     */
    public function getDocumentsCount($categoriesArr)
    {
        if (!$this->collection) {
            return 0;
        }
        return $this->collection
            ->countDocuments(['parentId' => ['$in' => $categoriesArr]]);
    }

    /**
     * Get categories IDs from tree
     * @param array $categoriesTree
     * @param array $categoriesArr
     * @return array
     */
    public function getCategoriesFromTree($categoriesTree, $categoriesArr = [], $parentId = 0)
    {
        if (empty($categoriesTree)) {
            return $categoriesArr;
        }
        foreach ($categoriesTree as $category) {
            if (isset($category['id'])) {
                $categoriesTitles = isset($categoriesArr[$parentId]) ? $categoriesArr[$parentId] : [];
                $categoriesTitles[] = $category['title'];
                $categoriesArr[$category['id']] = $categoriesTitles;
            }
            if (!empty($category['children'])) {
                $categoriesArr = $this->getCategoriesFromTree($category['children'], $categoriesArr, $category['id']);
            }
        }
        return $categoriesArr;
    }

    /**
     * @param array $array1
     * @param array $array2
     */
    public function mergeFieldsData(&$array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key])) {
                $array1[$key] = array_merge($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
    }

    /**
     * @param $value
     * @param $separator
     * @return array
     */
    public function stringToArray($value, $separator)
    {
        if (!$separator) {
            $value = array_filter([trim($value)]);
            return $value;
        }
        $value = explode($separator, $value);
        $value = array_map('trim', $value);
        $value = array_filter($value);
        return array_merge($value);
    }

    /**
     * @param array $fileData|null
     * @return string
     */
    public function getFilePath($fileData)
    {
        if (empty($fileData)) {
            return '';
        }
        $filesDirPath = realpath($this->container->getParameter('app.files_dir_path'));
        $filePath = $filesDirPath . DIRECTORY_SEPARATOR . $fileData['dirPath'];
        $filePath .= DIRECTORY_SEPARATOR . $fileData['fileName'] . '.' . $fileData['extension'];

        return $filePath;
    }

    /**
     * @param string $key
     * @param string|array $value
     * @param Session|null $session
     */
    public function logToSession($key, $value, Session $session = null)
    {
        if (!$session) {
            /** @var Session $session */
            $session = $this->container->get('session');
        }
        $session->set($key, $value);
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
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->isError = true;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
