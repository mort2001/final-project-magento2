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

/**
 * Class TopLink
 *
 * @package Tigren\Events\Block
 */
class TopLink extends Link
{
    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * TopLink constructor.
     *
     * @param Context $context
     * @param Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Manager $moduleManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_moduleManager = $moduleManager;
    }

    /**
     * @return             string
     * @codeCoverageIgnore
     */
    public function getHref()
    {
        return $this->getUrl('events/wishlist/index');
    }

    /**
     * @return             string
     * @codeCoverageIgnore
     */
    public function getLabel()
    {
        return __('My Events');
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_moduleManager->isOutputEnabled(
            'Tigren_Events'
        )
        ) {
            return '';
        }
        return parent::_toHtml();
    }
}
