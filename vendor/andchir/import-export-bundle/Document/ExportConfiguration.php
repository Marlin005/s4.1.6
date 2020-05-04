<?php

namespace Andchir\ImportExportBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @MongoDB\Document(collection="export_configuration", repositoryClass="Andchir\ImportExportBundle\Repository\ExportConfigurationRepository")
 */
class ExportConfiguration
{
    const OWNER_NAME = 'plugin_export';

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
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $type;

    /**
     * @MongoDB\Field(type="hash")
     * @Groups({"details"})
     * @var array
     */
    protected $options;

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



}
