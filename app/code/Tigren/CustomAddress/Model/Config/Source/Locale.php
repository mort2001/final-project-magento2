<?php

namespace Tigren\CustomAddress\Model\Config\Source;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Locale\ListsInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Locale
 * @package Tigren\CustomAddress\Model\Config\Source
 */
class Locale implements OptionSourceInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ListsInterface
     */
    private $localeLists;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ListsInterface $localeLists
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ListsInterface $localeLists
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->localeLists = $localeLists;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $locales = $this->getAvailableLocales();
        $_localeLists = $this->localeLists->getOptionLocales();
        $result = [];
        foreach ($locales as $eachStoreLocale) {
            foreach ($_localeLists as $locale) {
                if ($locale['value'] == $eachStoreLocale) {
                    $result[] = [
                        'value' => $locale['value'],
                        'label' => $locale['label']
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getOptionsArray()
    {
        $options = [];
        foreach ($this->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }
        return $options;
    }

    /**
     * @return array
     */
    private function getAvailableLocales()
    {
        $locales = [];
        $stores = $this->storeManager->getStores(true, true);
        foreach ($stores as $storeCode => $store) {
            $locale = $this->scopeConfig->getValue(
                DirectoryHelper::XML_PATH_DEFAULT_LOCALE,
                ScopeInterface::SCOPE_STORE,
                $storeCode
            );
            $locales[$storeCode] = $locale;
        }

        return array_unique($locales);
    }
}
