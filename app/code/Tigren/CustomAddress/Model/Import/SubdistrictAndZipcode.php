<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomAddress\Model\Import;

use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import as ImportExport;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Tigren\CustomAddress\Model\Import\Subdistrict\RowValidatorInterface as ValidatorInterface;
use Tigren\CustomAddress\Model\Config\Source\Locale as LocaleSource;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Tigren\CustomAddress\Model\Import\Subdistrict\Validator;
use Tigren\CustomAddress\Model\ResourceModel\City\CollectionFactory as CityCollectionFactory;

/**
 * Class Subdistrict
 * @package Tigren\CustomAddress\Model\Import
 */
class SubdistrictAndZipcode extends AbstractEntity
{
    const ENTITY_CODE = 'custom_subdistrict_and_zipcode';

    const TABLE_MAIN = 'directory_city_subdistrict';
    const TABLE_LOCALE = 'directory_city_subdistrict_name';

    const COL_CITY_CODE = 'city_code';
    const COL_SUBDISTRICT_ID = 'subdistrict_id';
    const COL_CODE = 'code';
    const COL_ZIP_CODE = 'zipcode';
    const COL_DEFAULT_NAME = 'default_name';
    const COL_LOCALE = 'locale';
    const COL_LOCALE_NAME = 'name';

    const LOCALE_PREFIX = 'name';

    /**
     * @var CityCollectionFactory
     */
    protected $cityCollectionFactory;

    /**
     * If we should check column names
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history
     */
    protected $logInHistory = true;

    /**
     * @var array
     */
    protected $_validators = [];

    /**
     * Permanent entity columns.
     */
    protected $_permanentAttributes = [
        self::COL_CITY_CODE,
        self::COL_CODE,
        self::COL_DEFAULT_NAME,
        self::COL_ZIP_CODE,
    ];

    /**
     * @var array
     */
    protected $_specialAttributes = [];

    /**
     * Valid column names
     */
    protected $validColumnNames = [
        self::COL_CITY_CODE,
        self::COL_CODE,
        self::COL_DEFAULT_NAME,
        self::COL_ZIP_CODE,
        // other dynamic groups are initiated by constructor
    ];

    /**
     * Validation failure message template definitions.
     *
     * @var array
     */
    protected $_messageTemplates = [
        ValidatorInterface::ERROR_INVALID_DATA => 'Region data is invalid',
        ValidatorInterface::ERROR_CITY_IS_EMPTY => 'City is empty',
        ValidatorInterface::ERROR_INVALID_CITY => 'City is invalid.Please check data again!!!',
        ValidatorInterface::ERROR_CODE_IS_EMPTY => 'Code is empty',
        ValidatorInterface::ERROR_DEFAULT_NAME_IS_EMPTY => 'Default name is empty',
        ValidatorInterface::ERROR_INVALID_LOCALE_CODE => 'Locale code is invalid',
    ];

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var LocaleSource
     */
    private $localeSource;

    /**
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param ImportData $importData
     * @param Config $config
     * @param ResourceConnection $resource
     * @param ResourceHelper $resourceHelper
     * @param StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param Validator $validator
     * @param LocaleSource $localeSource
     * @param CityCollectionFactory $cityCollectionFactory
     */
    public function __construct(
        JsonHelper                         $jsonHelper,
        ImportHelper                       $importExportData,
        ImportData                         $importData,
        Config                             $config,
        ResourceConnection                 $resource,
        ResourceHelper                     $resourceHelper,
        StringUtils                        $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        Validator                          $validator,
        LocaleSource                       $localeSource,
        CityCollectionFactory              $cityCollectionFactory
    )
    {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->errorAggregator = $errorAggregator;

        $this->validator = $validator;
        $this->validator->init($this);

        $this->localeSource = $localeSource;
        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->initMessageTemplates();
        $this->initValidColumnNames();

        $this->cityCollectionFactory = $cityCollectionFactory;
    }

    /**
     * Init Error Messages
     */
    private function initMessageTemplates()
    {
        foreach (array_merge($this->errorMessageTemplates, $this->_messageTemplates) as $errorCode => $message) {
            $this->getErrorAggregator()->addErrorMessageTemplate($errorCode, $message);
        }
    }

    /**
     * @return void
     */
    private function initValidColumnNames()
    {
        $locales = $this->localeSource->getOptionsArray();
        foreach ($locales as $locale => $name) {
            $this->validColumnNames = array_merge(
                $this->validColumnNames,
                [self::LOCALE_PREFIX . ':' . $locale]
            );
        }
    }

    /**
     * Validator object getter.
     *
     * @param string $type
     * @return \Tigren\CustomAddress\Model\Import\City\Validator
     */
    protected function _getValidator($type)
    {
        return $this->_validators[$type];
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return self::ENTITY_CODE;
    }

    /**
     * Get valid columns
     *
     * @return array
     */
    public function getValidColumnNames()
    {
        return $this->validColumnNames;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    public function getMainColumns()
    {
        return $this->_permanentAttributes;
    }

    /**
     * @param array $rowData
     * @param $rowNum
     * @return bool
     * @throws \Zend_Validate_Exception
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;

        //        $countryId = $rowData[self::COL_COUNTRY_ID] ?? '';
        $cityId = $rowData[self::COL_CITY_CODE] ?? '';
        $code = $rowData[self::COL_CODE] ?? '';
        $defaultName = $rowData[self::COL_DEFAULT_NAME] ?? '';
        $zipCode = $rowData[self::COL_ZIP_CODE] ?? '';

        $mainColExists = true;
        if (!strlen($cityId)) {
            $mainColExists = false;
            $this->skipRow($rowNum, ValidatorInterface::ERROR_CITY_IS_EMPTY);
        }
        if (!strlen($code)) {
            $mainColExists = false;
            $this->skipRow($rowNum, ValidatorInterface::ERROR_CODE_IS_EMPTY);
        }
        if (!strlen($defaultName)) {
            $mainColExists = false;
            $this->skipRow($rowNum, ValidatorInterface::ERROR_DEFAULT_NAME_IS_EMPTY);
        }
        if (!strlen($zipCode)) {
            $mainColExists = false;
            $this->skipRow($rowNum, ValidatorInterface::ERROR_INVALID_ZIP_CODE);
        }

        $errorLevel = $this->getValidationErrorLevel($mainColExists);

        if (!$this->validator->isValid($rowData)) {
            foreach ($this->validator->getMessages() as $message) {
                $this->skipRow($rowNum, $message, $errorLevel);
            }
        }

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Overridden validate data functionality
     * To support <locale>:<code> pattern
     *
     * @return ProcessingErrorAggregatorInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validateData()
    {
        if (!$this->_dataValidated) {
            $this->getErrorAggregator()->clear();
            // do all permanent columns exist?
            $absentColumns = array_diff($this->_permanentAttributes, $this->getSource()->getColNames());
            $this->addErrors(self::ERROR_CODE_COLUMN_NOT_FOUND, $absentColumns);

            if (ImportExport::BEHAVIOR_DELETE != $this->getBehavior()) {
                // check attribute columns names validity
                $columnNumber = 0;
                $emptyHeaderColumns = [];
                $invalidColumns = [];
                $invalidAttributes = [];
                foreach ($this->getSource()->getColNames() as $columnName) {
                    $columnNumber++;
                    if (!$this->isAttributeParticular($columnName)) {
                        if (trim($columnName) == '') {
                            $emptyHeaderColumns[] = $columnNumber;
                        } elseif (!preg_match('/^[a-z][a-zA-Z0-9_\:]*$/', $columnName)) {
                            $invalidColumns[] = $columnName;
                        } elseif ($this->needColumnCheck && !in_array($columnName, $this->getValidColumnNames())) {
                            $invalidAttributes[] = $columnName;
                        }
                    }
                }
                $this->addErrors(self::ERROR_CODE_INVALID_ATTRIBUTE, $invalidAttributes);
                $this->addErrors(self::ERROR_CODE_COLUMN_EMPTY_HEADER, $emptyHeaderColumns);
                $this->addErrors(self::ERROR_CODE_COLUMN_NAME_INVALID, $invalidColumns);
            }

            if (!$this->getErrorAggregator()->getErrorsCount()) {
                $this->_saveValidatedBunches();
                $this->_dataValidated = true;
            }
        }
        return $this->getErrorAggregator();
    }

    /**
     * Add row as skipped
     *
     * @param int $rowNum
     * @param string $errorCode Error code or simply column name
     * @param string $errorLevel error level
     * @param string|null $colName optional column name
     * @return $this
     */
    private function skipRow(
        $rowNum,
        string $errorCode,
        string $errorLevel = ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
        $colName = null
    )
    {
        $this->addRowError($errorCode, $rowNum, $colName, null, $errorLevel);
        $this->getErrorAggregator()
            ->addRowToSkip($rowNum);
        return $this;
    }

    /**
     * Returns errorLevel for validation
     *
     * @param bool $mainColExists
     * @return string
     */
    private function getValidationErrorLevel($mainColExists)
    {
        return !$mainColExists
            ? ProcessingError::ERROR_LEVEL_CRITICAL
            : ProcessingError::ERROR_LEVEL_NOT_CRITICAL;
    }

    /**
     * @return bool
     */
    protected function _importData()
    {
        if (Import::BEHAVIOR_APPEND == $this->getBehavior()) {
            $this->saveEntity();
        }

        return true;
    }

    /**
     * @return $this
     */
    public function saveEntity()
    {
        $this->saveAndReplaceEntity();
        return $this;
    }

    /**
     * @return $this
     * @throws \Zend_Validate_Exception
     */
    protected function saveAndReplaceEntity()
    {
        $behavior = $this->getBehavior();
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityList = [];
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addRowError(ValidatorInterface::ERROR_INVALID_DATA, $rowNum);
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                $entityList[] = $this->prepareEntityRow($rowData);
            }

            // Ignore invalid rows
            foreach ($bunch as $rowNum => $rowData) {
                if ($this->getErrorAggregator()->isRowInvalid($rowNum)) {
                    unset($bunch[$rowNum]);
                }
            }

            if (Import::BEHAVIOR_APPEND == $behavior) {
                $this->saveEntityFinish($entityList);
            }
        }

        return $this;
    }

    /**
     * @param $rowData
     * @return array
     */
    protected function prepareEntityRow($rowData)
    {
        $preparedData = [];
        foreach ($rowData as $field => $value) {
            if (in_array($field, $this->getMainColumns())) {
                $preparedData['main'][$field] = $value;
            }
            if (preg_match('/' . self::LOCALE_PREFIX . ':(.*)/', $field, $matches)) {
                $locale = isset($matches[1]) ? trim($matches[1]) : null;
                if (empty($locale) || !strlen($value)) {
                    continue;
                }

                $preparedData['locale'][$locale] = $value;
            }
        }
        return $preparedData;
    }

    /**
     * Main operation which updates/inserts the data in main & locale table
     *
     * @param array $entityList
     * @return bool
     */
    protected function saveEntityFinish(array $entityList)
    {
        if (empty($entityList)) {
            return false;
        }
        $mainTableName = $this->resource->getTableName(self::TABLE_MAIN);
        $localeTableName = $this->resource->getTableName(self::TABLE_LOCALE);
        foreach ($entityList as $entityData) {
            $mainData = $entityData['main'] ?? [];
            $localeData = $entityData['locale'] ?? [];

            if ($subdistrictId = $this->checkIfSubdistrictDataExists(
                $mainData[self::COL_CITY_CODE],
                $mainData[self::COL_CODE]
            )) {
                $this->countItemsUpdated += $this->connection->update(
                    $mainTableName,
                    [
                        self::COL_DEFAULT_NAME => $mainData[self::COL_DEFAULT_NAME],
                        self::COL_ZIP_CODE => $mainData[self::COL_ZIP_CODE],
                    ],
                    [
                        $this->connection->quoteInto('subdistrict_id = ?', $subdistrictId)
                    ]
                );
            } else {
                $cityId = $this->convertCityCodeIntoCityId($mainData[self::COL_CITY_CODE]);
                $mainData = array_merge($mainData, ['city_id' => $cityId]);
                unset($mainData['city_code']);

                $this->countItemsCreated += $this->connection->insert(
                    $mainTableName,
                    $mainData,
                    $this->getMainColumns()
                );
                $subdistrictId = $this->connection->lastInsertId();
            }

            if (!empty($localeData) && $subdistrictId) {
                foreach ($localeData as $locale => $localeName) {
                    if ($this->checkIfLocaleDataExists($subdistrictId, $locale)) {
                        $this->countItemsUpdated += $this->connection->update(
                            $localeTableName,
                            [
                                self::COL_LOCALE_NAME => $localeName
                            ],
                            [
                                $this->connection->quoteInto('subdistrict_id = ?', $subdistrictId),
                                $this->connection->quoteInto('locale = ?', $locale)
                            ]
                        );
                    } else {
                        $insertData = [
                            self::COL_SUBDISTRICT_ID => $subdistrictId,
                            self::COL_LOCALE => $locale,
                            self::COL_LOCALE_NAME => $localeName
                        ];
                        $this->countItemsCreated += $this->connection->insertOnDuplicate(
                            $localeTableName,
                            $insertData,
                            array_keys($insertData)
                        );
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param $cityId
     * @param $code
     * @return mixed|null
     */
    protected function checkIfSubdistrictDataExists($cityCode, $code)
    {
        $cityId = $this->convertCityCodeIntoCityId($cityCode);
        if ($cityId) {
            $select = $this->connection->select()
                ->from($this->resource->getTableName(self::TABLE_MAIN))
                ->where('city_id = ?', $cityId)
                ->where('code = ?', $code);
            $row = $this->connection->fetchRow($select);
            return $row['subdistrict_id'] ?? null;
        }
        return null;
    }

    /**
     * @param $subdistrictId
     * @param $locale
     * @return mixed
     */
    protected function checkIfLocaleDataExists($subdistrictId, $locale)
    {
        $select = $this->connection->select()
            ->from($this->resource->getTableName(self::TABLE_LOCALE))
            ->where('subdistrict_id = ?', $subdistrictId)
            ->where('locale = ?', $locale);
        return $this->connection->fetchRow($select);
    }

    /**
     * @param $cityCode
     * @return mixed
     */
    protected function convertCityCodeIntoCityId($cityCode)
    {
        $regionCollection = $this->cityCollectionFactory->create();
        $regionCollection->addFieldToFilter('main_table.code', $cityCode);
        return $regionCollection->getFirstItem()->getCityId();
    }
}
