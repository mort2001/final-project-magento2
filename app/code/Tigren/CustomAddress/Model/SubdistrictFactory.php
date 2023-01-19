<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class SubdistrictFactory
 * @package Tigren\CustomAddress\Model
 */
class SubdistrictFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create new subdistrict model
     *
     * @param array $arguments
     * @return Subdistrict
     */
    public function create(array $arguments = [])
    {
        return $this->_objectManager->create(Subdistrict::class, $arguments);
    }
}
