<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Label
 */


namespace Amasty\Label\Model;

use Amasty\Label\Api\Data\LabelInterface;
use Amasty\Label\Model\Labels;
use Amasty\Label\Model\ResourceModel\Labels\CollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Session;
use Magento\Framework\Profiler;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

/**
 * Class LabelViewer
 * @package Amasty\Label\Model
 */
class LabelViewer
{
    /**
     * @var \Amasty\Label\Model\ResourceModel\Labels\Collection|null
     */
    private $activeLabelCollection = null;

    /**
     * @var bool|null
     */
    private $showSeveralLabels = null;

    /**
     * @var int|null
     */
    private $maxLabelCount = null;

    /**
     * @var Configurable
     */
    private $productTypeConfigurable;

    /**
     * @var CollectionFactory
     */
    private $labelCollectionFactory;

    /**
     * @var \Amasty\Base\Model\Serializer
     */
    private $serializer;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var \Amasty\Label\Model\ResourceModel\Index
     */
    private $labelIndex;

    /**
     * @var \Amasty\Label\Helper\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        Configurable $catalogProductTypeConfigurable,
        CollectionFactory $labelCollectionFactory,
        \Amasty\Base\Model\Serializer $serializer,
        Session $customerSession,
        \Amasty\Label\Model\ResourceModel\Index $labelIndex,
        \Amasty\Label\Helper\Config $config
    ) {
        $this->productTypeConfigurable = $catalogProductTypeConfigurable;
        $this->labelCollectionFactory = $labelCollectionFactory;
        $this->serializer = $serializer;
        $this->customerSession = $customerSession;
        $this->labelIndex = $labelIndex;
        $this->config = $config;
        $this->layout = $layout;
    }

    /**
     * @param Product $product
     * @param string $mode
     * @param bool $shouldMove
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function renderProductLabel(Product $product, $mode = 'category', $shouldMove = false)
    {
        $html = '';
        $appliedLabelIds = [];
        $applied = 0;
        $maxLabelCount = $this->getMaxLabelCount();

        Profiler::start('__RenderAmastyProductLabel__');
        foreach ($this->getCollection($product->getId(), $product->getStoreId()) as $label) {
            if ($applied == $maxLabelCount) {
                break;
            }

            if ($this->validateNonProductDependConditions($label, $applied)) {
                continue;
            }

            $label->setShouldMove($shouldMove);
            $label->init($product, $mode);
            if ($this->addLabelToApplied($label, $appliedLabelIds)) {
                $applied++;
                $html .= $this->generateHtml($label);
            }
        }

        /* apply label from child products*/
        if ($applied !== $maxLabelCount
            && in_array($product->getTypeId(), [Grouped::TYPE_CODE, Configurable::TYPE_CODE])
            && $this->isLabelForParentEnabled($product->getStoreId())
        ) {
            $usedProds = $this->getUsedProducts($product);
            foreach ($usedProds as $child) {
                foreach ($this->getCollection($child->getId(), $child->getStoreId()) as $label) {
                    /** @var Labels $label */
                    if ($applied == $maxLabelCount) {
                        break;
                    }

                    if (!$label->getUseForParent()
                        || $this->validateNonProductDependConditions($label, $applied)
                        || array_key_exists($label->getId(), $appliedLabelIds) // (remove duplicated)
                    ) {
                        continue;
                    }

                    $label->setShouldMove($shouldMove);
                    $label->init($child, $mode, $product);

                    if ($this->addLabelToApplied($label, $appliedLabelIds)) {
                        $applied++;
                        $html .= $this->generateHtml($label);
                    }
                }
            }
        }
        Profiler::stop('__RenderAmastyProductLabel__');

        return $html;
    }

    /**
     * @param \Amasty\Label\Model\Labels $label
     * @param $appliedLabelIds
     *
     * @return bool
     */
    private function addLabelToApplied(Labels $label, &$appliedLabelIds)
    {
        $position = $label->getMode() == 'cat' ? $label->getCatPos() : $label->getProdPos();
        if (!$this->isShowSeveralLabels()) {
            if (array_search($position, $appliedLabelIds) !== false) {
                return false;
            }
        }

        $appliedLabelIds[$label->getId()] = $position;
        return true;
    }

    /**
     * @param \Amasty\Label\Model\Labels $label
     * @param bool $applied
     * @return bool
     */
    private function validateNonProductDependConditions(Labels $label, &$applied)
    {
        if ($label->getIsSingle() === '1' && $applied) {
            return true;
        }

        // need this condition, because in_array returns true for NOT LOGGED IN customers
        if ($label->getCustomerGroupEnabled()
            && !$this->checkCustomerGroupCondition($label)
        ) {
            return true;
        }

        if (!$label->checkDateRange()) {
            return true;
        }

        return false;
    }

    /**
     * if anyone label has setting - UseForParent - check all
     * @param int $storeId
     * @return bool
     */
    private function isLabelForParentEnabled($storeId)
    {
        $collection = $this->labelCollectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter('stores', ['like' => "%$storeId%"])
            ->addFieldToFilter(LabelInterface::USE_FOR_PARENT, 1);

        return $collection->getSize() ? true : false;
    }

    /**
     * @param Labels $label
     * @return bool
     */
    private function checkCustomerGroupCondition(Labels $label)
    {
        $groups = $label->getData('customer_group_ids');
        if ($groups === '') {
            return true;
        }
        $groups = $this->serializer->unserialize($groups);

        return in_array(
            (int)$this->customerSession->getCustomerGroupId(),
            $groups
        );
    }

    /*
     * generate block with label configuration
     * @param \Amasty\Label\Model\Labels $label
     * @return string
     */
    private function generateHtml(Labels $label)
    {
        $block = $this->layout->createBlock(
            \Amasty\Label\Block\Label::class,
            '',
            ['data' => ['label' => $label]]
        );
        $html = $block->setLabel($label)->toHtml();

        return $html;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCollection($productId, $storeId)
    {
        $labelIds = $this->labelIndex->getIdsFromIndex($productId, $storeId);
        if (empty($labelIds)) {
            return [];
        }

        $labelIds = array_column($labelIds, 'label_id');

        $result = [];
        $collection = $this->getFullLabelCollection();
        foreach ($collection as $item) {
            if (in_array($item->getId(), $labelIds)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @return $this|ResourceModel\Labels\Collection|null
     */
    private function getFullLabelCollection()
    {
        if ($this->activeLabelCollection === null) {
            $this->activeLabelCollection = $this->labelCollectionFactory->create()
                ->addActiveFilter()
                ->setOrder('pos', 'asc');
        }

        return $this->activeLabelCollection;
    }

    /**
     * @param Product $product
     * @return array|\Magento\Catalog\Api\Data\ProductInterface[]
     */
    private function getUsedProducts(Product $product)
    {
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            return $this->productTypeConfigurable->getUsedProducts($product);
        } else { // product is grouped
            return $product->getTypeInstance(true)->getAssociatedProducts($product);
        }
    }

    /**
     * @return bool
     */
    private function isShowSeveralLabels()
    {
        if ($this->showSeveralLabels === null) {
            $this->showSeveralLabels = $this->config->getModuleConfig('display/show_several_on_place');
        }

        return (bool)$this->showSeveralLabels;
    }

    /**
     * @return int
     */
    private function getMaxLabelCount()
    {
        if ($this->maxLabelCount === null) {
            $this->maxLabelCount = $this->config->getMaxLabels();
        }

        return $this->maxLabelCount;
    }
}
