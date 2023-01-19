<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 *
 */

namespace Tigren\CustomAddress\Model\Import\Subdistrict;

use Magento\Framework\Validator\ValidatorInterface;

interface RowValidatorInterface extends ValidatorInterface
{
    const ERROR_CITY_IS_EMPTY = 'cityEmpty';
    const ERROR_INVALID_CITY = 'invalidCity';

    const ERROR_CODE_IS_EMPTY = 'codeEmpty';
    const ERROR_DEFAULT_NAME_IS_EMPTY = 'defaultNameEmpty';
    const ERROR_INVALID_LOCALE_CODE = 'invalidLocaleCode';
    const ERROR_INVALID_ZIP_CODE = 'zipcodeEmpty';

    const ERROR_INVALID_DATA = 'invalidData';
    const ERROR_VALUE_IS_REQUIRED = 'isRequired';

    const LOCALE_PREFIX = 'locale';

    /**
     * Initialize validator
     *
     * @param \Tigren\CustomAddress\Model\Import\SubdistrictAndZipcode $context
     * @return $this
     */
    public function init($context);
}
