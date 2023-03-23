<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGroupCatalog\Plugin\Magento\Catalog\Model;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context;

/**
 * Class HideButton
 * @package Tigren\CustomerGroupCatalog\Plugin
 */
class HideAddToCartButton
{
    protected $_httpContext;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     * @param Context $httpContext
     */
    public function __construct(Session $session, Context $httpContext)
    {
        $this->_httpContext = $httpContext;
        $this->session = $session;
    }

    /**
     * @param Product $product
     * @param $result
     * @return bool
     */
    public function afterIsSalable(Product $product, $result): bool
    {
        if ($this->_httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }
}
