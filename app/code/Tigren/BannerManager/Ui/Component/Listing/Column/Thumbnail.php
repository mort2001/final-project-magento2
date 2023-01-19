<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Ui\Component\Listing\Column;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Tigren\BannerManager\Helper\Data;

/**
 * Class Thumbnail
 *
 * @package Tigren\BannerManager\Ui\Component\Listing\Column
 */
class Thumbnail extends Column
{
    /**
     *
     */
    const NAME = 'banner_image';

    /**
     * @var Data
     */
    protected $bannermanagerHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Data $bannermanagerHelper
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Data $bannermanagerHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->bannermanagerHelper = $bannermanagerHelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as & $item) {
                $banner = new DataObject($item);
                $item[$fieldName . '_src'] = $this->bannermanagerHelper->getImageUrl($banner->getData($fieldName));
                $item[$fieldName . '_alt'] = $banner->getData($fieldName);
                $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                    'bannersmanager/banners/edit',
                    ['image_id' => $banner->getData('banner_id')]
                );
                $item[$fieldName . '_orig_src'] = $this->bannermanagerHelper->getImageUrl($banner->getData($fieldName));
            }
        }

        return $dataSource;
    }
}
