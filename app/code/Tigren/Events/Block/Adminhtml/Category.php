<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Category
 *
 * @package Tigren\Events\Block\Adminhtml
 */
class Category extends Container
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_category';
        $this->_blockGroup = 'Tigren_Events';
        $this->_headerText = __('Manage Events Categories');

        parent::_construct();

        if ($this->_isAllowedAction('Tigren_Events::save')) {
            $this->buttonList->update('add', 'label', __('Add New Category'));
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
