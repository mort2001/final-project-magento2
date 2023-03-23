<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Model\Rule;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class CmsBlockOptionsProvider
 * @package Tigren\CustomerGroupCatalog\Model\Rule
 */
class CmsBlockOptionsProvider implements OptionSourceInterface
{
    /**
     * @var array|null
     */
    protected $options;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->collectionFactory->create()->toOptionArray();
        }
        return $this->options;
    }
}
