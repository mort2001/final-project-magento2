<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Controller\Adminhtml\Block;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Widget\Controller\Adminhtml\Widget\Instance;

/**
 * Class Blocks
 *
 * @package Tigren\BannerManager\Controller\Adminhtml\Block
 */
class Blocks extends Instance
{
    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws LocalizedException
     */
    public function execute()
    {

        $selected = $this->getRequest()->getParam('selected', '');

        $chooser = $this->_view->getLayout()->createBlock(
            'Tigren\BannerManager\Block\Adminhtml\Block\Widget\Chooser'
        )->setName(
            $this->mathRandom->getUniqueHash('blocks_grid_')
        )->setUseMassaction(
            true
        )
            ->setSelectedBlocks(
                explode(',', $selected)
            );

        $serializer = $this->_view->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Grid\Serializer',
            '',
            [
                'data' => [
                    'grid_block' => $chooser,
                    'callback' => 'getSelectedBlocks',
                    'input_element_name' => 'selected_blocks',
                    'reload_param_name' => 'selected_blocks',
                ]
            ]
        );
        $this->setBody($chooser->toHtml() . $serializer->toHtml());
    }
}
