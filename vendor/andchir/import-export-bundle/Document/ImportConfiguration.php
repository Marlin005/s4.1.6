<?php

namespace Andchir\ImportExportBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @MongoDB\Document(collection="import_configuration", repositoryClass="Andchir\ImportExportBundle\Repository\ImportConfigurationRepository")
 */
class ImportConfiguration
{
    const OWNER_NAME = 'plugin_import';
    const FIELD_TYPE_CATEGORY = 'category';
    const FIELD_TYPE_FIELD = 'field';
    const FIELD_TYPE_NEW = 'new';
    const FIELD_TYPE_SPLIT = 'split';
    public static $allowedFileExt = ['xls', 'xlsx', 'csv'];

    /**
     * @MongoDB\Id(type="int", strategy="INCREMENT")
     * @Groups({"details", "list"})
     * @var int
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $title;

    /**
     * @MongoDB\Field(type="hash")
     * @Groups({"details", "list"})
     * @var array
     */
    protected $fileData;

    /**
     * @MongoDB\Field(type="float")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $fileSize;

    /**
     * @MongoDB\Field(type="int")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $rowsQuantity;

    /**
     * @MongoDB\Field(type="int")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $sheetsQuantity;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $type;

    /**
     * @MongoDB\Field(type="collection")
     * @Groups({"details"})
     * @var array
     */
    protected $sheetsNames;

    /**
     * @MongoDB\Field(type="hash")
     * @Groups({"details"})
     * @var array
     */
    protected $options;

    /**
     * @MongoDB\Field(type="hash")
     * @Groups({"details"})
     * @var array
     */
    protected $stepsOptions;

    /**
     * @MongoDB\Field(type="collection")
     * @MongoDB\EmbedMany(targetDocument="Andchir\ImportExportBundle\Document\FieldOption")
     * @Groups({"details"})
     * @var array
     */
    protected $fieldsOptions;

    /**
     * Get id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set fileData
     *
     * @param array $fileData
     * @return $this
     */
    public function setFileData($fileData)
    {
        $this->fileData = $fileData;
        return $this;
    }

    /**
     * Get fileData
     *
     * @return array
     */
    public function getFileData()
    {
        return $this->fileData;
    }

    /**
     * Set fileSize
     *
     * @param int $fileSize
     * @return $this
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    /**
     * Get fileSize
     *
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * Set rowsQuantity
     *
     * @param int $rowsQuantity
     * @return $this
     */
    public function setRowsQuantity($rowsQuantity)
    {
        $this->rowsQuantity = $rowsQuantity;
        return $this;
    }

    /**
     * Get rowsQuantity
     *
     * @return int
     */
    public function getRowsQuantity()
    {
        return $this->rowsQuantity;
    }

    /**
     * Set sheetsQuantity
     *
     * @param int $sheetsQuantity
     * @return $this
     */
    public function setSheetsQuantity($sheetsQuantity)
    {
        $this->sheetsQuantity = $sheetsQuantity;
        return $this;
    }

    /**
     * Get sheetsQuantity
     *
     * @return int
     */
    public function getSheetsQuantity()
    {
        return $this->sheetsQuantity;
    }

    /**
     * Set type
     *
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set sheetsNames
     *
     * @param array $sheetsNames
     * @return $this
     */
    public function setSheetsNames($sheetsNames)
    {
        $this->sheetsNames = $sheetsNames;
        return $this;
    }

    /**
     * Get sheetsNames
     *
     * @return array
     */
    public function getSheetsNames()
    {
        return $this->sheetsNames ?? [];
    }

    /**
     * Set options
     *
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options ?? [];
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getOptionValue($key, $default = null)
    {
        $options = $this->getOptions();
        return $options[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setOptionValue($key, $value)
    {
        $options = $this->getOptions();
        $options[$key] = $value;
        $this->setOptions($options);
        return $this;
    }

    /**
     * Set stepsOptions
     *
     * @param array $stepsOptions
     * @return $this
     */
    public function setStepsOptions($stepsOptions)
    {
        $this->stepsOptions = $stepsOptions;
        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getStepsOptions()
    {
        return $this->stepsOptions ?? [];
    }

    /**
     * @param int $stepNumber
     * @return array
     */
    public function getStepRange($stepNumber = 1)
    {
        $rowNumberFirst = (int) $this->getOptionValue('rowNumberFirst', 1);
        $rowNumberLast = (int) $this->getOptionValue('rowNumberLast', 2);
        $stepsOptions = $this->getStepsOptions();
        if (!empty($stepsOptions) && !empty($stepsOptions['steps'][$stepNumber - 1])) {
            $rowNumberFirst = $stepsOptions['steps'][$stepNumber - 1]['first'];
            $rowNumberLast = $stepsOptions['steps'][$stepNumber - 1]['last'];
        }
        return [$rowNumberFirst, $rowNumberLast];
    }

    /**
     * Get fieldsOptions
     * @return array
     */
    public function getFieldsOptions()
    {
        return $this->fieldsOptions ?? [];
    }

    /**
     * Set fieldsOptions
     * @param array $fieldsOptions
     * @return $this
     */
    public function setFieldOptions($fieldsOptions)
    {
        $this->fieldsOptions = $fieldsOptions;
        return $this;
    }

    /**
     * @param array $fieldsOptionsArr
     * @return $this
     */
    public function setFieldOptionsFromArray($fieldsOptionsArr)
    {
        $fieldsOptions = [];
        foreach ($fieldsOptionsArr as $fieldsOptionArr) {
            $fieldsOptions[] = new FieldOption($fieldsOptionArr);
        }
        $this->fieldsOptions = $fieldsOptions;
        return $this;
    }

    /**
     * @return array
     */
    public function getFieldsOptionsArray()
    {
        $output = [];
        /** @var FieldOption $fieldsOption */
        foreach ($this->getFieldsOptions() as $fieldsOption) {
            $output[] = $fieldsOption->toArray();
        }
        return $output;
    }

    /**
     * @param string $filesRootDirPath
     * @return string
     */
    public function getLogFilePath($filesRootDirPath)
    {
        $tmpDitPath = $filesRootDirPath . DIRECTORY_SEPARATOR . 'tmp';
        return $tmpDitPath . DIRECTORY_SEPARATOR . 'import_log_' . $this->getId() . '.txt';
    }

    /**
     * @param string $action
     * @return string
     */
    public function getLogSessionKey($action = 'import')
    {
        return "{$action}_log_" . $this->getId();
    }
}
