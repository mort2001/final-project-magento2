<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxcart\Block\Product;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Image
 *
 * @package Tigren\Ajaxcart\Block\Product
 */
class Image extends Template
{
    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Image constructor.
     *
     * @param Context                $context
     * @param Registry               $registry
     * @param ObjectManagerInterface $objectManager
     * @param array                  $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ObjectManagerInterface $objectManager,
        array $data
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
    }

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        $color = $this->_request->getParam('color');
        $configurablePrdModel = $this->_objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
        $attributeOptions = [93 => $color];
        $prdId = $this->_coreRegistry->registry('current_product')->getId();
        $product = $this->_objectManager->get('Magento\Catalog\Model\Product')->load($prdId);
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $assPro = $configurablePrdModel->getProductByAttributes($attributeOptions, $product);
            if (!empty($assPro)) {
                $imageUrl = $this->_objectManager->get('Tigren\Ajaxcart\Helper\Data')->getProductImageUrl(
                    $assPro,
                    'category'
                );
            } else {
                $imageUrl = $this->_objectManager->get('Tigren\Ajaxcart\Helper\Data')->getProductImageUrl(
                    $product,
                    'category'
                );
            }
        } else {
            $imageUrl = $this->_objectManager->get('Tigren\Ajaxcart\Helper\Data')->getProductImageUrl(
                $product,
                'category'
            );
        }
        return $imageUrl;
    }
}
