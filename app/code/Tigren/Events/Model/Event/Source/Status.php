<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Model\Event\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Tigren\Events\Model\Event;

/**
 * Class Status
 *
 * @package Tigren\Events\Model\Event\Source
 */
class Status implements OptionSourceInterface
{
    /**
     * @var Event
     */
    protected $_model;

    /**
     * Status constructor.
     *
     * @param Event $model
     */
    public function __construct(
        Event $model
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
