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

namespace Pimcore\Model;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

/**
 * @method \Pimcore\Model\Property\Dao getDao()
 * @method void save()
 */
final class Property extends AbstractModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $ctype;

    /**
     * @var string|null
     */
    protected $cpath;

    /**
     * @var int
     */
    protected $cid;

    /**
     * @var bool
     */
    protected $inheritable;

    /**
     * @var bool
     */
    protected $inherited = false;

    /**
     * @internal
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        // IMPORTANT: if you use this method be sure that the type of the property is already set

        if (in_array($this->getType(), ['document', 'asset', 'object'])) {
            $el = Element\Service::getElementByPath($this->getType(), $data);
            $this->data = null;
            if ($el) {
                $this->data = $el->getId();
            }
        } elseif ($this->type == 'bool') {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        } else {
            // plain text
            $this->data = $data;
        }

        return $this;
    }

    /**
     * @internal
     *
     * @param mixed $data
     *
     * @return static
     */
    public function setDataFromResource($data)
    {
        // IMPORTANT: if you use this method be sure that the type of the property is already set
        // do not set data for object, asset and document here, this is loaded dynamically when calling $this->getData();
        if ($this->type == 'date') {
            $this->data = \Pimcore\Tool\Serialize::unserialize($data);
        } elseif ($this->type == 'bool') {
            $this->data = false;
            if (!empty($data)) {
                $this->data = true;
            }
        } else {
            // plain text
            $this->data = $data;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * enum('document','asset','object')
     *
     * @return string
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @return mixed
     */
    public function getData()
    {

        // lazy-load data of type asset, document, object
        if (in_array($this->getType(), ['document', 'asset', 'object']) && !$this->data instanceof ElementInterface && is_numeric($this->data)) {
            return Element\Service::getElementById($this->getType(), $this->data);
        }

        return $this->data;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * enum('text','document','asset','object','bool','select')
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $cid
     *
     * @return static
     */
    public function setCid($cid)
    {
        $this->cid = (int) $cid;

        return $this;
    }

    /**
     * enum('document','asset','object')
     *
     * @param string $ctype
     *
     * @return static
     */
    public function setCtype($ctype)
    {
        $this->ctype = $ctype;

        return $this;
    }

    /**
     * @param mixed $data
     *
     * @return static
     */
    public function setData($data)
    {
        if ($data instanceof ElementInterface) {
            $this->setType(Service::getElementType($data));
            $data = $data->getId();
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * enum('text','document','asset','object','bool','select')
     *
     * @param string $type
     *
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCpath()
    {
        return $this->cpath;
    }

    /**
     * @return bool
     */
    public function getInherited()
    {
        return $this->inherited;
    }

    /**
     * Alias for getInherited()
     *
     * @return bool
     */
    public function isInherited()
    {
        return $this->getInherited();
    }

    /**
     * @param string|null $cpath
     *
     * @return static
     */
    public function setCpath($cpath)
    {
        $this->cpath = $cpath;

        return $this;
    }

    /**
     * @param bool $inherited
     *
     * @return static
     */
    public function setInherited($inherited)
    {
        $this->inherited = (bool) $inherited;

        return $this;
    }

    /**
     * @return bool
     */
    public function getInheritable()
    {
        return $this->inheritable;
    }

    /**
     * @param bool $inheritable
     *
     * @return static
     */
    public function setInheritable($inheritable)
    {
        $this->inheritable = (bool) $inheritable;

        return $this;
    }

    /**
     * @internal
     *
     * @return array
     */
    public function resolveDependencies()
    {
        $dependencies = [];

        if ($this->getData() instanceof ElementInterface) {
            $elementType = Element\Service::getElementType($this->getData());
            $key = $elementType . '_' . $this->getData()->getId();
            $dependencies[$key] = [
                'id' => $this->getData()->getId(),
                'type' => $elementType,
            ];
        }

        return $dependencies;
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @internal
     *
     * @param array $idMapping
     */
    public function rewriteIds($idMapping)
    {
        if (!$this->isInherited()) {
            if (array_key_exists($this->getType(), $idMapping)) {
                if ($this->getData() instanceof ElementInterface) {
                    if (array_key_exists((int) $this->getData()->getId(), $idMapping[$this->getType()])) {
                        $this->setData(Element\Service::getElementById($this->getType(), $idMapping[$this->getType()][$this->getData()->getId()]));
                    }
                }
            }
        }
    }

    /**
     * @internal
     *
     * @return array
     */
    public function serialize()
    {
        return [
          'name' => $this->getName(),
          'type' => $this->getType(),
          'data' => $this->getData(),
        ];
    }
}
