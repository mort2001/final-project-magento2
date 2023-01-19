<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\BannerManager\Block;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Tigren\BannerManager\Model\ResourceModel\Banner\CollectionFactory;
use Tigren\BannerManager\Model\ResourceModel\Block\Collection;

/**
 * Class Banner
 *
 * @package Tigren\BannerManager\Block
 */
class Banner extends Template
{
    /**
     * banner template
     *
     * @var string
     */
    protected $_template = 'banner.phtml';

    /**
     * Registry object.
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * block collecion factory.
     *
     * @var \Tigren\BannerManager\Model\ResourceModel\Block\CollectionFactory
     */
    protected $_blockCollectionFactory;

    /**
     * scope config.
     *
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * stdlib timezone.
     *
     * @var Timezone
     */
    protected $_stdTimezone;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Timezone $_stdTimezone
     * @param Session $customerSession
     * @param \Tigren\BannerManager\Model\ResourceModel\Block\CollectionFactory $blockCollectionFactory
     * @param CollectionFactory $bannerCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Timezone $_stdTimezone,
        Session $customerSession,
        \Tigren\BannerManager\Model\ResourceModel\Block\CollectionFactory $blockCollectionFactory,
        CollectionFactory $bannerCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_stdTimezone = $_stdTimezone;
        $this->_customerSession = $customerSession;
        $this->_blockCollectionFactory = $blockCollectionFactory;

        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $context->getStoreManager();
    }

    /**
     * set position for banner block.
     *
     * @param mixed string|array $position
     * @return Banner
     * @throws LocalizedException
     */
    public function setPosition($position)
    {
        $currentStoreId = 0;
        if ($store = $this->_storeManager->getStore()) {
            $currentStoreId = $store->getId();
        }

        $dateTimeNow = $this->_stdTimezone->date()->format('Y-m-d H:i:s');

        $blockCollection = $this->_blockCollectionFactory->create()
            ->addFieldToFilter('block_position', $position)
            ->addFieldToFilter('from_date', [['to' => $dateTimeNow], ['from_date', 'null' => '']])
            ->addFieldToFilter('to_date', [['gteq' => $dateTimeNow], ['to_date', 'null' => '']])
            ->addFieldToFilter('is_active', 1)
            ->setStoreFilter($currentStoreId);

        $currentCategoryId = 0;
        if ($category = $this->_coreRegistry->registry('current_category')) {
            $currentCategoryId = $category->getEntityId();
        }

        foreach ($blockCollection as $key => $block) {

            $customerGroup = explode(',', $block->getCustomerGroupIds());
            if (!in_array((int)$this->_customerSession->getCustomerGroupId(), $customerGroup)) {
                $blockCollection->removeItemByKey($key);
                continue;
            }

            if ($currentCategoryId) {
                $filterCategoryIds = explode(',', $block->getCategory());
                if ($block->getCategoryType() == 2) {
                    //all categories except filterCategoryIds
                    if (in_array($currentCategoryId, $filterCategoryIds)) {
                        $blockCollection->removeItemByKey($key);
                    }
                } else {
                    if ($block->getCategoryType() == 3) {
                        //specific categoryIds
                        if (!in_array($currentCategoryId, $filterCategoryIds)) {
                            $blockCollection->removeItemByKey($key);
                        }
                    }
                }
            }
        }

        $this->appendChildBlockBlocks($blockCollection);

        return $this;
    }

    /**
     * add child block banner.
     *
     * @param Collection $blockCollection [description]
     *
     * @return Banner [description]
     * @throws LocalizedException
     */
    public function appendChildBlockBlocks(
        Collection $blockCollection
    ) {
        foreach ($blockCollection as $block) {
            $this->append(
                $this->getLayout()->createBlock(
                    'Tigren\BannerManager\Block\BannerItem'
                )->setBlockId($block->getId())
            );
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAdditionalClass()
    {
        return $this->getData('additional_class');
    }
}
