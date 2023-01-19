<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Events\Model\Event;

/**
 * Class Register
 *
 * @package Tigren\Events\Block
 */
class Register extends Template
{
    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * Register constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;

    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [Event::CACHE_TAG . '_' . 'view'];
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->_coreRegistry->registry('events_event');
    }

    /**
     * @return string
     */
    public function getPostActionUrl()
    {
        return $this->getUrl('*/*/submitregistration');
    }
}
