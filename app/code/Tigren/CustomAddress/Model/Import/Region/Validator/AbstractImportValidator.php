<?php

namespace Tigren\CustomAddress\Model\Import\Region\Validator;

use Magento\Framework\Validator\AbstractValidator;
use Tigren\CustomAddress\Model\Import\Region\RowValidatorInterface;

/**
 * Class AbstractImportValidator
 * @package Tigren\CustomAddress\Model\Import\Region\Validator
 */
abstract class AbstractImportValidator extends AbstractValidator implements RowValidatorInterface
{
    /**
     * @var \Tigren\CustomAddress\Model\Import\Region
     */
    protected $context;

    /**
     * @param \Tigren\CustomAddress\Model\Import\Region $context
     * @return $this|RowValidatorInterface
     */
    public function init($context)
    {
        $this->context = $context;
        return $this;
    }
}
