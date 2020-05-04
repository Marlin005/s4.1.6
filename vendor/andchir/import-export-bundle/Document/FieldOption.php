<?php

namespace Andchir\ImportExportBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @MongoDB\EmbeddedDocument()
 */
class FieldOption
{
    /**
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $sourceName;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $sourceTitle;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $targetAction = '';

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $targetName;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $separator;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"details", "list"})
     * @var string
     */
    protected $targetTitle;

    /**
     * @MongoDB\Field(type="collection")
     * @Groups({"details"})
     * @var array
     */
    protected $options = [];

    public function __construct($fieldOptionArr = null)
    {
        if (!empty($fieldOptionArr) && is_array($fieldOptionArr)) {
            $this->sourceName = $fieldOptionArr['sourceName'] ?? null;
            $this->sourceTitle = $fieldOptionArr['sourceTitle'] ?? null;
            $this->targetAction = $fieldOptionArr['targetAction'] ?? null;
            $this->targetName = $fieldOptionArr['targetName'] ?? null;
            $this->targetTitle = $fieldOptionArr['targetTitle'] ?? null;
            $this->separator = $fieldOptionArr['separator'] ?? null;
            $this->options = $fieldOptionArr['options'] ?? [];
        }
    }

    /**
     * Set sourceName
     *
     * @param string $sourceName
     * @return $this
     */
    public function setSourceName($sourceName)
    {
        $this->sourceName = $sourceName;
        return $this;
    }

    /**
     * Get sourceName
     *
     * @return string
     */
    public function getSourceName()
    {
        return $this->sourceName;
    }

    /**
     * Set sourceTitle
     *
     * @param string $sourceTitle
     * @return $this
     */
    public function setSourceTitle($sourceTitle)
    {
        $this->sourceTitle = $sourceTitle;
        return $this;
    }

    /**
     * Get sourceTitle
     *
     * @return string
     */
    public function getSourceTitle()
    {
        return $this->sourceTitle;
    }

    /**
     * Set targetAction
     *
     * @param string $targetAction
     * @return $this
     */
    public function setTargetAction($targetAction)
    {
        $this->targetAction = $targetAction;
        return $this;
    }

    /**
     * Get targetAction
     *
     * @return string
     */
    public function getTargetAction()
    {
        return $this->targetAction;
    }

    /**
     * Set targetName
     *
     * @param string $targetName
     * @return $this
     */
    public function setTargetName($targetName)
    {
        $this->targetName = $targetName;
        return $this;
    }

    /**
     * Get targetName
     *
     * @return string
     */
    public function getTargetName()
    {
        return $this->targetName;
    }

    /**
     * Set targetTitle
     *
     * @param string $targetTitle
     * @return $this
     */
    public function setTargetTitle($targetTitle)
    {
        $this->targetTitle = $targetTitle;
        return $this;
    }

    /**
     * Get targetTitle
     *
     * @return string
     */
    public function getTargetTitle()
    {
        return $this->targetTitle;
    }

    /**
     * Set separator
     *
     * @param string $separator
     * @return $this
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Get separator
     *
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
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
        return $this->options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'sourceName' => $this->getSourceName(),
            'sourceTitle' => $this->getSourceTitle(),
            'targetAction' => $this->getTargetAction(),
            'targetName' => $this->getTargetName(),
            'targetTitle' => $this->getTargetTitle(),
            'separator' => $this->getSeparator(),
            'options' => $this->getOptions()
        ];
    }

}
