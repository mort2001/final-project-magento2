<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Event
 *
 * @package Tigren\Events\Block\Adminhtml
 */
class Event extends Container
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_event';
        $this->_blockGroup = 'Tigren_Events';
        $this->_headerText = __('Manage Events');

        parent::_construct();

        if ($this->_isAllowedAction('Tigren_Events::save')) {
            $this->buttonList->update('add', 'label', __('Add New Event'));
        } else {
            $this->buttonList->remove('add');
        }
    }

    /**
     * @param  $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
