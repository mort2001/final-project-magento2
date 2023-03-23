<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Pricing\Render;

use Magento\Catalog\Model\Product\Pricing\Renderer\SalableResolverInterface;
use Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface;
use Magento\Customer\Model\Context;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\Pricing\SaleableInterface;

/**
 * Class FinalPriceBox
 * @package Tigren\CustomerGroupCatalog\Pricing\Render
 */
class FinalPriceBox extends \Magento\Catalog\Pricing\Render\FinalPriceBox
{
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param SaleableInterface $saleableItem
     * @param PriceInterface $price
     * @param RendererPool $rendererPool
     * @param SalableResolverInterface|null $salableResolver
     * @param MinimalPriceCalculatorInterface|null $minimalPriceCalculator
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        SaleableInterface                                $saleableItem,
        PriceInterface                                   $price,
        RendererPool                                     $rendererPool,
        SalableResolverInterface                         $salableResolver = null,
        MinimalPriceCalculatorInterface                  $minimalPriceCalculator = null,
        \Magento\Framework\App\Http\Context              $httpContext,
        array                                            $data = [],
    )
    {
        $this->httpContext = $httpContext;
        parent::__construct($context,
            $saleableItem,
            $price,
            $rendererPool,
            $data,
            $salableResolver,
            $minimalPriceCalculator);
    }

    /**
     * @param $html
     * @return string
     */
    protected function wrapResult($html)
    {
        $isLoggedIn = $this->httpContext->getValue(Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            return '<div class="price-box ' . $this->getData('css_classes') . '" ' .
                'data-role="priceBox" ' .
                'data-product-id="' . $this->getSaleableItem()->getId() . '"' .
                '>' . $html . '</div>';
        } else {
            $wording = '<a style="color: darkred; font-family: monospace; font-weight: bold; text-decoration: underline;" 
                href="customer/account/login"> Please Login To See The Price!!!</a>';
            return '<div class="" ' .
                'data-role="priceBox" ' .
                'data-product-id="' . $this->getSaleableItem()->getId() . '"' .
                '>' . $wording . '</div>';
        }
    }
}