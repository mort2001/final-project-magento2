<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\Category\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Tigren\Events\Model\Category;

/**
 * Class Status
 *
 * @package Tigren\Events\Model\Category\Source
 */
class Status implements OptionSourceInterface
{
    /**
     * @var Category
     */
    protected $_model;

    /**
     * Status constructor.
     *
     * @param Category $model
     */
    public function __construct(
        Category $model
    ) {
        $this->_model = $model;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->_model->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
