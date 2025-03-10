<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Search\Backend;

use ForceUTF8\Encoding;
use Pimcore\Event\Model\SearchBackendEvent;
use Pimcore\Event\SearchBackendEvents;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Search\Backend\Data\Dao;

/**
 * @internal
 *
 * @method Dao getDao()
 */
class Data extends \Pimcore\Model\AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    // if a word occures more often than this number it will get stripped to keep the search_backend_data table from getting too big
    const MAX_WORD_OCCURENCES = 3;

    /**
     * @var Data\Id|null
     */
    protected ?Data\Id $id = null;

    protected ?string $key = null;

    protected ?int $index = null;

    /**
     * @var string
     */
    protected $fullPath;

    /**
     * document | object | asset
     *
     * @var string
     */
    protected $maintype;

    /**
     * webresource type (e.g. page, snippet ...)
     *
     * @var string
     */
    protected $type;

    /**
     * currently only relevant for objects where it portrays the class name
     *
     * @var string
     */
    protected $subtype;

    /**
     * published or not
     *
     * @var bool
     */
    protected $published;

    /**
     * timestamp of creation date
     *
     * @var int|null
     */
    protected $creationDate;

    /**
     * timestamp of modification date
     *
     * @var int|null
     */
    protected $modificationDate;

    /**
     * User-ID of the owner
     *
     * @var int
     */
    protected $userOwner;

    /**
     * User-ID of the user last modified the element
     *
     * @var int|null
     */
    protected $userModification;

    /**
     * @var string|null
     */
    protected $data;

    /**
     * @var string
     */
    protected $properties;

    /**
     * @param Element\ElementInterface $element
     */
    public function __construct($element = null)
    {
        if ($element instanceof Element\ElementInterface) {
            $this->setDataFromElement($element);
        }
    }

    /**
     * @return Data\Id|null
     */
    public function getId(): ?Data\Id
    {
        return $this->id;
    }

    /**
     * @param Data\Id|null $id
     *
     * @return $this
     */
    public function setId(?Data\Id $id)
    {
        $this->id = $id;

        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function setIndex(?int $index): static
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullPath()
    {
        return $this->fullPath;
    }

    /**
     * @param  string $fullpath
     *
     * @return $this
     */
    public function setFullPath($fullpath)
    {
        $this->fullPath = $fullpath;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * @param string $subtype
     *
     * @return $this
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * @param int $userModification
     *
     * @return $this
     */
    public function setUserModification($userModification)
    {
        $this->userModification = $userModification;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * @param int $userOwner
     *
     * @return $this
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return (bool) $this->getPublished();
    }

    /**
     * @return bool
     */
    public function getPublished()
    {
        return (bool) $this->published;
    }

    /**
     * @param bool $published
     *
     * @return $this
     */
    public function setPublished($published)
    {
        $this->published = (bool) $published;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param  string $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param  string $properties
     *
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param Element\ElementInterface $element
     *
     * @return $this
     */
    public function setDataFromElement(Element\ElementInterface $element)
    {
        $this->data = null;

        $this->id = new Data\Id($element);
        $this->key = $element->getKey();
        $this->fullPath = $element->getRealFullPath();
        $this->creationDate = $element->getCreationDate();
        $this->modificationDate = $element->getModificationDate();
        $this->userModification = $element->getUserModification();
        $this->userOwner = $element->getUserOwner();

        $this->type = $element->getType();
        if ($element instanceof DataObject\Concrete) {
            $this->subtype = $element->getClassName();
            $this->index = $element->getIndex();
        } else {
            $this->subtype = $this->type;
            if ($element instanceof Document) {
                $this->index = $element->getIndex();
            }
        }

        $this->properties = '';
        $properties = $element->getProperties();
        if (is_array($properties)) {
            foreach ($properties as $nextProperty) {
                $pData = (string) $nextProperty->getData();
                if ($nextProperty->getName() === 'bool') {
                    $pData = $pData ? 'true' : 'false';
                }

                $this->properties .= $nextProperty->getName() . ':' . $pData .' ';
            }
        }

        $this->data = $element->getKey();

        if ($element instanceof Document) {
            if ($element instanceof Document\Folder) {
                $this->published = true;
            } elseif ($element instanceof Document\Link) {
                $this->published = $element->isPublished();
                $this->data = ' ' . $element->getHref();
            } elseif ($element instanceof Document\PageSnippet) {
                $this->published = $element->isPublished();
                $editables = $element->getEditables();
                if (is_array($editables) && !empty($editables)) {
                    foreach ($editables as $editable) {
                        if ($editable instanceof Document\Editable\EditableInterface) {
                            // areabrick elements are handled by getElementTypes()/getElements() as they return area elements as well
                            if ($editable instanceof Document\Editable\Area || $editable instanceof Document\Editable\Areablock) {
                                continue;
                            }

                            ob_start();
                            $this->data .= strip_tags($editable->frontend()).' ';
                            $this->data .= ob_get_clean();
                        }
                    }
                }
                if ($element instanceof Document\Page) {
                    $this->published = $element->isPublished();
                    $this->data .= ' '.$element->getTitle().' '.$element->getDescription().' ' . $element->getPrettyUrl();
                }
            }
        } elseif ($element instanceof Asset) {
            $elementMetadata = $element->getMetadata();
            if (is_array($elementMetadata)) {
                foreach ($elementMetadata as $md) {
                    try {
                        $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
                        /** @var \Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
                        $instance = $loader->build($md['type']);
                        $dataForSearchIndex = $instance->getDataForSearchIndex($md['data'], $md);
                        if ($dataForSearchIndex) {
                            $this->data .= ' ' . $dataForSearchIndex;
                        }
                    } catch (UnsupportedException $e) {
                        Logger::error('asset metadata type ' . $md['type'] . ' could not be resolved');
                    }
                }
            }

            if ($element instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
                if (\Pimcore\Document::isFileTypeSupported($element->getFilename())) {
                    try {
                        $contentText = $element->getText();
                        if ($contentText) {
                            $contentText = Encoding::toUTF8($contentText);
                            $contentText = str_replace(["\r\n", "\r", "\n", "\t", "\f"], ' ', $contentText);
                            $contentText = preg_replace('/[ ]+/', ' ', $contentText);
                            $this->data .= ' ' . $contentText;
                        }
                    } catch (\Exception $e) {
                        Logger::error((string) $e);
                    }
                }
            } elseif ($element instanceof Asset\Text) {
                try {
                    if ($element->getFileSize() < 2000000) {
                        // it doesn't make sense to add text files bigger than 2MB to the full text index (performance)
                        $contentText = $element->getData();
                        $contentText = Encoding::toUTF8($contentText);
                        $this->data .= ' ' . $contentText;
                    }
                } catch (\Exception $e) {
                    Logger::error((string) $e);
                }
            } elseif ($element instanceof Asset\Image) {
                try {
                    $metaData = array_merge($element->getEXIFData(), $element->getIPTCData());
                    foreach ($metaData as $key => $value) {
                        if (is_array($value)) {
                            $this->data .= ' ' . $key . ' : ' . implode(' - ', $value);
                        } else {
                            $this->data .= ' ' . $key . ' : ' . $value;
                        }
                    }
                } catch (\Exception $e) {
                    Logger::error((string) $e);
                }
            }

            $this->published = true;
        } elseif ($element instanceof DataObject\AbstractObject) {
            if ($element instanceof DataObject\Concrete) {
                $getInheritedValues = DataObject::doGetInheritedValues();
                DataObject::setGetInheritedValues(true);

                $this->published = $element->isPublished();
                foreach ($element->getClass()->getFieldDefinitions() as $key => $value) {
                    $this->data .= ' ' . $value->getDataForSearchIndex($element);
                }

                DataObject::setGetInheritedValues($getInheritedValues);
            } elseif ($element instanceof DataObject\Folder) {
                $this->published = true;
            }
        } else {
            Logger::crit('Search\\Backend\\Data received an unknown element!');
        }

        // replace all occurrences of @ to # because when using InnoDB @ is reserved for the @distance operator
        $this->data = str_replace('@', '#', $this->data);

        $pathWords = str_replace([ '-', '_', '/', '.', '(', ')'], ' ', $this->getFullPath());
        $this->data .= ' ' . $pathWords;
        $this->data = 'ID: ' . $element->getId() . "  \nPath: " . $this->getFullPath() . "  \n"  . $this->cleanupData($this->data);

        return $this;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    protected function cleanupData($data)
    {
        $data = preg_replace('/(<\?.*?(\?>|$)|<[^<]+>)/s', '', $data);

        $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');

        // we don't remove ".", otherwise it would be impossible to search for email addresses
        $data = str_replace([',', ':', ';', "'", '"'], ' ', $data);
        $data = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $data);
        $data = preg_replace('#[ ]+#', ' ', $data);

        $minWordLength = $this->getDao()->getMinWordLengthForFulltextIndex();
        $maxWordLength = $this->getDao()->getMaxWordLengthForFulltextIndex();

        $words = explode(' ', $data);

        $wordOccurrences = [];
        foreach ($words as $key => $word) {
            $wordLength = \mb_strlen($word);
            if ($wordLength < $minWordLength || $wordLength > $maxWordLength) {
                unset($words[$key]);

                continue;
            }

            $wordOccurrences[$word] = ($wordOccurrences[$word] ?? 0) + 1;
            if ($wordOccurrences[$word] > self::MAX_WORD_OCCURENCES) {
                unset($words[$key]);
            }
        }

        $data = implode(' ', $words);

        return $data;
    }

    /**
     * @param Element\ElementInterface $element
     *
     * @return self
     */
    public static function getForElement(Element\ElementInterface $element): self
    {
        $data = new self();
        $data->getDao()->getForElement($element);

        return $data;
    }

    public function delete()
    {
        $this->getDao()->delete();
    }

    /**
     * @throws \Exception
     */
    public function save()
    {
        if ($this->id instanceof Data\Id) {
            $this->dispatchEvent(new SearchBackendEvent($this), SearchBackendEvents::PRE_SAVE);

            $maxRetries = 5;
            for ($retries = 0; $retries < $maxRetries; $retries++) {
                try {
                    $this->getDao()->save();
                    // successfully completed, so we cancel the loop here -> no restart required
                    break;
                } catch (\Exception $e) {
                    // we try to start saving $maxRetries times again (deadlocks, ...)
                    if ($retries < ($maxRetries - 1)) {
                        $run = $retries + 1;
                        $waitTime = rand(1, 5) * 100000;
                        Logger::warn('Unable to finish transaction (' . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . ' microseconds ... (' . ($run + 1) . ' of ' . $maxRetries . ')');

                        // wait specified time until we restart
                        usleep($waitTime);
                    } else {
                        // if we fail after $maxRetries retries, we throw out the exception
                        throw $e;
                    }
                }
            }

            $this->dispatchEvent(new SearchBackendEvent($this), SearchBackendEvents::POST_SAVE);
        } else {
            throw new \Exception('Search\\Backend\\Data cannot be saved - no id set!');
        }
    }
}
