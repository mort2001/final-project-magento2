<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGroupCatalog\Ui\DataProvider\Rule;

use Tigren\CustomerGroupCatalog\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Class Listing
 * @package Tigren\CustomerGroupCatalog\Ui\DataProvider\Rule
 */
class Listing extends AbstractDataProvider
{
    /**
     * @param CollectionFactory $collectionFactory
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        string  $name,
        string $primaryFieldName,
        string  $requestFieldName,
        array             $meta = [],
        array             $data = []
    )
    {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter)
    {
        if ($filter->getField() == 'rule_id') {
            $filter->setField('main_table.' . $filter->getField());
        }

        parent::addFilter($filter);
    }
}