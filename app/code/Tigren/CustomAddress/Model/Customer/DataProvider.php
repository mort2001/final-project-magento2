<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2021 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Customer;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\Config\Share;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\FileProcessor;
use Magento\Customer\Model\FileProcessorFactory;
use Magento\Customer\Model\ResourceModel\Address\Attribute\Source\CountryWithWebsites;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterPool;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\EavValidationRules;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @api
 * @since 100.0.2
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * Maximum file size allowed for file_uploader UI component
     */
    const MAX_FILE_SIZE = 2097152;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var FilterPool
     */
    protected $filterPool;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * EAV attribute properties to fetch from meta storage
     * @var array
     */
    protected $metaProperties = [
        'dataType' => 'frontend_input',
        'visible' => 'is_visible',
        'required' => 'is_required',
        'label' => 'frontend_label',
        'sortOrder' => 'sort_order',
        'notice' => 'note',
        'default' => 'default_value',
        'size' => 'multiline_count',
    ];

    /**
     * Form element mapping
     *
     * @var array
     */
    protected $formElement = [
        'text' => 'input',
        'hidden' => 'input',
        'boolean' => 'checkbox',
    ];
    /**
     * @var EavValidationRules
     */
    protected $eavValidationRules;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CountryWithWebsites
     */
    private $countryWithWebsiteSource;

    /**
     * @var Share
     */
    private $shareConfig;

    /**
     * @var FileProcessorFactory
     */
    private $fileProcessorFactory;

    /**
     * File types allowed for file_uploader UI component
     *
     * @var array
     */
    private $fileUploaderTypes = [
        'image',
        'file',
    ];

    /**
     * Customer fields that must be removed
     *
     * @var array
     */
    private $forbiddenCustomerFields = [
        'password_hash',
        'rp_token',
        'confirmation',
    ];

    /*
     * @var ContextInterface
     */
    /**
     * @var mixed
     */
    private $context;

    /**
     * Allow to manage attributes, even they are hidden on storefront
     *
     * @var bool
     */
    private $allowToShowHiddenAttributes;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param EavValidationRules $eavValidationRules
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param Session $customerSession
     * @param Config $eavConfig
     * @param FilterPool $filterPool
     * @param FileProcessorFactory|null $fileProcessorFactory
     * @param array $meta
     * @param array $data
     * @param ContextInterface|null $context
     * @param bool $allowToShowHiddenAttributes
     * @throws LocalizedException
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        EavValidationRules $eavValidationRules,
        CustomerCollectionFactory $customerCollectionFactory,
        Session $customerSession,
        Config $eavConfig,
        FilterPool $filterPool,
        FileProcessorFactory $fileProcessorFactory = null,
        array $meta = [],
        array $data = [],
        ContextInterface $context = null,
        $allowToShowHiddenAttributes = true
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->eavValidationRules = $eavValidationRules;
        $this->collection = $customerCollectionFactory->create();
        $this->collection->addAttributeToSelect('*');
        $this->customerSession = $customerSession;
        $this->eavConfig = $eavConfig;
        $this->filterPool = $filterPool;
        $this->fileProcessorFactory = $fileProcessorFactory ?: $this->getFileProcessorFactory();
        $this->context = $context ?: ObjectManager::getInstance()->get(ContextInterface::class);
        $this->allowToShowHiddenAttributes = $allowToShowHiddenAttributes;
        $this->meta['customer']['children'] = $this->getAttributesMeta(
            $this->eavConfig->getEntityType('customer')
        );
        $this->meta['address']['children'] = $this->getAttributesMeta(
            $this->eavConfig->getEntityType('customer_address')
        );
    }

    /**
     * Get FileProcessorFactory instance
     *
     * @return FileProcessorFactory
     * @deprecated 100.1.3
     */
    private function getFileProcessorFactory()
    {
        if ($this->fileProcessorFactory === null) {
            $this->fileProcessorFactory = ObjectManager::getInstance()
                ->get(FileProcessorFactory::class);
        }
        return $this->fileProcessorFactory;
    }

    /**
     * Get attributes meta
     *
     * @param Type $entityType
     * @return array
     * @throws LocalizedException
     */
    protected function getAttributesMeta(Type $entityType)
    {
        $meta = [];
        $attributes = $entityType->getAttributeCollection();
        /* @var AbstractAttribute $attribute */
        foreach ($attributes as $attribute) {
            $this->processFrontendInput($attribute, $meta);

            $code = $attribute->getAttributeCode();

            // use getDataUsingMethod, since some getters are defined and apply additional processing of returning value
            foreach ($this->metaProperties as $metaName => $origName) {
                $value = $attribute->getDataUsingMethod($origName);
                $meta[$code]['arguments']['data']['config'][$metaName] = ($metaName === 'label') ? __($value) : $value;
                if ('frontend_input' === $origName) {
                    $meta[$code]['arguments']['data']['config']['formElement'] = isset($this->formElement[$value])
                        ? $this->formElement[$value]
                        : $value;
                }
            }

            if ($attribute->usesSource()) {
                if ($code == AddressInterface::COUNTRY_ID) {
                    $meta[$code]['arguments']['data']['config']['options'] = $this->getCountryWithWebsiteSource()
                        ->getAllOptions();
                } else {
                    $meta[$code]['arguments']['data']['config']['options'] = $attribute->getSource()->getAllOptions();
                }
            }

            $rules = $this->eavValidationRules->build($attribute, $meta[$code]['arguments']['data']['config']);
            if (!empty($rules)) {
                $meta[$code]['arguments']['data']['config']['validation'] = $rules;
            }

            $meta[$code]['arguments']['data']['config']['componentType'] = Field::NAME;
            $meta[$code]['arguments']['data']['config']['visible'] = $this->canShowAttribute($attribute);

            $this->overrideFileUploaderMetadata($entityType, $attribute, $meta[$code]['arguments']['data']['config']);
        }

        $this->processWebsiteMeta($meta);
        return $meta;
    }

    /**
     * Process attributes by frontend input type
     *
     * @param AttributeInterface $attribute
     * @param array $meta
     * @return mixed
     */
    private function processFrontendInput(AttributeInterface $attribute, array &$meta)
    {
        $code = $attribute->getAttributeCode();
        if ($attribute->getFrontendInput() === 'boolean') {
            $meta[$code]['arguments']['data']['config']['prefer'] = 'toggle';
            $meta[$code]['arguments']['data']['config']['valueMap'] = [
                'true' => '1',
                'false' => '0'
            ];
        }
        return true;
    }

    /**
     * Retrieve Country With Websites Source
     *
     * @return CountryWithWebsites
     * @deprecated 100.2.0
     */
    private function getCountryWithWebsiteSource()
    {
        if (!$this->countryWithWebsiteSource) {
            $this->countryWithWebsiteSource = ObjectManager::getInstance()->get(CountryWithWebsites::class);
        }

        return $this->countryWithWebsiteSource;
    }

    /**
     * Detect can we show attribute on specific form or not
     *
     * @param Attribute $customerAttribute
     * @return bool
     */
    private function canShowAttribute(AbstractAttribute $customerAttribute)
    {
        $userDefined = (bool)$customerAttribute->getIsUserDefined();
        if (!$userDefined) {
            return $customerAttribute->getIsVisible();
        }

        $canShowOnForm = $this->canShowAttributeInForm($customerAttribute);

        return ($this->allowToShowHiddenAttributes && $canShowOnForm) ||
            (!$this->allowToShowHiddenAttributes && $canShowOnForm && $customerAttribute->getIsVisible());
    }

    /**
     * Check whether the specific attribute can be shown in form: customer registration, customer edit, etc...
     *
     * @param AbstractAttribute $customerAttribute
     * @return bool
     */
    private function canShowAttributeInForm(AbstractAttribute $customerAttribute)
    {
        $isRegistration = $this->context->getRequestParam($this->getRequestFieldName()) === null;

        if ($customerAttribute->getEntityType()->getEntityTypeCode() === 'customer') {
            return is_array($customerAttribute->getUsedInForms()) &&
                (
                    (in_array('customer_account_create', $customerAttribute->getUsedInForms()) && $isRegistration) ||
                    (in_array('customer_account_edit', $customerAttribute->getUsedInForms()) && !$isRegistration)
                );
        } else {
            return is_array($customerAttribute->getUsedInForms()) &&
                in_array('customer_address_edit', $customerAttribute->getUsedInForms());
        }
    }

    /**
     * Override file uploader UI component metadata
     *
     * Overrides metadata for attributes with frontend_input equal to 'image' or 'file'.
     *
     * @param Type $entityType
     * @param AbstractAttribute $attribute
     * @param array $config
     * @return void
     */
    private function overrideFileUploaderMetadata(
        Type $entityType,
        AbstractAttribute $attribute,
        array &$config
    ) {
        if (in_array($attribute->getFrontendInput(), $this->fileUploaderTypes)) {
            $maxFileSize = self::MAX_FILE_SIZE;

            if (isset($config['validation']['max_file_size'])) {
                $maxFileSize = (int)$config['validation']['max_file_size'];
            }

            $allowedExtensions = [];

            if (isset($config['validation']['file_extensions'])) {
                $allowedExtensions = explode(',', $config['validation']['file_extensions']);
                array_walk($allowedExtensions, function (&$value) {
                    $value = strtolower(trim($value));
                });
            }

            $allowedExtensions = implode(' ', $allowedExtensions);

            $entityTypeCode = $entityType->getEntityTypeCode();
            $url = $this->getFileUploadUrl($entityTypeCode);

            $config = [
                'formElement' => 'fileUploader',
                'componentType' => 'fileUploader',
                'maxFileSize' => $maxFileSize,
                'allowedExtensions' => $allowedExtensions,
                'uploaderConfig' => [
                    'url' => $url,
                ],
                'label' => $this->getMetadataValue($config, 'label'),
                'sortOrder' => $this->getMetadataValue($config, 'sortOrder'),
                'required' => $this->getMetadataValue($config, 'required'),
                'visible' => $this->getMetadataValue($config, 'visible'),
                'validation' => $this->getMetadataValue($config, 'validation'),
            ];
        }
    }

    /**
     * Retrieve URL to file upload
     *
     * @param string $entityTypeCode
     * @return string
     */
    private function getFileUploadUrl($entityTypeCode)
    {
        switch ($entityTypeCode) {
            case CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER:
                $url = 'customer/file/customer_upload';
                break;

            case AddressMetadataInterface::ENTITY_TYPE_ADDRESS:
                $url = 'customer/file/address_upload';
                break;

            default:
                $url = '';
                break;
        }
        return $url;
    }

    /**
     * Retrieve metadata value
     *
     * @param array $config
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    private function getMetadataValue($config, $name, $default = null)
    {
        $value = isset($config[$name]) ? $config[$name] : $default;
        return $value;
    }

    /**
     * Add global scope parameter and filter options to website meta
     *
     * @param array $meta
     * @return void
     */
    private function processWebsiteMeta(&$meta)
    {
        if (isset($meta[CustomerInterface::WEBSITE_ID]) && $this->getShareConfig()->isGlobalScope()) {
            $meta[CustomerInterface::WEBSITE_ID]['arguments']['data']['config']['isGlobalScope'] = 1;
        }

        if (isset($meta[AddressInterface::COUNTRY_ID]) && !$this->getShareConfig()->isGlobalScope()) {
            $meta[AddressInterface::COUNTRY_ID]['arguments']['data']['config']['filterBy'] = [
                'target' => '${ $.provider }:data.customer.website_id',
                'field' => 'website_ids'
            ];
        }
    }

    /**
     * Retrieve Customer Config Share
     *
     * @return Share
     * @deprecated 100.1.3
     */
    private function getShareConfig()
    {
        if (!$this->shareConfig) {
            $this->shareConfig = ObjectManager::getInstance()->get(Share::class);
        }

        return $this->shareConfig;
    }

    /**
     * Get data
     *
     * @return array
     * @throws LocalizedException
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $customer = $this->customerSession->getCustomer();
        $result['customer'] = $customer->getData();

        $this->overrideFileUploaderData($customer, $result['customer']);

        $result['customer'] = array_diff_key(
            $result['customer'],
            array_flip($this->forbiddenCustomerFields)
        );
        unset($result['address']);

        /** @var Address $address */
        foreach ($customer->getAddresses() as $address) {
            $addressId = $address->getId();
            $address->load($addressId);
            $result['address'][$addressId] = $address->getData();
            $this->prepareAddressData($addressId, $result['address'], $result['customer']);

            $this->overrideFileUploaderData($address, $result['address'][$addressId]);
        }

        $this->loadedData = $result;

        return $this->loadedData;
    }

    /**
     * Override file uploader UI component data
     *
     * Overrides data for attributes with frontend_input equal to 'image' or 'file'.
     *
     * @param Customer|Address $entity
     * @param array $entityData
     * @return void
     * @throws LocalizedException
     */
    private function overrideFileUploaderData($entity, array &$entityData)
    {
        $attributes = $entity->getAttributes();
        foreach ($attributes as $attribute) {
            /** @var Attribute $attribute */
            if (in_array($attribute->getFrontendInput(), $this->fileUploaderTypes)) {
                $entityData[$attribute->getAttributeCode()] = $this->getFileUploaderData(
                    $entity->getEntityType(),
                    $attribute,
                    $entityData
                );
            }
        }
    }

    /**
     * Retrieve array of values required by file uploader UI component
     *
     * @param Type $entityType
     * @param Attribute $attribute
     * @param array $customerData
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getFileUploaderData(
        Type $entityType,
        Attribute $attribute,
        array $customerData
    ) {
        $attributeCode = $attribute->getAttributeCode();

        $file = isset($customerData[$attributeCode])
            ? $customerData[$attributeCode]
            : '';

        /** @var FileProcessor $fileProcessor */
        $fileProcessor = $this->getFileProcessorFactory()->create([
            'entityTypeCode' => $entityType->getEntityTypeCode(),
        ]);

        if (!empty($file)
            && $fileProcessor->isExist($file)
        ) {
            $stat = $fileProcessor->getStat($file);
            $viewUrl = $fileProcessor->getViewUrl($file, $attribute->getFrontendInput());

            return [
                [
                    'file' => $file,
                    'size' => isset($stat) ? $stat['size'] : 0,
                    'url' => isset($viewUrl) ? $viewUrl : '',
                    'name' => basename($file),
                    'type' => $fileProcessor->getMimeType($file),
                ],
            ];
        }

        return [];
    }

    /**
     * Prepare address data
     *
     * @param int $addressId
     * @param array $addresses
     * @param array $customer
     * @return void
     */
    protected function prepareAddressData($addressId, array &$addresses, array $customer)
    {
        if (isset($customer['default_billing'])
            && $addressId == $customer['default_billing']
        ) {
            $addresses[$addressId]['default_billing'] = $customer['default_billing'];
        }
        if (isset($customer['default_shipping'])
            && $addressId == $customer['default_shipping']
        ) {
            $addresses[$addressId]['default_shipping'] = $customer['default_shipping'];
        }
        if (isset($addresses[$addressId]['street']) && !is_array($addresses[$addressId]['street'])) {
            $addresses[$addressId]['street'] = explode("\n", $addresses[$addressId]['street']);
        }
    }
}
