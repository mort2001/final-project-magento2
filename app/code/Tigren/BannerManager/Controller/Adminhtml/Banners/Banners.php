<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Controller\Adminhtml\Banners;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Widget\Controller\Adminhtml\Widget\Instance;

/**
 * Class Banners
 *
 * @package Tigren\BannerManager\Controller\Adminhtml\Banners
 */
class Banners extends Instance
{
    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws LocalizedException
     */
    public function execute()
    {

        $selected = $this->getRequest()->getParam('selected', '');

        $chooser = $this->_view->getLayout()->createBlock(
            'Tigren\BannerManager\Block\Adminhtml\Banner\Widget\Chooser'
        )->setName(
            $this->mathRandom->getUniqueHash('banners_grid_')
        )->setUseMassaction(
            true
        )
            ->setSelectedBanners(
                explode(',', $selected)
            );

        $serializer = $this->_view->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Grid\Serializer',
            '',
            [
                'data' => [
                    'grid_block' => $chooser,
                    'callback' => 'getSelectedBanners',
                    'input_element_name' => 'selected_banners',
                    'reload_param_name' => 'selected_banners',
                ]
            ]
        );
        $this->setBody($chooser->toHtml() . $serializer->toHtml());
    }
}
