<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Banner
 *
 * @package Tigren\BannerManager\Block\Adminhtml
 */
class Banner extends Container
{
    /**
     *
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_banner';
        $this->_blockGroup = 'Tigren_BannerManager';
        $this->_headerText = __('Manage Banners');

        parent::_construct();

        if ($this->_isAllowedAction('Tigren_BannerManager::save')) {
            $this->buttonList->update('add', 'label', __('Add New Banner'));
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
