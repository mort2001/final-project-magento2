<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 *
 */

namespace Tigren\CustomAddress\Model\Import\Region;

use Magento\Framework\Validator\ValidatorInterface;

interface RowValidatorInterface extends ValidatorInterface
{
    const ERROR_COUNTRY_IS_EMPTY        = 'countryEmpty';
    const ERROR_INVALID_COUNTRY         = 'invalidCountry';
    const ERROR_REGION_IS_EMPTY        = 'regionEmpty';
    const ERROR_INVALID_REGION       = 'invalidRegion';

    const ERROR_CODE_IS_EMPTY            = 'codeEmpty';
    const ERROR_DEFAULT_NAME_IS_EMPTY    = 'defaultNameEmpty';
    const ERROR_INVALID_LOCALE_CODE      = 'invalidLocaleCode';

    const ERROR_INVALID_DATA        = 'invalidData';
    const ERROR_VALUE_IS_REQUIRED   = 'isRequired';

    const LOCALE_PREFIX = 'locale';

    /**
     * Initialize validator
     * @return $this
     */
    public function init($context);
}
