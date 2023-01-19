<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Block\Adminhtml\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Catalog\Helper\Image;
use Magento\Framework\DataObject;

/**
 * Class Thumbnail
 *
 * @package Tigren\Events\Block\Adminhtml\Grid\Column\Renderer
 */
class Thumbnail extends AbstractRenderer
{
    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * Thumbnail constructor.
     *
     * @param Image $imageHelper
     */
    public function __construct(
        Image $imageHelper
    ) {
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $imageHelper = $this->imageHelper->init($row, 'product_listing_thumbnail');
        $src = $imageHelper->getUrl();
        $alt = $this->getAlt($row) ?: $imageHelper->getLabel();

        $imageHtml = "<img alt='$alt' src='$src' class='admin__control-thumbnail' />";
        return $imageHtml;
    }
}
