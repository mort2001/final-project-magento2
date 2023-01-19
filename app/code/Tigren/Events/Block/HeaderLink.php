<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block;

use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\Html\Link;
use Magento\Framework\View\Element\Template\Context;
use Tigren\Events\Helper\Data;

/**
 * Class HeaderLink
 *
 * @package Tigren\Events\Block
 */
class HeaderLink extends Link
{
    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * @var Data
     */
    protected $_eventsHelper;

    /**
     * HeaderLink constructor.
     *
     * @param Context $context
     * @param Manager $moduleManager
     * @param Data $eventsHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Manager $moduleManager,
        Data $eventsHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_moduleManager = $moduleManager;
        $this->_eventsHelper = $eventsHelper;
    }

    /**
     * @return             string
     * @codeCoverageIgnore
     */
    public function getHref()
    {
        return $this->getUrl('events', ['_secure' => true]);
    }

    /**
     * @return             string
     * @codeCoverageIgnore
     */
    public function getLabel()
    {
        return __('Event Calendar');
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_eventsHelper->isHeaderlinkEnabled() || !$this->_moduleManager->isOutputEnabled(
                'Tigren_Events'
            )
        ) {
            return '';
        }
        return parent::_toHtml();
    }
}
